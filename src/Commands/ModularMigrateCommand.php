<?php

declare(strict_types=1);

namespace AlizHarb\Modular\Commands;

use AlizHarb\Modular\ModuleRegistry;
use Illuminate\Console\Command;

/**
 * Console command to run modular migrations.
 */
final class ModularMigrateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'modular:migrate
                            {module? : The name of the module}
                            {--fresh : Fresh the database before migrating}
                            {--seed : Seed the database after migrating}
                            {--rollback : Roll back the last database migration}
                            {--step=0 : The number of migrations to be reverted (used with --rollback)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run the database migrations for modules';

    /**
     * Execute the console command.
     */
    public function handle(ModuleRegistry $registry): int
    {
        $moduleName = $this->argument('module');

        if ($moduleName) {
            return $this->migrateModule((string) $moduleName, $registry);
        }

        foreach ($registry->getModules() as $module) {
            $this->migrateModule($module['name'], $registry);
        }

        return self::SUCCESS;
    }

    /**
     * Run (or rollback) migrations for a specific module.
     */
    protected function migrateModule(string $name, ModuleRegistry $registry): int
    {
        $path = $registry->resolvePath($name, 'database/migrations');

        if (! is_dir($path)) {
            $this->warn("No migrations found for module: {$name}");

            return self::SUCCESS;
        }

        if ($this->option('rollback')) {
            $step = max(1, (int) $this->option('step'));
            $this->info("Rolling back module: {$name} (step: {$step})...");

            $this->call('migrate:rollback', [
                '--path' => $path,
                '--realpath' => true,
                '--step' => $step,
            ]);

            return self::SUCCESS;
        }

        $this->info("Migrating module: {$name}...");

        $this->call('migrate', array_filter([
            '--path' => $path,
            '--realpath' => true,
            '--fresh' => $this->option('fresh'),
            '--seed' => $this->option('seed'),
        ]));

        return self::SUCCESS;
    }
}
