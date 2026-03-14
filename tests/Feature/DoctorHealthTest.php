<?php

use Ridwans2\RajaModularCore\ModuleRegistry;
use Illuminate\Support\Facades\File;

afterEach(function () {
    File::deleteDirectory(base_path('modules'));
    File::delete(base_path('composer.json'));
    File::delete(base_path('config/modular.php'));
    File::deleteDirectory(public_path('modules'));
    app()->forgetInstance(ModuleRegistry::class);
});

it('modular:doctor shows health scores for modules', function () {
    File::ensureDirectoryExists(base_path('config'));
    File::put(base_path('composer.json'), json_encode([
        'autoload' => ['psr-4' => ['App\\' => 'app/', 'Modules\\' => 'modules/']],
    ]));
    File::put(base_path('config/modular.php'), '<?php return [];');
    File::ensureDirectoryExists(public_path('modules'));

    $modulePath = base_path('modules/HealthModule');
    File::ensureDirectoryExists($modulePath . '/database/migrations');
    File::ensureDirectoryExists($modulePath . '/tests/Feature');
    File::put($modulePath . '/module.json', json_encode([
        'name'    => 'HealthModule',
        'version' => '2.0.0',
        'authors' => [['name' => 'Test Author']],
    ]));
    File::put($modulePath . '/README.md', '# Health Module');
    File::put($modulePath . '/database/migrations/2024_01_01_000000_create_test.php', '<?php return new class{};');
    File::put($modulePath . '/tests/Feature/ExampleTest.php', '<?php');

    app()->forgetInstance(ModuleRegistry::class);

    $this->artisan('modular:doctor')
        ->expectsOutput('Running Modular Doctor...')
        ->expectsOutputToContain('Module Health Scores')
        ->assertExitCode(0);
});

it('modular:doctor gives low score for a bare module', function () {
    File::ensureDirectoryExists(base_path('config'));
    File::put(base_path('composer.json'), json_encode([
        'autoload' => ['psr-4' => ['App\\' => 'app/', 'Modules\\' => 'modules/']],
    ]));
    File::put(base_path('config/modular.php'), '<?php return [];');
    File::ensureDirectoryExists(public_path('modules'));

    $modulePath = base_path('modules/BareModule');
    File::ensureDirectoryExists($modulePath);
    File::put($modulePath . '/module.json', json_encode(['name' => 'BareModule']));

    app()->forgetInstance(ModuleRegistry::class);

    $this->artisan('modular:doctor')
        ->expectsOutputToContain('Module Health Scores')
        ->assertExitCode(0);
});

it('modular:doctor reports healthy when all requirements are met', function () {
    File::ensureDirectoryExists(base_path('config'));
    File::put(base_path('composer.json'), json_encode([
        'autoload' => ['psr-4' => ['App\\' => 'app/', 'Modules\\' => 'modules/']],
    ]));
    File::put(base_path('config/modular.php'), '<?php return [];');
    File::ensureDirectoryExists(public_path('modules'));

    $this->artisan('modular:doctor')
        ->expectsOutput('Running Modular Doctor...')
        ->assertExitCode(0);
});

it('modular:doctor warns when modules directory is missing', function () {
    config(['modular.paths.modules' => base_path('non-existent')]);

    $this->artisan('modular:doctor')
        ->expectsOutput('Doctor found some issues. Please review the warnings above.')
        ->assertExitCode(1);
});
