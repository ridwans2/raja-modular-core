<?php

declare(strict_types=1);

namespace Ridwans2\RajaModularCore\Concerns;

use Ridwans2\RajaModularCore\ModuleRegistry;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;

trait HasResources
{
    /**
     * Register modular resources during the registration phase.
     */
    protected function registerModularResources(): void
    {
        $registry = $this->getModuleRegistry();
        $modules = $registry->getModules();

        foreach ($modules as $moduleName => $module) {
        }
    }

    /**
     * Boot modular resources during the booting phase.
     */
    protected function bootModularResources(): void
    {
        $registry = $this->getModuleRegistry();
        $modules = $registry->getModules();

        foreach ($modules as $moduleName => $module) {
            $lowerName = strtolower($moduleName);

            $this->loadModuleViews($moduleName, $lowerName, $registry);
            $this->loadModuleTranslations($moduleName, $lowerName, $registry);
            $this->loadModuleMigrations($moduleName, $registry);

            if (config('modular.discovery.policies', true)) {
                $this->discoverModulePolicies($moduleName, $registry);
            }

            if (config('modular.discovery.events', true)) {
                $this->discoverModuleEvents($moduleName, $registry);
            }
        }

        $this->registerThemerIntegration($registry, $modules);
    }

    /**
     * Discover and register policies within a module.
     */
    protected function discoverModulePolicies(string $moduleName, ModuleRegistry $registry): void
    {
        $cachedPolicies = $registry->getDiscoveredPolicies($moduleName);

        if (! empty($cachedPolicies)) {
            foreach ($cachedPolicies as $model => $policy) {
                \Illuminate\Support\Facades\Gate::policy($model, $policy);
                $registry->setDiscoverySource($moduleName, 'policies', $model, 'Cached/Explicit');
            }

            return;
        }

        $policyPath = $registry->resolvePath($moduleName, 'app/Policies');
        if (! is_dir($policyPath)) {
            return;
        }

        foreach (File::allFiles($policyPath) as $file) {
            $className = $file->getBasename('.php');
            $module = $registry->getModule($moduleName);
            $policyClass = rtrim($module['namespace'], '\\') . "\\Policies\\{$className}";

            if (class_exists($policyClass)) {
                $modelName = str_replace('Policy', '', $className);
                $modelClass = rtrim($module['namespace'], '\\') . "\\Models\\{$modelName}";

                if (class_exists($modelClass)) {
                    \Illuminate\Support\Facades\Gate::policy($modelClass, $policyClass);
                    $registry->setDiscoverySource($moduleName, 'policies', $modelClass, 'Convention');
                }
            }
        }
    }

    /**
     * Discover and register event listeners within a module.
     */
    protected function discoverModuleEvents(string $moduleName, ModuleRegistry $registry): void
    {
        $module = $registry->getModule($moduleName);

        // 1. Explicit event→listener map from module.json "events" key
        // Format: { "Events\\PostCreated": ["Listeners\\SendEmail", "Listeners\\LogPost"] }
        $explicitEvents = $module['events'] ?? [];

        if (! empty($explicitEvents)) {
            foreach ($explicitEvents as $event => $listeners) {
                foreach ((array) $listeners as $listener) {
                    if (class_exists($listener)) {
                        \Illuminate\Support\Facades\Event::listen($event, $listener);
                        $registry->setDiscoverySource($moduleName, 'events', "{$event}@{$listener}", 'Explicit/module.json');
                    }
                }
            }

            return;
        }

        // 2. Cached events (subscribers only)
        $cachedEvents = $registry->getDiscoveredEvents($moduleName);

        if (! empty($cachedEvents)) {
            foreach ($cachedEvents as $subscriber) {
                \Illuminate\Support\Facades\Event::subscribe($subscriber);
                $registry->setDiscoverySource($moduleName, 'events', $subscriber, 'Cached/Explicit');
            }

            return;
        }

        // 3. Convention-based: discover subscribers from app/Listeners/
        $eventsPath = $registry->resolvePath($moduleName, 'app/Listeners');
        if (! is_dir($eventsPath)) {
            return;
        }

        foreach (File::allFiles($eventsPath) as $file) {
            $className = $file->getBasename('.php');
            $listenerClass = rtrim($module['namespace'], '\\') . "\\Listeners\\{$className}";

            if (class_exists($listenerClass)) {
                if (method_exists($listenerClass, 'subscribe')) {
                    \Illuminate\Support\Facades\Event::subscribe($listenerClass);
                    $registry->setDiscoverySource($moduleName, 'events', $listenerClass, 'Convention');
                }
            }
        }
    }

    /**
     * Register integration with Laravel Themer if available.
     *
     * @param array<string, mixed> $modules
     */
    protected function registerThemerIntegration(ModuleRegistry $registry, array $modules): void
    {
        if (class_exists('Ridwans2\\Themer\\ThemeServiceProvider')) {
            /** @var \Ridwans2\Themer\ThemeServiceProvider $themer */
            $themer = app('Ridwans2\\Themer\\ThemeServiceProvider');

            if (class_exists('Ridwans2\\Themer\\Plugins\\ModulesPlugin')) {
                $pluginClass = 'Ridwans2\\Themer\\Plugins\\ModulesPlugin';
                $themer::registerPlugin(new $pluginClass());
            }
        }
    }

    /**
     * Load views and components for a specific module.
     */
    protected function loadModuleViews(string $moduleName, string $lowerName, ModuleRegistry $registry): void
    {
        // Performance optimization: check cache first
        if (config('modular.cache.enabled', false) || file_exists(config('modular.cache.path'))) {
            if (! $registry->hasViews($moduleName)) {
                return;
            }
        }

        $viewsPath = $registry->resolvePath($moduleName, 'resources/views');

        if (is_dir($viewsPath)) {
            $this->loadViewsFrom($viewsPath, $lowerName);

            $componentPath = $viewsPath . '/components';
            if (is_dir($componentPath)) {
                Blade::anonymousComponentPath($componentPath, $lowerName);
            }

            $module = $registry->getModule($moduleName);
            if ($module) {
                Blade::componentNamespace(rtrim($module['namespace'], '\\') . '\\View\\Components', $lowerName);
            }
        }
    }

    /**
     * Load translations for a specific module.
     */
    protected function loadModuleTranslations(string $moduleName, string $lowerName, ModuleRegistry $registry): void
    {
        // Performance optimization: check cache first
        if (config('modular.cache.enabled', false) || file_exists(config('modular.cache.path'))) {
            if (! $registry->hasTranslations($moduleName)) {
                return;
            }
        }

        $langPath = $registry->resolvePath($moduleName, 'lang');

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $lowerName);
        }
    }

    /**
     * Load migrations for a specific module.
     */
    protected function loadModuleMigrations(string $moduleName, ModuleRegistry $registry): void
    {
        // Performance optimization: check cache first
        if (config('modular.cache.enabled', false) || file_exists(config('modular.cache.path'))) {
            if (! $registry->hasMigrations($moduleName)) {
                return;
            }
        }

        $migrationPath = $registry->resolvePath($moduleName, 'database/migrations');

        if (is_dir($migrationPath)) {
            $this->loadMigrationsFrom($migrationPath);
        }
    }

    /**
     * Get the modular registry instance.
     */
    protected function getModuleRegistry(): ModuleRegistry
    {
        return $this->app->make(ModuleRegistry::class);
    }
}
