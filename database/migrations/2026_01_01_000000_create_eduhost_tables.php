<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tabla de Grupos Escolares
        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Ej. "5A - Desarrollo Web Backend"
            $table->string('code')->unique(); // Ej. "5A-2026"
            $table->string('semester')->nullable(); // Ej. "2026-2"
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // 2. Tabla de Usuarios (Profesor y los 30 Alumnos)
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('username')->unique(); // Matrícula o identificador de subruta /~{username}/
            $table->enum('role', ['teacher', 'student'])->default('student');
            $table->foreignId('group_id')->nullable()->constrained('groups')->nullOnDelete();
            $table->string('password');

            // Credenciales de MariaDB auto-aprovisionadas para el alumno
            $table->string('db_user')->nullable();
            $table->string('db_password_plain')->nullable(); // Visible en su panel tipo cPanel para configurar su PDO / .env
            $table->boolean('is_active')->default(true);
            $table->integer('disk_quota_mb')->default(250);

            $table->rememberToken();
            $table->timestamps();
        });

        // 3. Tabla de Actividades / Prácticas asignadas por el Profesor
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            $table->string('title'); // Ej. "Práctica 1: CRUD de Inventario"
            $table->string('slug'); // Sugerencia de carpeta para el proyecto
            $table->enum('project_type', ['php_pure', 'php_mariadb', 'laravel'])->default('php_pure');
            $table->text('instructions');
            $table->dateTime('due_date')->nullable();
            $table->integer('max_score')->default(100);
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        // 4. Tabla de Bases de Datos MariaDB creadas por los Alumnos
        Schema::create('student_databases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('db_name')->unique(); // Ej. "alu_2026001_tienda"
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // 5. Tabla de Proyectos / Sitios Alojados (vinculados o libres por alumno)
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('activity_id')->nullable()->constrained('activities')->nullOnDelete();
            $table->foreignId('student_database_id')->nullable()->constrained('student_databases')->nullOnDelete();

            $table->string('name'); // Nombre visible del proyecto
            $table->string('folder_slug'); // Carpeta física en /var/www/students/{username}/{folder_slug}
            $table->enum('project_type', ['php_pure', 'php_mariadb', 'laravel'])->default('php_pure');
            $table->enum('status', ['draft', 'submitted', 'graded'])->default('draft');

            // Calificación y retroalimentación del profesor
            $table->decimal('score', 5, 2)->nullable();
            $table->text('teacher_feedback')->nullable();
            $table->text('student_notes')->nullable();
            $table->dateTime('submitted_at')->nullable();
            $table->dateTime('graded_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'folder_slug']);
        });

        // Tablas auxiliares de sesiones y caché de Laravel 11
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('projects');
        Schema::dropIfExists('student_databases');
        Schema::dropIfExists('activities');
        Schema::dropIfExists('users');
        Schema::dropIfExists('groups');
    }
};

