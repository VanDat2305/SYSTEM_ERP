<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Đây là cách đăng ký command tự định nghĩa
// Mọi schedule phải nằm trong closure return phía dưới

return function (Schedule $schedule) {
    $schedule->command('notify:package-expiry')->dailyAt('08:00');
};
