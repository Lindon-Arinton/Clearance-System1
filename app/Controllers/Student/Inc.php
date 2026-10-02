<?php

namespace App\Controllers\Student;

use App\Services\SubjectClearanceService;

class Inc extends StudentController
{
    public function index(): string
    {
        // INC subjects across all of the student's clearances (current and past).
        $rows = db_connect()->table('clearance_subjects cs')
            ->select('cs.*, c.reference_no, c.status AS clearance_status, st.semester, sy.name AS school_year, tu.email AS teacher_email')
            ->join('clearances c', 'c.id = cs.clearance_id')
            ->join('school_terms st', 'st.id = c.school_term_id')
            ->join('school_years sy', 'sy.id = st.school_year_id')
            ->join('teachers t', 't.id = cs.teacher_id')
            ->join('users tu', 'tu.id = t.user_id')
            ->where('c.student_id', $this->studentId())
            ->groupStart()->where('cs.status', 'INC')->orWhere('cs.was_incomplete', 1)->groupEnd()
            ->orderBy('st.start_date', 'DESC')->orderBy('cs.subject_code')
            ->get()->getResultArray();

        $requirements = (new \App\Services\ClearanceService())->requirementsFor(array_map('intval', array_column($rows, 'id')));

        return $this->page('student/inc', [
            'title'        => 'INC requirements',
            'subtitle'     => 'Complete each requirement personally with your subject teacher. Only your teacher can mark it done.',
            'subjects'     => $rows,
            'requirements' => $requirements,
        ]);
    }

    public function report(int $id)
    {
        return $this->act(function () use ($id) {
            (new SubjectClearanceService())->studentReport($id, $this->studentId(), $this->request->getPost('note'));
        }, 'Your teacher has been notified. They will verify the requirement with you.');
    }
}
