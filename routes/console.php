<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('eduhost:status', function () {
    $this->info('EduHost - Plataforma LMS y Mini-Hosting Académico operativa.');
})->purpose('Muestra el estado de EduHost');

