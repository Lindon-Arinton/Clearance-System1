<?php

namespace App\Models;

use CodeIgniter\Model;

class EnrollmentModel extends Model
{
    protected $table          = 'enrollments';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useTimestamps  = true;
    protected $allowedFields  = ['student_id', 'subject_offering_id', 'enrollment_type', 'status', 'created_by'];
}
