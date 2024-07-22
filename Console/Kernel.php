<?php

namespace App\Console;

use App\Services\ScheduleCustomerBirthdayNotificationService;
use Illuminate\Console\Scheduling\Schedule;
use App\Services\ScheduleDebtNotificationService;
use App\Services\ScheduleShopAniversaryNotificationService;
use App\Services\ScheduleShopAnniNotificationService;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->call(function () {
            $service = new ScheduleDebtNotificationService();
            $service->sendDebtNotification();
            \Log::info('Schecule Debt Notification');
        })->twiceDailyAt( 13, 16);

        $schedule->call(function () {
            \Log::info("Schecule Customer Birthday Notification");
            $service = new ScheduleCustomerBirthdayNotificationService();
            $service->sendCustomerBirthdayNotification();
        })->twiceDailyAt(06, 16);

        $schedule->call(function () {
            \Log::info("Schecule Shop Anniversary Notification");
            $service = new ScheduleShopAnniNotificationService();
            $service->sendShopAnniNotification();
        })->twiceDailyAt(06, 16);

        // $schedule->call(function () {
        //     $service = new ScheduleDebtNotificationService();
        //     $service->sendDebtNotification();
        // })->twiceDailyAt( 15, 20);
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
