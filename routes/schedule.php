<?php

declare(strict_types=1);

return static function (): void {
    /**
     * To register a schedule, accept a \Infocyph\Foundation\Scheduling\Schedule $schedule parameter.
     * Usage:
     * $schedule->command('reports:daily')
     *     ->dailyAt('02:00')
     *     ->onOneServer()
     *     ->withoutOverlap();
     */
};
