<?php

namespace App\Services;

use CodeIgniter\HTTP\Files\UploadedFile;

/**
 * Stores uploads outside the web root (writable/uploads) under random names,
 * after checking extension, real MIME type, size and file signature.
 */
class FileStorage
{
    public const RECEIPT_TYPES = [
        'jpg'  => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png'  => ['image/png'],
        'pdf'  => ['application/pdf'],
    ];

    public const SIGNATURE_TYPES = [
        'png'  => ['image/png'],
        'jpg'  => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
    ];

    public static function root(): string
    {
        return rtrim(WRITEPATH, '\\/') . DIRECTORY_SEPARATOR . 'uploads';
    }

    public static function maxBytes(): int
    {
        return max(1, (int) setting('max_upload_mb', '5')) * 1024 * 1024;
    }

    /**
     * @param array<string, list<string>> $allowed extension => mime types
     *
     * @return array{file_path: string, original_name: string, mime_type: string, file_size: int, file_hash: string}
     */
    public static function store(?UploadedFile $file, string $folder, array $allowed = self::RECEIPT_TYPES, ?int $maxBytes = null): array
    {
        $maxBytes ??= self::maxBytes();

        if ($file === null || ! $file->isValid()) {
            throw new WorkflowException($file ? 'Upload failed: ' . $file->getErrorString() : 'Please choose a file to upload.');
        }
        if ($file->hasMoved()) {
            throw new WorkflowException('The uploaded file could not be processed.');
        }
        if ($file->getSize() <= 0 || $file->getSize() > $maxBytes) {
            throw new WorkflowException('The file must be smaller than ' . round($maxBytes / 1048576) . ' MB.');
        }

        $ext = strtolower($file->getClientExtension());
        if (! isset($allowed[$ext])) {
            throw new WorkflowException('Only ' . strtoupper(implode(', ', array_keys($allowed))) . ' files are allowed.');
        }

        // Detect the real MIME type from file contents, never the browser-supplied one.
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = (string) $finfo->file($file->getTempName());
        if (! in_array($mime, $allowed[$ext], true)) {
            throw new WorkflowException('The file content does not match its extension.');
        }

        if (str_starts_with($mime, 'image/')) {
            $info = @getimagesize($file->getTempName());
            if ($info === false || $info[0] < 50 || $info[1] < 50) {
                throw new WorkflowException('The image could not be read. Please upload a clear photo or scan.');
            }
        } else {
            $handle = fopen($file->getTempName(), 'rb');
            $magic  = $handle ? fread($handle, 5) : '';
            if ($handle) {
                fclose($handle);
            }
            if ($magic !== '%PDF-') {
                throw new WorkflowException('The PDF file appears to be damaged.');
            }
        }

        $relativeDir = trim($folder, '/') . '/' . date('Y/m');
        $absoluteDir = self::root() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativeDir);
        if (! is_dir($absoluteDir) && ! mkdir($absoluteDir, 0775, true) && ! is_dir($absoluteDir)) {
            throw new WorkflowException('Upload storage is not writable. Please contact the registrar.');
        }

        $name     = bin2hex(random_bytes(16)) . '.' . ($ext === 'jpeg' ? 'jpg' : $ext);
        $hash     = hash_file('sha256', $file->getTempName());
        $size     = $file->getSize();
        $original = mb_substr(preg_replace('/[^\w.\- ()]+/u', '_', $file->getClientName()), 0, 200);
        $file->move($absoluteDir, $name);

        return [
            'file_path'     => $relativeDir . '/' . $name,
            'original_name' => $original,
            'mime_type'     => $mime,
            'file_size'     => $size,
            'file_hash'     => $hash,
        ];
    }

    /**
     * Resolve a stored relative path, refusing anything outside the uploads root.
     */
    public static function absolute(string $relative): ?string
    {
        $root = realpath(self::root());
        $path = realpath(self::root() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative));

        if ($root === false || $path === false || ! str_starts_with($path, $root . DIRECTORY_SEPARATOR) || ! is_file($path)) {
            return null;
        }

        return $path;
    }
}
