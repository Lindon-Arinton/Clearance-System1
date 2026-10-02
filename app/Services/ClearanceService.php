<?php

namespace App\Services;

use App\Models\ClearanceModel;
use App\Models\ClearanceSubjectModel;
use App\Models\IncRequirementModel;
use App\Models\ReenrollmentReceiptModel;
use App\Models\SchoolTermModel;
use App\Models\StudentModel;
use App\Models\TuitionReceiptModel;

/**
 * The clearance record is the hub of the workflow. This service owns its
 * lifecycle: start → receipt → card issued → subjects resolved → completed.
 */
class ClearanceService
{
    private ClearanceModel $clearances;
    private ClearanceSubjectModel $subjects;

    public function __construct()
    {
        $this->clearances = model(ClearanceModel::class);
        $this->subjects   = model(ClearanceSubjectModel::class);
    }

    public function currentTerm(): ?array
    {
        return model(SchoolTermModel::class)->current();
    }

    /**
     * Subjects a student is enrolled in for a term, with teacher names.
     */
    public function enrolledOfferings(int $studentId, int $termId): array
    {
        return db_connect()->table('enrollments e')
            ->select("e.id AS enrollment_id, e.enrollment_type, so.id AS offering_id, so.section, so.teacher_id, s.id AS subject_id, s.code, s.title, s.units,
                CONCAT(t.title, ' ', u.first_name, ' ', u.last_name) AS teacher_name, u.id AS teacher_user_id")
            ->join('subject_offerings so', 'so.id = e.subject_offering_id')
            ->join('subjects s', 's.id = so.subject_id')
            ->join('teachers t', 't.id = so.teacher_id')
            ->join('users u', 'u.id = t.user_id')
            ->where('e.student_id', $studentId)
            ->where('so.school_term_id', $termId)
            ->where('e.status', 'enrolled')
            ->orderBy('s.code')
            ->get()->getResultArray();
    }

    public function latestTuitionReceipt(int $clearanceId): ?array
    {
        return model(TuitionReceiptModel::class)->where('clearance_id', $clearanceId)->orderBy('id', 'DESC')->first();
    }

    /**
     * Student starts a clearance application for the current term.
     */
    public function start(int $studentId): array
    {
        $term = $this->currentTerm();
        if (! $term) {
            throw new WorkflowException('There is no active school term yet. Please check back later.');
        }
        if ($term['status'] === 'closed') {
            throw new WorkflowException('Clearance for ' . term_label($term) . ' is already closed.');
        }

        $student = model(StudentModel::class)->find($studentId);
        if (! $student || $student['status'] !== 'active') {
            throw new WorkflowException('Only active students can apply for clearance.');
        }
        if ($this->clearances->where('student_id', $studentId)->where('school_term_id', $term['id'])->first()) {
            throw new WorkflowException('You already have a clearance application for ' . term_label($term) . '.');
        }
        if ($this->enrolledOfferings($studentId, (int) $term['id']) === []) {
            throw new WorkflowException('You are not enrolled in any subjects for ' . term_label($term) . '. Please contact the registrar.');
        }

        $id = $this->clearances->insert([
            'reference_no'   => 'TMP-' . bin2hex(random_bytes(6)),
            'student_id'     => $studentId,
            'school_term_id' => $term['id'],
            'status'         => 'awaiting_receipt',
            'started_at'     => date('Y-m-d H:i:s'),
        ]);
        $reference = sprintf('CLR-%s-%s-%05d', substr((string) $term['school_year'], 0, 4), strtoupper(substr($term['semester'], 0, 1)), $id);
        $this->clearances->update($id, ['reference_no' => $reference]);

        AuditLogger::log('clearance.started', 'clearance', (int) $id, null, ['status' => 'awaiting_receipt', 'term' => term_label($term)], "Clearance {$reference} started");

        return $this->clearances->findDetailed((int) $id);
    }

    /**
     * Create the clearance card: one clearance_subject per enrolled subject.
     * Called only after the tuition receipt has been approved.
     */
    public function issueCard(int $clearanceId): int
    {
        $clearance = $this->clearances->findDetailed($clearanceId);
        if (! $clearance) {
            throw new WorkflowException('Clearance record not found.');
        }

        $offerings = $this->enrolledOfferings((int) $clearance['student_id'], (int) $clearance['school_term_id']);
        if ($offerings === []) {
            throw new WorkflowException('This student has no enrolled subjects for the term, so a clearance card cannot be issued.');
        }

        $created       = 0;
        $teacherUsers  = [];
        foreach ($offerings as $o) {
            if ($this->addSubjectRow($clearanceId, $o)) {
                $created++;
                $teacherUsers[(int) $o['teacher_user_id']][] = $o['code'];
            }
        }

        $now = date('Y-m-d H:i:s');
        $this->clearances->update($clearanceId, [
            'status'           => 'in_progress',
            'card_issued_at'   => $now,
            'student_snapshot' => json_encode($this->snapshot($clearance)),
        ]);

        AuditLogger::log('clearance.card_issued', 'clearance', $clearanceId, ['status' => $clearance['status']], ['status' => 'in_progress', 'subjects' => $created], 'Clearance card issued');
        Notifier::notify((int) $clearance['student_user_id'], 'clearance_created', 'Your clearance card is ready',
            "Your clearance card for " . term_label($clearance) . " has been created with {$created} subject(s). Your teachers can now review each subject.", 'student/card');

        $name = person_name($clearance);
        foreach ($teacherUsers as $userId => $codes) {
            Notifier::notify($userId, 'clearance_action_required', 'Student requires clearance action',
                "{$name} ({$clearance['student_number']}) needs your clearance decision for " . implode(', ', $codes) . '.', 'teacher/clearance');
        }

        return $created;
    }

    /**
     * Adds a subject to an active card (used when the registrar enrolls a student late).
     */
    public function addSubjectRow(int $clearanceId, array $offering): bool
    {
        $exists = $this->subjects->where('clearance_id', $clearanceId)->where('subject_offering_id', $offering['offering_id'])->first();
        if ($exists) {
            return false;
        }

        $this->subjects->insert([
            'clearance_id'        => $clearanceId,
            'enrollment_id'       => $offering['enrollment_id'],
            'subject_offering_id' => $offering['offering_id'],
            'subject_id'          => $offering['subject_id'],
            'teacher_id'          => $offering['teacher_id'],
            'subject_code'        => $offering['code'],
            'subject_title'       => $offering['title'],
            'units'               => $offering['units'],
            'teacher_name'        => $offering['teacher_name'],
            'status'              => 'PENDING',
        ]);

        return true;
    }

    /**
     * A subject is resolved when it is PASSED and signed by the teacher, or when a
     * FAILED subject's re-enrollment payment has been approved by the registrar.
     */
    public static function isResolved(array $cs): bool
    {
        if ($cs['status'] === 'PASSED') {
            return ! empty($cs['signed_at']);
        }

        return $cs['status'] === 'FAILED' && $cs['reenrollment_status'] === 'RE_ENROLLMENT_APPROVED';
    }

    /**
     * Re-check every subject and complete the clearance when all are resolved.
     *
     * @return bool true when this call completed the clearance
     */
    public function evaluate(int $clearanceId): bool
    {
        $clearance = $this->clearances->findDetailed($clearanceId);
        if (! $clearance || $clearance['status'] !== 'in_progress') {
            return false;
        }

        $rows       = $this->subjects->where('clearance_id', $clearanceId)->findAll();
        $unresolved = 0;
        $now        = date('Y-m-d H:i:s');

        foreach ($rows as $cs) {
            $resolved = self::isResolved($cs);
            if ($resolved !== (bool) $cs['is_resolved']) {
                $this->subjects->update($cs['id'], ['is_resolved' => $resolved ? 1 : 0, 'resolved_at' => $resolved ? $now : null]);
            }
            if (! $resolved) {
                $unresolved++;
            }
        }

        if ($rows === [] || $unresolved > 0) {
            return false;
        }

        $this->clearances->update($clearanceId, [
            'status'              => 'completed',
            'completed_at'        => $now,
            'enrollment_eligible' => 1,
            'eligible_at'         => $now,
            'student_snapshot'    => json_encode($this->snapshot($clearance)),
        ]);

        AuditLogger::log('clearance.completed', 'clearance', $clearanceId, ['status' => 'in_progress'], ['status' => 'completed', 'enrollment_eligible' => 1], "Clearance {$clearance['reference_no']} completed");

        $label = term_label($clearance);
        Notifier::notify((int) $clearance['student_user_id'], 'clearance_completed', 'Clearance completed',
            "Congratulations! Your clearance for {$label} is complete and has been saved to your clearance history.", 'student/dashboard');
        Notifier::notify((int) $clearance['student_user_id'], 'eligible_next_semester', 'Eligible for next semester',
            'You are now qualified to enroll for the next semester.', 'student/dashboard');

        return true;
    }

    /**
     * Student details frozen onto the clearance so history is never rewritten.
     */
    public function snapshot(array $clearance): array
    {
        return [
            'name'           => trim(($clearance['first_name'] ?? '') . ' ' . ($clearance['middle_name'] ? mb_substr($clearance['middle_name'], 0, 1) . '. ' : '') . ($clearance['last_name'] ?? '')),
            'student_number' => $clearance['student_number'],
            'program_code'   => $clearance['program_code'],
            'program_name'   => $clearance['program_name'],
            'year_level'     => (int) $clearance['year_level'],
            'section'        => $clearance['section'],
        ];
    }

    /**
     * Everything the student-facing pages need about one clearance.
     */
    public function bundle(?array $clearance): array
    {
        $bundle = [
            'clearance' => $clearance,
            'receipt'   => null,
            'receipts'  => [],
            'subjects'  => [],
            'stats'     => ['total' => 0, 'resolved' => 0, 'passed' => 0, 'inc' => 0, 'failed' => 0, 'pending' => 0, 'unsigned' => 0],
            'blockers'  => [],
            'snapshot'  => null,
        ];
        if (! $clearance) {
            return $bundle;
        }

        $bundle['receipts'] = model(TuitionReceiptModel::class)->where('clearance_id', $clearance['id'])->orderBy('id', 'DESC')->findAll();
        $bundle['receipt']  = $bundle['receipts'][0] ?? null;
        $bundle['subjects'] = $this->subjects->forClearance((int) $clearance['id']);
        $bundle['snapshot'] = $clearance['student_snapshot'] ? json_decode($clearance['student_snapshot'], true) : null;

        foreach ($bundle['subjects'] as $cs) {
            $bundle['stats']['total']++;
            if (self::isResolved($cs)) {
                $bundle['stats']['resolved']++;
            }
            match ($cs['status']) {
                'PASSED'  => $bundle['stats']['passed']++,
                'INC'     => $bundle['stats']['inc']++,
                'FAILED'  => $bundle['stats']['failed']++,
                default   => $bundle['stats']['pending']++,
            };
            if ($cs['status'] === 'PASSED' && empty($cs['signed_at'])) {
                $bundle['stats']['unsigned']++;
            }
        }

        $bundle['blockers'] = $this->blockers($clearance, $bundle['receipt'], $bundle['subjects']);

        return $bundle;
    }

    /**
     * Plain-language list of what still stands between the student and completion.
     *
     * @return list<array{title: string, detail: string, status: string, link: string}>
     */
    public function blockers(?array $clearance, ?array $receipt, array $subjects): array
    {
        if (! $clearance) {
            return [['title' => 'Clearance not started', 'detail' => 'Start your clearance application for this term.', 'status' => 'not_started', 'link' => 'student/dashboard']];
        }
        if ($clearance['status'] === 'completed') {
            return [];
        }

        $items = [];
        switch ($clearance['status']) {
            case 'awaiting_receipt':
                $items[] = ['title' => 'Tuition receipt', 'detail' => 'Upload your official tuition receipt to continue.', 'status' => 'not_submitted', 'link' => 'student/receipt'];
                break;

            case 'reupload_required':
                $items[] = ['title' => 'Tuition receipt', 'detail' => 'The registrar asked for a new upload: ' . ($receipt['reupload_reason'] ?? ''), 'status' => 'reupload_required', 'link' => 'student/receipt'];
                break;

            case 'receipt_review':
                $items[] = ['title' => 'Tuition receipt', 'detail' => 'Waiting for the registrar to validate your receipt.', 'status' => $receipt['status'] ?? 'submitted', 'link' => 'student/receipt'];
                break;
        }

        foreach ($subjects as $cs) {
            if (self::isResolved($cs)) {
                continue;
            }
            $title = $cs['subject_title'];
            $items[] = match (true) {
                $cs['status'] === 'PENDING' => ['title' => $title, 'detail' => 'Awaiting decision from ' . $cs['teacher_name'] . '.', 'status' => 'PENDING', 'link' => 'student/subjects'],
                $cs['status'] === 'PASSED'  => ['title' => $title, 'detail' => 'Passed — awaiting ' . $cs['teacher_name'] . "'s signature.", 'status' => 'AWAITING_SIGNATURE', 'link' => 'student/subjects'],
                $cs['status'] === 'INC'     => ['title' => $title, 'detail' => 'Complete the missing requirement(s) with ' . $cs['teacher_name'] . '.', 'status' => 'INC', 'link' => 'student/inc'],
                $cs['reenrollment_status'] === 'RE_ENROLLMENT_RECEIPT_PENDING' => ['title' => $title, 'detail' => 'Re-enrollment receipt is being reviewed by the registrar.', 'status' => 'RE_ENROLLMENT_RECEIPT_PENDING', 'link' => 'student/reenrollment'],
                default                     => ['title' => $title, 'detail' => 'Failed — re-enroll and upload the re-enrollment receipt.', 'status' => 'FAILED', 'link' => 'student/reenrollment'],
            };
        }

        return $items;
    }

    /**
     * Latest re-enrollment receipt per clearance subject id.
     *
     * @param list<int> $csIds
     *
     * @return array<int, array>
     */
    public function latestReenrollmentReceipts(array $csIds): array
    {
        if ($csIds === []) {
            return [];
        }
        $rows = model(ReenrollmentReceiptModel::class)->whereIn('clearance_subject_id', $csIds)->orderBy('id', 'ASC')->findAll();
        $out  = [];
        foreach ($rows as $r) {
            $out[(int) $r['clearance_subject_id']] = $r; // later attempts overwrite earlier ones
        }

        return $out;
    }

    /**
     * INC requirements grouped by clearance subject id.
     *
     * @param list<int> $csIds
     *
     * @return array<int, list<array>>
     */
    public function requirementsFor(array $csIds): array
    {
        if ($csIds === []) {
            return [];
        }
        $out = [];
        foreach (model(IncRequirementModel::class)->whereIn('clearance_subject_id', $csIds)->orderBy('id')->findAll() as $r) {
            $out[(int) $r['clearance_subject_id']][] = $r;
        }

        return $out;
    }
}
