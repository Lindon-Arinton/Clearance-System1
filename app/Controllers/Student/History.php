<?php

namespace App\Controllers\Student;

use App\Models\ClearanceModel;
use App\Services\ClearanceService;

class History extends StudentController
{
    public function index(): string
    {
        $clearances = model(ClearanceModel::class)->detailed()
            ->select('(SELECT COUNT(*) FROM clearance_subjects x WHERE x.clearance_id = clearances.id) AS subject_count')
            ->where('clearances.student_id', $this->studentId())
            ->orderBy('school_terms.start_date', 'DESC')
            ->findAll();

        return $this->page('student/history', [
            'title'      => 'Clearance history',
            'subtitle'   => 'Every clearance you have completed is kept here permanently.',
            'clearances' => $clearances,
        ]);
    }

    public function show(int $id): string
    {
        $clearance = model(ClearanceModel::class)->findDetailed($id);
        if (! $clearance || (int) $clearance['student_id'] !== $this->studentId()) {
            $this->notFound();
        }

        $svc    = new ClearanceService();
        $bundle = $svc->bundle($clearance);
        $ids    = array_map('intval', array_column($bundle['subjects'], 'id'));

        return $this->page('student/history_show', [
            'title'         => term_label($clearance),
            'eyebrow'       => 'Clearance history',
            'subtitle'      => 'Reference ' . $clearance['reference_no'],
            'bundle'        => $bundle,
            'reenrollments' => $svc->latestReenrollmentReceipts($ids),
            'requirements'  => $svc->requirementsFor($ids),
        ]);
    }
}
