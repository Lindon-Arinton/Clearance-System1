<?php

namespace App\Models;

use CodeIgniter\Model;

class SubjectOfferingModel extends Model
{
    protected $table          = 'subject_offerings';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useTimestamps  = true;
    protected $allowedFields  = ['school_term_id', 'subject_id', 'teacher_id', 'section', 'schedule'];
}
