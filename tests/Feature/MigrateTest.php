<?php

use Ridwans2\RajaModularCore\ModuleRegistry;
use Illuminate\Support\Facades\File;

afterEach(function () {
    File::deleteDirectory(base_path('modules'));
    app()->forgetInstance(ModuleRegistry::class);
});

it('runs migrations for a single module', function () {
    $modulePath = base_path('modules/MigrateModule');
    File::ensureDirectoryExists($modulePath . '/database/migrations');
    File::put($modulePath . '/module.json', json_encode(['name' => 'MigrateModule']));

    app()->forgetInstance(ModuleRegistry::class);

    $this->artisan('modular:migrate MigrateModule')
        ->expectsOutputToContain('Migrating module: MigrateModule')
        ->assertExitCode(0);
});

it('rolls back migrations for a module with --rollback flag', function () {
    $modulePath = base_path('modules/RollbackModule');
    File::ensureDirectoryExists($modulePath . '/database/migrations');
    File::put($modulePath . '/module.json', json_encode(['name' => 'RollbackModule']));

    app()->forgetInstance(ModuleRegistry::class);

    // Create the migrations table in the test DB first
    $this->artisan('migrate:install');

    $this->artisan('modular:migrate', [
        'module'     => 'RollbackModule',
        '--rollback' => true,
    ])
        ->expectsOutputToContain('Rolling back module: RollbackModule')
        ->assertExitCode(0);
});

it('rolls back a specific number of steps with --step', function () {
    $modulePath = base_path('modules/StepModule');
    File::ensureDirectoryExists($modulePath . '/database/migrations');
    File::put($modulePath . '/module.json', json_encode(['name' => 'StepModule']));

    app()->forgetInstance(ModuleRegistry::class);

    // Create the migrations table in the test DB first
    $this->artisan('migrate:install');

    $this->artisan('modular:migrate', [
        'module'     => 'StepModule',
        '--rollback' => true,
        '--step'     => 2,
    ])
        ->expectsOutputToContain('Rolling back module: StepModule (step: 2)')
        ->assertExitCode(0);
});

it('warns when a module has no migrations directory', function () {
    $modulePath = base_path('modules/NoMigrationsModule');
    File::ensureDirectoryExists($modulePath);
    File::put($modulePath . '/module.json', json_encode(['name' => 'NoMigrationsModule']));

    app()->forgetInstance(ModuleRegistry::class);

    $this->artisan('modular:migrate NoMigrationsModule')
        ->expectsOutputToContain('No migrations found for module: NoMigrationsModule')
        ->assertExitCode(0);
});
