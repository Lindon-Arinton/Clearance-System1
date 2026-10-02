<?php

namespace App\Controllers\Student;

use App\Controllers\BaseController;
use App\Models\ClearanceModel;
use App\Services\ClearanceService;

abstract class StudentController extends BaseController
{
    protected function studentId(): int
    {
        return (int) $this->auth->studentId();
    }

    protected function student(): array
    {
        return $this->auth->profile();
    }

    /**
     * [term, clearance] for the current school term.
     */
    protected function current(): array
    {
        $term      = (new ClearanceService())->currentTerm();
        $clearance = $term ? model(ClearanceModel::class)->forStudentTerm($this->studentId(), (int) $term['id']) : null;

        return [$term, $clearance];
    }
}
