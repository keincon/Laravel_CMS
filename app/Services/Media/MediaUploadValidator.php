<?php

declare(strict_types=1);

namespace App\Services\Media;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

/**
 * Server-side media validation that does not trust client MIME headers.
 */
final class MediaUploadValidator
{
    /** @var array<string, list<string>> */
    private array $allowed = [
        'image/jpeg' => ['jpg', 'jpeg'],
        'image/png' => ['png'],
        'image/gif' => ['gif'],
        'image/webp' => ['webp'],
        'image/x-icon' => ['ico'],
        'image/vnd.microsoft.icon' => ['ico'],
        'application/pdf' => ['pdf'],
        'video/mp4' => ['mp4'],
        'video/webm' => ['webm'],
        'audio/mpeg' => ['mp3'],
        'audio/wav' => ['wav'],
    ];

    public function assertSafe(UploadedFile $file, int $maxKilobytes = 10240): string
    {
        if (! $file->isValid()) {
            throw ValidationException::withMessages(['file' => 'The uploaded file is invalid.']);
        }

        if ($file->getSize() > $maxKilobytes * 1024) {
            throw ValidationException::withMessages(['file' => "File exceeds {$maxKilobytes}KB limit."]);
        }

        $detected = $this->detectMime($file);
        $extension = strtolower($file->getClientOriginalExtension());

        if (! isset($this->allowed[$detected])) {
            throw ValidationException::withMessages(['file' => "MIME type [{$detected}] is not allowed."]);
        }

        if (! in_array($extension, $this->allowed[$detected], true)) {
            throw ValidationException::withMessages(['file' => 'File extension does not match detected MIME type.']);
        }

        // Block double extensions / dangerous names.
        $name = $file->getClientOriginalName();
        if (preg_match('/\.(php|phtml|phar|exe|sh|bat|cmd)(\.|$)/i', $name)) {
            throw ValidationException::withMessages(['file' => 'Dangerous file name rejected.']);
        }

        return $detected;
    }

    public function detectMime(UploadedFile $file): string
    {
        $path = $file->getRealPath();
        if ($path && class_exists(\finfo::class)) {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($path);
            if (is_string($mime) && $mime !== '') {
                return $mime;
            }
        }

        // Fallback — still prefer server guess over client header.
        return (string) ($file->getMimeType() ?: 'application/octet-stream');
    }
}
