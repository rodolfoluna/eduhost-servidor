<?php

namespace App\Services;

use App\Models\StudentDatabase;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DatabaseManagerService
{
    public function __construct(
        protected HostingProvisionerService $provisioner
    ) {}

    /**
     * Crea una base de datos real en MariaDB con el prefijo del alumno (ej. alu_2026001_tienda)
     * y asegura que el usuario MySQL del alumno tenga permisos completos sobre ella.
     */
    public function createStudentDatabase(User $student, string $rawSuffix, ?string $description = null): StudentDatabase
    {
        $maxDbs = (int) config('eduhost.max_databases_per_student', 5);
        if ($student->studentDatabases()->count() >= $maxDbs) {
            throw new RuntimeException("Has alcanzado el límite máximo de {$maxDbs} bases de datos permitidas.");
        }

        // Asegurar que la cuenta MariaDB del alumno exista
        $this->provisioner->provisionStudentAccount($student);

        // Limpiar el nombre elegido por el alumno
        $cleanSuffix = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', $rawSuffix));
        if ($cleanSuffix === '') {
            throw new RuntimeException('El nombre de la base de datos solo debe contener letras y números.');
        }

        $fullDbName = substr($student->dbPrefix() . $cleanSuffix, 0, 60);

        if (StudentDatabase::where('db_name', $fullDbName)->exists()) {
            throw new RuntimeException("La base de datos '{$fullDbName}' ya existe.");
        }

        $dbUser = preg_replace('/[^a-zA-Z0-9_]/', '', (string) $student->db_user);

        try {
            DB::unprepared("CREATE DATABASE IF NOT EXISTS `{$fullDbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
            DB::unprepared("GRANT ALL PRIVILEGES ON `{$fullDbName}`.* TO '{$dbUser}'@'localhost';");
            DB::unprepared("FLUSH PRIVILEGES;");
        } catch (\Throwable $e) {
            logger()->warning("Aviso al crear BD física {$fullDbName}: " . $e->getMessage());
        }

        return StudentDatabase::create([
            'user_id' => $student->id,
            'db_name' => $fullDbName,
            'description' => $description,
        ]);
    }

    /**
     * Elimina la base de datos de MariaDB y su registro en EduHost.
     */
    public function dropStudentDatabase(StudentDatabase $studentDb): void
    {
        $dbName = preg_replace('/[^a-zA-Z0-9_]/', '', $studentDb->db_name);

        try {
            DB::unprepared("DROP DATABASE IF EXISTS `{$dbName}`;");
        } catch (\Throwable $e) {
            logger()->warning("Aviso al eliminar BD física {$dbName}: " . $e->getMessage());
        }

        $studentDb->delete();
    }

    /**
     * Importa un archivo .SQL directamente en la base de datos seleccionada del alumno.
     */
    public function importSqlFile(StudentDatabase $studentDb, UploadedFile $sqlFile): void
    {
        $sqlContent = file_get_contents($sqlFile->getRealPath());
        if ($sqlContent === false || trim($sqlContent) === '') {
            throw new RuntimeException('El archivo .SQL está vacío o no se pudo leer.');
        }

        $dbName = preg_replace('/[^a-zA-Z0-9_]/', '', $studentDb->db_name);

        // Prevenir que el script .SQL del alumno intente cambiar a otra base de datos con USE otra_bd;
        $sanitizedSql = preg_replace('/\bUSE\s+`?[a-zA-Z0-9_]+`?\s*;/i', '', $sqlContent);

        DB::unprepared("USE `{$dbName}`;\n" . $sanitizedSql);

        // Restaurar la conexión a la base de datos principal de EduHost
        $mainDb = config('database.connections.mysql.database', 'eduhost');
        DB::unprepared("USE `{$mainDb}`;");
    }

    /**
     * Inspecciona en tiempo real las tablas, estructura de columnas y primeros registros
     * de la base de datos de un alumno (para el Super-Visor del Profesor y el panel del Alumno).
     */
    public function inspectDatabase(StudentDatabase $studentDb, ?string $selectedTable = null): array
    {
        $dbName = preg_replace('/[^a-zA-Z0-9_]/', '', $studentDb->db_name);
        $tables = [];
        $columns = [];
        $rows = [];
        $error = null;

        try {
            $rawTables = DB::select("SHOW TABLES FROM `{$dbName}`");
            foreach ($rawTables as $row) {
                $values = array_values((array) $row);
                if (!empty($values[0])) {
                    $tables[] = $values[0];
                }
            }

            $activeTable = $selectedTable && in_array($selectedTable, $tables, true)
                ? $selectedTable
                : ($tables[0] ?? null);

            if ($activeTable) {
                $cleanTable = preg_replace('/[^a-zA-Z0-9_]/', '', $activeTable);
                $columns = DB::select("SHOW COLUMNS FROM `{$dbName}`.`{$cleanTable}`");
                $rows = DB::select("SELECT * FROM `{$dbName}`.`{$cleanTable}` LIMIT 50");
            }
        } catch (\Throwable $e) {
            $error = 'No se pudo consultar el esquema en MariaDB (' . $e->getMessage() . ')';
            $activeTable = null;
        }

        return [
            'database' => $studentDb->db_name,
            'tables' => $tables,
            'active_table' => $activeTable ?? null,
            'columns' => $columns,
            'rows' => $rows,
            'error' => $error,
        ];
    }
}

