<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

class ValidAttachmentRule implements ValidationRule
{
    /**
     * Whitelist of allowed file extensions per specification.
     */
    protected array $allowedExtensions = [
        // Images
        'jpg', 'jpeg', 'png', 'gif', 'svg', 'webp',
        // Documents
        'pdf', 'doc', 'docx',
        // Spreadsheets
        'xls', 'xlsx', 'csv',
        // Presentations
        'ppt', 'pptx',
        // Archives
        'zip',
        // CAD & GIS files
        'dwg', 'dxf', 'kml',
    ];

    /**
     * Dangerous extensions that are strictly rejected.
     */
    protected array $blockedExtensions = [
        'exe', 'bat', 'cmd', 'sh', 'php', 'phtml', 'phar', 'js', 'vbs', 'scr', 'bin', 'dll', 'msi', 'com', 'jar',
    ];

    /**
     * Map of valid extensions to acceptable MIME types.
     */
    protected array $allowedMimeTypes = [
        'jpg' => ['image/jpeg', 'image/pjpeg'],
        'jpeg' => ['image/jpeg', 'image/pjpeg'],
        'png' => ['image/png', 'image/x-png'],
        'gif' => ['image/gif'],
        'svg' => ['image/svg+xml', 'text/xml', 'text/plain'],
        'webp' => ['image/webp'],
        'pdf' => ['application/pdf', 'application/x-pdf', 'application/acrobat'],
        'doc' => ['application/msword', 'application/vnd.ms-office'],
        'docx' => [
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/zip',
            'application/x-zip-compressed',
        ],
        'xls' => ['application/vnd.ms-excel', 'application/msexcel'],
        'xlsx' => [
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/zip',
            'application/x-zip-compressed',
        ],
        'csv' => ['text/csv', 'text/plain', 'application/csv', 'text/comma-separated-values'],
        'ppt' => ['application/vnd.ms-powerpoint'],
        'pptx' => [
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'application/zip',
            'application/x-zip-compressed',
        ],
        'zip' => ['application/zip', 'application/x-zip', 'application/x-zip-compressed', 'multipart/x-zip'],
        'dwg' => [
            'application/acad',
            'image/vnd.dwg',
            'image/x-dwg',
            'application/octet-stream',
            'application/x-autocad',
            'application/x-dwg',
        ],
        'dxf' => [
            'image/vnd.dxf',
            'image/x-dxf',
            'application/dxf',
            'text/plain',
            'application/octet-stream',
        ],
        'kml' => [
            'application/vnd.google-earth.kml+xml',
            'application/xml',
            'text/xml',
            'text/plain',
            'application/octet-stream',
        ],
    ];

    /**
     * Strictly blocked executable MIME types.
     */
    protected array $blockedMimes = [
        'application/x-dosexec',
        'application/x-msdownload',
        'application/x-executable',
        'application/x-sharedlib',
        'application/x-sh',
        'application/x-bat',
        'application/x-msdos-program',
    ];

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile) {
            $fail('The attachment must be a valid uploaded file.');
            return;
        }

        if (! $value->isValid()) {
            $fail("File upload error: {$value->getErrorMessage()}");
            return;
        }

        // Max 100 MB per file (100 * 1024 * 1024 bytes = 104,857,600 bytes)
        $maxBytes = 100 * 1024 * 1024;
        if ($value->getSize() > $maxBytes) {
            $fail("File '{$value->getClientOriginalName()}' exceeds the maximum allowed limit of 100 MB.");
            return;
        }

        $clientExt = strtolower($value->getClientOriginalExtension());

        // 1. Strictly block executable/script extensions
        if (in_array($clientExt, $this->blockedExtensions, true)) {
            $fail("Executable and script files (.{$clientExt}) are strictly prohibited.");
            return;
        }

        // 2. Extension must be in whitelist
        if (! in_array($clientExt, $this->allowedExtensions, true)) {
            $fail("File '{$value->getClientOriginalName()}' has an unsupported extension (.{$clientExt}). Allowed types: images, PDF, DOC/DOCX, XLS/XLSX, PPT/PPTX, ZIP, DWG, DXF, KML.");
            return;
        }

        // 3. Check MIME type
        $mime = strtolower($value->getMimeType() ?? $value->getClientMimeType() ?? '');

        // Strictly block executable MIME types
        if (in_array($mime, $this->blockedMimes, true)) {
            $fail("File '{$value->getClientOriginalName()}' is detected as an executable ({$mime}) and has been rejected.");
            return;
        }

        // Verify MIME type matches extension
        $validMimesForExt = $this->allowedMimeTypes[$clientExt] ?? [];
        if (! empty($validMimesForExt) && ! in_array($mime, $validMimesForExt, true)) {
            $fail("File '{$value->getClientOriginalName()}' MIME type ({$mime}) does not match its extension (.{$clientExt}).");
            return;
        }
    }
}
