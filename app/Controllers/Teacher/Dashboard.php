<?php

namespace App\Controllers\Teacher;

class Dashboard extends TeacherController
{
    public function index(): string
    {
        $term      = $this->currentTerm();
        $offerings = $term ? $this->offerings((int) $term['id']) : [];

        // Students waiting on this teacher, most urgent first.
        $queue = $term ? db_connect()->table('clearance_subjects cs')
            ->select("cs.id, cs.status, cs.signed_at, cs.subject_code, cs.subject_title, cs.subject_offering_id, cs.updated_at, s.student_number, u.first_name, u.last_name,
                (SELECT COUNT(*) FROM inc_requirements r WHERE r.clearance_subject_id = cs.id AND r.status = 'pending' AND r.student_reported_at IS NOT NULL) AS reported")
            ->join('clearances c', 'c.id = cs.clearance_id')
            ->join('students s', 's.id = c.student_id')
            ->join('users u', 'u.id = s.user_id')
            ->join('subject_offerings so', 'so.id = cs.subject_offering_id')
            ->where('cs.teacher_id', $this->teacherId())
            ->where('so.school_term_id', $term['id'])
            ->where('c.status', 'in_progress')
            ->groupStart()
                ->where('cs.status', 'PENDING')
                ->orGroupStart()->where('cs.status', 'PASSED')->where('cs.signed_at', null)->groupEnd()
                ->orWhere("(cs.status = 'INC' AND EXISTS (SELECT 1 FROM inc_requirements r WHERE r.clearance_subject_id = cs.id AND r.status = 'pending' AND r.student_reported_at IS NOT NULL))", null, false)
            ->groupEnd()
            ->orderBy('cs.created_at')
            ->limit(8)->get()->getResultArray() : [];

        $totals = ['students' => 0, 'pending' => 0, 'passed' => 0, 'inc' => 0, 'failed' => 0, 'reported' => 0, 'awaiting_sign' => 0];
        foreach ($offerings as $o) {
            foreach ($totals as $k => $_) {
                $totals[$k] += (int) $o[$k];
            }
        }

        return $this->page('teacher/dashboard', [
            'title'     => 'Teacher dashboard',
            'subtitle'  => 'Your subjects this term and the students waiting on your decision.',
            'term'      => $term,
            'offerings' => $offerings,
            'queue'     => $queue,
            'totals'    => $totals,
        ]);
    }
}
