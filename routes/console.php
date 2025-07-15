<?php

// use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
// Đăng ký custom command (nếu cần)
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::command('orders:auto-complete')->everyMinute();
Schedule::command('notify:package-expiry')->dailyAt('08:00');

// Đăng ký schedule, tất cả lệnh nằm trong closure return phía dưới
// return function (Schedule $schedule) {
//     $schedule->command('notify:package-expiry')->dailyAt('08:00');
//     // $schedule->command('orders:auto-complete')->everyMinute();
//     $schedule->command('orders:auto-complete')->everyMinute()->withoutOverlapping();
//     $schedule->command('inspire')
//             ->everyMinute()
//             ->appendOutputTo(storage_path('logs/schedule.log'));
// };
