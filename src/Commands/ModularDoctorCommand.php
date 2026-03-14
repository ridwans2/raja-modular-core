<?php

declare(strict_types=1);

namespace Ridwans2\RajaModularCore\Commands;

use Ridwans2\RajaModularCore\ModuleRegistry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'modular:doctor')]
final class ModularDoctorCommand extends Command
{
    protected $signature = 'modular:doctor {--score : Show per-module health scores}';

    protected $description = 'Diagnose common configuration issues within the modular ecosystem.';

    public function handle(): int
    {
        $this->info('Running Modular Doctor...');
        $this->newLine();

        $checks = [
            'checkComposerDependencies',
            'checkModuleStructure',
            'checkVitalConfigFiles',
            'checkAutoloading',
            'checkCircularDependencies',
            'checkModuleValidation',
            'checkGhostModules',
            'checkDuplicateProviders',
            'checkAssetLinking',
        ];

        $hasIssues = false;

        foreach ($checks as $check) {
            if (! $this->{$check}()) {
                $hasIssues = true;
            }
        }

        $this->newLine();

        // Always show health scores (or only if --score flag is used)
        $registry = app(ModuleRegistry::class);
        $modules = $registry->getModules();

        if (! empty($modules)) {
            $this->displayHealthScores($modules, $registry);
        }

        if ($hasIssues) {
            $this->error('Doctor found some issues. Please review the warnings above.');

            return self::FAILURE;
        }

        $this->info('All systems operational! Modular ecosystem looks healthy.');

        return self::SUCCESS;
    }

    /**
     * Display per-module health scores (0–100).
     *
     * Score breakdown:
     * - Has module.json with name    : 10 pts
     * - Has version set              : 10 pts
     * - Has authors set              : 10 pts
     * - Has migrations               : 15 pts
     * - Has tests directory          : 20 pts
     * - Has README.md                : 10 pts
     * - Dependencies satisfied       : 15 pts
     * - Has service provider         : 10 pts
     *
     * @param array<string, array<string, mixed>> $modules
     */
    private function displayHealthScores(array $modules, ModuleRegistry $registry): void
    {
        $this->components->info('Module Health Scores');
        $rows = [];

        foreach ($modules as $name => $module) {
            $score = 0;
            $path = $module['path'];

            // Has module.json with name
            if (File::exists($path . '/module.json') && ! empty($module['name'])) {
                $score += 10;
            }

            // Has version set (non-default)
            if (! empty($module['version']) && $module['version'] !== '1.0.0') {
                $score += 10;
            } elseif (! empty($module['version'])) {
                $score += 5;
            }

            // Has authors set
            if (! empty($module['authors'])) {
                $score += 10;
            }

            // Has migrations directory with at least one file
            $migrationPath = $path . '/database/migrations';
            if (File::isDirectory($migrationPath) && count(File::files($migrationPath)) > 0) {
                $score += 15;
            }

            // Has tests directory with at least one file
            $testsPath = $path . '/tests';
            if (File::isDirectory($testsPath) && count(File::allFiles($testsPath)) > 0) {
                $score += 20;
            }

            // Has README.md
            if (File::exists($path . '/README.md')) {
                $score += 10;
            }

            // Dependencies satisfied
            $deps = $registry->checkDependencies($name);
            if ($deps['satisfied']) {
                $score += 15;
            }

            // Has a service provider registered
            if (! empty($module['providers'])) {
                $score += 10;
            }

            $grade = match (true) {
                $score >= 90 => '<fg=green>Excellent</>',
                $score >= 70 => '<fg=cyan>Good</>',
                $score >= 50 => '<fg=yellow>Fair</>',
                default      => '<fg=red>Needs Work</>',
            };

            $rows[] = [$name, "{$score}/100", $grade];
        }

        $this->table(['Module', 'Score', 'Grade'], $rows);
        $this->newLine();
    }

    protected function checkComposerDependencies(): bool
    {
        $exists = File::exists(base_path('composer.json'));
        $this->components->task('Checking core dependencies', fn() => $exists);

        return $exists;
    }

    protected function checkModuleStructure(): bool
    {
        $modulesPath = config('modular.paths.modules', base_path('modules'));

        if (! File::isDirectory($modulesPath)) {
            $this->components->warn("Modules directory [{$modulesPath}] does not exist.");

            return false;
        }

        $this->components->task('Checking modules directory', fn() => true);

        return true;
    }

    protected function checkVitalConfigFiles(): bool
    {
        $requiredFiles = ['config/modular.php', 'composer.json'];
        $missing = array_filter($requiredFiles, fn($f) => ! File::exists(base_path($f)));

        if (! empty($missing)) {
            $this->components->error('Missing vital config files: ' . implode(', ', $missing));

            return false;
        }

        $this->components->task('Checking vital configuration files', fn() => true);

        return true;
    }

    protected function checkAutoloading(): bool
    {
        $composer = json_decode((string) File::get(base_path('composer.json')), true);
        $psrs = array_merge(
            $composer['autoload']['psr-4'] ?? [],
            $composer['autoload-dev']['psr-4'] ?? []
        );

        $moduleNamespace = config('modular.paths.namespace', 'Modules\\');

        if (! isset($psrs[$moduleNamespace])) {
            $this->components->warn("Namespace [{$moduleNamespace}] is not registered in composer.json PSR-4 autoloading. Performance will be degraded.");

            return false;
        }

        $this->components->task('Checking PSR-4 autoloading', fn() => true);

        return true;
    }

    protected function checkCircularDependencies(): bool
    {
        $result = $this->callSilent('modular:check');
        $this->components->task('Checking circular dependencies', fn() => $result === Command::SUCCESS);

        return $result === Command::SUCCESS;
    }

    protected function checkModuleValidation(): bool
    {
        $registry = app(ModuleRegistry::class);
        $modules = $registry->getModules();
        $allValid = true;

        foreach ($modules as $name => $config) {
            if (! File::exists($config['path'] . '/module.json')) {
                $this->components->warn("Module [{$name}] is missing module.json file.");
                $allValid = false;
            }
        }

        $this->components->task('Validating module metadata', fn() => $allValid);

        return $allValid;
    }

    protected function checkGhostModules(): bool
    {
        $modulesPath = config('modular.paths.modules', base_path('modules'));

        if (! File::isDirectory($modulesPath)) {
            return true;
        }

        $ghosts = array_filter(
            File::directories($modulesPath),
            fn($dir) => ! File::exists($dir . '/module.json')
        );

        if (! empty($ghosts)) {
            $this->components->warn('Ghost modules detected (directories without module.json): ' . implode(', ', array_map('basename', $ghosts)));

            return false;
        }

        $this->components->task('Checking for ghost modules', fn() => true);

        return true;
    }

    protected function checkDuplicateProviders(): bool
    {
        $registry = app(ModuleRegistry::class);
        $modules = $registry->getModules();
        $allProviders = [];
        $duplicates = [];

        foreach ($modules as $name => $config) {
            foreach ($config['providers'] as $provider) {
                if (isset($allProviders[$provider])) {
                    $duplicates[$provider][] = $name;
                } else {
                    $allProviders[$provider] = $name;
                }
            }
        }

        if (! empty($duplicates)) {
            foreach ($duplicates as $provider => $modNames) {
                $this->components->warn("Duplicate provider [{$provider}] registered in modules: " . implode(', ', array_merge([$allProviders[$provider]], $modNames)));
            }

            return false;
        }

        $this->components->task('Checking for duplicate service providers', fn() => true);

        return true;
    }

    protected function checkAssetLinking(): bool
    {
        if (! File::exists(public_path('modules'))) {
            $this->components->warn('Module assets directory [public/modules] does not exist. Run php artisan modular:link.');

            return false;
        }

        $this->components->task('Verifying asset linking', fn() => true);

        return true;
    }
}
