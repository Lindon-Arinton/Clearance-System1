<?php

namespace App\Controllers\Student;

use App\Models\ClearanceModel;
use App\Services\ClearanceService;
use App\Services\EligibilityService;

class Dashboard extends StudentController
{
    public function index(): string
    {
        $svc               = new ClearanceService();
        [$term, $clearance] = $this->current();

        return $this->page('student/dashboard', [
            'title'       => 'Semester clearance',
            'subtitle'    => 'Your single place to track what is ready, what needs attention, and what comes next.',
            'student'     => $this->student(),
            'term'        => $term,
            'bundle'      => $svc->bundle($clearance),
            'eligibility' => (new EligibilityService())->forStudent($this->studentId()),
            'enrolled'    => $term ? $svc->enrolledOfferings($this->studentId(), (int) $term['id']) : [],
            'completedCount' => model(ClearanceModel::class)->where('student_id', $this->studentId())->where('status', 'completed')->countAllResults(),
        ]);
    }
}
