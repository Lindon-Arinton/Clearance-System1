<?php

namespace App\Models;

use CodeIgniter\Model;

class TeacherModel extends Model
{
    protected $table         = 'teachers';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = ['user_id', 'employee_number', 'title', 'department', 'signature_path'];

    public function detailed(): self
    {
        return $this->select("teachers.*, users.username, users.email, users.first_name, users.middle_name, users.last_name, users.is_active, users.last_login_at, CONCAT(teachers.title, ' ', users.first_name, ' ', users.last_name) AS display_name")
            ->join('users', 'users.id = teachers.user_id');
    }

    public function findDetailed(int $id): ?array
    {
        return $this->detailed()->where('teachers.id', $id)->first();
    }

    /**
     * Active teachers for select boxes.
     */
    public function options(): array
    {
        return $this->detailed()->where('users.is_active', 1)->orderBy('users.last_name')->findAll();
    }
}
