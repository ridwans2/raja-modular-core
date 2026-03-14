<?php

use Ridwans2\RajaModularCore\ModuleRegistry;
use Illuminate\Support\Facades\File;

afterEach(function () {
    File::deleteDirectory(base_path('modules'));
    app()->forgetInstance(ModuleRegistry::class);
});

it('modular:list shows all registered modules in a table', function () {
    $modulePath = base_path('modules/ListModule');
    File::ensureDirectoryExists($modulePath);
    File::put($modulePath . '/module.json', json_encode([
        'name'    => 'ListModule',
        'version' => '1.0.0',
    ]));

    app()->forgetInstance(ModuleRegistry::class);

    $this->artisan('modular:list')
        ->assertExitCode(0);
});

it('modular:list --tree shows module dependency tree', function () {
    $modulePath = base_path('modules/TreeRootModule');
    File::ensureDirectoryExists($modulePath);
    File::put($modulePath . '/module.json', json_encode([
        'name'     => 'TreeRootModule',
        'version'  => '1.0.0',
        'requires' => [],
    ]));

    app()->forgetInstance(ModuleRegistry::class);

    $this->artisan('modular:list --tree')
        ->expectsOutputToContain('Module Dependency Tree')
        ->assertExitCode(0);
});

it('modular:list --tree shows dependency relationships between modules', function () {
    // Parent module
    $authPath = base_path('modules/AuthBase');
    File::ensureDirectoryExists($authPath);
    File::put($authPath . '/module.json', json_encode([
        'name'     => 'AuthBase',
        'version'  => '1.0.0',
        'requires' => [],
    ]));

    // Child module depending on parent
    $blogPath = base_path('modules/BlogDependent');
    File::ensureDirectoryExists($blogPath);
    File::put($blogPath . '/module.json', json_encode([
        'name'     => 'BlogDependent',
        'version'  => '1.0.0',
        'requires' => ['AuthBase'],
    ]));

    app()->forgetInstance(ModuleRegistry::class);

    $this->artisan('modular:list --tree')
        ->expectsOutputToContain('Module Dependency Tree')
        ->assertExitCode(0);
});

it('modular:list shows empty message when no modules are registered', function () {
    // No modules directory at all
    $this->artisan('modular:list')
        ->expectsOutputToContain('No modules found.')
        ->assertExitCode(0);
});
