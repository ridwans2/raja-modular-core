<?php

declare(strict_types=1);

namespace AlizHarb\Modular\Commands;

use AlizHarb\Modular\ModuleRegistry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Gate;

/**
 * Console command to list all modules and their discovered resources.
 */
final class ModularListCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'modular:list
                            {--only= : Only show a specific type [modules, policies, events]}
                            {--tree : Display module dependency tree}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all modules and their discovered resources';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $registry = app(ModuleRegistry::class);
        $modules = $registry->getModules();
        $only = $this->option('only');

        if (empty($modules)) {
            $this->components->warn('No modules found.');

            return self::SUCCESS;
        }

        if ($this->option('tree')) {
            return $this->displayTree($modules, $registry);
        }

        if (! $only || $only === 'modules') {
            $this->displayModules($modules, $registry);
        }

        if (! $only || $only === 'policies') {
            $this->displayPolicies($modules, $registry);
        }

        if (! $only || $only === 'events') {
            $this->displayEvents($modules, $registry);
        }

        return self::SUCCESS;
    }

    /**
     * Display an ASCII dependency tree for all modules.
     *
     * @param array<string, array<string, mixed>> $modules
     */
    private function displayTree(array $modules, ModuleRegistry $registry): int
    {
        $this->components->info('Module Dependency Tree');
        $this->newLine();

        // Find root modules (modules with no dependents pointing to them from within the set)
        $allDeps = [];
        foreach ($modules as $name => $module) {
            foreach ($module['requires'] ?? [] as $dep) {
                $allDeps[] = explode(':', $dep)[0];
            }
        }

        $roots = array_filter(array_keys($modules), fn($name) => ! in_array($name, $allDeps));

        if (empty($roots)) {
            // Fallback: circular or no deps — print all as roots
            $roots = array_keys($modules);
        }

        $printed = [];
        foreach ($roots as $root) {
            $this->printTreeNode($root, $modules, $registry, $printed, 0);
        }

        return self::SUCCESS;
    }

    /**
     * Recursively print a module and its dependents.
     *
     * @param array<string, array<string, mixed>> $modules
     * @param array<string, bool> $printed
     */
    private function printTreeNode(string $name, array $modules, ModuleRegistry $registry, array &$printed, int $depth): void
    {
        if (isset($printed[$name])) {
            return;
        }

        $printed[$name] = true;
        $module = $modules[$name] ?? null;
        $version = $module['version'] ?? '?';
        $enabled = $registry->isEnabled($name);
        $status = $enabled ? '<fg=green>●</>' : '<fg=red>●</>';

        $prefix = $depth === 0 ? '' : str_repeat('  ', $depth - 1) . '└─ ';
        $this->line("{$prefix}{$status} <info>{$name}</info> <fg=gray>(v{$version})</>");

        $requires = $module['requires'] ?? [];
        foreach ($requires as $dep) {
            $depName = explode(':', $dep)[0];
            $this->printTreeNode($depName, $modules, $registry, $printed, $depth + 1);
        }
    }

    /**
     * Display the list of modules.
     *
     * @param array<string, array<string, mixed>> $modules
     */
    private function displayModules(array $modules, ModuleRegistry $registry): void
    {
        $this->components->info('Registered Modules');

        $rows = [];
        foreach ($modules as $name => $module) {
            $rows[] = [
                $name,
                $module['version'] ?? '1.0.0',
                $module['namespace'],
                str_replace(base_path() . '/', '', $module['path']),
                $registry->isEnabled($name) ? '<fg=green>✓ Enabled</>' : '<fg=red>✗ Disabled</>',
                ($module['has_migrations'] ?? false) ? '✅' : '❌',
            ];
        }

        $this->table(['Name', 'Version', 'Namespace', 'Path', 'Status', 'Migrations'], $rows);
        $this->line('');
    }

    /**
     * Display discovered policies.
     *
     * @param array<string, array<string, mixed>> $modules
     */
    private function displayPolicies(array $modules, ModuleRegistry $registry): void
    {
        $this->components->info('Discovered Policies');

        $rows = [];
        foreach ($modules as $name => $module) {
            $info = $registry->getDiscoveryInfo($name);
            foreach ($info['policies'] as $model => $source) {
                $policy = 'None';
                if (class_exists($model)) {
                    $policyClass = Gate::getPolicyFor($model);
                    $policy = $policyClass ? (is_string($policyClass) ? $policyClass : get_class($policyClass)) : 'None';
                }

                $rows[] = [$name, $model, $policy, $source];
            }
        }

        if (empty($rows)) {
            $this->line('No policies discovered.');
        } else {
            $this->table(['Module', 'Model', 'Policy', 'Source'], $rows);
        }
        $this->line('');
    }

    /**
     * Display discovered events/listeners.
     *
     * @param array<string, array<string, mixed>> $modules
     */
    private function displayEvents(array $modules, ModuleRegistry $registry): void
    {
        $this->components->info('Discovered Events/Subscribers');

        $rows = [];
        foreach ($modules as $name => $module) {
            $info = $registry->getDiscoveryInfo($name);
            foreach ($info['events'] as $listener => $source) {
                $rows[] = [$name, $listener, $source];
            }
        }

        if (empty($rows)) {
            $this->line('No events discovered.');
        } else {
            $this->table(['Module', 'Listener/Subscriber', 'Source'], $rows);
        }
        $this->line('');
    }
}
