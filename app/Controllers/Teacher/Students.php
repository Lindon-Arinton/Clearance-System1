<?php

namespace App\Controllers\Teacher;

class Students extends TeacherController
{
    public function index(): string
    {
        $term = $this->currentTerm();
        $q    = trim((string) $this->request->getGet('q'));

        $rows = [];
        if ($term) {
            $b = db_connect()->table('enrollments e')
                ->select('e.id AS enrollment_id, so.id AS offering_id, sub.code, sub.title, s.student_number, s.year_level, p.code AS program_code, u.first_name, u.last_name,
                    cs.id AS cs_id, cs.status AS cs_status, cs.signed_at, cs.reenrollment_status, c.status AS clearance_status')
                ->join('subject_offerings so', 'so.id = e.subject_offering_id')
                ->join('subjects sub', 'sub.id = so.subject_id')
                ->join('students s', 's.id = e.student_id')
                ->join('users u', 'u.id = s.user_id')
                ->join('programs p', 'p.id = s.program_id')
                ->join('clearances c', 'c.student_id = s.id AND c.school_term_id = so.school_term_id', 'left')
                ->join('clearance_subjects cs', 'cs.clearance_id = c.id AND cs.subject_offering_id = so.id', 'left')
                ->where('so.teacher_id', $this->teacherId())
                ->where('so.school_term_id', $term['id'])
                ->where('e.status', 'enrolled');
            if ($q !== '') {
                $b->groupStart()->like('u.first_name', $q)->orLike('u.last_name', $q)->orLike('s.student_number', $q)->orLike('sub.code', $q)->groupEnd();
            }
            $rows = $b->orderBy('u.last_name')->orderBy('sub.code')->get()->getResultArray();
        }

        return $this->page('teacher/students', [
            'title'    => 'Students',
            'subtitle' => 'Students enrolled in your subjects this term.',
            'term'     => $term,
            'rows'     => $rows,
            'q'        => $q,
        ]);
    }
}
