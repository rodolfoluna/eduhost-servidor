<?php

namespace App\Services;

use App\Models\Project;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use RuntimeException;
use ZipArchive;

class FileManagerService
{
    /**
     * Resuelve y valida de forma segura que una ruta relativa pertenezca estrictamente
     * a la carpeta del proyecto del alumno (previene ataques Path Traversal ../).
     */
    public function resolveSafePath(Project $project, string $relativePath = ''): string
    {
        $basePath = $project->absolutePath();
        if (!File::exists($basePath)) {
            File::makeDirectory($basePath, 0755, true);
        }

        $realBase = realpath($basePath);
        if ($realBase === false) {
            throw new RuntimeException('No se pudo localizar el directorio base del proyecto.');
        }

        // Normalizar separadores y remover intentos de subir de directorio
        $cleanRelative = str_replace(['..', '\\'], ['', '/'], trim($relativePath, '/\\'));
        if ($cleanRelative === '') {
            return $realBase;
        }

        $targetPath = $realBase . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $cleanRelative);

        // Si el archivo ya existe, validar con realpath
        if (file_exists($targetPath)) {
            $realTarget = realpath($targetPath);
            if ($realTarget === false || !str_starts_with($realTarget, $realBase)) {
                throw new RuntimeException('Acceso denegado: Ruta fuera del espacio del proyecto.');
            }
            return $realTarget;
        }

        return $targetPath;
    }

    /**
     * Devuelve el árbol de directorios y archivos del proyecto para el Explorador de Código.
     * Omite el interior de 'vendor/', 'node_modules/' y '.git/' para mantener la interfaz rápida.
     */
    public function getProjectTree(Project $project, string $subDir = ''): array
    {
        $dirPath = $this->resolveSafePath($project, $subDir);
        if (!is_dir($dirPath)) {
            return [];
        }

        $items = scandir($dirPath);
        if ($items === false) {
            return [];
        }

        $directories = [];
        $files = [];
        $ignoredDirs = ['.git', 'node_modules', 'vendor'];

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $relPath = ltrim($subDir . '/' . $item, '/');
            $fullPath = $dirPath . DIRECTORY_SEPARATOR . $item;

            if (is_dir($fullPath)) {
                $isSkippedVendor = in_array($item, $ignoredDirs, true);
                $directories[] = [
                    'name' => $item,
                    'path' => $relPath,
                    'type' => 'dir',
                    'is_vendor' => $isSkippedVendor,
                    'children' => $isSkippedVendor ? [] : $this->getProjectTree($project, $relPath),
                ];
            } else {
                $files[] = [
                    'name' => $item,
                    'path' => $relPath,
                    'type' => 'file',
                    'size_kb' => round(filesize($fullPath) / 1024, 1),
                    'extension' => strtolower(pathinfo($item, PATHINFO_EXTENSION)),
                    'language' => $this->detectMonacoLanguage($item),
                ];
            }
        }

        return array_merge($directories, $files);
    }

    /**
     * Extrae un archivo .ZIP subido por el alumno dentro de la carpeta de su proyecto.
     * - Si el .ZIP tiene una única carpeta raíz contenedora, aplana su contenido automáticamente.
     * - Ignora carpetas pesadas como 'node_modules/' o '.git/' y archivos peligrosos.
     */
    public function extractUploadedZip(Project $project, UploadedFile $zipFile, bool $clearExisting = false): array
    {
        $projectPath = $this->resolveSafePath($project);

        if ($clearExisting && File::exists($projectPath)) {
            File::cleanDirectory($projectPath);
        }

        $zip = new ZipArchive();
        if ($zip->open($zipFile->getRealPath()) !== true) {
            throw new RuntimeException('No se pudo abrir el archivo .ZIP. Verifica que sea un archivo comprimido válido.');
        }

        // Detectar si todos los archivos vienen dentro de una sola subcarpeta raíz (ej. mi-proyecto/index.php)
        $commonPrefix = $this->detectSingleRootFolderPrefix($zip);
        $extractedCount = 0;
        $skippedVendor = false;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entryName = str_replace('\\', '/', $zip->getNameIndex($i));

            // Ignorar metadatos de macOS o rutas inseguras
            if (str_contains($entryName, '__MACOSX') || str_contains($entryName, '..')) {
                continue;
            }

            if ($commonPrefix !== '' && str_starts_with($entryName, $commonPrefix)) {
                $entryName = substr($entryName, strlen($commonPrefix));
            }

            $entryName = ltrim($entryName, '/');
            if ($entryName === '') {
                continue;
            }

            // En proyectos Laravel, omitir 'vendor/' y 'node_modules/' del ZIP para ahorrar gigas en el servidor
            // (el alumno luego presiona el botón 1-Clic 'Composer Install' que usa la caché local del servidor)
            if (str_starts_with($entryName, 'vendor/') || str_starts_with($entryName, 'node_modules/') || str_starts_with($entryName, '.git/')) {
                $skippedVendor = true;
                continue;
            }

            $destPath = $this->resolveSafePath($project, $entryName);

            if (str_ends_with($entryName, '/')) {
                if (!File::exists($destPath)) {
                    File::makeDirectory($destPath, 0755, true);
                }
                continue;
            }

            $parentDir = dirname($destPath);
            if (!File::exists($parentDir)) {
                File::makeDirectory($parentDir, 0755, true);
            }

            $stream = $zip->getStream($zip->getNameIndex($i));
            if ($stream !== false) {
                file_put_contents($destPath, stream_get_contents($stream));
                fclose($stream);
                $extractedCount++;
            }
        }

        $zip->close();

        // Si el proyecto tiene un archivo 'artisan' en la raíz, marcarlo automáticamente como Laravel
        if (File::exists($projectPath . DIRECTORY_SEPARATOR . 'artisan') && $project->project_type !== 'laravel') {
            $project->update(['project_type' => 'laravel']);
        }

        return [
            'extracted_count' => $extractedCount,
            'skipped_vendor' => $skippedVendor,
        ];
    }

    /**
     * Lee el contenido de un archivo del proyecto para mostrarlo en Monaco Editor.
     */
    public function readFile(Project $project, string $relativePath): array
    {
        $fullPath = $this->resolveSafePath($project, $relativePath);

        if (!is_file($fullPath)) {
            throw new RuntimeException('El archivo solicitado no existe.');
        }

        if (filesize($fullPath) > 2 * 1024 * 1024) {
            throw new RuntimeException('El archivo supera los 2 MB y no puede abrirse en el editor de texto.');
        }

        return [
            'path' => $relativePath,
            'name' => basename($fullPath),
            'language' => $this->detectMonacoLanguage($relativePath),
            'content' => File::get($fullPath),
            'size_kb' => round(filesize($fullPath) / 1024, 2),
        ];
    }

    /**
     * Guarda cambios en un archivo existente o crea uno nuevo desde el Editor Web (Alumno o Profesor).
     */
    public function writeFile(Project $project, string $relativePath, string $content): void
    {
        $fullPath = $this->resolveSafePath($project, $relativePath);
        $parentDir = dirname($fullPath);

        if (!File::exists($parentDir)) {
            File::makeDirectory($parentDir, 0755, true);
        }

        File::put($fullPath, $content);
    }

    /**
     * Elimina un archivo o carpeta dentro del proyecto del alumno.
     */
    public function deleteItem(Project $project, string $relativePath): void
    {
        if (trim($relativePath, '/\\') === '') {
            throw new RuntimeException('No se puede eliminar la carpeta raíz del proyecto.');
        }

        $fullPath = $this->resolveSafePath($project, $relativePath);
        if (is_dir($fullPath)) {
            File::deleteDirectory($fullPath);
        } elseif (is_file($fullPath)) {
            File::delete($fullPath);
        }
    }

    private function detectSingleRootFolderPrefix(ZipArchive $zip): string
    {
        $topFolders = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = str_replace('\\', '/', $zip->getNameIndex($i));
            if (str_starts_with($name, '__MACOSX')) {
                continue;
            }
            $parts = explode('/', trim($name, '/'));
            if (count($parts) === 1 && !str_ends_with($name, '/')) {
                // Hay un archivo suelto en la raíz del ZIP, no hay carpeta contenedora única
                return '';
            }
            $topFolders[$parts[0]] = true;
        }

        if (count($topFolders) === 1) {
            $folder = array_key_first($topFolders);
            // No aplanar si la única carpeta se llama 'public', 'app', 'config', etc.
            if (!in_array($folder, ['app', 'public', 'resources', 'routes', 'config', 'database', 'css', 'js', 'img'], true)) {
                return $folder . '/';
            }
        }

        return '';
    }

    public function detectMonacoLanguage(string $filename): string
    {
        $lower = strtolower($filename);
        if (str_ends_with($lower, '.blade.php') || str_ends_with($lower, '.php')) {
            return 'php';
        }
        $ext = pathinfo($lower, PATHINFO_EXTENSION);
        return match ($ext) {
            'js', 'mjs' => 'javascript',
            'ts' => 'typescript',
            'css' => 'css',
            'html', 'htm' => 'html',
            'json' => 'json',
            'sql' => 'sql',
            'md' => 'markdown',
            'xml' => 'xml',
            'yml', 'yaml' => 'yaml',
            default => 'ini',
        };
    }
}

