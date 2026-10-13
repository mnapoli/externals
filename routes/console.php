<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

Schedule::command('externals:sync')->everyFifteenMinutes()->withoutOverlapping();
Schedule::command('externals:sync-rfcs')->hourly()->withoutOverlapping();
