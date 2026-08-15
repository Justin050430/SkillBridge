<?php

/**
 * @author OOI JIAN XIN
 *
 * Shared validation rules for lesson resources.
 *
 * File uploads are validated by their actual MIME type (magic bytes via PHP
 * finfo - Laravel's "mimetypes" rule), NOT by trusting the file extension.
 * The filename stored in the database is only ever displayed escaped; the
 * physical stored name is generated server-side.
 */

namespace App\Support;

class LessonResourceRules
{
    /**
     * MIME types a mentor/admin may attach to a lesson.
     */
    public const ALLOWED_MIME_TYPES = [
        // Documents
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/zip',
        'text/plain',
        'text/markdown',
        'application/rtf',
        // Images
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        // Videos
        'video/mp4',
        'video/webm',
        'video/quicktime',
    ];

    /**
     * Maximum allowed upload size in kilobytes (25 MB).
     */
    public const MAX_FILE_KB = 25600;

    /**
     * Maximum number of resources a single lesson may hold.
     */
    public const MAX_RESOURCES = 25;

    public static function rules(): array
    {
        return [
            'resources' => 'nullable|array|max:'.self::MAX_RESOURCES,
            'resources.*.id' => 'nullable|integer|exists:lesson_resources,id',
            'resources.*.type' => 'required|in:File,Image,Video,Link',
            'resources.*.title' => 'required|string|max:150',
            'resources.*.description' => 'nullable|string|max:500',
            'resources.*.url' => 'nullable|url|max:2048',
            'resources.*.file' => 'nullable|file|mimetypes:'.implode(',', self::ALLOWED_MIME_TYPES).'|max:'.self::MAX_FILE_KB,
            'resources.*.display_order' => 'nullable|integer|min:0|max:9999',
        ];
    }
}
