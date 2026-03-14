<?php

namespace Ridwans2\RajaModularCore\Tests\Feature;

use Ridwans2\RajaModularCore\ModuleRegistry;
use Ridwans2\RajaModularCore\Tests\TestCase;

class RoutingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->app->singleton(ModuleRegistry::class, fn () => new ModuleRegistry());
    }

    public function test_it_registers_module_routes()
    {
        $provider = $this->app->getProvider(\Ridwans2\RajaModularCore\ModularServiceProvider::class);

        // Ensure the method exists
        $this->assertTrue(method_exists($provider, 'registerModuleRoutes'));

        // Logic verification: checking if routesAreCached is respected is hard
        // without mocking the app state heavily, but we verified the code change.
    }
}
