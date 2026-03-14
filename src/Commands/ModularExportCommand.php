<?php

declare(strict_types=1);

namespace Ridwans2\RajaModularCore\Commands;

use Ridwans2\RajaModularCore\ModuleRegistry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Console command to export a module as a standalone Composer package.
 */
final class ModularExportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'modular:export
                            {module : The name of the module to export}
                            {--path= : The target directory to export the module into}
                            {--dry-run : Show what would be done without actually exporting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export a module as a standalone Composer package';

    /**
     * Execute the console command.
     */
    public function handle(ModuleRegistry $registry): int
    {
        $moduleName = (string) $this->argument('module');
        $module = $registry->getModule($moduleName);

        if (! $module) {
            $this->components->error("Module [{$moduleName}] not found.");

            return self::FAILURE;
        }

        $defaultTarget = base_path("packages/{$moduleName}");
        $targetPath = (string) ($this->option('path') ?: $defaultTarget);
        $isDryRun = (bool) $this->option('dry-run');

        $this->components->info("Exporting module [{$moduleName}]...");
        $this->line("  Source: <fg=cyan>{$module['path']}</>");
        $this->line("  Target: <fg=cyan>{$targetPath}</>");
        $this->newLine();

        if ($isDryRun) {
            $this->components->warn('[DRY RUN] No files will be written.');
            $this->showExportPlan($module, $targetPath);

            return self::SUCCESS;
        }

        if (File::exists($targetPath)) {
            if (! $this->confirm("Target path [{$targetPath}] already exists. Overwrite?", false)) {
                $this->components->warn('Export cancelled.');

                return self::FAILURE;
            }
        }

        if (! $this->confirm("Export module [{$moduleName}] to [{$targetPath}]?", true)) {
            $this->components->warn('Export cancelled.');

            return self::FAILURE;
        }

        // Copy the module directory to the target path
        File::copyDirectory($module['path'], $targetPath);

        // Ensure standalone composer.json has no merge-plugin leftover
        $this->normalizeComposerJson($targetPath, $module, $moduleName);

        // Register as a path repository in root composer.json
        $this->registerPathRepository($targetPath, $moduleName);

        $this->components->info("Module [{$moduleName}] exported successfully!");
        $this->line('');
        $this->line('Next steps:');
        $this->line("  1. Run <fg=cyan>composer update</> to update the path repository.");
        $this->line("  2. Remove the module directory from <fg=cyan>modules/{$moduleName}</> if desired.");
        $this->line("  3. The module is now a standalone package at <fg=cyan>{$targetPath}</>");

        return self::SUCCESS;
    }

    /**
     * Show what would be exported.
     *
     * @param array<string, mixed> $module
     */
    private function showExportPlan(array $module, string $targetPath): void
    {
        $files = File::allFiles($module['path']);
        $this->line('Files to export:');
        foreach ($files as $file) {
            $relative = str_replace($module['path'] . '/', '', $file->getPathname());
            $this->line("  <fg=gray>{$relative}</>");
        }
        $this->newLine();
        $this->line("Would register path repository: <fg=cyan>{$targetPath}</>");
    }

    /**
     * Normalize the exported module's composer.json for standalone use.
     *
     * @param array<string, mixed> $module
     */
    private function normalizeComposerJson(string $targetPath, array $module, string $moduleName): void
    {
        $composerPath = $targetPath . '/composer.json';

        if (! File::exists($composerPath)) {
            return;
        }

        $composer = json_decode((string) File::get($composerPath), true) ?? [];

        // Remove merge-plugin settings (irrelevant for standalone packages)
        unset($composer['extra']['merge-plugin']);

        // Ensure the package has a proper name if missing
        if (empty($composer['name'])) {
            $vendor = config('modular.composer.vendor', 'vendor');
            $composer['name'] = $vendor . '/' . strtolower($moduleName);
        }

        File::put(
            $composerPath,
            (string) json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }

    /**
     * Register the exported module as a path repository in the root composer.json.
     */
    private function registerPathRepository(string $targetPath, string $moduleName): void
    {
        $composerJsonPath = base_path('composer.json');

        if (! File::exists($composerJsonPath)) {
            return;
        }

        $composer = json_decode((string) File::get($composerJsonPath), true) ?? [];
        $repositories = $composer['repositories'] ?? [];

        $relativePath = str_replace(base_path() . '/', '', $targetPath);

        // Avoid duplicates
        foreach ($repositories as $repo) {
            if (($repo['url'] ?? '') === $relativePath) {
                return;
            }
        }

        $repositories[] = [
            'type' => 'path',
            'url' => $relativePath,
        ];

        $composer['repositories'] = $repositories;

        File::put(
            $composerJsonPath,
            (string) json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }
}
