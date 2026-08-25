<?php

declare(strict_types=1);

use Infocyph\Foundation\Scheduling\Schedule;

return static function (Schedule $schedule): void {
    /**
     * Usage:
     * $schedule->command('reports:daily')
     *     ->dailyAt('02:00')
     *     ->onOneServer()
     *     ->withoutOverlap();
     */
};
