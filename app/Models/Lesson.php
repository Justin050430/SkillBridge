<?php

/**
 * @author ZHI FOOK CHUA
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lesson extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'title',
        'description',
        'content',
        'order_number',
        'estimated_duration',
        'status',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(Assessment::class);
    }

    public function progresses(): HasMany
    {
        return $this->hasMany(Progress::class);
    }

    /**
     * Learning materials attached to this lesson, in display order.
     */
    public function resources(): HasMany
    {
        return $this->hasMany(LessonResource::class)->orderBy('display_order');
    }

    /**
     * Whether the lesson may be seen by students.
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'Published');
    }
}