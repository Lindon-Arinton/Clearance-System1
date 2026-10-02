<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\ClearanceModel;
use App\Models\SchoolTermModel;
use App\Models\StudentModel;
use App\Services\ReportService;

class Dashboard extends BaseController
{
    public function index(): string
    {
        $term    = model(SchoolTermModel::class)->current();
        $reports = new ReportService();
        $termId  = (int) ($term['id'] ?? 0);

        $statuses = $term ? $reports->studentStatuses($termId) : [];
        $summary  = $reports->summary($statuses);

        $queue = db_connect()->table('tuition_receipts r')
            ->select('r.id, r.status, r.or_number, r.amount, r.submitted_at, s.student_number, p.code AS program_code, s.year_level, u.first_name, u.last_name')
            ->join('students s', 's.id = r.student_id')->join('users u', 'u.id = s.user_id')->join('programs p', 'p.id = s.program_id')
            ->whereIn('r.status', ['submitted', 'under_review'])->orderBy('r.submitted_at')->limit(5)->get()->getResultArray();

        $recentCompleted = $term ? model(ClearanceModel::class)->detailed()
            ->where('clearances.school_term_id', $termId)->where('clearances.status', 'completed')
            ->orderBy('clearances.completed_at', 'DESC')->findAll(5) : [];

        return $this->page('admin/dashboard', [
            'title'       => 'Registrar dashboard',
            'subtitle'    => 'Where every clearance stands this term, and what needs the registrar next.',
            'term'        => $term,
            'students'    => model(StudentModel::class)->where('status', 'active')->countAllResults(),
            'pendingSignups' => model(\App\Models\UserModel::class)->where('role', 'student')->where('approval_status', 'pending')->countAllResults(),
            'summary'     => $summary,
            'receipts'    => $term ? $reports->receiptStats($termId) : ['submitted' => 0, 'under_review' => 0, 'approved' => 0, 'reupload_required' => 0, 'attempts' => 0],
            'reenrollPending' => $reports->pendingReenrollmentReceipts(),
            'byProgram'   => $reports->byProgram($statuses),
            'queue'       => $queue,
            'incCases'    => $term ? $reports->incCases($termId, 6) : [],
            'failedCases' => $term ? $reports->failedCases($termId, 6) : [],
            'recentCompleted' => $recentCompleted,
        ]);
    }
}
