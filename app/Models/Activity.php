<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Activity extends Model
{
    protected $fillable = [
        'group_id',
        'title',
        'slug',
        'project_type',
        'instructions',
        'due_date',
        'max_score',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'datetime',
            'is_published' => 'boolean',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function typeLabel(): string
    {
        return match ($this->project_type) {
            'php_pure' => 'PHP Puro',
            'php_mariadb' => 'PHP + MariaDB',
            'laravel' => 'Framework Laravel',
            default => 'PHP',
        };
    }

    public function typeBadgeColor(): string
    {
        return match ($this->project_type) {
            'php_pure' => 'bg-blue-100 text-blue-800 border-blue-200',
            'php_mariadb' => 'bg-amber-100 text-amber-800 border-amber-200',
            'laravel' => 'bg-rose-100 text-rose-800 border-rose-200',
            default => 'bg-gray-100 text-gray-800',
        };
    }
}

