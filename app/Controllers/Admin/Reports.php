<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\ProgramModel;
use App\Models\SchoolTermModel;
use App\Services\AuditLogger;
use App\Services\ReportService;

class Reports extends BaseController
{
    private function filters(): array
    {
        $termId = (int) ($this->request->getGet('term') ?: (model(SchoolTermModel::class)->current()['id'] ?? 0));

        return [$termId, (int) $this->request->getGet('program') ?: null, model(SchoolTermModel::class)->findWithYear($termId)];
    }

    public function index(): string
    {
        [$termId, $programId, $term] = $this->filters();
        $reports  = new ReportService();
        $statuses = $term ? $reports->studentStatuses($termId, $programId) : [];

        return $this->page('admin/reports/index', [
            'title'     => 'Reports',
            'subtitle'  => 'Clearance, subject, INC, re-enrollment and eligibility reports by term.',
            'terms'     => model(SchoolTermModel::class)->allWithYear(),
            'programs'  => model(ProgramModel::class)->orderBy('code')->findAll(),
            'termId'    => $termId,
            'programId' => $programId,
            'term'      => $term,
            'statuses'  => $statuses,
            'summary'   => $reports->summary($statuses),
            'byProgram' => $reports->byProgram($statuses),
            'subjects'  => $term ? $reports->subjectOutcomes($termId) : [],
            'incCases'  => $term ? $reports->incCases($termId) : [],
            'failed'    => $term ? $reports->failedCases($termId) : [],
            'receipts'  => $term ? $reports->receiptStats($termId) : [],
        ]);
    }

    public function export(string $type)
    {
        [$termId, $programId, $term] = $this->filters();
        if (! $term) {
            return redirect()->back()->with('toast_error', 'Choose a term to export.');
        }
        $reports = new ReportService();

        [$headers, $rows] = match ($type) {
            'eligibility' => [
                ['Student number', 'Last name', 'First name', 'Program', 'Year', 'Clearance ref', 'Clearance status', 'Subjects', 'Resolved', 'INC', 'Failed', 'Completed at', 'Enrollment eligibility'],
                array_map(static fn ($r) => [$r['student_number'], $r['last_name'], $r['first_name'], $r['program_code'], $r['year_level'], $r['reference_no'] ?? '',
                    status_meta($r['clearance_status'] ?? 'not_started')['label'], $r['subjects'], $r['resolved'], $r['inc'], $r['failed'], $r['completed_at'] ?? '',
                    (int) $r['enrollment_eligible'] === 1 ? 'ELIGIBLE' : 'NOT ELIGIBLE'], $reports->studentStatuses($termId, $programId)),
            ],
            'subjects' => [
                ['Code', 'Title', 'Section', 'Teacher', 'Students', 'Pending', 'Passed (signed)', 'Passed (unsigned)', 'INC', 'Failed'],
                array_map(static fn ($r) => [$r['code'], $r['title'], $r['section'], $r['teacher_name'], $r['students'], $r['pending'], $r['passed'], $r['awaiting_sign'], $r['inc'], $r['failed']], $reports->subjectOutcomes($termId)),
            ],
            'inc' => [
                ['Student number', 'Student', 'Subject', 'Teacher', 'Marked INC', 'Pending requirements', 'Reported complete'],
                array_map(static fn ($r) => [$r['student_number'], person_name($r, true), $r['subject_code'] . ' ' . $r['subject_title'], $r['teacher_name'], $r['decided_at'], $r['requirements'], $r['reported']], $reports->incCases($termId)),
            ],
            'failed' => [
                ['Student number', 'Student', 'Subject', 'Teacher', 'Grade', 'Marked FAILED', 'Re-enrollment status', 'Confirmed at'],
                array_map(static fn ($r) => [$r['student_number'], person_name($r, true), $r['subject_code'] . ' ' . $r['subject_title'], $r['teacher_name'], $r['final_grade'], $r['decided_at'],
                    status_meta($r['reenrollment_status'] ?? 'RE_ENROLLMENT_REQUIRED')['label'], $r['reenrollment_confirmed_at'] ?? ''], $reports->failedCases($termId)),
            ],
            default => $this->notFound(),
        };

        $fh = fopen('php://temp', 'r+');
        fwrite($fh, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel shows ₱ and names correctly
        fputcsv($fh, $headers);
        foreach ($rows as $row) {
            // Neutralise spreadsheet formula injection.
            fputcsv($fh, array_map(static fn ($v) => is_string($v) && preg_match('/^[=+\-@]/', $v) ? "'" . $v : $v, $row));
        }
        rewind($fh);
        $csv = stream_get_contents($fh);
        fclose($fh);

        AuditLogger::log('report.exported', 'report', null, null, ['type' => $type, 'term' => $termId], "Exported {$type} report");
        $name = sprintf('%s-%s-%s.csv', $type, $term['school_year'], $term['semester']);

        return $this->response->setHeader('Content-Type', 'text/csv; charset=utf-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $name . '"')
            ->setBody($csv);
    }
}
