<?php

namespace App\Models;

use CodeIgniter\Model;

class SchoolYearModel extends Model
{
    protected $table          = 'school_years';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useTimestamps  = true;
    protected $allowedFields  = ['name', 'start_date', 'end_date'];
}
