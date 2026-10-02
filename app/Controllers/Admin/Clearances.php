<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\ClearanceModel;
use App\Models\ProgramModel;
use App\Models\SchoolTermModel;
use App\Services\ClearanceService;

class Clearances extends BaseController
{
    public function index(): string
    {
        $terms   = model(SchoolTermModel::class)->allWithYear();
        $termId  = (int) ($this->request->getGet('term') ?: (model(SchoolTermModel::class)->current()['id'] ?? 0));
        $status  = (string) $this->request->getGet('status');
        $program = (int) $this->request->getGet('program');
        $q       = trim((string) $this->request->getGet('q'));

        $model = model(ClearanceModel::class)->detailed()
            ->select("(SELECT COUNT(*) FROM clearance_subjects cs WHERE cs.clearance_id = clearances.id) AS subjects,
                (SELECT COUNT(*) FROM clearance_subjects cs WHERE cs.clearance_id = clearances.id AND cs.is_resolved = 1) AS resolved,
                (SELECT COUNT(*) FROM clearance_subjects cs WHERE cs.clearance_id = clearances.id AND cs.status = 'INC') AS inc,
                (SELECT COUNT(*) FROM clearance_subjects cs WHERE cs.clearance_id = clearances.id AND cs.status = 'FAILED' AND cs.reenrollment_status <> 'RE_ENROLLMENT_APPROVED') AS failed_open");
        if ($termId) {
            $model->where('clearances.school_term_id', $termId);
        }
        if (in_array($status, ['awaiting_receipt', 'receipt_review', 'reupload_required', 'in_progress', 'completed'], true)) {
            $model->where('clearances.status', $status);
        }
        if ($program) {
            $model->where('students.program_id', $program);
        }
        if ($q !== '') {
            $model->groupStart()->like('users.first_name', $q)->orLike('users.last_name', $q)->orLike('students.student_number', $q)->orLike('clearances.reference_no', $q)->groupEnd();
        }

        return $this->page('admin/clearances/index', [
            'title'      => 'Clearances',
            'subtitle'   => 'Monitor every student clearance and its progress.',
            'clearances' => $model->orderBy('clearances.updated_at', 'DESC')->paginate(20),
            'pager'      => model(ClearanceModel::class)->pager,
            'terms'      => $terms,
            'programs'   => model(ProgramModel::class)->orderBy('code')->findAll(),
            'termId'     => $termId, 'status' => $status, 'program' => $program, 'q' => $q,
        ]);
    }

    public function show(int $id): string
    {
        $clearance = model(ClearanceModel::class)->findDetailed($id) ?? $this->notFound();
        $svc       = new ClearanceService();
        $bundle    = $svc->bundle($clearance);
        $ids       = array_map('intval', array_column($bundle['subjects'], 'id'));

        $timeline = db_connect()->table('audit_logs a')->select('a.*, u.first_name, u.last_name')->join('users u', 'u.id = a.user_id', 'left')
            ->groupStart()
                ->groupStart()->where('a.entity_type', 'clearance')->where('a.entity_id', $id)->groupEnd()
                ->orGroupStart()->where('a.entity_type', 'clearance_subject')->whereIn('a.entity_id', $ids ?: [0])->groupEnd()
                ->orGroupStart()->where('a.entity_type', 'tuition_receipt')->whereIn('a.entity_id', array_map('intval', array_column($bundle['receipts'], 'id')) ?: [0])->groupEnd()
            ->groupEnd()
            ->orderBy('a.id', 'DESC')->limit(40)->get()->getResultArray();

        return $this->page('admin/clearances/show', [
            'title'         => person_name($clearance) . ' · ' . term_label($clearance, true),
            'eyebrow'       => 'Clearance ' . $clearance['reference_no'],
            'subtitle'      => $clearance['student_number'] . ' · ' . $clearance['program_code'] . ' · ' . year_level_label($clearance['year_level']),
            'bundle'        => $bundle,
            'reenrollments' => $svc->latestReenrollmentReceipts($ids),
            'requirements'  => $svc->requirementsFor($ids),
            'timeline'      => $timeline,
        ]);
    }
}
