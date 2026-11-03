<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Project extends Model
{
    protected $fillable = [
        'user_id',
        'activity_id',
        'student_database_id',
        'name',
        'folder_slug',
        'project_type',
        'status',
        'score',
        'teacher_feedback',
        'student_notes',
        'submitted_at',
        'graded_at',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'graded_at' => 'datetime',
            'score' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function studentDatabase(): BelongsTo
    {
        return $this->belongsTo(StudentDatabase::class);
    }

    /**
     * Ruta física completa del proyecto en el servidor.
     * Ej: /var/www/students/2026001/practica-crud
     */
    public function absolutePath(): string
    {
        return $this->user->hostingRootPath() . DIRECTORY_SEPARATOR . $this->folder_slug;
    }

    /**
     * URL pública en vivo donde se ejecuta el sitio del alumno (vía Nginx y Tailscale Funnel).
     * Ej: https://eduhost-server.tail12345.ts.net/~2026001/practica-crud/
     */
    public function publicUrl(): string
    {
        return url('/~' . $this->user->username . '/' . $this->folder_slug) . '/';
    }

    public function typeLabel(): string
    {
        return match ($this->project_type) {
            'php_pure' => 'PHP Puro',
            'php_mariadb' => 'PHP + MariaDB',
            'laravel' => 'Laravel',
            default => 'PHP',
        };
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'draft' => 'En Desarrollo',
            'submitted' => 'Entregado (Por Revisar)',
            'graded' => 'Calificado',
            default => $this->status,
        };
    }
}

