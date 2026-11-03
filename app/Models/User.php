<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'username',
        'role',
        'group_id',
        'password',
        'db_user',
        'db_password_plain',
        'is_active',
        'disk_quota_mb',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function isTeacher(): bool
    {
        return $this->role === 'teacher';
    }

    public function isStudent(): bool
    {
        return $this->role === 'student';
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function studentDatabases(): HasMany
    {
        return $this->hasMany(StudentDatabase::class);
    }

    /**
     * Ruta física en el disco del servidor donde se alojan todos los sitios del alumno.
     * Ej: /var/www/students/2026001
     */
    public function hostingRootPath(): string
    {
        $root = rtrim((string) config('eduhost.students_root_path'), '/\\');
        return $root . DIRECTORY_SEPARATOR . $this->username;
    }

    /**
     * Prefijo obligatorio para todas las bases de datos MariaDB del alumno.
     * Ej: alu_2026001_
     */
    public function dbPrefix(): string
    {
        $cleanUsername = preg_replace('/[^a-zA-Z0-9]/', '', $this->username);
        return 'alu_' . strtolower(substr($cleanUsername, 0, 12)) . '_';
    }
}

