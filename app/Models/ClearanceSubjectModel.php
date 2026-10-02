<?php

namespace App\Models;

use CodeIgniter\Model;

class ClearanceSubjectModel extends Model
{
    protected $table         = 'clearance_subjects';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'clearance_id', 'enrollment_id', 'subject_offering_id', 'subject_id', 'teacher_id', 'subject_code', 'subject_title',
        'units', 'teacher_name', 'status', 'reenrollment_status', 'final_grade', 'remarks', 'was_incomplete', 'decided_by',
        'decided_at', 'signed_by', 'signed_at', 'signature_hash', 'signature_image', 'reenrollment_confirmed_at', 'is_resolved', 'resolved_at',
    ];

    /**
     * Subject rows with teacher contact, signature image and INC counters.
     */
    public function withDetails(): self
    {
        return $this->select("clearance_subjects.*, teachers.signature_path, teachers.department AS teacher_department, tu.email AS teacher_email,
                subject_offerings.section AS offering_section, subject_offerings.schedule AS offering_schedule,
                (SELECT COUNT(*) FROM inc_requirements ir WHERE ir.clearance_subject_id = clearance_subjects.id) AS inc_total,
                (SELECT COUNT(*) FROM inc_requirements ir WHERE ir.clearance_subject_id = clearance_subjects.id AND ir.status = 'pending') AS inc_pending,
                (SELECT COUNT(*) FROM inc_requirements ir WHERE ir.clearance_subject_id = clearance_subjects.id AND ir.status = 'pending' AND ir.student_reported_at IS NOT NULL) AS inc_reported")
            ->join('teachers', 'teachers.id = clearance_subjects.teacher_id')
            ->join('users tu', 'tu.id = teachers.user_id')
            ->join('subject_offerings', 'subject_offerings.id = clearance_subjects.subject_offering_id');
    }

    public function forClearance(int $clearanceId): array
    {
        return $this->withDetails()
            ->where('clearance_subjects.clearance_id', $clearanceId)
            ->orderBy('clearance_subjects.subject_code')
            ->findAll();
    }
}
