<?php

declare(strict_types=1);

namespace Ridwans2\RajaModularCore\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Console command to install and configure Laravel Modular.
 */
class ModularInstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'modular:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install and configure Laravel Modular';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->components->info('Installing Laravel Modular...');

        $this->publishResources();
        $this->configureAutoloading();
        $this->configureModuleAutoloading();
        $this->configureVite();
        $this->createViteBaseHelper();
        $this->configureNpmWorkspaces();
        $this->configurePhpUnit();
        $this->configureTestScript();

        $this->components->info('Laravel Modular has been successfully installed! 🚀');
        $this->comment('You can now create your first module using: php artisan make:module {name}');

        if ($this->confirm('Would you like to show some love by starring the repo on GitHub? ⭐', true)) {
            $url = 'https://github.com/ridwans2/raja-modular-core';
            if (PHP_OS_FAMILY === 'Darwin') {
                exec("open {$url}");
            } elseif (PHP_OS_FAMILY === 'Windows') {
                exec("start {$url}");
            } elseif (PHP_OS_FAMILY === 'Linux') {
                exec("xdg-open {$url}");
            }
            $this->line("Thanks! You're awesome! 💙");
        }

        return self::SUCCESS;
    }

    /**
     * Publish the package resources.
     */
    protected function publishResources(): void
    {
        $this->info('Publishing resources...');

        $this->call('vendor:publish', [
            '--provider' => "Ridwans2\RajaModularCore\ModularServiceProvider",
            '--tag' => 'modular-config',
        ]);

        if ($this->confirm('Would you like to publish the modular stubs for customization?', true)) {
            $this->call('vendor:publish', [
                '--provider' => "Ridwans2\RajaModularCore\ModularServiceProvider",
                '--tag' => 'modular-stubs',
            ]);
        }
    }

    /**
     * Configure composer.json for autoloading.
     */
    protected function configureAutoloading(): void
    {
        $composerJsonPath = base_path('composer.json');

        if (! File::exists($composerJsonPath)) {
            return;
        }

        /** @var array<string, mixed> $composer */
        $composer = json_decode((string) File::get($composerJsonPath), true);

        // 1. Configure standard PSR-4 for the Modules namespace
        $rootNamespace = config('modular.naming.root_namespace', 'Modules') . '\\';
        $modulesPath = Str::after(config('modular.paths.modules', base_path('modules')), base_path() . '/') . '/';

        if (! isset($composer['autoload']['psr-4'][$rootNamespace])) {
            if ($this->confirm("Would you like to add optimized PSR-4 autoloading for '{$rootNamespace}' to your composer.json?", true)) {
                $composer['autoload']['psr-4'][$rootNamespace] = $modulesPath;
                File::put($composerJsonPath, (string) json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                $this->components->info("Added PSR-4 autoloading for '{$rootNamespace}' pointing to '{$modulesPath}'");
            }
        }

        // 2. Configure Composer Merge Plugin for module-specific dependencies
        $mergeConfig = $composer['extra']['merge-plugin'] ?? [];
        $include = (array) ($mergeConfig['include'] ?? []);
        $relativeMergePath = $modulesPath . '*/composer.json';

        if (! in_array($relativeMergePath, $include)) {
            $this->components->warn('Composer Merge Plugin is recommended for module-specific dependencies.');

            if ($this->confirm('Would you like to automatically configure it?', true)) {
                $include[] = $relativeMergePath;
                $composer['extra']['merge-plugin']['include'] = $include;
                $composer['extra']['merge-plugin']['recurse'] = true;
                $composer['extra']['merge-plugin']['merge-dev'] = true;

                File::put($composerJsonPath, (string) json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                $this->components->info("Configured composer.json to include {$relativeMergePath}");
            }
        }

        $this->warn('Please run "composer dump-autoload" to apply autoloading changes.');
    }

    /**
     * Patch existing module composer.json files to include autoload-dev for Tests.
     */
    protected function configureModuleAutoloading(): void
    {
        $modulesPath = config('modular.paths.modules', base_path('modules'));

        if (! is_string($modulesPath) || ! File::isDirectory($modulesPath)) {
            return;
        }

        $directories = File::directories($modulesPath);

        foreach ($directories as $directory) {
            $composerJsonPath = $directory . '/composer.json';

            if (! File::exists($composerJsonPath)) {
                continue;
            }

            /** @var array<string, mixed> $composer */
            $composer = json_decode((string) File::get($composerJsonPath), true);
            $needsUpdate = false;

            // Try to figure out the module namespace
            $name = basename($directory);
            $moduleNamespace = "Modules\\{$name}\\";

            // Look for custom namespace in psr-4 if standard is missing
            if (isset($composer['autoload']['psr-4'])) {
                $keys = array_keys($composer['autoload']['psr-4']);
                if (!empty($keys)) {
                    $moduleNamespace = rtrim($keys[0], '\\') . '\\';
                }
            }

            $testsNamespace = $moduleNamespace . "Tests\\";

            if (! isset($composer['autoload-dev']['psr-4'][$testsNamespace])) {
                $composer['autoload-dev']['psr-4'][$testsNamespace] = 'tests/';
                $needsUpdate = true;
            }

            if ($needsUpdate) {
                File::put($composerJsonPath, (string) json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                $this->components->info("Updated autoload-dev for module [{$name}] to support native testing.");
            }
        }
    }

    /**
     * Configure Vite for modular views and assets.
     */
    protected function configureVite(): void
    {
        $viteConfigPath = base_path('vite.config.js');

        if (! File::exists($viteConfigPath)) {
            return;
        }

        $this->createViteModularLoader();

        $content = (string) File::get($viteConfigPath);

        if (! str_contains($content, 'vite.modular.js')) {
            $this->components->warn('Vite needs to be configured to load modular assets.');

            if ($this->confirm('Would you like to automatically configure vite.config.js?', true)) {
                // Add modularLoader import - handle both single-line and multiline imports
                if (! str_contains($content, 'modularLoader')) {
                    // Try to find the vite import (single or multiline)
                    if (preg_match('/import\s+\{[^}]*defineConfig[^}]*\}\s+from\s+[\'"]vite[\'"];?/', $content, $matches)) {
                        $viteImport = $matches[0];
                        $content = str_replace(
                            $viteImport,
                            $viteImport . "\nimport { modularLoader } from './vite.modular.js';",
                            $content
                        );
                    }
                }

                $content = preg_replace(
                    "/input:\s*\[([^\]]+)\],/",
                    "input: [\n                $1,\n                ...modularLoader.inputs()\n            ],",
                    $content
                );

                // Try to find if refresh is already an array or true
                if (str_contains($content, 'refresh: [')) {
                    $content = str_replace(
                        'refresh: [',
                        "refresh: [\n                ...modularLoader.refreshPaths(),",
                        $content
                    );
                } else {
                    $content = preg_replace(
                        "/refresh:\s*true,/",
                        "refresh: [\n                ...modularLoader.refreshPaths(),\n                'resources/views/**',\n                'routes/**',\n            ],",
                        $content
                    );
                }

                File::put($viteConfigPath, $content);
                $this->components->info('Configured vite.config.js to use the modular loader.');
            } else {
                $this->components->info('To manually configure Vite, add the following to your vite.config.js:');
                $this->line("\nimport { modularLoader } from './vite.modular.js';\n");
                $this->line('// In plugins -> laravel() configuration:');
                $this->line("input: [\n    // ... existing inputs,\n    ...modularLoader.inputs()\n],");
                $this->line("refresh: [\n    // ... existing paths,\n    ...modularLoader.refreshPaths()\n],");
            }
        }
    }

    /**
     * Create the vite.modular.js loader file.
     */
    protected function createViteModularLoader(): void
    {
        $path = base_path('vite.modular.js');

        if (File::exists($path)) {
            return;
        }

        $stubPath = __DIR__ . '/../../resources/stubs/vite.modular.js.stub';

        if (File::exists($stubPath)) {
            $content = (string) File::get($stubPath);
            File::put($path, $content);
            $this->components->info('Created vite.modular.js loader.');
        }
    }

    /**
     * Create the vite.base.js helper file.
     */
    protected function createViteBaseHelper(): void
    {
        $path = base_path('vite.base.js');

        if (File::exists($path)) {
            return;
        }

        $stubPath = __DIR__ . '/../../resources/stubs/vite.base.js.stub';

        if (File::exists($stubPath)) {
            $content = (string) File::get($stubPath);
            File::put($path, $content);
            $this->components->info('Created vite.base.js helper.');
        }
    }

    /**
     * Configure NPM Workspaces in package.json.
     */
    protected function configureNpmWorkspaces(): void
    {
        $packageJsonPath = base_path('package.json');

        if (! File::exists($packageJsonPath)) {
            return;
        }

        /** @var array<string, mixed> $packageJson */
        $packageJson = json_decode((string) File::get($packageJsonPath), true);

        $modulesPath = Str::after(config('modular.paths.modules', base_path('modules')), base_path() . '/') . '/*';
        $workspaces = (array) ($packageJson['workspaces'] ?? []);

        if (! in_array($modulesPath, $workspaces)) {
            $this->components->warn('NPM Workspaces are recommended for per-module assets.');

            if ($this->confirm('Would you like to automatically configure NPM Workspaces?', true)) {
                $workspaces[] = $modulesPath;
                $packageJson['workspaces'] = array_values(array_unique($workspaces));

                File::put($packageJsonPath, (string) json_encode($packageJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                $this->components->info("Configured package.json workspaces to include {$modulesPath}");
                $this->warn('Please run "npm install" to initialize workspaces.');
            }
        }
    }

    /**
     * Configure phpunit.xml to include module test directories.
     */
    protected function configurePhpUnit(): void
    {
        $phpUnitPath = base_path('phpunit.xml');

        if (! File::exists($phpUnitPath)) {
            $phpUnitPath = base_path('phpunit.xml.dist');
            if (! File::exists($phpUnitPath)) {
                return;
            }
        }

        $content = (string) File::get($phpUnitPath);
        $modulesPath = Str::after(config('modular.paths.modules', base_path('modules')), base_path() . '/');
        $needsUpdate = false;

        // Add Feature test directory
        if (str_contains($content, '<testsuite name="Feature">') && ! str_contains($content, "{$modulesPath}/*/tests/Feature")) {
            $content = preg_replace(
                '/(<testsuite name="Feature">\s*<directory[^>]*>.*?<\/directory>\s*)/',
                "$1<directory suffix=\"Test.php\">./{$modulesPath}/*/tests/Feature</directory>\n        ",
                $content
            );
            $needsUpdate = true;
        }

        // Add Unit test directory
        if (str_contains($content, '<testsuite name="Unit">') && ! str_contains($content, "{$modulesPath}/*/tests/Unit")) {
            $content = preg_replace(
                '/(<testsuite name="Unit">\s*<directory[^>]*>.*?<\/directory>\s*)/',
                "$1<directory suffix=\"Test.php\">./{$modulesPath}/*/tests/Unit</directory>\n        ",
                $content
            );
            $needsUpdate = true;
        }

        if ($needsUpdate) {
            if ($this->confirm("Would you like to automatically configure PHPUnit to run module tests natively?", true)) {
                File::put($phpUnitPath, $content);
                $this->components->info('Configured phpunit to include module test directories.');
            }
        }
    }

    /**
     * Configure the test script in composer.json.
     */
    protected function configureTestScript(): void
    {
        $composerJsonPath = base_path('composer.json');

        if (! File::exists($composerJsonPath)) {
            return;
        }

        /** @var array<string, mixed> $composer */
        $composer = json_decode((string) File::get($composerJsonPath), true);

        $scripts = $composer['scripts'] ?? [];
        $testScript = $scripts['test'] ?? null;

        $needsUpdate = false;

        // If 'test' script doesn't exist, create it
        if (! $testScript) {
            if ($this->confirm('Would you like to add a "test" script to your composer.json?', true)) {
                $composer['scripts']['test'] = [
                    '@php artisan test',
                ];
                $needsUpdate = true;
            }
        }

        if ($needsUpdate) {
            File::put($composerJsonPath, (string) json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            $this->components->info('Updated composer.json "test" script.');
        }
    }
}
