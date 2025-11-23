<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Process subscription renewals daily at 9 AM
        $schedule->command('subscriptions:process-renewals')
            ->dailyAt('09:00')
            ->timezone('Africa/Lagos')
            ->withoutOverlapping()
            ->onFailure(function () {
                \Log::error('Subscription renewal processing failed');
            })
            ->onSuccess(function () {
                \Log::info('Subscription renewal processing completed successfully');
            });

        // Send renewal reminders 3 days before expiration
        $schedule->command('subscriptions:send-renewal-reminders')
            ->dailyAt('10:00')
            ->timezone('Africa/Lagos')
            ->withoutOverlapping();

        // Clean up old completed orders (archive after 90 days)
        $schedule->command('orders:cleanup')
            ->weekly()
            ->mondays()
            ->at('02:00')
            ->timezone('Africa/Lagos');

        // Generate monthly seller performance reports
        $schedule->command('reports:generate-seller-performance')
            ->monthlyOn(1, '08:00')
            ->timezone('Africa/Lagos');

        // Update seller ratings and statistics
        $schedule->command('sellers:update-stats')
            ->dailyAt('06:00')
            ->timezone('Africa/Lagos');

        // Clear expired banner placements
        $schedule->command('banners:cleanup-expired')
            ->dailyAt('01:00')
            ->timezone('Africa/Lagos');

        // Send overdue payment reminders
        $schedule->command('invoices:send-overdue-reminders')
            ->dailyAt('11:00')
            ->timezone('Africa/Lagos');

        // Archive old sold or hired listings
        $schedule->command('app:archive-sold-or-hired-listings')
            ->dailyAt('03:00')
            ->timezone('Africa/Lagos');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}