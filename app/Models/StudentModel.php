<?php

namespace App\Models;

use CodeIgniter\Model;

class StudentModel extends Model
{
    protected $table         = 'students';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = ['user_id', 'student_number', 'program_id', 'year_level', 'section', 'contact_number', 'status'];

    /**
     * Student joined with their account and program.
     */
    public function detailed(): self
    {
        return $this->select('students.*, users.username, users.email, users.first_name, users.middle_name, users.last_name, users.is_active, users.approval_status, users.last_login_at, programs.code AS program_code, programs.name AS program_name')
            ->join('users', 'users.id = students.user_id')
            ->join('programs', 'programs.id = students.program_id');
    }

    public function findDetailed(int $id): ?array
    {
        return $this->detailed()->where('students.id', $id)->first();
    }

    public function findDetailedByUser(int $userId): ?array
    {
        return $this->detailed()->where('students.user_id', $userId)->first();
    }
}
