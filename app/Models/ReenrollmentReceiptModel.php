<?php

namespace App\Models;

use CodeIgniter\Model;

class ReenrollmentReceiptModel extends Model
{
    protected $table          = 'reenrollment_receipts';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useTimestamps  = true;
    protected $allowedFields  = ['clearance_subject_id', 'student_id', 'attempt_no', 'or_number', 'amount', 'payment_date', 'file_path', 'original_name', 'mime_type', 'file_size', 'file_hash', 'status', 'submitted_at', 'review_started_at', 'reviewed_by', 'reviewed_at', 'reupload_reason_code', 'reupload_reason'];
}
