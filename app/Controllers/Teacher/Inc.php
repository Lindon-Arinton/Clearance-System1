<?php

namespace App\Controllers\Teacher;

use App\Services\SubjectClearanceService;

class Inc extends TeacherController
{
    public function index(): string
    {
        $show = $this->request->getGet('show') === 'all' ? 'all' : 'open';
        $b    = db_connect()->table('inc_requirements r')
            ->select('r.*, cs.id AS cs_id, cs.status AS cs_status, cs.subject_code, cs.subject_title, cs.subject_offering_id, c.status AS clearance_status,
                s.student_number, u.first_name, u.last_name, st.semester, sy.name AS school_year')
            ->join('clearance_subjects cs', 'cs.id = r.clearance_subject_id')
            ->join('clearances c', 'c.id = cs.clearance_id')
            ->join('students s', 's.id = c.student_id')
            ->join('users u', 'u.id = s.user_id')
            ->join('school_terms st', 'st.id = c.school_term_id')
            ->join('school_years sy', 'sy.id = st.school_year_id')
            ->where('cs.teacher_id', $this->teacherId());
        if ($show === 'open') {
            $b->where('r.status', 'pending');
        }
        $rows = $b->orderBy('r.student_reported_at IS NULL', '', false)->orderBy('r.student_reported_at', 'DESC')->orderBy('r.created_at', 'DESC')
            ->get()->getResultArray();

        return $this->page('teacher/inc', [
            'title'    => 'INC requirements',
            'subtitle' => 'Verify requirements students completed with you in person.',
            'rows'     => $rows,
            'show'     => $show,
        ]);
    }

    public function verify(int $id)
    {
        return $this->act(function () use ($id) {
            $allDone = (new SubjectClearanceService())->verifyRequirement($id, $this->teacherId(), (int) $this->auth->id(), $this->request->getPost('notes'));

            return $allDone
                ? 'Requirement verified. All requirements are complete — you can now mark the subject PASSED.'
                : 'Requirement verified and the student notified.';
        }, '');
    }

    public function returnReport(int $id)
    {
        return $this->act(function () use ($id) {
            (new SubjectClearanceService())->returnRequirement($id, $this->teacherId(), $this->request->getPost('notes'));
        }, 'The student has been told what is still missing.');
    }

    public function delete(int $id)
    {
        return $this->act(function () use ($id) {
            (new SubjectClearanceService())->removeRequirement($id, $this->teacherId());
        }, 'Requirement removed.');
    }
}
