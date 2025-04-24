<?php

declare(strict_types=1);

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Process;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('db:open', function (): void {
    if (! file_exists('/Applications/Sequel Ace.app')) {
        $this->warn('This command uses Sequel Ace, are you sure it\'s installed?');
        $this->line("Install here: https://sequel-ace.com/\n");
    }

    $driver = config('database.default');
    $host = config("database.connections.{$driver}.host");
    $user = config("database.connections.{$driver}.username");
    $password = config("database.connections.{$driver}.password");
    $database = config("database.connections.{$driver}.database");

    // Ref: https://github.com/Sequel-Ace/Sequel-Ace/issues/189#issuecomment-656289269
    Process::run("open {$driver}://{$user}:{$password}@{$host}/{$database} -a 'Sequel Ace'");
})->purpose('Open database management from command line');
