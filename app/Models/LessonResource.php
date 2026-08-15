<?php

/**
 * @author OOI JIAN XIN
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A learning material attached to a lesson.
 *
 * Types: File (document/archive), Image, Video (YouTube embed), Link
 * (external URL). Files are stored on the private local disk; url is used for
 * Link/Video resources.
 */
class LessonResource extends Model
{
    protected $fillable = [
        'lesson_id',
        'type',
        'title',
        'description',
        'url',
        'file_path',
        'file_name',
        'mime_type',
        'file_size',
        'display_order',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'display_order' => 'integer',
        ];
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function isFile(): bool
    {
        return $this->type === 'File';
    }

    public function isImage(): bool
    {
        return $this->type === 'Image';
    }

    public function isVideo(): bool
    {
        return $this->type === 'Video';
    }

    public function isLink(): bool
    {
        return $this->type === 'Link';
    }

    /**
     * The YouTube video id for Video resources, or null if the stored URL is
     * not a recognised YouTube URL. Used to build a sanitised embed src from
     * the id only - never from user-supplied HTML.
     */
    public function getYouTubeIdAttribute(): ?string
    {
        if (! $this->isVideo() || ! $this->url) {
            return null;
        }

        return self::parseYouTubeId($this->url);
    }

    /**
     * Parse a YouTube watch / short / youtu.be / embed URL into its video id.
     */
    public static function parseYouTubeId(string $url): ?string
    {
        $patterns = [
            '#(?:youtube\.com/watch\?.*v=|youtube\.com/embed/|youtube\.com/shorts/|youtu\.be/)([A-Za-z0-9_-]{6,20})#',
            '#^https?://(?:www\.)?youtube\.com/watch\?.*[?&]v=([A-Za-z0-9_-]{6,20})#',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }
}
