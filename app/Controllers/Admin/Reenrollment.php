<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\ReceiptService;

/**
 * Re-enrollment receipts for FAILED subjects, plus an overview of all failed cases.
 */
class Reenrollment extends BaseController
{
    public function index(): string
    {
        $filter = (string) ($this->request->getGet('filter') ?? 'review');
        $statuses = match ($filter) {
            'reupload' => ['reupload_required'],
            'approved' => ['approved'],
            'all'      => null,
            default    => ['submitted', 'under_review'],
        };

        $b = db_connect()->table('reenrollment_receipts r')
            ->select('r.*, cs.subject_code, cs.subject_title, cs.teacher_name, cs.final_grade, cs.reenrollment_status, c.reference_no, s.student_number, s.year_level,
                p.code AS program_code, u.first_name, u.last_name, st.semester, sy.name AS school_year')
            ->join('clearance_subjects cs', 'cs.id = r.clearance_subject_id')
            ->join('clearances c', 'c.id = cs.clearance_id')
            ->join('students s', 's.id = r.student_id')
            ->join('users u', 'u.id = s.user_id')
            ->join('programs p', 'p.id = s.program_id')
            ->join('school_terms st', 'st.id = c.school_term_id')
            ->join('school_years sy', 'sy.id = st.school_year_id')
            ->where('r.id = (SELECT MAX(r2.id) FROM reenrollment_receipts r2 WHERE r2.clearance_subject_id = r.clearance_subject_id)', null, false);
        if ($statuses) {
            $b->whereIn('r.status', $statuses);
        }
        $rows = $b->orderBy('r.submitted_at')->get()->getResultArray();

        $selectedId = (int) ($this->request->getGet('id') ?: ($rows[0]['id'] ?? 0));
        $selected   = null;
        foreach ($rows as &$r) {
            if ((int) $r['id'] === $selectedId) {
                (new ReceiptService())->beginReenrollmentReview($selectedId, (int) $this->auth->id());
                if ($r['status'] === 'submitted') {
                    $r['status'] = 'under_review';
                }
                $selected = $r;
            }
        }
        unset($r);
        if (! $selected && $selectedId) {
            // Deep link to a receipt outside the current filter.
            $selected = db_connect()->table('reenrollment_receipts r')
                ->select('r.*, cs.subject_code, cs.subject_title, cs.teacher_name, cs.final_grade, cs.reenrollment_status, c.reference_no, s.student_number, s.year_level, p.code AS program_code, u.first_name, u.last_name, st.semester, sy.name AS school_year')
                ->join('clearance_subjects cs', 'cs.id = r.clearance_subject_id')->join('clearances c', 'c.id = cs.clearance_id')->join('students s', 's.id = r.student_id')
                ->join('users u', 'u.id = s.user_id')->join('programs p', 'p.id = s.program_id')->join('school_terms st', 'st.id = c.school_term_id')->join('school_years sy', 'sy.id = st.school_year_id')
                ->where('r.id', $selectedId)->get()->getRowArray();
        }

        // Failed subjects still waiting for the student to pay/upload.
        $awaiting = db_connect()->table('clearance_subjects cs')
            ->select('cs.*, c.reference_no, s.id AS student_id, s.student_number, u.first_name, u.last_name')
            ->join('clearances c', 'c.id = cs.clearance_id')->join('students s', 's.id = c.student_id')->join('users u', 'u.id = s.user_id')
            ->where('cs.status', 'FAILED')->where('cs.reenrollment_status', 'RE_ENROLLMENT_REQUIRED')->where('c.status', 'in_progress')
            ->orderBy('cs.decided_at')->get()->getResultArray();

        return $this->page('admin/reenrollment/index', [
            'title'    => 'Re-enrollment',
            'subtitle' => 'Validate re-enrollment payments for failed subjects. FAILED records are kept in history.',
            'rows'     => $rows,
            'filter'   => $filter,
            'selected' => $selected,
            'awaiting' => $awaiting,
        ]);
    }

    public function approve(int $id)
    {
        return $this->act(function () use ($id) {
            (new ReceiptService())->approveReenrollment($id, (int) $this->auth->id());

            return 'Re-enrollment confirmed. The subject is now resolved on the student\'s clearance.';
        }, '', 'admin/reenrollment');
    }

    public function reupload(int $id)
    {
        return $this->act(function () use ($id) {
            (new ReceiptService())->requestReenrollmentReupload($id, (int) $this->auth->id(), (string) $this->request->getPost('reason_code'), (string) $this->request->getPost('reason'));

            return 'Re-upload requested. The student has been notified.';
        }, '', 'admin/reenrollment');
    }
}
