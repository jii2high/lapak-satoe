<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('orders:remind')->everyMinute()->withoutOverlapping();
Schedule::command('orders:expire')->everyMinute()->withoutOverlapping();
Schedule::command('orders:auto-confirm')->everyMinute()->withoutOverlapping();
