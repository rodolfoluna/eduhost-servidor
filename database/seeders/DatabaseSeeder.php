<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\Group;
use App\Models\User;
use App\Services\HostingProvisionerService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Crear cuenta del Profesor (Administrador del Servidor y LMS)
        User::updateOrCreate(
            ['email' => 'profesor@instituto.edu'],
            [
                'name' => 'Profesor Titular',
                'username' => 'profesor',
                'role' => 'teacher',
                'password' => Hash::make('password123'),
                'is_active' => true,
                'disk_quota_mb' => 2048,
            ]
        );

        // 2. Crear Grupos de Ejemplo
        $groupWeb = Group::updateOrCreate(
            ['code' => 'WEB-2026A'],
            [
                'name' => 'Desarrollo Web Backend (PHP & MariaDB)',
                'semester' => '2026-1',
                'description' => 'Grupo de 30 alumnos - Programación con PHP Puro, PDO y MariaDB.',
            ]
        );

        $groupLaravel = Group::updateOrCreate(
            ['code' => 'LARAVEL-2026A'],
            [
                'name' => 'Frameworks Web Avanzados (Laravel 11)',
                'semester' => '2026-1',
                'description' => 'Desarrollo de aplicaciones MVC empresariales con Laravel y MariaDB.',
            ]
        );

        // 3. Crear las 3 Actividades Modelo (PHP Puro, PHP + MariaDB y Laravel)
        Activity::updateOrCreate(
            ['slug' => 'practica-1-php-puro', 'group_id' => $groupWeb->id],
            [
                'title' => 'Práctica 1: Calculadora Financiera y Procesamiento de Formularios en PHP Puro',
                'project_type' => 'php_pure',
                'instructions' => "1. Crea un sitio en PHP puro con un archivo principal index.php.\n2. Debe recibir datos por POST, validarlos y mostrar una tabla de amortización.\n3. Sube tu archivo .ZIP o edita tu código directamente en el editor web.",
                'due_date' => now()->addDays(7),
                'max_score' => 100,
                'is_published' => true,
            ]
        );

        Activity::updateOrCreate(
            ['slug' => 'practica-2-crud-mariadb', 'group_id' => $groupWeb->id],
            [
                'title' => 'Práctica 2: Sistema CRUD de Inventario con PHP y MariaDB (PDO)',
                'project_type' => 'php_mariadb',
                'instructions' => "1. Ve a la sección 'Mis Bases de Datos' y crea una base de datos llamada 'inventario'.\n2. Crea la tabla 'productos' (id, nombre, precio, stock) desde Adminer o importando tu archivo .sql.\n3. Programa el CRUD completo usando PDO con las credenciales que te da tu panel.\n4. Vincula tu base de datos al entregar el proyecto para que el profesor pueda revisarla.",
                'due_date' => now()->addDays(14),
                'max_score' => 100,
                'is_published' => true,
            ]
        );

        Activity::updateOrCreate(
            ['slug' => 'proyecto-final-laravel', 'group_id' => $groupLaravel->id],
            [
                'title' => 'Proyecto Integrador: Aplicación Web con Laravel 11, Migraciones y Eloquent',
                'project_type' => 'laravel',
                'instructions' => "1. Comprime tu proyecto de Laravel en un archivo .ZIP (no es necesario incluir la carpeta vendor/).\n2. Crea una base de datos en tu panel de 'Mis Bases de Datos'.\n3. Sube tu .ZIP y entra a la 'Consola Laravel 1-Clic' de tu proyecto.\n4. Haz clic en 'Autoconfigurar .env', luego en 'Composer Install' y finalmente en 'Artisan Migrate'.\n5. Verifica tu sitio en vivo antes de marcar como entregado.",
                'due_date' => now()->addDays(21),
                'max_score' => 100,
                'is_published' => true,
            ]
        );

        // 4. Crear un Alumno de Demostración y aprovisionar su espacio y cuenta MariaDB
        $demoStudent = User::updateOrCreate(
            ['email' => 'alumno01@instituto.edu'],
            [
                'name' => 'Juan Pérez (Alumno Demo)',
                'username' => '2026001',
                'role' => 'student',
                'group_id' => $groupWeb->id,
                'password' => Hash::make('alumno123'),
                'db_user' => 'alu_2026001',
                'db_password_plain' => 'DbPass_2026001',
                'is_active' => true,
                'disk_quota_mb' => 250,
            ]
        );

        try {
            app(HostingProvisionerService::class)->provisionStudentAccount($demoStudent);
        } catch (\Throwable $e) {
            // Si se corre en un entorno sin permisos GRANT de MySQL, continúa sin detener el seeder
        }
    }
}

