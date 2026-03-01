<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use Illuminate\View\Compilers\BladeCompiler;
use AlizHarb\Modular\ModuleRegistry;

afterEach(function () {
    exec('rm -rf ' . escapeshellarg(base_path('modules/ViewModule')));
});

it('registers class based blade components dynamically', function () {
    $modulePath = base_path('modules/ViewModule');
    @mkdir($modulePath . '/resources/views/components', 0755, true);

    file_put_contents($modulePath . '/module.json', json_encode([
        'name' => 'ViewModule',
    ]));

    $registry = $this->app->make(ModuleRegistry::class);
    $registry->discoverModules();
    $registry->getActivator()->setStatus('ViewModule', true);

    // Manually trigger resources boot
    $provider = new \AlizHarb\Modular\ModularServiceProvider($this->app);

    $reflection = new \ReflectionMethod($provider, 'bootModularResources');
    $reflection->setAccessible(true);
    $reflection->invoke($provider);

    /** @var BladeCompiler $compiler */
    $compiler = app('blade.compiler');

    $reflectionCompiler = new \ReflectionClass($compiler);
    $property = $reflectionCompiler->getProperty('classComponentNamespaces');
    $property->setAccessible(true);

    $namespaces = $property->getValue($compiler);

    expect($namespaces)->toHaveKey('viewmodule')
        ->and($namespaces['viewmodule'])->toBe('Modules\ViewModule\View\Components');
});
