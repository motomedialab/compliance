<?php

namespace Motomedialab\Compliance\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;
use Motomedialab\Compliance\Console\Commands;

class ComplianceServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this
            ->registerAssets()
            ->registerCommands()
            ->registerSchedules();
    }

    /**
     * Register our publishable assets.
     */
    protected function registerAssets(): static
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        $this->mergeConfigFrom(__DIR__ . '/../../config/compliance.php', 'compliance');

        $this->publishes([
            __DIR__ . '/../../config/compliance.php' => config_path('compliance.php'),
        ], 'config');

        return $this;
    }

    /**
     * Register our commands to be available for Artisan.
     */
    protected function registerCommands(): static
    {
        $this->commands([
            Commands\ComplianceCheckCommand::class,
            Commands\CompliancePruneCommand::class,
        ]);

        return $this;
    }

    /**
     * Register our scheduled tasks to run on a daily basis.
     */
    protected function registerSchedules(): static
    {
        $this->app->booted(function () {
            /** @var Schedule $schedule */
            $schedule = $this->app->make(Schedule::class);

            $this->scheduleCommand($schedule, Commands\ComplianceCheckCommand::class, 'check');
            $this->scheduleCommand($schedule, Commands\CompliancePruneCommand::class, 'prune');
        });

        return $this;
    }

    protected function scheduleCommand(Schedule $schedule, string $command, string $type): void
    {
        $time = config("compliance.schedule.{$type}_time", '00:00');

        $schedule->command($command)->dailyAt($time);
    }
}
