<?php

return [
    // Directorio raíz donde viven los espacios web de los alumnos (/var/www/students/{matricula})
    'students_root_path' => env('STUDENTS_ROOT_PATH', storage_path('app/students')),

    // Directorio de caché compartida de Composer para acelerar instalaciones de proyectos Laravel
    'composer_cache_dir' => env('COMPOSER_CACHE_DIR', '/var/www/composer-cache'),

    // Máximo de bases de datos MariaDB que puede crear cada estudiante
    'max_databases_per_student' => (int) env('MAX_DATABASES_PER_STUDENT', 5),

    // Cuota de disco por estudiante en MB
    'student_quota_mb' => (int) env('STUDENT_QUOTA_MB', 250),

    // URL del gestor visual de bases de datos Adminer
    'adminer_url' => env('ADMINER_URL', '/db-manager/'),

    // Extensiones permitidas para edición de código en el navegador (Monaco Editor)
    'editable_extensions' => [
        'php', 'html', 'htm', 'css', 'js', 'json', 'sql', 'md', 'txt',
        'env', 'xml', 'yaml', 'yml', 'htaccess', 'gitignore', 'blade.php'
    ],
];

