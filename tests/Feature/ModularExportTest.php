<?php

use AlizHarb\Modular\ModuleRegistry;
use Illuminate\Support\Facades\File;

afterEach(function () {
    File::deleteDirectory(base_path('modules'));
    File::deleteDirectory(base_path('packages'));
    File::delete(base_path('composer.json'));
    app()->forgetInstance(ModuleRegistry::class);
});

it('modular:export --dry-run shows plan without writing files', function () {
    $modulePath = base_path('modules/ExportModule');
    File::ensureDirectoryExists($modulePath . '/app/Http/Controllers');
    File::put($modulePath . '/module.json', json_encode(['name' => 'ExportModule']));
    File::put($modulePath . '/composer.json', json_encode([
        'name'    => 'test/export-module',
        'require' => new stdClass,
    ]));

    app()->forgetInstance(ModuleRegistry::class);

    $targetPath = base_path('packages/ExportModule');

    $this->artisan('modular:export', [
        'module'    => 'ExportModule',
        '--path'    => $targetPath,
        '--dry-run' => true,
    ])
        ->expectsOutputToContain('[DRY RUN] No files will be written.')
        ->assertExitCode(0);

    expect(File::exists($targetPath))->toBeFalse();
});

it('modular:export fails when the module does not exist', function () {
    $this->artisan('modular:export', [
        'module'    => 'NonExistentModule',
        '--dry-run' => true,
    ])
        ->expectsOutputToContain('Module [NonExistentModule] not found.')
        ->assertExitCode(1);
});

it('modular:export copies module files to the target directory', function () {
    $modulePath = base_path('modules/CopyModule');
    File::ensureDirectoryExists($modulePath);
    File::put($modulePath . '/module.json', json_encode(['name' => 'CopyModule']));
    File::put($modulePath . '/composer.json', json_encode([
        'name'    => 'test/copy-module',
        'require' => new stdClass,
    ]));

    File::put(base_path('composer.json'), json_encode([
        'require' => new stdClass,
    ]));

    app()->forgetInstance(ModuleRegistry::class);

    $targetPath = base_path('packages/CopyModule');

    $this->artisan('modular:export', [
        'module' => 'CopyModule',
        '--path' => $targetPath,
    ])
        ->expectsConfirmation('Export module [CopyModule] to [' . $targetPath . ']?', 'yes')
        ->assertExitCode(0);

    expect(File::exists($targetPath . '/module.json'))->toBeTrue();
    expect(File::exists($targetPath . '/composer.json'))->toBeTrue();
});
