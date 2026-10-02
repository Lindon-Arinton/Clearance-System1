<?php

namespace App\Models;

use CodeIgniter\Model;

class IncRequirementModel extends Model
{
    protected $table          = 'inc_requirements';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useTimestamps  = true;
    protected $allowedFields  = ['clearance_subject_id', 'description', 'instructions', 'due_date', 'status', 'student_reported_at', 'student_note', 'created_by', 'verified_by', 'verified_at', 'verification_notes'];
}
