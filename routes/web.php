<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\TeacherController;
use Illuminate\Support\Facades\Route;

// Redirección principal
Route::get('/', function () {
    if (!auth()->check()) {
        return redirect()->route('login');
    }
    return auth()->user()->isTeacher()
        ? redirect()->route('teacher.dashboard')
        : redirect()->route('student.dashboard');
});

// Autenticación
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// =========================================================================
// RUTAS DEL PROFESOR (LMS + GESTIÓN DE ALUMNOS + SUPER-VISOR DE PROYECTOS)
// =========================================================================
Route::middleware(['auth', 'role:teacher'])->prefix('profesor')->name('teacher.')->group(function () {
    Route::get('/dashboard', [TeacherController::class, 'dashboard'])->name('dashboard');

    // Grupos
    Route::get('/grupos', [TeacherController::class, 'groupsIndex'])->name('groups.index');
    Route::post('/grupos', [TeacherController::class, 'storeGroup'])->name('groups.store');
    Route::delete('/grupos/{group}', [TeacherController::class, 'deleteGroup'])->name('groups.destroy');

    // Alumnos (Alta individual, importación CSV de los 30 alumnos, control de cuentas)
    Route::get('/alumnos', [TeacherController::class, 'studentsIndex'])->name('students.index');
    Route::post('/alumnos', [TeacherController::class, 'storeStudent'])->name('students.store');
    Route::post('/alumnos/importar-csv', [TeacherController::class, 'importStudentsBulk'])->name('students.import');
    Route::post('/alumnos/{student}/toggle-status', [TeacherController::class, 'toggleStudentStatus'])->name('students.toggle');
    Route::post('/alumnos/{student}/reset-password', [TeacherController::class, 'resetStudentPassword'])->name('students.reset-password');

    // Actividades / Prácticas
    Route::get('/actividades', [TeacherController::class, 'activitiesIndex'])->name('activities.index');
    Route::post('/actividades', [TeacherController::class, 'storeActivity'])->name('activities.store');
    Route::get('/actividades/{activity}/entregas', [TeacherController::class, 'showActivitySubmissions'])->name('activities.submissions');

    // Super-Visor del Profesor: Revisar sitio en vivo, código en Monaco Editor, BD MariaDB y Calificar
    Route::get('/proyectos/{project}/revisar', [TeacherController::class, 'inspectProject'])->name('projects.inspect');
    Route::post('/proyectos/{project}/calificar', [TeacherController::class, 'gradeProject'])->name('projects.grade');
});

// =========================================================================
// RUTAS DEL ALUMNO (PANEL DE ACTIVIDADES + MINI-CPANEL DE HOSTING)
// =========================================================================
Route::middleware(['auth', 'role:student'])->prefix('alumno')->name('student.')->group(function () {
    Route::get('/dashboard', [StudentController::class, 'dashboard'])->name('dashboard');

    // Proyectos y Workspace (Subida ZIP, Archivos, Consola Laravel 1-Clic, Entrega)
    Route::post('/proyectos', [StudentController::class, 'storeProject'])->name('projects.store');
    Route::get('/proyectos/{project}/workspace', [StudentController::class, 'projectWorkspace'])->name('projects.workspace');
    Route::post('/proyectos/{project}/upload-zip', [StudentController::class, 'uploadZip'])->name('projects.upload-zip');
    Route::post('/proyectos/{project}/upload-file', [StudentController::class, 'uploadSingleFile'])->name('projects.upload-file');
    Route::post('/proyectos/{project}/entregar', [StudentController::class, 'submitProject'])->name('projects.submit');

    // Bases de Datos MariaDB del Alumno
    Route::get('/bases-de-datos', [StudentController::class, 'databasesIndex'])->name('databases.index');
    Route::post('/bases-de-datos', [StudentController::class, 'storeDatabase'])->name('databases.store');
    Route::post('/bases-de-datos/{studentDatabase}/importar-sql', [StudentController::class, 'importSql'])->name('databases.import');
    Route::delete('/bases-de-datos/{studentDatabase}', [StudentController::class, 'destroyDatabase'])->name('databases.destroy');
});

// =========================================================================
// RUTAS COMPARTIDAS (API DEL EDITOR DE CÓDIGO MONACO Y CONSOLA LARAVEL)
// Tanto el alumno dueño como el profesor pueden leer/editar archivos o correr Artisan
// =========================================================================
Route::middleware(['auth'])->prefix('workspace-api')->name('workspace.')->group(function () {
    Route::get('/proyectos/{project}/file', [StudentController::class, 'apiReadFile'])->name('file.read');
    Route::post('/proyectos/{project}/file', [StudentController::class, 'apiSaveFile'])->name('file.save');
    Route::delete('/proyectos/{project}/file', [StudentController::class, 'apiDeleteFile'])->name('file.delete');
    Route::post('/proyectos/{project}/laravel-cmd', [StudentController::class, 'runLaravelCommand'])->name('laravel.cmd');
});

