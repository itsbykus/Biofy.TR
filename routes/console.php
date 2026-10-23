<?php

use App\Services\System\MaintenanceRunner;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
| Optional cron: "* * * * * php /path/to/biofy/artisan schedule:run >> /dev/null 2>&1"
| Without cron the app keeps working; see docs/SHARED_HOSTING.md for the URL-cron alternative.
*/
Artisan::command('biofy:maintenance', function (MaintenanceRunner $runner) {
    foreach ($runner->run() as $task => $result) {
        $this->line(str_pad($task, 26).' '.$result);
    }
})->purpose('Prune old analytics/logs, process account deletions, re-check domains, run scheduled backups');

Schedule::command('biofy:maintenance')->everyFifteenMinutes()->withoutOverlapping();
