<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

beforeEach(function () {
    $this->modulePath = base_path('modules/RouteModule');
    @mkdir($this->modulePath . '/routes', 0755, true);
});

afterEach(function () {
    exec('rm -rf ' . escapeshellarg($this->modulePath));
});

it('registers modular routes with custom route prefix from module.json', function () {
    file_put_contents($this->modulePath . '/module.json', json_encode([
        'name' => 'RouteModule',
        'route_prefix' => 'api/v2',
    ]));

    file_put_contents($this->modulePath . '/routes/web.php', '<?php use Illuminate\Support\Facades\Route; Route::get("ping", fn() => "pong")->name("ping");');

    $registry = $this->app->make(\AlizHarb\Modular\ModuleRegistry::class);
    $registry->discoverModules();
    $registry->getActivator()->setStatus('RouteModule', true);

    // Force route loading via reflection
    $provider = new \AlizHarb\Modular\ModularServiceProvider($this->app);
    $reflection = new \ReflectionMethod($provider, 'registerModuleRoutes');
    $reflection->setAccessible(true);
    $reflection->invoke($provider);

    Route::getRoutes()->refreshNameLookups();
    $route = Route::getRoutes()->getByName('api/v2.ping');
    expect($route)->not->toBeNull();
    expect($route->uri())->toBe('api/v2/ping');
});
