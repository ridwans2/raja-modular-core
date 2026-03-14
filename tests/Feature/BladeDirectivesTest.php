<?php

use Ridwans2\RajaModularCore\ModuleRegistry;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;

afterEach(function () {
    File::deleteDirectory(base_path('modules'));
    app()->forgetInstance(ModuleRegistry::class);
});

it('moduleEnabled directive returns true for an enabled module', function () {
    $this->artisan('make:module', ['name' => 'BladeTestModule']);
    app()->forgetInstance(ModuleRegistry::class);

    expect(Blade::check('moduleEnabled', 'BladeTestModule'))->toBeTrue();
});

it('moduleEnabled directive returns false for a non-existent module', function () {
    expect(Blade::check('moduleEnabled', 'GhostModule'))->toBeFalse();
});

it('moduleDisabled directive returns true for a non-existent module', function () {
    expect(Blade::check('moduleDisabled', 'GhostModule'))->toBeTrue();
});

it('moduleDisabled directive returns false for an enabled module', function () {
    $this->artisan('make:module', ['name' => 'BladeEnabledModule']);
    app()->forgetInstance(ModuleRegistry::class);

    expect(Blade::check('moduleDisabled', 'BladeEnabledModule'))->toBeFalse();
});
