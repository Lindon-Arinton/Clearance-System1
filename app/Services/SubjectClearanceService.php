<?php

namespace App\Services;

use App\Models\ClearanceSubjectModel;
use App\Models\IncRequirementModel;
use App\Models\TeacherModel;

/**
 * Teacher decisions on clearance subjects (PASSED / INC / FAILED), digital
 * signatures and the INC requirement workflow. Every method re-checks that the
 * subject is assigned to the acting teacher.
 */
class SubjectClearanceService
{
    public const DECISIONS = ['PASSED', 'INC', 'FAILED'];

    private ClearanceSubjectModel $subjects;
    private IncRequirementModel $requirements;

    public function __construct()
    {
        $this->subjects     = model(ClearanceSubjectModel::class);
        $this->requirements = model(IncRequirementModel::class);
    }

    /**
     * Clearance subject + clearance + student details, restricted to the teacher's own subjects.
     */
    public function loadForTeacher(int $csId, int $teacherId, bool $requireOpen = true): array
    {
        $cs = db_connect()->table('clearance_subjects cs')
            ->select('cs.*, c.status AS clearance_status, c.reference_no, c.student_id, s.student_number, u.id AS student_user_id, u.first_name, u.last_name')
            ->join('clearances c', 'c.id = cs.clearance_id')
            ->join('students s', 's.id = c.student_id')
            ->join('users u', 'u.id = s.user_id')
            ->where('cs.id', $csId)
            ->get()->getRowArray();

        if (! $cs) {
            throw new WorkflowException('Clearance subject not found.');
        }
        if ((int) $cs['teacher_id'] !== $teacherId) {
            AuditLogger::log('security.teacher_scope_violation', 'clearance_subject', $csId, null, null, 'Teacher attempted to act on a subject assigned to another teacher');

            throw new WorkflowException('You can only update subjects assigned to you.');
        }
        if ($requireOpen && $cs['clearance_status'] !== 'in_progress') {
            throw new WorkflowException($cs['clearance_status'] === 'completed'
                ? 'This clearance is already completed and locked.'
                : 'This clearance card is not active yet.');
        }

        return $cs;
    }

    public static function signatureHash(array $cs, int $teacherId, string $signedAt): string
    {
        $key = config('Encryption')->key ?: 'clearance-system';

        return hash_hmac('sha256', implode('|', ['cs', $cs['id'], 'clr', $cs['clearance_id'], 't', $teacherId, 'PASSED', $signedAt]), $key);
    }

    /**
     * True when the stored signature still matches the record (tamper check).
     */
    public static function signatureValid(array $cs): bool
    {
        if (empty($cs['signed_at']) || empty($cs['signature_hash'])) {
            return false;
        }

        return hash_equals($cs['signature_hash'], self::signatureHash($cs, (int) $cs['teacher_id'], $cs['signed_at']));
    }

    /**
     * The teacher's uploaded e-signature. Approvals are impossible without one.
     */
    public function requireSignature(int $teacherId): string
    {
        $path = model(TeacherModel::class)->find($teacherId)['signature_path'] ?? null;
        if (! $path || FileStorage::absolute($path) === null) {
            throw new WorkflowException('Upload your e-signature in your profile before approving students.');
        }

        return $path;
    }

    /**
     * Fields that stamp the teacher's e-signature onto an approval.
     */
    private function signatureFields(array $cs, int $teacherId, int $userId, string $signaturePath, string $now): array
    {
        return [
            'signed_by'       => $userId,
            'signed_at'       => $now,
            'signature_hash'  => self::signatureHash($cs, $teacherId, $now),
            'signature_image' => $signaturePath,
        ];
    }

    /**
     * Record a teacher decision. PASSED always applies the teacher's e-signature.
     *
     * @param list<string> $newRequirements requirement descriptions to add when marking INC
     */
    public function decide(int $csId, int $teacherId, int $userId, string $decision, ?string $remarks, ?string $grade, array $newRequirements = []): string
    {
        $decision = strtoupper(trim($decision));
        if (! in_array($decision, self::DECISIONS, true)) {
            throw new WorkflowException('Choose PASSED, INC or FAILED.');
        }

        $cs      = $this->loadForTeacher($csId, $teacherId);
        $current = $cs['status'];
        $remarks = $remarks !== null ? mb_substr(trim(strip_tags($remarks)), 0, 1000) : null;
        $grade   = $grade !== null ? mb_substr(trim(strip_tags($grade)), 0, 10) : null;
        $newRequirements = array_values(array_filter(array_map(static fn ($r) => mb_substr(trim(strip_tags((string) $r)), 0, 255), $newRequirements)));

        // Allowed transitions.
        $allowed = match ($current) {
            'PENDING' => ['PASSED', 'INC', 'FAILED'],
            'INC'     => ['PASSED', 'INC', 'FAILED'],
            'PASSED'  => empty($cs['signed_at']) ? ['PASSED'] : [],
            default   => [],
        };
        if (! in_array($decision, $allowed, true)) {
            throw new WorkflowException(match ($current) {
                'PASSED' => 'This subject is already passed and signed. Signed approvals cannot be changed.',
                'FAILED' => 'This subject is already marked FAILED and is in the re-enrollment process.',
                default  => "A subject cannot move from {$current} to {$decision}.",
            });
        }

        $pendingReqs = $this->requirements->where('clearance_subject_id', $csId)->where('status', 'pending')->countAllResults();
        if ($decision === 'INC' && $pendingReqs === 0 && $newRequirements === []) {
            throw new WorkflowException('Add at least one missing requirement when marking a subject INC.');
        }
        if ($current === 'INC' && $decision === 'PASSED' && $pendingReqs > 0) {
            throw new WorkflowException("Verify all {$pendingReqs} pending INC requirement(s) before marking this subject PASSED.");
        }

        $signature = $decision === 'PASSED' ? $this->requireSignature($teacherId) : null;

        $now    = date('Y-m-d H:i:s');
        $update = [
            'status'      => $decision,
            'remarks'     => $remarks,
            'final_grade' => $grade ?: null,
            'decided_by'  => $userId,
            'decided_at'  => $now,
        ];
        if ($decision === 'PASSED') {
            if ($current === 'INC') {
                $update['was_incomplete'] = 1;
            }
            $update += $this->signatureFields($cs, $teacherId, $userId, $signature, $now);
        }
        if ($decision === 'FAILED') {
            $update['reenrollment_status'] = 'RE_ENROLLMENT_REQUIRED';
        }

        $db = db_connect();
        $db->transBegin();

        try {
            $this->subjects->update($csId, $update);

            foreach ($newRequirements as $description) {
                $this->requirements->insert([
                    'clearance_subject_id' => $csId,
                    'description'          => $description,
                    'instructions'         => 'Complete the missing requirement personally with your subject teacher.',
                    'status'               => 'pending',
                    'created_by'           => $userId,
                ]);
            }

            AuditLogger::log('subject.decision', 'clearance_subject', $csId,
                ['status' => $current, 'signed' => ! empty($cs['signed_at'])],
                ['status' => $decision, 'signed' => $decision === 'PASSED', 'remarks' => $remarks, 'requirements_added' => $newRequirements],
                "{$cs['subject_code']}: {$current} → {$decision}" . ($decision === 'PASSED' ? ' (e-signed)' : ''));

            $this->notifyDecision($cs, $decision, $current, $decision === 'PASSED', $newRequirements);

            if ($decision === 'PASSED') {
                (new ClearanceService())->evaluate((int) $cs['clearance_id']);
            }

            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();

            throw $e;
        }

        return $decision;
    }

    private function notifyDecision(array $cs, string $decision, string $previous, bool $signed, array $newRequirements): void
    {
        $user    = (int) $cs['student_user_id'];
        $subject = $cs['subject_title'];
        $teacher = $cs['teacher_name'];

        if ($decision === 'PASSED') {
            Notifier::notify($user, 'subject_passed', "{$subject}: PASSED",
                $previous === 'INC'
                    ? "{$teacher} verified your completed requirements and marked {$subject} as PASSED" . ($signed ? ' and signed your clearance.' : '.')
                    : "{$teacher} marked {$subject} as PASSED" . ($signed ? ' and signed your clearance.' : '.'), 'student/subjects');
        } elseif ($decision === 'INC') {
            if ($previous !== 'INC') {
                Notifier::notify($user, 'subject_inc', "{$subject}: INC",
                    "{$teacher} marked {$subject} as Incomplete. Complete the missing requirement(s) personally with your teacher.", 'student/inc');
            }
            if ($newRequirements !== []) {
                Notifier::notify($user, 'inc_requirement_added', 'INC requirement added',
                    "{$subject}: " . implode('; ', $newRequirements), 'student/inc');
            }
        } else {
            Notifier::notify($user, 'subject_failed', "{$subject}: FAILED",
                "{$teacher} marked {$subject} as FAILED. You must re-enroll in this subject and upload the re-enrollment receipt.", 'student/reenrollment');
        }
    }

    /**
     * Sign an already-passed subject.
     */
    public function sign(int $csId, int $teacherId, int $userId): void
    {
        $cs = $this->loadForTeacher($csId, $teacherId);
        if ($cs['status'] !== 'PASSED') {
            throw new WorkflowException('Only PASSED subjects can be signed.');
        }
        if (! empty($cs['signed_at'])) {
            throw new WorkflowException('This subject is already signed.');
        }

        $signature = $this->requireSignature($teacherId);
        $now       = date('Y-m-d H:i:s');
        $db        = db_connect();
        $db->transBegin();

        try {
            $this->subjects->update($csId, $this->signatureFields($cs, $teacherId, $userId, $signature, $now));
            AuditLogger::log('subject.signed', 'clearance_subject', $csId, ['signed' => false], ['signed' => true], "{$cs['subject_code']} signed");
            Notifier::notify((int) $cs['student_user_id'], 'subject_signed', "{$cs['subject_title']}: teacher approved",
                "{$cs['teacher_name']} signed your clearance for {$cs['subject_title']}.", 'student/card');
            (new ClearanceService())->evaluate((int) $cs['clearance_id']);
            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();

            throw $e;
        }
    }

    // ------------------------------------------------------------------
    // INC requirements
    // ------------------------------------------------------------------

    public function addRequirement(int $csId, int $teacherId, int $userId, string $description, ?string $instructions, ?string $dueDate): void
    {
        $cs = $this->loadForTeacher($csId, $teacherId);
        if ($cs['status'] !== 'INC') {
            throw new WorkflowException('Requirements can only be added to subjects marked INC.');
        }
        $description = mb_substr(trim(strip_tags($description)), 0, 255);
        if (mb_strlen($description) < 3) {
            throw new WorkflowException('Describe the missing requirement.');
        }
        if ($dueDate && (! strtotime($dueDate) || $dueDate < date('Y-m-d'))) {
            throw new WorkflowException('The due date must be today or later.');
        }

        $id = $this->requirements->insert([
            'clearance_subject_id' => $csId,
            'description'          => $description,
            'instructions'         => trim(strip_tags((string) $instructions)) ?: 'Complete the missing requirement personally with your subject teacher.',
            'due_date'             => $dueDate ?: null,
            'status'               => 'pending',
            'created_by'           => $userId,
        ]);

        AuditLogger::log('inc.requirement_added', 'inc_requirement', (int) $id, null, ['description' => $description], "{$cs['subject_code']}: requirement added");
        Notifier::notify((int) $cs['student_user_id'], 'inc_requirement_added', 'INC requirement added',
            "{$cs['subject_title']}: {$description}", 'student/inc');
    }

    private function loadRequirement(int $reqId, int $teacherId): array
    {
        $req = $this->requirements->find($reqId);
        if (! $req) {
            throw new WorkflowException('Requirement not found.');
        }

        return [$req, $this->loadForTeacher((int) $req['clearance_subject_id'], $teacherId)];
    }

    public function removeRequirement(int $reqId, int $teacherId): void
    {
        [$req, $cs] = $this->loadRequirement($reqId, $teacherId);
        if ($req['status'] !== 'pending' || $cs['status'] !== 'INC') {
            throw new WorkflowException('Only pending requirements on an INC subject can be removed.');
        }
        $remaining = $this->requirements->where('clearance_subject_id', $cs['id'])->where('id !=', $reqId)->countAllResults();
        if ($remaining === 0) {
            throw new WorkflowException('An INC subject must keep at least one requirement. Change the decision instead.');
        }

        $this->requirements->delete($reqId);
        AuditLogger::log('inc.requirement_removed', 'inc_requirement', $reqId, ['description' => $req['description']], null, "{$cs['subject_code']}: requirement removed");
    }

    /**
     * Teacher confirms the student personally completed the requirement.
     */
    public function verifyRequirement(int $reqId, int $teacherId, int $userId, ?string $notes): bool
    {
        [$req, $cs] = $this->loadRequirement($reqId, $teacherId);
        if ($req['status'] !== 'pending') {
            throw new WorkflowException('This requirement is already verified.');
        }
        if ($cs['status'] !== 'INC') {
            throw new WorkflowException('This subject is no longer INC.');
        }

        $this->requirements->update($reqId, [
            'status'             => 'completed',
            'verified_by'        => $userId,
            'verified_at'        => date('Y-m-d H:i:s'),
            'verification_notes' => $notes ? mb_substr(trim(strip_tags($notes)), 0, 255) : null,
        ]);
        AuditLogger::log('inc.requirement_verified', 'inc_requirement', $reqId, ['status' => 'pending'], ['status' => 'completed'], "{$cs['subject_code']}: {$req['description']} verified");
        Notifier::notify((int) $cs['student_user_id'], 'inc_requirement_completed', 'INC requirement completed',
            "{$cs['teacher_name']} verified \"{$req['description']}\" for {$cs['subject_title']}.", 'student/inc');

        return $this->requirements->where('clearance_subject_id', $cs['id'])->where('status', 'pending')->countAllResults() === 0;
    }

    /**
     * Teacher declines a student's "I have completed this" report.
     */
    public function returnRequirement(int $reqId, int $teacherId, ?string $notes): void
    {
        [$req, $cs] = $this->loadRequirement($reqId, $teacherId);
        if ($req['status'] !== 'pending' || empty($req['student_reported_at'])) {
            throw new WorkflowException('There is no completion report to return.');
        }
        $notes = mb_substr(trim(strip_tags((string) $notes)), 0, 255);
        if ($notes === '') {
            throw new WorkflowException('Tell the student what is still missing.');
        }

        $this->requirements->update($reqId, ['student_reported_at' => null, 'verification_notes' => $notes]);
        AuditLogger::log('inc.report_returned', 'inc_requirement', $reqId, null, ['notes' => $notes]);
        Notifier::notify((int) $cs['student_user_id'], 'inc_requirement_returned', 'INC requirement not yet complete',
            "{$cs['subject_title']} — {$req['description']}: {$notes}", 'student/inc');
    }

    /**
     * Student tells the teacher they have finished a requirement. This does NOT
     * complete it; only the teacher's verification does.
     */
    public function studentReport(int $reqId, int $studentId, ?string $note): void
    {
        $row = db_connect()->table('inc_requirements r')
            ->select('r.*, cs.status AS cs_status, cs.subject_title, cs.subject_code, cs.teacher_id, c.student_id, c.status AS clearance_status, t.user_id AS teacher_user_id, s.student_number, u.first_name, u.last_name')
            ->join('clearance_subjects cs', 'cs.id = r.clearance_subject_id')
            ->join('clearances c', 'c.id = cs.clearance_id')
            ->join('teachers t', 't.id = cs.teacher_id')
            ->join('students s', 's.id = c.student_id')
            ->join('users u', 'u.id = s.user_id')
            ->where('r.id', $reqId)->get()->getRowArray();

        if (! $row || (int) $row['student_id'] !== $studentId) {
            throw new WorkflowException('Requirement not found.');
        }
        if ($row['status'] !== 'pending' || $row['cs_status'] !== 'INC' || $row['clearance_status'] !== 'in_progress') {
            throw new WorkflowException('This requirement is no longer open.');
        }
        if (! empty($row['student_reported_at'])) {
            throw new WorkflowException('Your teacher has already been notified. Please wait for verification.');
        }

        $this->requirements->update($reqId, [
            'student_reported_at' => date('Y-m-d H:i:s'),
            'student_note'        => $note ? mb_substr(trim(strip_tags($note)), 0, 255) : null,
        ]);
        AuditLogger::log('inc.student_reported', 'inc_requirement', $reqId, null, ['reported' => true], "{$row['subject_code']}: student reported completion");
        Notifier::notify((int) $row['teacher_user_id'], 'inc_student_completed', 'Student completed an INC requirement',
            person_name($row) . " ({$row['student_number']}) reports completing \"{$row['description']}\" for {$row['subject_code']}. Please verify.", 'teacher/inc');
    }
}
