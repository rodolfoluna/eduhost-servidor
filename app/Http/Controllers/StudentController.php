<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Project;
use App\Models\StudentDatabase;
use App\Services\DatabaseManagerService;
use App\Services\FileManagerService;
use App\Services\HostingProvisionerService;
use App\Services\LaravelRunnerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class StudentController extends Controller
{
    public function __construct(
        protected HostingProvisionerService $provisioner,
        protected FileManagerService $fileManager,
        protected DatabaseManagerService $dbManager,
        protected LaravelRunnerService $laravelRunner
    ) {}

    /**
     * Panel Principal del Alumno: Actividades asignadas, Mis Sitios Alojados y Mis Bases de Datos MariaDB.
     */
    public function dashboard()
    {
        $student = Auth::user()->load(['group', 'projects.activity', 'projects.studentDatabase', 'studentDatabases']);

        // Asegurar que su carpeta y usuario MariaDB estén inicializados
        $this->provisioner->provisionStudentAccount($student);

        $activities = $student->group_id
            ? Activity::where('group_id', $student->group_id)->where('is_published', true)->orderBy('due_date')->get()
            : collect();

        $diskUsedMb = $this->provisioner->getStudentDiskUsageMb($student);

        return view('student.dashboard', compact('student', 'activities', 'diskUsedMb'));
    }

    /**
     * Crear un nuevo Proyecto / Sitio Web en su espacio (/~matricula/nombre-proyecto/)
     */
    public function storeProject(Request $request)
    {
        $student = Auth::user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'folder_slug' => ['required', 'alpha_dash', 'max:50'],
            'project_type' => ['required', 'in:php_pure,php_mariadb,laravel'],
            'activity_id' => ['nullable', 'exists:activities,id'],
            'student_database_id' => ['nullable', 'exists:student_databases,id'],
        ]);

        $slug = strtolower(Str::slug($data['folder_slug']));

        if ($student->projects()->where('folder_slug', $slug)->exists()) {
            return back()->withErrors(['folder_slug' => "Ya tienes un proyecto con la carpeta '{$slug}'."]);
        }

        $project = $student->projects()->create([
            'name' => $data['name'],
            'folder_slug' => $slug,
            'project_type' => $data['project_type'],
            'activity_id' => $data['activity_id'] ?? null,
            'student_database_id' => $data['student_database_id'] ?? null,
            'status' => 'draft',
        ]);

        $this->provisioner->initializeProjectDirectory($project);

        return redirect()->route('student.projects.workspace', $project)
            ->with('success', "¡Proyecto creado! Tu sitio ya está activo en {$project->publicUrl()}");
    }

    /**
     * Workspace del Proyecto (Mini-cPanel del Alumno):
     * - Subida de .ZIP
     * - Gestor de Archivos + Editor de Código en Vivo (Monaco Editor)
     * - Consola 1-Clic para Laravel (Composer, Artisan, .env, Logs)
     * - Entrega de Práctica al Profesor
     */
    public function projectWorkspace(Project $project)
    {
        $this->authorizeProjectAccess($project);

        $project->load(['activity', 'studentDatabase', 'user.studentDatabases']);
        $this->provisioner->initializeProjectDirectory($project);

        $fileTree = $this->fileManager->getProjectTree($project);
        $laravelLogs = $project->project_type === 'laravel'
            ? $this->laravelRunner->readProjectLogs($project)
            : null;

        return view('student.workspace', compact('project', 'fileTree', 'laravelLogs'));
    }

    /**
     * Subir y descomprimir archivo .ZIP en el proyecto
     */
    public function uploadZip(Request $request, Project $project)
    {
        $this->authorizeProjectAccess($project);

        $request->validate([
            'zip_file' => ['required', 'file', 'mimes:zip', 'max:65536'], // Hasta 64 MB
            'clear_existing' => ['nullable', 'boolean'],
        ]);

        try {
            $result = $this->fileManager->extractUploadedZip(
                $project,
                $request->file('zip_file'),
                $request->boolean('clear_existing')
            );

            $msg = "Se extrajeron {$result['extracted_count']} archivos exitosamente.";
            if ($result['skipped_vendor']) {
                $msg .= " (Se omitió la carpeta vendor/ pesada; si es Laravel, usa el botón 'Composer Install' abajo).";
            }

            return back()->with('success', $msg);
        } catch (\Throwable $e) {
            return back()->withErrors(['zip_file' => $e->getMessage()]);
        }
    }

    /**
     * Subir un archivo individual (ej. index.php, estilo.css, imagen.png) a una carpeta del proyecto
     */
    public function uploadSingleFile(Request $request, Project $project)
    {
        $this->authorizeProjectAccess($project);

        $data = $request->validate([
            'target_dir' => ['nullable', 'string', 'max:200'],
            'single_file' => ['required', 'file', 'max:16384'],
        ]);

        try {
            $file = $request->file('single_file');
            $relPath = ltrim(trim((string) ($data['target_dir'] ?? ''), '/') . '/' . $file->getClientOriginalName(), '/');
            $destPath = $this->fileManager->resolveSafePath($project, $relPath);

            if (!is_dir(dirname($destPath))) {
                mkdir(dirname($destPath), 0755, true);
            }

            $file->move(dirname($destPath), basename($destPath));

            return back()->with('success', "Archivo '{$relPath}' subido correctamente.");
        } catch (\Throwable $e) {
            return back()->withErrors(['single_file' => $e->getMessage()]);
        }
    }

    /**
     * Vincular base de datos o marcar el proyecto como Entregado para revisión del Profesor
     */
    public function submitProject(Request $request, Project $project)
    {
        $this->authorizeProjectAccess($project);

        $data = $request->validate([
            'student_database_id' => ['nullable', 'exists:student_databases,id'],
            'student_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $project->update([
            'student_database_id' => $data['student_database_id'] ?? $project->student_database_id,
            'student_notes' => $data['student_notes'] ?? $project->student_notes,
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        return back()->with('success', '¡Tu práctica ha sido marcada como ENTREGADA! El profesor ya puede calificarla.');
    }

    /**
     * Ejecutar comando de la Consola 1-Clic para proyectos Laravel
     */
    public function runLaravelCommand(Request $request, Project $project)
    {
        $this->authorizeProjectAccess($project);

        $data = $request->validate([
            'action' => ['required', 'string'],
        ]);

        try {
            $result = $this->laravelRunner->runAllowedAction($project, $data['action']);
            return back()
                ->with('console_output', $result['output'])
                ->with('console_command', $result['command'])
                ->with($result['success'] ? 'success' : 'error', $result['success'] ? 'Comando ejecutado correctamente.' : 'El comando reportó advertencias o errores.');
        } catch (\Throwable $e) {
            return back()->withErrors(['action' => $e->getMessage()]);
        }
    }

    // =========================================================================
    // API JSON PARA EL EDITOR DE CÓDIGO WEB (MONACO EDITOR: ALUMNO Y PROFESOR)
    // =========================================================================
    public function apiReadFile(Request $request, Project $project)
    {
        $this->authorizeProjectAccess($project);
        $path = (string) $request->query('path', 'index.php');

        try {
            return response()->json($this->fileManager->readFile($project, $path));
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function apiSaveFile(Request $request, Project $project)
    {
        $this->authorizeProjectAccess($project);

        $data = $request->validate([
            'path' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
        ]);

        try {
            $this->fileManager->writeFile($project, $data['path'], (string) ($data['content'] ?? ''));
            return response()->json(['message' => "Archivo '{$data['path']}' guardado correctamente."]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function apiDeleteFile(Request $request, Project $project)
    {
        $this->authorizeProjectAccess($project);

        $data = $request->validate([
            'path' => ['required', 'string', 'max:255'],
        ]);

        try {
            $this->fileManager->deleteItem($project, $data['path']);
            return back()->with('success', "Elemento '{$data['path']}' eliminado.");
        } catch (\Throwable $e) {
            return back()->withErrors(['path' => $e->getMessage()]);
        }
    }

    // =========================================================================
    // GESTIÓN DE BASES DE DATOS MARIADB DEL ALUMNO
    // =========================================================================
    public function databasesIndex(Request $request)
    {
        $student = Auth::user()->load('studentDatabases');
        $this->provisioner->provisionStudentAccount($student);

        $selectedDbId = $request->integer('db_id') ?: $student->studentDatabases->first()?->id;
        $selectedTable = $request->query('table');
        $dbInspection = null;

        if ($selectedDbId) {
            $studentDb = $student->studentDatabases->firstWhere('id', $selectedDbId);
            if ($studentDb) {
                $dbInspection = $this->dbManager->inspectDatabase($studentDb, $selectedTable);
            }
        }

        return view('student.databases', compact('student', 'dbInspection', 'selectedDbId'));
    }

    public function storeDatabase(Request $request)
    {
        $data = $request->validate([
            'db_suffix' => ['required', 'alpha_dash', 'max:30'],
            'description' => ['nullable', 'string', 'max:120'],
        ]);

        try {
            $db = $this->dbManager->createStudentDatabase(Auth::user(), $data['db_suffix'], $data['description'] ?? null);
            return back()->with('success', "Base de datos '{$db->db_name}' creada exitosamente en MariaDB.");
        } catch (\Throwable $e) {
            return back()->withErrors(['db_suffix' => $e->getMessage()]);
        }
    }

    public function importSql(Request $request, StudentDatabase $studentDatabase)
    {
        if ($studentDatabase->user_id !== Auth::id() && !Auth::user()->isTeacher()) {
            abort(403);
        }

        $request->validate([
            'sql_file' => ['required', 'file', 'max:32768'],
        ]);

        try {
            $this->dbManager->importSqlFile($studentDatabase, $request->file('sql_file'));
            return back()->with('success', "Archivo .SQL importado correctamente en '{$studentDatabase->db_name}'.");
        } catch (\Throwable $e) {
            return back()->withErrors(['sql_file' => $e->getMessage()]);
        }
    }

    public function destroyDatabase(StudentDatabase $studentDatabase)
    {
        if ($studentDatabase->user_id !== Auth::id() && !Auth::user()->isTeacher()) {
            abort(403);
        }

        $name = $studentDatabase->db_name;
        $this->dbManager->dropStudentDatabase($studentDatabase);

        return back()->with('success', "Base de datos '{$name}' eliminada.");
    }

    private function authorizeProjectAccess(Project $project): void
    {
        $user = Auth::user();
        if (!$user->isTeacher() && $project->user_id !== $user->id) {
            abort(403, 'No tienes permiso para acceder a este proyecto.');
        }
    }
}

