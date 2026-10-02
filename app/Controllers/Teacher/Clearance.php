<?php

namespace App\Controllers\Teacher;

use App\Models\IncRequirementModel;
use App\Services\SubjectClearanceService;
use App\Services\WorkflowException;

class Clearance extends TeacherController
{
    /**
     * Landing: open the current-term subject with the most waiting students.
     */
    public function index()
    {
        $term      = $this->currentTerm();
        $offerings = $term ? $this->offerings((int) $term['id']) : [];
        if ($offerings === []) {
            return $this->page('teacher/no_subjects', ['title' => 'Clearance reviews', 'term' => $term]);
        }
        usort($offerings, static fn ($a, $b) => ($b['pending'] + $b['awaiting_sign'] + $b['reported']) <=> ($a['pending'] + $a['awaiting_sign'] + $a['reported']));

        return redirect()->to(site_url('teacher/subjects/' . $offerings[0]['id']));
    }

    /**
     * Review screen for one subject offering (student list + decision panel).
     */
    public function offering(int $offeringId): string
    {
        $offering   = null;
        $switchable = [];
        foreach ($this->offerings() as $o) {
            if ((int) $o['id'] === $offeringId) {
                $offering = $o;
            }
            if ((int) $o['is_current'] === 1) {
                $switchable[] = $o;
            }
        }
        if (! $offering) {
            $this->notFound('Subject not found or not assigned to you.');
        }

        $filter = (string) ($this->request->getGet('filter') ?? 'all');
        $rows   = db_connect()->table('enrollments e')
            ->select("e.student_id, s.student_number, s.year_level, p.code AS program_code, u.first_name, u.last_name,
                c.id AS clearance_id, c.status AS clearance_status, c.reference_no, cs.id AS cs_id, cs.status, cs.signed_at, cs.reenrollment_status, cs.decided_at, cs.updated_at,
                (SELECT COUNT(*) FROM inc_requirements r WHERE r.clearance_subject_id = cs.id AND r.status = 'pending' AND r.student_reported_at IS NOT NULL) AS reported")
            ->join('students s', 's.id = e.student_id')
            ->join('users u', 'u.id = s.user_id')
            ->join('programs p', 'p.id = s.program_id')
            ->join('clearances c', "c.student_id = s.id AND c.school_term_id = {$offering['school_term_id']}", 'left')
            ->join('clearance_subjects cs', "cs.clearance_id = c.id AND cs.subject_offering_id = {$offeringId}", 'left')
            ->where('e.subject_offering_id', $offeringId)
            ->where('e.status', 'enrolled')
            ->orderBy('u.last_name')
            ->get()->getResultArray();

        foreach ($rows as &$r) {
            $r['needs_action'] = $r['cs_id'] && $r['clearance_status'] === 'in_progress'
                && ($r['status'] === 'PENDING' || ($r['status'] === 'PASSED' && ! $r['signed_at']) || (int) $r['reported'] > 0);
        }
        unset($r);

        $visible = array_values(array_filter($rows, static fn ($r) => match ($filter) {
            'action' => $r['needs_action'],
            'passed' => $r['status'] === 'PASSED',
            'inc'    => $r['status'] === 'INC',
            'failed' => $r['status'] === 'FAILED',
            default  => true,
        }));

        // Selected student.
        $selectedId = (int) ($this->request->getGet('cs') ?? 0);
        if (! $selectedId) {
            foreach ($visible as $r) {
                if ($r['cs_id'] && ($r['needs_action'] || ! $selectedId)) {
                    $selectedId = (int) $r['cs_id'];
                    if ($r['needs_action']) {
                        break;
                    }
                }
            }
        }

        $selected = null;
        $requirements = [];
        $timeline = [];
        if ($selectedId) {
            try {
                $selected = (new SubjectClearanceService())->loadForTeacher($selectedId, $this->teacherId(), false);
            } catch (WorkflowException) {
                $selected = null;
            }
            if ($selected && (int) $selected['subject_offering_id'] !== $offeringId) {
                $selected = null;
            }
            if ($selected) {
                $selected['year_level']   = db_connect()->table('students')->where('id', $selected['student_id'])->get()->getRow('year_level');
                $selected['program_code'] = db_connect()->table('students s')->join('programs p', 'p.id = s.program_id')->where('s.id', $selected['student_id'])->get()->getRow('code');
                $requirements = model(IncRequirementModel::class)->where('clearance_subject_id', $selectedId)->orderBy('id')->findAll();
                $timeline     = db_connect()->table('audit_logs a')->select('a.*, u.first_name, u.last_name')->join('users u', 'u.id = a.user_id', 'left')
                    ->groupStart()
                        ->groupStart()->where('a.entity_type', 'clearance_subject')->where('a.entity_id', $selectedId)->groupEnd()
                        ->orGroupStart()->where('a.entity_type', 'inc_requirement')->whereIn('a.entity_id', array_merge([0], array_map('intval', array_column($requirements, 'id'))))->groupEnd()
                    ->groupEnd()
                    ->orderBy('a.id', 'DESC')->limit(12)->get()->getResultArray();
            }
        }

        return $this->page('teacher/review', [
            'title'        => 'Clearance reviews',
            'subtitle'     => 'Review only the students assigned to your subject, then sign off with confidence.',
            'offering'     => $offering,
            'switchable'   => $switchable,
            'rows'         => $visible,
            'allCount'     => count($rows),
            'filter'       => $filter,
            'selected'     => $selected,
            'requirements' => $requirements,
            'timeline'     => $timeline,
        ]);
    }

    public function decide(int $csId)
    {
        return $this->act(function () use ($csId) {
            $decision = (new SubjectClearanceService())->decide(
                $csId,
                $this->teacherId(),
                (int) $this->auth->id(),
                (string) $this->request->getPost('decision'),
                $this->request->getPost('remarks'),
                $this->request->getPost('final_grade'),
                (array) ($this->request->getPost('requirements') ?? []),
            );

            return match ($decision) {
                'PASSED' => 'Subject marked PASSED. Your e-signature was added to the student\'s clearance.',
                'INC'    => 'Subject marked INC. The student has been notified of the missing requirement(s).',
                default  => 'Subject marked FAILED. The student must now re-enroll.',
            };
        }, 'Decision saved.');
    }

    public function sign(int $csId)
    {
        return $this->act(function () use ($csId) {
            (new SubjectClearanceService())->sign($csId, $this->teacherId(), (int) $this->auth->id());
        }, 'Approval signed.');
    }

    public function addRequirement(int $csId)
    {
        return $this->act(function () use ($csId) {
            (new SubjectClearanceService())->addRequirement(
                $csId,
                $this->teacherId(),
                (int) $this->auth->id(),
                (string) $this->request->getPost('description'),
                $this->request->getPost('instructions'),
                $this->request->getPost('due_date') ?: null,
            );
        }, 'Requirement added and the student notified.');
    }
}
