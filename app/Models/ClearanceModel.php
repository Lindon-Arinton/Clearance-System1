<?php

namespace App\Models;

use CodeIgniter\Model;

class ClearanceModel extends Model
{
    protected $table         = 'clearances';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'reference_no', 'student_id', 'school_term_id', 'status', 'started_at', 'card_issued_at',
        'completed_at', 'enrollment_eligible', 'eligible_at', 'student_snapshot',
    ];

    /**
     * Clearance joined with student, program and term details.
     */
    public function detailed(): self
    {
        return $this->select('clearances.*, students.student_number, students.year_level, students.section, students.user_id AS student_user_id,
                users.first_name, users.middle_name, users.last_name, programs.code AS program_code, programs.name AS program_name,
                school_terms.semester, school_terms.start_date AS term_start, school_terms.end_date AS term_end, school_terms.is_current AS term_is_current, school_years.name AS school_year')
            ->join('students', 'students.id = clearances.student_id')
            ->join('users', 'users.id = students.user_id')
            ->join('programs', 'programs.id = students.program_id')
            ->join('school_terms', 'school_terms.id = clearances.school_term_id')
            ->join('school_years', 'school_years.id = school_terms.school_year_id');
    }

    public function findDetailed(int $id): ?array
    {
        return $this->detailed()->where('clearances.id', $id)->first();
    }

    public function forStudentTerm(int $studentId, int $termId): ?array
    {
        return $this->detailed()
            ->where('clearances.student_id', $studentId)
            ->where('clearances.school_term_id', $termId)
            ->first();
    }
}
