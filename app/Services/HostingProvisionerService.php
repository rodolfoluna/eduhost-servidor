<?php

namespace App\Services;

use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class HostingProvisionerService
{
    /**
     * Aprovisiona la carpeta física en el servidor y el usuario aislado en MariaDB para un alumno.
     */
    public function provisionStudentAccount(User $student): void
    {
        // 1. Asegurar que tenga credenciales de BD asignadas
        if (!$student->db_user || !$student->db_password_plain) {
            $cleanUser = preg_replace('/[^a-zA-Z0-9]/', '', $student->username);
            $student->db_user = 'alu_' . strtolower(substr($cleanUser, 0, 12));
            $student->db_password_plain = 'Db_' . Str::random(10);
            $student->save();
        }

        // 2. Crear su carpeta raíz en /var/www/students/{username}
        $studentRoot = $student->hostingRootPath();
        if (!File::exists($studentRoot)) {
            File::makeDirectory($studentRoot, 0755, true);
        }

        // 3. Crear usuario aislado en MariaDB que SOLO puede ver/modificar bases de datos con su prefijo (alu_2026001_%)
        $dbUser = preg_replace('/[^a-zA-Z0-9_]/', '', $student->db_user);
        $dbPass = addslashes($student->db_password_plain);
        $prefixWildcard = $student->dbPrefix() . '%';

        try {
            DB::unprepared("CREATE USER IF NOT EXISTS '{$dbUser}'@'localhost' IDENTIFIED BY '{$dbPass}';");
            DB::unprepared("ALTER USER '{$dbUser}'@'localhost' IDENTIFIED BY '{$dbPass}';");
            DB::unprepared("GRANT ALL PRIVILEGES ON `{$prefixWildcard}`.* TO '{$dbUser}'@'localhost';");
            DB::unprepared("FLUSH PRIVILEGES;");
        } catch (\Throwable $e) {
            // En caso de correr en SQLite o entorno local sin privilegios GRANT, se registra en log sin interrumpir
            logger()->warning("No se pudo crear usuario MariaDB para {$student->username}: " . $e->getMessage());
        }
    }

    /**
     * Inicializa la carpeta de un nuevo proyecto del alumno con una plantilla de bienvenida según el tipo.
     */
    public function initializeProjectDirectory(Project $project): void
    {
        $path = $project->absolutePath();

        if (!File::exists($path)) {
            File::makeDirectory($path, 0755, true);
        }

        $indexFile = $path . DIRECTORY_SEPARATOR . 'index.php';

        // Si el directorio está completamente vacío, crear un archivo index.php guía para el alumno
        if (count(File::allFiles($path)) === 0 && $project->project_type !== 'laravel') {
            $studentName = addslashes($project->user->name);
            $projectName = addslashes($project->name);
            $dbUser = $project->user->db_user;
            $dbPass = $project->user->db_password_plain;
            $dbName = $project->studentDatabase?->db_name ?? ($project->user->dbPrefix() . 'mibd');

            $template = <<<PHP
<?php
/**
 * Proyecto: {$projectName}
 * Alumno:   {$studentName} ({$project->user->username})
 * Servidor: EduHost - Hosting Académico
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{$projectName} - {$studentName}</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 800px; margin: 40px auto; padding: 20px; background: #f8fafc; color: #1e293b; }
        .card { background: white; border-radius: 12px; padding: 24px; box-shadow: 0 4px 12px rgba(0,0,0,0.06); border: 1px solid #e2e8f0; }
        code { background: #f1f5f9; padding: 2px 6px; border-radius: 4px; color: #0f172a; }
        pre { background: #0f172a; color: #f8fafc; padding: 16px; border-radius: 8px; overflow-x: auto; }
        .badge { display: inline-block; background: #dcfce7; color: #166534; padding: 4px 10px; border-radius: 999px; font-size: 13px; font-weight: 600; }
    </style>
</head>
<body>
    <div class="card">
        <span class="badge">Sitio Activo en EduHost (PHP <?= PHP_VERSION ?>)</span>
        <h1>¡Hola! Sitio de {$projectName}</h1>
        <p>Alumno: <strong>{$studentName}</strong> (Matrícula: <code>{$project->user->username}</code>)</p>
        <p>Fecha y hora del servidor: <strong><?= date('Y-m-d H:i:s') ?></strong></p>
        <hr style="border:0; border-top:1px solid #e2e8f0; margin: 20px 0;">
        <h3>Ejemplo de conexión PDO a tu MariaDB:</h3>
        <pre><code>&lt;?php
\$host = '127.0.0.1';
\$db   = '{$dbName}';
\$user = '{$dbUser}';
\$pass = '{$dbPass}';

\$pdo = new PDO("mysql:host=\$host;dbname=\$db;charset=utf8mb4", \$user, \$pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);
?&gt;</code></pre>
        <p>Puedes reemplazar este archivo subiendo tu <code>.ZIP</code> o editando <code>index.php</code> desde el Editor Web de EduHost.</p>
    </div>
</body>
</html>
PHP;
            File::put($indexFile, $template);
        }
    }

    /**
     * Calcula el espacio en disco utilizado por un alumno en Megabytes (MB).
     */
    public function getStudentDiskUsageMb(User $student): float
    {
        $root = $student->hostingRootPath();
        if (!File::exists($root)) {
            return 0.0;
        }

        $bytes = 0;
        foreach (File::allFiles($root, true) as $file) {
            // Ignorar enlaces simbólicos para no contar el mismo archivo dos veces
            if (!$file->isLink()) {
                $bytes += $file->getSize();
            }
        }

        return round($bytes / (1024 * 1024), 2);
    }
}

