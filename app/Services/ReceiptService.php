<?php

namespace App\Services;

use App\Models\ClearanceModel;
use App\Models\ClearanceSubjectModel;
use App\Models\ReenrollmentReceiptModel;
use App\Models\TuitionReceiptModel;
use CodeIgniter\HTTP\Files\UploadedFile;

/**
 * Tuition and re-enrollment receipts: upload by students, validation by admins.
 * Status values are decided here — never taken from the request.
 */
class ReceiptService
{
    private const REVIEWABLE = ['submitted', 'under_review'];

    /**
     * Validate the payment details typed by the student.
     */
    private function paymentDetails(array $input): array
    {
        $or     = strtoupper(trim((string) ($input['or_number'] ?? '')));
        $amount = str_replace(',', '', trim((string) ($input['amount'] ?? '')));
        $date   = trim((string) ($input['payment_date'] ?? ''));

        if (! preg_match('/^[A-Z0-9][A-Z0-9\-\/]{2,39}$/', $or)) {
            throw new WorkflowException('Enter the official receipt (OR) number exactly as printed (letters, numbers and dashes only).');
        }
        if (! is_numeric($amount) || (float) $amount <= 0 || (float) $amount > 10000000) {
            throw new WorkflowException('Enter the amount paid as shown on the receipt.');
        }
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        if (! $d || $d->format('Y-m-d') !== $date || $date > date('Y-m-d') || $date < date('Y-m-d', strtotime('-2 years'))) {
            throw new WorkflowException('Enter a valid payment date (not in the future).');
        }

        return ['or_number' => $or, 'amount' => round((float) $amount, 2), 'payment_date' => $date];
    }

    private function reason(string $code, string $message): array
    {
        $reasons = reupload_reasons();
        if (! isset($reasons[$code])) {
            throw new WorkflowException('Choose a reason for the re-upload request.');
        }
        $message = trim(strip_tags($message));
        if (mb_strlen($message) < 10) {
            throw new WorkflowException('Please explain what the student needs to fix (at least 10 characters).');
        }

        return ['reupload_reason_code' => $code, 'reupload_reason' => mb_substr($message, 0, 1000)];
    }

    // ------------------------------------------------------------------
    // Tuition receipts
    // ------------------------------------------------------------------

    public function submitTuition(int $studentId, int $clearanceId, ?UploadedFile $file, array $input): int
    {
        $clearances = model(ClearanceModel::class);
        $clearance  = $clearances->findDetailed($clearanceId);

        if (! $clearance || (int) $clearance['student_id'] !== $studentId) {
            throw new WorkflowException('Clearance record not found.');
        }
        if ($clearance['status'] === 'receipt_review') {
            throw new WorkflowException('Your receipt is already with the registrar. You can upload again only if a re-upload is requested.');
        }
        if (! in_array($clearance['status'], ['awaiting_receipt', 'reupload_required'], true)) {
            throw new WorkflowException('Your tuition receipt has already been approved for this term.');
        }

        $details = $this->paymentDetails($input);
        $meta    = FileStorage::store($file, 'receipts/tuition');
        $model   = model(TuitionReceiptModel::class);
        $db      = db_connect();

        $db->transBegin();

        try {
            $attempt = $model->where('clearance_id', $clearanceId)->countAllResults() + 1;
            $id      = $model->insert($details + $meta + [
                'clearance_id' => $clearanceId,
                'student_id'   => $studentId,
                'attempt_no'   => $attempt,
                'status'       => 'submitted',
                'submitted_at' => date('Y-m-d H:i:s'),
            ]);
            $clearances->update($clearanceId, ['status' => 'receipt_review']);

            AuditLogger::log('receipt.submitted', 'tuition_receipt', (int) $id, ['clearance_status' => $clearance['status']], ['status' => 'submitted', 'attempt' => $attempt], "Tuition receipt {$details['or_number']} submitted (attempt {$attempt})");
            Notifier::notify((int) $clearance['student_user_id'], 'receipt_submitted', 'Tuition receipt submitted',
                "We received your receipt {$details['or_number']}. The registrar will review it shortly.", 'student/receipt');
            Notifier::notifyAdmins('receipt_new', $attempt > 1 ? 'Re-uploaded receipt needs validation' : 'New tuition receipt submitted',
                person_name($clearance) . " ({$clearance['student_number']}) submitted receipt {$details['or_number']} for validation.", 'admin/receipts?id=' . $id);

            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            @unlink((string) FileStorage::absolute($meta['file_path']));

            throw $e;
        }

        return (int) $id;
    }

    /**
     * Opening a submitted receipt claims it: SUBMITTED → UNDER REVIEW.
     */
    public function beginTuitionReview(int $receiptId, int $adminUserId): void
    {
        $model   = model(TuitionReceiptModel::class);
        $receipt = $model->find($receiptId);
        if ($receipt && $receipt['status'] === 'submitted') {
            $model->update($receiptId, ['status' => 'under_review', 'review_started_at' => date('Y-m-d H:i:s'), 'reviewed_by' => $adminUserId]);
            AuditLogger::log('receipt.under_review', 'tuition_receipt', $receiptId, ['status' => 'submitted'], ['status' => 'under_review']);
        }
    }

    public function approveTuition(int $receiptId, int $adminUserId, array $checklist): void
    {
        $model   = model(TuitionReceiptModel::class);
        $receipt = $model->find($receiptId);
        if (! $receipt) {
            throw new WorkflowException('Receipt not found.');
        }
        if (! in_array($receipt['status'], self::REVIEWABLE, true)) {
            throw new WorkflowException('This receipt has already been processed.');
        }

        $latest = $model->where('clearance_id', $receipt['clearance_id'])->orderBy('id', 'DESC')->first();
        if ((int) $latest['id'] !== $receiptId) {
            throw new WorkflowException('A newer receipt exists for this clearance. Review the latest upload instead.');
        }

        $missing = array_diff(array_keys(receipt_checklist_items()), array_keys(array_filter($checklist)));
        if ($missing !== []) {
            throw new WorkflowException('Confirm every item in the review checklist before approving.');
        }

        $db = db_connect();
        $db->transBegin();

        try {
            $model->update($receiptId, [
                'status'      => 'approved',
                'reviewed_by' => $adminUserId,
                'reviewed_at' => date('Y-m-d H:i:s'),
                'checklist'   => json_encode(array_fill_keys(array_keys(receipt_checklist_items()), true)),
            ]);
            AuditLogger::log('receipt.approved', 'tuition_receipt', $receiptId, ['status' => $receipt['status']], ['status' => 'approved'], "Tuition receipt {$receipt['or_number']} approved");

            $clearance = model(ClearanceModel::class)->findDetailed((int) $receipt['clearance_id']);
            Notifier::notify((int) $clearance['student_user_id'], 'receipt_approved', 'Tuition receipt approved',
                "Your receipt {$receipt['or_number']} was verified by the registrar.", 'student/receipt');

            (new ClearanceService())->issueCard((int) $receipt['clearance_id']);

            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();

            throw $e;
        }
    }

    public function requestTuitionReupload(int $receiptId, int $adminUserId, string $code, string $message): void
    {
        $model   = model(TuitionReceiptModel::class);
        $receipt = $model->find($receiptId);
        if (! $receipt || ! in_array($receipt['status'], self::REVIEWABLE, true)) {
            throw new WorkflowException('This receipt has already been processed.');
        }
        $reason = $this->reason($code, $message);

        $db = db_connect();
        $db->transBegin();

        try {
            $model->update($receiptId, $reason + ['status' => 'reupload_required', 'reviewed_by' => $adminUserId, 'reviewed_at' => date('Y-m-d H:i:s')]);
            model(ClearanceModel::class)->update($receipt['clearance_id'], ['status' => 'reupload_required']);

            AuditLogger::log('receipt.reupload_requested', 'tuition_receipt', $receiptId, ['status' => $receipt['status']], ['status' => 'reupload_required'] + $reason, 'Re-upload requested: ' . reupload_reasons()[$code]);
            $clearance = model(ClearanceModel::class)->findDetailed((int) $receipt['clearance_id']);
            Notifier::notify((int) $clearance['student_user_id'], 'receipt_reupload', 'Receipt re-upload required',
                'Reason: ' . $reason['reupload_reason'], 'student/receipt');

            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();

            throw $e;
        }
    }

    // ------------------------------------------------------------------
    // Re-enrollment receipts (for FAILED subjects)
    // ------------------------------------------------------------------

    /**
     * Load a clearance subject that belongs to the student.
     */
    private function ownedFailedSubject(int $studentId, int $csId): array
    {
        $cs = db_connect()->table('clearance_subjects cs')
            ->select('cs.*, c.student_id, c.status AS clearance_status, c.reference_no')
            ->join('clearances c', 'c.id = cs.clearance_id')
            ->where('cs.id', $csId)->get()->getRowArray();

        if (! $cs || (int) $cs['student_id'] !== $studentId) {
            throw new WorkflowException('Subject not found.');
        }

        return $cs;
    }

    public function submitReenrollment(int $studentId, int $csId, ?UploadedFile $file, array $input): int
    {
        $cs = $this->ownedFailedSubject($studentId, $csId);

        if ($cs['status'] !== 'FAILED') {
            throw new WorkflowException('Re-enrollment receipts are only needed for failed subjects.');
        }
        if ($cs['reenrollment_status'] === 'RE_ENROLLMENT_RECEIPT_PENDING') {
            throw new WorkflowException('Your re-enrollment receipt for this subject is already being reviewed.');
        }
        if ($cs['reenrollment_status'] === 'RE_ENROLLMENT_APPROVED') {
            throw new WorkflowException('Re-enrollment for this subject is already confirmed.');
        }
        if ($cs['clearance_status'] !== 'in_progress') {
            throw new WorkflowException('This clearance is no longer open for changes.');
        }

        $details = $this->paymentDetails($input);
        $meta    = FileStorage::store($file, 'receipts/reenrollment');
        $model   = model(ReenrollmentReceiptModel::class);
        $db      = db_connect();

        $db->transBegin();

        try {
            $attempt = $model->where('clearance_subject_id', $csId)->countAllResults() + 1;
            $id      = $model->insert($details + $meta + [
                'clearance_subject_id' => $csId,
                'student_id'           => $studentId,
                'attempt_no'           => $attempt,
                'status'               => 'submitted',
                'submitted_at'         => date('Y-m-d H:i:s'),
            ]);
            model(ClearanceSubjectModel::class)->update($csId, ['reenrollment_status' => 'RE_ENROLLMENT_RECEIPT_PENDING']);

            AuditLogger::log('reenrollment.receipt_submitted', 'clearance_subject', $csId, ['reenrollment_status' => $cs['reenrollment_status']], ['reenrollment_status' => 'RE_ENROLLMENT_RECEIPT_PENDING', 'receipt_id' => $id], "Re-enrollment receipt for {$cs['subject_code']} submitted");

            $student = model(\App\Models\StudentModel::class)->findDetailed($studentId);
            Notifier::notify((int) $student['user_id'], 'reenrollment_submitted', 'Re-enrollment receipt submitted',
                "Your re-enrollment receipt for {$cs['subject_title']} was received and is waiting for registrar validation.", 'student/reenrollment');
            Notifier::notifyAdmins('reenrollment_receipt_new', 'Re-enrollment receipt submitted',
                person_name($student) . " ({$student['student_number']}) uploaded a re-enrollment receipt for {$cs['subject_code']}.", 'admin/reenrollment?id=' . $id);

            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            @unlink((string) FileStorage::absolute($meta['file_path']));

            throw $e;
        }

        return (int) $id;
    }

    public function beginReenrollmentReview(int $receiptId, int $adminUserId): void
    {
        $model   = model(ReenrollmentReceiptModel::class);
        $receipt = $model->find($receiptId);
        if ($receipt && $receipt['status'] === 'submitted') {
            $model->update($receiptId, ['status' => 'under_review', 'review_started_at' => date('Y-m-d H:i:s'), 'reviewed_by' => $adminUserId]);
            AuditLogger::log('reenrollment.under_review', 'reenrollment_receipt', $receiptId, ['status' => 'submitted'], ['status' => 'under_review']);
        }
    }

    public function approveReenrollment(int $receiptId, int $adminUserId): void
    {
        $model   = model(ReenrollmentReceiptModel::class);
        $receipt = $model->find($receiptId);
        if (! $receipt || ! in_array($receipt['status'], self::REVIEWABLE, true)) {
            throw new WorkflowException('This receipt has already been processed.');
        }

        $subjects = model(ClearanceSubjectModel::class);
        $cs       = $subjects->find($receipt['clearance_subject_id']);
        if ($cs['status'] !== 'FAILED' || $cs['reenrollment_status'] !== 'RE_ENROLLMENT_RECEIPT_PENDING') {
            throw new WorkflowException('This subject is not waiting for a re-enrollment receipt.');
        }

        $db = db_connect();
        $db->transBegin();

        try {
            $now = date('Y-m-d H:i:s');
            $model->update($receiptId, ['status' => 'approved', 'reviewed_by' => $adminUserId, 'reviewed_at' => $now]);
            // The FAILED decision stays on record; only the re-enrollment track moves forward.
            $subjects->update($cs['id'], ['reenrollment_status' => 'RE_ENROLLMENT_APPROVED', 'reenrollment_confirmed_at' => $now]);

            AuditLogger::log('reenrollment.approved', 'clearance_subject', (int) $cs['id'], ['reenrollment_status' => 'RE_ENROLLMENT_RECEIPT_PENDING'], ['reenrollment_status' => 'RE_ENROLLMENT_APPROVED', 'receipt_id' => $receiptId], "Re-enrollment for {$cs['subject_code']} confirmed");

            $student = model(\App\Models\StudentModel::class)->find($receipt['student_id']);
            Notifier::notify((int) $student['user_id'], 'reenrollment_approved', 'Re-enrollment receipt approved',
                "Your re-enrollment for {$cs['subject_title']} is confirmed. The subject is now resolved on your clearance.", 'student/reenrollment');

            (new ClearanceService())->evaluate((int) $cs['clearance_id']);

            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();

            throw $e;
        }
    }

    public function requestReenrollmentReupload(int $receiptId, int $adminUserId, string $code, string $message): void
    {
        $model   = model(ReenrollmentReceiptModel::class);
        $receipt = $model->find($receiptId);
        if (! $receipt || ! in_array($receipt['status'], self::REVIEWABLE, true)) {
            throw new WorkflowException('This receipt has already been processed.');
        }
        $reason = $this->reason($code, $message);

        $db = db_connect();
        $db->transBegin();

        try {
            $model->update($receiptId, $reason + ['status' => 'reupload_required', 'reviewed_by' => $adminUserId, 'reviewed_at' => date('Y-m-d H:i:s')]);
            $subjects = model(ClearanceSubjectModel::class);
            $cs       = $subjects->find($receipt['clearance_subject_id']);
            $subjects->update($cs['id'], ['reenrollment_status' => 'RE_ENROLLMENT_REQUIRED']);

            AuditLogger::log('reenrollment.reupload_requested', 'reenrollment_receipt', $receiptId, ['status' => $receipt['status']], ['status' => 'reupload_required'] + $reason);
            $student = model(\App\Models\StudentModel::class)->find($receipt['student_id']);
            Notifier::notify((int) $student['user_id'], 'reenrollment_reupload', 'Re-enrollment receipt re-upload required',
                "{$cs['subject_title']}: " . $reason['reupload_reason'], 'student/reenrollment');

            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();

            throw $e;
        }
    }
}
