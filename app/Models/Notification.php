<?php

/**
 * @author YIK HANG YIP
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'message',
        'notification_type',
        'related_type',
        'related_id',
        'is_read',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
            'read_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Resolve an actionable URL for this notification for the given viewer,
     * or null when the viewer is not authorised to see the related resource.
     * Recipient scoping is preserved: a link is only produced for resources
     * the viewer's role is allowed to access.
     */
    public function relatedRoute(User $viewer): ?string
    {
        if (!$this->related_type || !$this->related_id) {
            return null;
        }

        return match ($this->related_type) {
            Course::class => $this->courseRoute($viewer),
            Assessment::class => $this->assessmentRoute($viewer),
            User::class => $viewer->isAdmin() && User::find($this->related_id)
                ? route('admin.users.edit', $this->related_id)
                : null,
            default => null,
        };
    }

    protected function courseRoute(User $viewer): ?string
    {
        $course = Course::find($this->related_id);

        if (!$course) {
            return null;
        }

        return match (true) {
            $viewer->isAdmin() => route('admin.courses.edit', $course),
            $viewer->isMentor() && (int) $course->mentor_id === (int) $viewer->id
                => route('mentor.courses.edit', $course),
            $viewer->isStudent() => route('student.courses'),
            default => null,
        };
    }

    protected function assessmentRoute(User $viewer): ?string
    {
        $assessment = Assessment::find($this->related_id);

        if (!$assessment) {
            return null;
        }

        return match (true) {
            $viewer->isStudent() => route('student.assessments.result', $assessment),
            $viewer->isAdmin() || $viewer->isMentor() => route('assessments.edit', $assessment),
            default => null,
        };
    }
}