<?php

declare(strict_types=1);

namespace Ridwans2\RajaModularCore\Tests\Feature;

use Ridwans2\RajaModularCore\ModuleRegistry;
use Ridwans2\RajaModularCore\Tests\TestCase;
use Illuminate\Support\Facades\File;

class TopologicalSortTest extends TestCase
{
    private string $modulesPath;
    private ModuleRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();

        $this->modulesPath = base_path('modules');
        File::ensureDirectoryExists($this->modulesPath);

        // Delete existing modules to ensure a clean state
        File::cleanDirectory($this->modulesPath);

        // Clear cache
        File::delete(base_path('bootstrap/cache/modular.php'));
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->modulesPath);
        parent::tearDown();
    }

    public function test_modules_are_sorted_topologically_by_dependencies()
    {
        // ModuleA requires ModuleB
        // ModuleB requires ModuleC
        // ModuleC has no dependencies
        // Expected Boot Order: C, B, A

        $this->createModule('ModuleA', ['ModuleB']);
        $this->createModule('ModuleB', ['ModuleC']);
        $this->createModule('ModuleC', []);

        $this->registry = new ModuleRegistry();
        $modules = $this->registry->getModules();

        $names = array_keys($modules);

        // Assert order
        $this->assertEquals(['ModuleC', 'ModuleB', 'ModuleA'], $names);
    }

    public function test_circular_dependency_throws_exception()
    {
        $this->createModule('Alpha', ['Beta']);
        $this->createModule('Beta', ['Alpha']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Circular dependency detected involving module');

        $this->registry = new ModuleRegistry();
        $this->registry->getModules();
    }

    private function createModule(string $name, array $requires): void
    {
        $path = "{$this->modulesPath}/{$name}";
        File::ensureDirectoryExists($path);

        File::put("{$path}/module.json", json_encode([
            'name' => $name,
            'requires' => $requires
        ]));
    }
}
