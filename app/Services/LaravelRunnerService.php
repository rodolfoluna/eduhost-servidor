<?php

namespace App\Services;

use App\Models\Project;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use RuntimeException;

class LaravelRunnerService
{
    /**
     * Genera o actualiza automáticamente el archivo .env del proyecto Laravel del alumno
     * para que funcione sin errores en su subruta /~matricula/proyecto/ y conectado a su BD MariaDB.
     */
    public function configureDotEnv(Project $project): string
    {
        $projectPath = $project->absolutePath();
        $envPath = $projectPath . DIRECTORY_SEPARATOR . '.env';
        $envExamplePath = $projectPath . DIRECTORY_SEPARATOR . '.env.example';

        if (!File::exists($envPath)) {
            if (File::exists($envExamplePath)) {
                File::copy($envExamplePath, $envPath);
            } else {
                File::put($envPath, $this->defaultLaravelEnvStub());
            }
        }

        $envContent = File::get($envPath);

        $student = $project->user;
        $publicUrl = rtrim($project->publicUrl(), '/');
        $dbName = $project->studentDatabase?->db_name ?? ($student->dbPrefix() . 'laravel');
        $dbUser = $student->db_user ?? 'root';
        $dbPass = $student->db_password_plain ?? '';

        $replacements = [
            'APP_NAME' => '"' . addslashes($project->name) . '"',
            'APP_ENV' => 'local',
            'APP_DEBUG' => 'true',
            'APP_URL' => $publicUrl,
            'ASSET_URL' => $publicUrl,
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => '127.0.0.1',
            'DB_PORT' => '3306',
            'DB_DATABASE' => $dbName,
            'DB_USERNAME' => $dbUser,
            'DB_PASSWORD' => '"' . $dbPass . '"',
            'SESSION_DRIVER' => 'file',
            'CACHE_STORE' => 'file',
        ];

        foreach ($replacements as $key => $val) {
            if (preg_match("/^{$key}=.*/m", $envContent)) {
                $envContent = preg_replace("/^{$key}=.*/m", "{$key}={$val}", $envContent);
            } else {
                $envContent .= "\n{$key}={$val}";
            }
        }

        // Generar APP_KEY si está vacío
        if (!preg_match('/^APP_KEY=base64:.+/m', $envContent)) {
            $randomKey = 'base64:' . base64_encode(random_bytes(32));
            $envContent = preg_replace('/^APP_KEY=.*/m', "APP_KEY={$randomKey}", $envContent);
        }

        File::put($envPath, trim($envContent) . "\n");

        return "Archivo .env configurado exitosamente con APP_URL={$publicUrl} y BD={$dbName}.";
    }

    /**
     * Ejecuta uno de los comandos permitidos de la Consola 1-Clic de Laravel (lista blanca estricta).
     */
    public function runAllowedAction(Project $project, string $action): array
    {
        $projectPath = $project->absolutePath();

        if (!File::exists($projectPath)) {
            throw new RuntimeException('La carpeta del proyecto aún no existe.');
        }

        if ($action === 'env_autoconfig') {
            $msg = $this->configureDotEnv($project);
            return ['success' => true, 'command' => 'Autoconfigurar .env', 'output' => $msg];
        }

        // Lista blanca de comandos permitidos para proyectos de alumnos (100% seguro, sin inyección de shell)
        $commandsWhitelist = [
            'composer_install' => 'composer install --no-interaction --prefer-dist --no-progress',
            'key_generate'     => 'php artisan key:generate --force --no-interaction',
            'migrate'          => 'php artisan migrate --force --no-interaction',
            'migrate_fresh'    => 'php artisan migrate:fresh --seed --force --no-interaction',
            'storage_link'     => 'php artisan storage:link --no-interaction',
            'optimize_clear'   => 'php artisan optimize:clear --no-interaction',
            'route_list'       => 'php artisan route:list --no-ansi',
        ];

        if (!isset($commandsWhitelist[$action])) {
            throw new RuntimeException('Acción no permitida en la consola de proyectos.');
        }

        $command = $commandsWhitelist[$action];

        // Asegurar directorios requeridos por Laravel antes de correr comandos
        foreach (['storage/framework/sessions', 'storage/framework/views', 'storage/framework/cache', 'storage/logs', 'bootstrap/cache'] as $dir) {
            $fullDir = $projectPath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $dir);
            if (!File::exists($fullDir)) {
                File::makeDirectory($fullDir, 0775, true);
            }
        }

        $result = Process::path($projectPath)
            ->timeout(180)
            ->env([
                'COMPOSER_HOME' => config('eduhost.composer_cache_dir', '/var/www/composer-cache'),
                'COMPOSER_ALLOW_SUPERUSER' => '1',
            ])
            ->run($command);

        return [
            'success' => $result->successful(),
            'command' => $command,
            'output' => trim($result->output() . "\n" . $result->errorOutput()),
        ];
    }

    /**
     * Lee las últimas líneas de storage/logs/laravel.log del proyecto del alumno para que pueda depurar errores 500.
     */
    public function readProjectLogs(Project $project): string
    {
        $laravelLog = $project->absolutePath() . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'laravel.log';

        if (!File::exists($laravelLog)) {
            return "No se encontraron errores registrados aún en storage/logs/laravel.log.";
        }

        $content = File::get($laravelLog);
        if (strlen($content) > 15000) {
            return "... [Mostrando últimos 15 KB del log] ...\n" . substr($content, -15000);
        }

        return $content ?: 'El archivo de log está vacío.';
    }

    private function defaultLaravelEnvStub(): string
    {
        return <<<ENV
APP_NAME=Laravel
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost
ASSET_URL=http://localhost

LOG_CHANNEL=stack
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
ENV;
    }
}

