<?php

namespace App\Services;

use App\Models\ClearanceModel;
use App\Models\ClearanceSubjectModel;
use App\Models\EnrollmentModel;
use App\Models\SchoolTermModel;
use App\Models\StudentModel;

/**
 * Registrar-side enrollment records. Enrollment into a term is gated by the
 * student's clearance for the previous term (enrollment eligibility).
 */
class EnrollmentService
{
    public function enroll(int $studentId, int $offeringId, string $type, int $adminUserId): void
    {
        $type = $type === 're_enrollment' ? 're_enrollment' : 'regular';

        $offering = db_connect()->table('subject_offerings so')
            ->select("so.*, s.code, s.title, s.units, CONCAT(t.title, ' ', u.first_name, ' ', u.last_name) AS teacher_name, u.id AS teacher_user_id")
            ->join('subjects s', 's.id = so.subject_id')
            ->join('teachers t', 't.id = so.teacher_id')
            ->join('users u', 'u.id = t.user_id')
            ->where('so.id', $offeringId)->get()->getRowArray();
        if (! $offering) {
            throw new WorkflowException('Subject offering not found.');
        }

        $student = model(StudentModel::class)->findDetailed($studentId);
        if (! $student || $student['status'] !== 'active') {
            throw new WorkflowException('Only active students can be enrolled.');
        }
        if ($student['approval_status'] === 'pending') {
            throw new WorkflowException('Approve this student\'s sign-up before enrolling them.');
        }

        $term = model(SchoolTermModel::class)->findWithYear((int) $offering['school_term_id']);
        if ($term['status'] === 'closed') {
            throw new WorkflowException(term_label($term) . ' is closed for enrollment.');
        }

        $enrollments = model(EnrollmentModel::class);
        $existing    = $enrollments->where('student_id', $studentId)->where('subject_offering_id', $offeringId)->first();
        if ($existing && $existing['status'] === 'enrolled') {
            throw new WorkflowException('The student is already enrolled in this subject.');
        }

        $sameSubject = db_connect()->table('enrollments e')->join('subject_offerings so', 'so.id = e.subject_offering_id')
            ->where('e.student_id', $studentId)->where('e.status', 'enrolled')
            ->where('so.school_term_id', $offering['school_term_id'])->where('so.subject_id', $offering['subject_id'])->countAllResults();
        if ($sameSubject > 0) {
            throw new WorkflowException("The student is already enrolled in {$offering['code']} this term (another section).");
        }

        $eligibility = (new EligibilityService())->canEnrollInTerm($studentId, $term);
        if (! $eligibility['ok']) {
            throw new WorkflowException($eligibility['reason'] . ' The student is not eligible to enroll in ' . term_label($term) . '.');
        }

        $clearance = model(ClearanceModel::class)->where('student_id', $studentId)->where('school_term_id', $term['id'])->first();
        if ($clearance && $clearance['status'] === 'completed') {
            throw new WorkflowException('The student\'s clearance for this term is already completed. Enrollment records for a completed clearance are locked.');
        }

        $db = db_connect();
        $db->transException(true)->transStart();

        if ($existing) {
            $enrollments->update($existing['id'], ['status' => 'enrolled', 'enrollment_type' => $type, 'created_by' => $adminUserId]);
            $enrollmentId = (int) $existing['id'];
        } else {
            $enrollmentId = (int) $enrollments->insert([
                'student_id' => $studentId, 'subject_offering_id' => $offeringId, 'enrollment_type' => $type, 'status' => 'enrolled', 'created_by' => $adminUserId,
            ]);
        }
        AuditLogger::log('enrollment.created', 'enrollment', $enrollmentId, null, ['offering' => $offering['code'], 'type' => $type], "Enrolled in {$offering['code']} ({$type})");

        // Late enrollment onto an active card adds the subject for the teacher to review.
        if ($clearance && $clearance['status'] === 'in_progress') {
            (new ClearanceService())->addSubjectRow((int) $clearance['id'], [
                'enrollment_id' => $enrollmentId, 'offering_id' => $offeringId, 'subject_id' => $offering['subject_id'], 'teacher_id' => $offering['teacher_id'],
                'code' => $offering['code'], 'title' => $offering['title'], 'units' => $offering['units'], 'teacher_name' => $offering['teacher_name'],
            ]);
            Notifier::notify((int) $offering['teacher_user_id'], 'clearance_action_required', 'Student requires clearance action',
                person_name($student) . " ({$student['student_number']}) was added to {$offering['code']} and needs your clearance decision.", 'teacher/subjects/' . $offeringId);
            Notifier::notify((int) $student['user_id'], 'clearance_subject_added', 'Subject added to your clearance',
                "{$offering['title']} was added to your clearance card.", 'student/card');
        }

        $db->transComplete();
    }

    public function drop(int $enrollmentId): void
    {
        $enrollments = model(EnrollmentModel::class);
        $enrollment  = $enrollments->find($enrollmentId);
        if (! $enrollment || $enrollment['status'] !== 'enrolled') {
            throw new WorkflowException('Enrollment not found.');
        }

        $subjects = model(ClearanceSubjectModel::class);
        $cs       = $subjects->where('subject_offering_id', $enrollment['subject_offering_id'])
            ->join('clearances c', 'c.id = clearance_subjects.clearance_id')
            ->where('c.student_id', $enrollment['student_id'])
            ->select('clearance_subjects.*, c.status AS clearance_status')->first();

        if ($cs && $cs['clearance_status'] === 'completed') {
            throw new WorkflowException('This subject is part of a completed clearance and cannot be dropped.');
        }
        if ($cs && $cs['status'] !== 'PENDING') {
            throw new WorkflowException("The teacher already recorded {$cs['status']} for this subject, so it cannot be dropped.");
        }

        $db = db_connect();
        $db->transException(true)->transStart();

        $enrollments->update($enrollmentId, ['status' => 'dropped']);
        if ($cs) {
            $subjects->delete($cs['id']);
        }
        AuditLogger::log('enrollment.dropped', 'enrollment', $enrollmentId, ['status' => 'enrolled'], ['status' => 'dropped'], 'Enrollment dropped');
        if ($cs) {
            (new ClearanceService())->evaluate((int) $cs['clearance_id']);
        }

        $db->transComplete();
    }
}
