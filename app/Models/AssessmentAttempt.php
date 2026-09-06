<?php

/**
 * @author OOI JIAN XIN
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssessmentAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'enrollment_id',
        'assessment_id',
        'attempt_number',
        'score',
        'percentage',
        'is_passed',
        'started_at',
        'submitted_at',
        'expires_at',
        'expired_at',
    ];

    protected function casts(): array
    {
        return [
            'is_passed' => 'boolean',
            'started_at' => 'datetime',
            'submitted_at' => 'datetime',
            'expires_at' => 'datetime',
            'expired_at' => 'datetime',
        ];
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function hasExpired(): bool
    {
        return $this->submitted_at === null && ($this->expired_at !== null
            || ($this->expires_at !== null && now()->greaterThanOrEqualTo($this->expires_at)));
    }

    public function isActive(): bool
    {
        return $this->submitted_at === null && ! $this->hasExpired();
    }

    public function scopeEnded($query)
    {
        return $query->where(function ($query) {
            $query->whereNotNull('submitted_at')
                ->orWhereNotNull('expired_at')
                ->orWhere('expires_at', '<=', now());
        });
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }

    public function studentAnswers(): HasMany
    {
        return $this->hasMany(StudentAnswer::class, 'attempt_id');
    }
}
