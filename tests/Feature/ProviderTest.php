<?php

namespace Ridwans2\RajaModularCore\Tests\Feature;

use Ridwans2\RajaModularCore\ModuleRegistry;
use Ridwans2\RajaModularCore\Tests\TestCase;

class ProviderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->app->singleton(ModuleRegistry::class, fn () => new ModuleRegistry());
    }

    public function test_it_registers_module_providers()
    {
        $provider = $this->app->getProvider(\Ridwans2\RajaModularCore\ModularServiceProvider::class);
        $this->assertTrue(method_exists($provider, 'registerModuleProviders'));
    }
}
