<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\ClearanceService;
use App\Services\ReceiptService;

/**
 * Tuition receipt validation queue.
 */
class Receipts extends BaseController
{
    private const FILTERS = [
        'review'   => ['submitted', 'under_review'],
        'reupload' => ['reupload_required'],
        'approved' => ['approved'],
    ];

    public function index(): string
    {
        $filter = (string) ($this->request->getGet('filter') ?? 'review');
        $filter = array_key_exists($filter, self::FILTERS) || $filter === 'all' ? $filter : 'review';
        $q      = trim((string) $this->request->getGet('q'));
        $sort   = $this->request->getGet('sort') === 'newest' ? 'DESC' : 'ASC';

        $base = static function () {
            return db_connect()->table('tuition_receipts r')
                ->select('r.*, c.reference_no, c.status AS clearance_status, c.school_term_id, s.student_number, s.year_level, s.section, p.code AS program_code, p.name AS program_name,
                    u.first_name, u.last_name, st.semester, sy.name AS school_year, rv.first_name AS reviewer_first, rv.last_name AS reviewer_last')
                ->join('clearances c', 'c.id = r.clearance_id')
                ->join('students s', 's.id = r.student_id')
                ->join('users u', 'u.id = s.user_id')
                ->join('programs p', 'p.id = s.program_id')
                ->join('school_terms st', 'st.id = c.school_term_id')
                ->join('school_years sy', 'sy.id = st.school_year_id')
                ->join('users rv', 'rv.id = r.reviewed_by', 'left')
                // Only the latest attempt per clearance is actionable; older attempts are history.
                ->where('r.id = (SELECT MAX(r2.id) FROM tuition_receipts r2 WHERE r2.clearance_id = r.clearance_id)', null, false);
        };

        $b = $base();
        if ($filter !== 'all') {
            $b->whereIn('r.status', self::FILTERS[$filter]);
        }
        if ($q !== '') {
            $b->groupStart()->like('u.first_name', $q)->orLike('u.last_name', $q)->orLike('s.student_number', $q)->orLike('r.or_number', $q)->groupEnd();
        }
        $rows = $b->orderBy('r.submitted_at', $sort)->limit(100)->get()->getResultArray();

        $counts = [];
        foreach (['review', 'reupload'] as $k) {
            $counts[$k] = $base()->whereIn('r.status', self::FILTERS[$k])->countAllResults();
        }

        // Selected receipt (claiming it moves SUBMITTED → UNDER REVIEW).
        $selectedId = (int) ($this->request->getGet('id') ?: ($rows[0]['id'] ?? 0));
        $selected   = null;
        $attempts   = [];
        $enrolled   = [];
        if ($selectedId) {
            (new ReceiptService())->beginTuitionReview($selectedId, (int) $this->auth->id());
            $selected = db_connect()->table('tuition_receipts r')
                ->select('r.*, c.reference_no, c.status AS clearance_status, c.school_term_id, s.student_number, s.year_level, s.section, p.code AS program_code, p.name AS program_name,
                    u.first_name, u.last_name, u.email, st.semester, sy.name AS school_year, rv.first_name AS reviewer_first, rv.last_name AS reviewer_last')
                ->join('clearances c', 'c.id = r.clearance_id')->join('students s', 's.id = r.student_id')->join('users u', 'u.id = s.user_id')
                ->join('programs p', 'p.id = s.program_id')->join('school_terms st', 'st.id = c.school_term_id')->join('school_years sy', 'sy.id = st.school_year_id')
                ->join('users rv', 'rv.id = r.reviewed_by', 'left')
                ->where('r.id', $selectedId)->get()->getRowArray();
            if ($selected) {
                $attempts = db_connect()->table('tuition_receipts')->where('clearance_id', $selected['clearance_id'])->orderBy('id', 'DESC')->get()->getResultArray();
                $enrolled = (new ClearanceService())->enrolledOfferings((int) $selected['student_id'], (int) $selected['school_term_id']);
                foreach ($rows as &$r) {
                    if ((int) $r['id'] === $selectedId) {
                        $r['status'] = $selected['status'];
                    }
                }
                unset($r);
            }
        }

        return $this->page('admin/receipts/index', [
            'title'    => 'Receipt validation',
            'subtitle' => 'Make the next correct decision, then keep the queue moving.',
            'rows'     => $rows,
            'filter'   => $filter,
            'q'        => $q,
            'sort'     => $sort,
            'counts'   => $counts,
            'selected' => $selected,
            'attempts' => $attempts,
            'enrolled' => $enrolled,
            'kind'     => 'tuition',
        ]);
    }

    public function approve(int $id)
    {
        return $this->act(function () use ($id) {
            (new ReceiptService())->approveTuition($id, (int) $this->auth->id(), (array) ($this->request->getPost('checklist') ?? []));

            return 'Receipt approved. The clearance card was created and teachers were notified.';
        }, '', 'admin/receipts');
    }

    public function reupload(int $id)
    {
        return $this->act(function () use ($id) {
            (new ReceiptService())->requestTuitionReupload($id, (int) $this->auth->id(), (string) $this->request->getPost('reason_code'), (string) $this->request->getPost('reason'));

            return 'Re-upload requested. The student has been notified with your reason.';
        }, '', 'admin/receipts');
    }
}
