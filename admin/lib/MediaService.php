<?php
declare(strict_types=1);

namespace MotoBaku\Admin;

use RuntimeException;

class MediaService
{
    public const MAX_IMAGE_BYTES = 5 * 1024 * 1024; // 5 MB
    public const MAX_VIDEO_BYTES = 50 * 1024 * 1024; // 50 MB

    private const ALLOWED_IMAGE_MIME = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/svg+xml',
    ];

    private const ALLOWED_VIDEO_MIME = [
        'video/mp4',
        'video/webm',
        'video/ogg',
        'video/quicktime',
    ];

    private const MIME_EXTENSION_MAP = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
        'image/svg+xml' => 'svg',
        'video/mp4' => 'mp4',
        'video/webm' => 'webm',
        'video/ogg' => 'ogg',
        'video/quicktime' => 'mov',
    ];

    public static function uploadDirectory(): string
    {
        return __DIR__ . '/../storage/uploads';
    }

    public static function ensureWritable(): void
    {
        $dir = self::uploadDirectory();

        if (!is_dir($dir)) {
            throw new RuntimeException('Uploads directory does not exist.');
        }

        if (!is_writable($dir)) {
            throw new RuntimeException('Uploads directory is not writable.');
        }
    }

    public static function listMedia(): array
    {
        $dir = self::uploadDirectory();
        $files = [];

        if (!is_dir($dir)) {
            return $files;
        }

        $iterator = scandir($dir);
        if ($iterator === false) {
            return $files;
        }

        foreach ($iterator as $item) {
            if ($item === '.' || $item === '..' || str_starts_with($item, '.')) {
                continue;
            }

            $fullPath = $dir . '/' . $item;
            if (!is_file($fullPath)) {
                continue;
            }

            $size = filesize($fullPath);
            $modified = filemtime($fullPath);
            $url = base_url('storage/uploads/' . rawurlencode($item));

            $files[] = [
                'name' => $item,
                'size' => $size,
                'size_label' => self::formatSize($size),
                'modified' => $modified,
                'modified_label' => date('Y-m-d H:i', $modified),
                'url' => $url,
                'type' => self::detectTypeByExtension($item),
            ];
        }

        usort($files, static fn(array $a, array $b) => $b['modified'] <=> $a['modified']);

        return $files;
    }

    public static function storeUploadedFile(array $file): array
    {
        self::ensureWritable();

        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            throw new RuntimeException('No file uploaded.');
        }

        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            throw new RuntimeException(self::uploadErrorMessage((int)$file['error']));
        }

        $tmpPath = $file['tmp_name'] ?? '';
        if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
            throw new RuntimeException('Invalid upload.');
        }

        $size = (int)($file['size'] ?? 0);
        $originalName = (string)($file['name'] ?? 'file');

        $mime = self::detectMimeType($tmpPath);
        $type = self::classifyType($mime);

        if ($type === 'image' && $size > self::MAX_IMAGE_BYTES) {
            throw new RuntimeException('Image files must be smaller than 5MB.');
        }

        if ($type === 'video' && $size > self::MAX_VIDEO_BYTES) {
            throw new RuntimeException('Video files must be smaller than 50MB.');
        }

        $sanitizedName = sanitize_filename($originalName);
        $extension = strtolower((string)pathinfo($sanitizedName, PATHINFO_EXTENSION));

        if ($extension === '' && isset(self::MIME_EXTENSION_MAP[$mime])) {
            $extension = self::MIME_EXTENSION_MAP[$mime];
            $sanitizedName .= '.' . $extension;
        }

        $baseName = $extension !== ''
            ? substr($sanitizedName, 0, -(strlen($extension) + 1))
            : $sanitizedName;
        $baseName = $baseName !== '' ? $baseName : 'file';

        $destination = self::uniqueDestination($baseName, $extension);

        if (!move_uploaded_file($tmpPath, $destination['path'])) {
            throw new RuntimeException('Unable to move uploaded file.');
        }

        @chmod($destination['path'], 0644);

        $storedSize = filesize($destination['path']);
        $storedModified = filemtime($destination['path']);

        return [
            'name' => $destination['filename'],
            'url' => base_url('storage/uploads/' . rawurlencode($destination['filename'])),
            'type' => $type,
            'size' => $storedSize,
            'size_label' => self::formatSize($storedSize),
            'modified' => $storedModified,
            'modified_label' => date('Y-m-d H:i', $storedModified),
        ];
    }

    private static function uniqueDestination(string $baseName, string $extension): array
    {
        $dir = self::uploadDirectory();
        $counter = 0;

        do {
            $suffix = $counter > 0 ? '-' . $counter : '';
            $filename = $extension !== ''
                ? $baseName . $suffix . '.' . $extension
                : $baseName . $suffix;
            $path = $dir . '/' . $filename;
            $counter++;
        } while (file_exists($path));

        return [
            'filename' => $filename,
            'path' => $path,
        ];
    }

    private static function detectMimeType(string $path): string
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo ? finfo_file($finfo, $path) : false;
        if ($finfo) {
            finfo_close($finfo);
        }

        if ($mime === false || $mime === null) {
            throw new RuntimeException('Unable to determine file type.');
        }

        return $mime;
    }

    private static function classifyType(string $mime): string
    {
        if (in_array($mime, self::ALLOWED_IMAGE_MIME, true)) {
            return 'image';
        }
        if (in_array($mime, self::ALLOWED_VIDEO_MIME, true)) {
            return 'video';
        }

        if (str_starts_with($mime, 'image/')) {
            throw new RuntimeException('Image type not allowed. Allowed: JPEG, PNG, GIF, WEBP, SVG.');
        }
        if (str_starts_with($mime, 'video/')) {
            throw new RuntimeException('Video type not allowed. Allowed: MP4, WebM, OGG, MOV.');
        }

        throw new RuntimeException('Only image or video uploads are supported.');
    }

    private static function detectTypeByExtension(string $filename): string
    {
        $extension = strtolower((string)pathinfo($filename, PATHINFO_EXTENSION));

        if (preg_match('/\.(jpe?g|png|gif|webp|svg)$/i', $filename)) {
            return 'image';
        }

        if (preg_match('/\.(mp4|webm|ogg|mov)$/i', $filename)) {
            return 'video';
        }

        return 'file';
    }

    private static function uploadErrorMessage(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive.',
            UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive.',
            UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload.',
            default => 'Upload failed.',
        };
    }

    private static function formatSize(int $bytes): string
    {
        if ($bytes >= 1024 * 1024) {
            return number_format($bytes / (1024 * 1024), 2) . ' MB';
        }

        return number_format($bytes / 1024, 2) . ' KB';
    }
}
