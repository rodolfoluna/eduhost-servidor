<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Group;
use App\Models\Project;
use App\Models\StudentDatabase;
use App\Models\User;
use App\Services\DatabaseManagerService;
use App\Services\FileManagerService;
use App\Services\HostingProvisionerService;
use App\Services\LaravelRunnerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TeacherController extends Controller
{
    public function __construct(
        protected HostingProvisionerService $provisioner,
        protected FileManagerService $fileManager,
        protected DatabaseManagerService $dbManager,
        protected LaravelRunnerService $laravelRunner
    ) {}

    /**
     * Dashboard General del Profesor: Estadísticas del servidor, grupos, alumnos y entregas recientes.
     */
    public function dashboard()
    {
        $stats = [
            'groups_count' => Group::count(),
            'students_count' => User::where('role', 'student')->count(),
            'activities_count' => Activity::count(),
            'projects_count' => Project::count(),
            'pending_reviews' => Project::where('status', 'submitted')->count(),
            'databases_count' => StudentDatabase::count(),
        ];

        $recentSubmissions = Project::with(['user.group', 'activity', 'studentDatabase'])
            ->orderByDesc('updated_at')
            ->limit(12)
            ->get();

        $groups = Group::withCount(['students', 'activities'])->get();

        return view('teacher.dashboard', compact('stats', 'recentSubmissions', 'groups'));
    }

    // =========================================================================
    // GESTIÓN DE GRUPOS
    // =========================================================================
    public function groupsIndex()
    {
        $groups = Group::withCount(['students', 'activities'])->orderBy('name')->get();
        return view('teacher.groups', compact('groups'));
    }

    public function storeGroup(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => ['required', 'string', 'max:40', 'unique:groups,code'],
            'semester' => ['nullable', 'string', 'max:30'],
            'description' => ['nullable', 'string'],
        ]);

        Group::create($data);
        return back()->with('success', 'Grupo creado exitosamente.');
    }

    public function deleteGroup(Group $group)
    {
        $group->delete();
        return back()->with('success', 'Grupo eliminado.');
    }

    // =========================================================================
    // GESTIÓN DE LOS 30 ALUMNOS (INDIVIDUAL Y MASIVA POR CSV)
    // =========================================================================
    public function studentsIndex(Request $request)
    {
        $groups = Group::orderBy('name')->get();
        $query = User::where('role', 'student')->with(['group', 'projects', 'studentDatabases']);

        if ($request->filled('group_id')) {
            $query->where('group_id', $request->integer('group_id'));
        }

        $students = $query->orderBy('name')->get();

        return view('teacher.students', compact('students', 'groups'));
    }

    public function storeStudent(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'username' => ['required', 'alpha_dash', 'max:30', 'unique:users,username'],
            'email' => ['required', 'email', 'max:120', 'unique:users,email'],
            'group_id' => ['required', 'exists:groups,id'],
            'password' => ['required', 'string', 'min:6'],
        ]);

        $cleanUser = preg_replace('/[^a-zA-Z0-9]/', '', $data['username']);

        $student = User::create([
            'name' => $data['name'],
            'username' => strtolower($data['username']),
            'email' => $data['email'],
            'role' => 'student',
            'group_id' => $data['group_id'],
            'password' => Hash::make($data['password']),
            'db_user' => 'alu_' . strtolower(substr($cleanUser, 0, 12)),
            'db_password_plain' => 'Db_' . Str::random(8),
            'is_active' => true,
            'disk_quota_mb' => (int) config('eduhost.student_quota_mb', 250),
        ]);

        $this->provisioner->provisionStudentAccount($student);

        return back()->with('success', "Alumno {$student->name} registrado. Espacio web (/~{$student->username}/) y usuario MariaDB ({$student->db_user}) creados.");
    }

    /**
     * Importación rápida por lote (CSV en texto: matricula,nombre,email,password) para dar de alta a los 30 alumnos en segundos.
     */
    public function importStudentsBulk(Request $request)
    {
        $data = $request->validate([
            'group_id' => ['required', 'exists:groups,id'],
            'csv_lines' => ['required', 'string'],
        ]);

        $lines = preg_split('/\r\n|\r|\n/', trim($data['csv_lines']));
        $created = 0;

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with(strtolower($line), 'matricula,')) {
                continue;
            }

            $parts = array_map('trim', explode(',', $line));
            if (count($parts) < 3) {
                continue;
            }

            [$username, $name, $email] = $parts;
            $password = $parts[3] ?? $username; // Si no se especifica password, usa su matrícula como password inicial
            $username = strtolower(preg_replace('/[^a-zA-Z0-9_-]/', '', $username));

            if ($username === '' || User::where('username', $username)->orWhere('email', $email)->exists()) {
                continue;
            }

            $cleanUser = preg_replace('/[^a-zA-Z0-9]/', '', $username);

            $student = User::create([
                'name' => $name,
                'username' => $username,
                'email' => $email,
                'role' => 'student',
                'group_id' => $data['group_id'],
                'password' => Hash::make($password),
                'db_user' => 'alu_' . strtolower(substr($cleanUser, 0, 12)),
                'db_password_plain' => 'Db_' . Str::random(8),
                'is_active' => true,
                'disk_quota_mb' => (int) config('eduhost.student_quota_mb', 250),
            ]);

            $this->provisioner->provisionStudentAccount($student);
            $created++;
        }

        return back()->with('success', "Se importaron y aprovisionaron {$created} alumnos exitosamente.");
    }

    public function toggleStudentStatus(User $student)
    {
        $student->update(['is_active' => !$student->is_active]);
        return back()->with('success', "Estado de la cuenta de {$student->name} actualizado.");
    }

    public function resetStudentPassword(Request $request, User $student)
    {
        $data = $request->validate([
            'new_password' => ['required', 'string', 'min:6'],
        ]);

        $student->update(['password' => Hash::make($data['new_password'])]);
        return back()->with('success', "Contraseña de {$student->name} actualizada.");
    }

    // =========================================================================
    // GESTIÓN DE ACTIVIDADES / PRÁCTICAS (PHP PURO, PHP+MARIADB, LARAVEL)
    // =========================================================================
    public function activitiesIndex()
    {
        $groups = Group::orderBy('name')->get();
        $activities = Activity::with('group')->withCount('projects')->orderByDesc('created_at')->get();
        return view('teacher.activities', compact('activities', 'groups'));
    }

    public function storeActivity(Request $request)
    {
        $data = $request->validate([
            'group_id' => ['required', 'exists:groups,id'],
            'title' => ['required', 'string', 'max:160'],
            'project_type' => ['required', 'in:php_pure,php_mariadb,laravel'],
            'instructions' => ['required', 'string'],
            'due_date' => ['nullable', 'date'],
            'max_score' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $data['slug'] = Str::slug($data['title']);
        $data['is_published'] = true;

        Activity::create($data);
        return back()->with('success', 'Actividad creada y asignada al grupo.');
    }

    public function showActivitySubmissions(Activity $activity)
    {
        $activity->load(['group.students.projects' => function ($q) use ($activity) {
            $q->where('activity_id', $activity->id)->with('studentDatabase');
        }]);

        return view('teacher.activity-submissions', compact('activity'));
    }

    // =========================================================================
    // SUPER-VISOR DEL PROFESOR: REVISAR CÓDIGO (MONACO), SITIO EN VIVO Y BD MARIADB
    // =========================================================================
    public function inspectProject(Request $request, Project $project)
    {
        $project->load(['user.group', 'user.studentDatabases', 'activity', 'studentDatabase']);
        $fileTree = $this->fileManager->getProjectTree($project);

        // Inspeccionar base de datos seleccionada o vinculada al proyecto
        $selectedDbId = $request->integer('db_id') ?: $project->student_database_id;
        $selectedTable = $request->query('table');
        $dbInspection = null;

        if ($selectedDbId) {
            $studentDb = StudentDatabase::where('user_id', $project->user_id)->find($selectedDbId);
            if ($studentDb) {
                $dbInspection = $this->dbManager->inspectDatabase($studentDb, $selectedTable);
            }
        } elseif ($project->user->studentDatabases->isNotEmpty()) {
            $firstDb = $project->user->studentDatabases->first();
            $dbInspection = $this->dbManager->inspectDatabase($firstDb, $selectedTable);
        }

        $laravelLogs = $project->project_type === 'laravel'
            ? $this->laravelRunner->readProjectLogs($project)
            : null;

        return view('teacher.inspect-project', compact(
            'project',
            'fileTree',
            'dbInspection',
            'laravelLogs'
        ));
    }

    public function gradeProject(Request $request, Project $project)
    {
        $data = $request->validate([
            'score' => ['required', 'numeric', 'min:0', 'max:100'],
            'teacher_feedback' => ['nullable', 'string'],
        ]);

        $project->update([
            'score' => $data['score'],
            'teacher_feedback' => $data['teacher_feedback'],
            'status' => 'graded',
            'graded_at' => now(),
        ]);

        return back()->with('success', "Calificación ({$data['score']}/100) guardada para {$project->user->name}.");
    }
}

