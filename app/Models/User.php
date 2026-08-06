<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'role_id',
        'first_name',
        'last_name',
        'email',
        'password',
        'profile_picture',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class, 'mentor_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class, 'student_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }
    
    public function isAdmin(): bool {
        return $this->role->role_name === 'Admin';
    }

    public function isMentor(): bool {
        return $this->role->role_name === 'Mentor';
    }

    public function isStudent(): bool {
        return $this->role->role_name === 'Student';
    }
    
    public function scopeStudents($query) {
        return $query->whereHas('role', function ($q) {
                    $q->where('role_name', 'Student');
                });
    }

    public function scopeMentors($query) {
        return $query->whereHas('role', function ($q) {
                    $q->where('role_name', 'Mentor');
                });
    }

    public function scopeAdmins($query) {
        return $query->whereHas('role', function ($q) {
                    $q->where('role_name', 'Admin');
                });
    }
    
    public function scopeRole($query, string $role) {
        return $query->whereHas('role', function ($q) use ($role) {
                    $q->where('role_name', $role);
                });
    }
}