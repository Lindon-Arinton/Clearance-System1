<?php

namespace App\Controllers;

use App\Models\ReenrollmentReceiptModel;
use App\Models\TeacherModel;
use App\Models\TuitionReceiptModel;
use App\Services\AuditLogger;
use App\Services\FileStorage;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Serves private uploads after checking that the viewer may see the record.
 */
class Files extends BaseController
{
    public function tuitionReceipt(int $id): ResponseInterface
    {
        $receipt = model(TuitionReceiptModel::class)->find($id);

        return $this->serveReceipt($receipt, 'tuition_receipt', $id);
    }

    public function reenrollmentReceipt(int $id): ResponseInterface
    {
        $receipt = model(ReenrollmentReceiptModel::class)->find($id);

        return $this->serveReceipt($receipt, 'reenrollment_receipt', $id);
    }

    public function signature(int $teacherId): ResponseInterface
    {
        $teacher = model(TeacherModel::class)->find($teacherId);
        if (! $teacher || ! $teacher['signature_path']) {
            $this->notFound();
        }

        $allowed = match ($this->auth->role()) {
            'admin'   => true,
            'teacher' => (int) $this->auth->teacherId() === $teacherId,
            // Students may see the signature of a teacher who signed one of their subjects.
            'student' => db_connect()->table('clearance_subjects cs')->join('clearances c', 'c.id = cs.clearance_id')
                ->where('c.student_id', $this->auth->studentId())->where('cs.teacher_id', $teacherId)
                ->where('cs.signed_at IS NOT NULL')->countAllResults() > 0,
            default   => false,
        };
        if (! $allowed) {
            return $this->deny('teacher_signature', $teacherId);
        }

        return $this->stream($teacher['signature_path'], 'signature.png', false);
    }

    /**
     * The e-signature stamped on one approval (kept even if the teacher later replaces theirs).
     */
    public function approvalSignature(int $csId): ResponseInterface
    {
        $cs = db_connect()->table('clearance_subjects cs')->select('cs.signature_image, cs.teacher_id, c.student_id')
            ->join('clearances c', 'c.id = cs.clearance_id')->where('cs.id', $csId)->get()->getRowArray();
        if (! $cs || ! $cs['signature_image']) {
            $this->notFound();
        }

        $allowed = match ($this->auth->role()) {
            'admin'   => true,
            'teacher' => (int) $this->auth->teacherId() === (int) $cs['teacher_id'],
            'student' => (int) $this->auth->studentId() === (int) $cs['student_id'],
            default   => false,
        };
        if (! $allowed) {
            return $this->deny('approval_signature', $csId);
        }

        return $this->stream($cs['signature_image'], 'signature.png', false);
    }

    private function serveReceipt(?array $receipt, string $entity, int $id): ResponseInterface
    {
        if (! $receipt) {
            $this->notFound();
        }

        // Admins validate receipts; students may only open their own. Teachers never see payments.
        $allowed = $this->auth->role() === 'admin'
            || ($this->auth->role() === 'student' && (int) $receipt['student_id'] === (int) $this->auth->studentId());

        if (! $allowed) {
            return $this->deny($entity, $id);
        }

        return $this->stream($receipt['file_path'], $receipt['original_name'], $this->request->getGet('download') === '1');
    }

    private function stream(string $relative, string $downloadName, bool $download): ResponseInterface
    {
        $path = FileStorage::absolute($relative);
        if ($path === null) {
            $this->notFound('The file is no longer available.');
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($path) ?: 'application/octet-stream';
        $safe = preg_replace('/[^A-Za-z0-9._-]+/', '_', $downloadName) ?: 'file';

        return $this->response
            ->setHeader('Content-Type', $mime)
            ->setHeader('Content-Length', (string) filesize($path))
            ->setHeader('Content-Disposition', ($download ? 'attachment' : 'inline') . '; filename="' . $safe . '"')
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setHeader('Cache-Control', 'private, no-store')
            ->setBody(file_get_contents($path));
    }

    private function deny(string $entity, int $id): ResponseInterface
    {
        AuditLogger::log('security.file_access_denied', $entity, $id, null, null, 'Blocked access to a private file');

        return $this->response->setStatusCode(403)->setBody(view('errors/forbidden'));
    }
}
