<?php

declare(strict_types=1);

namespace AlizHarb\Modular\Tests\Feature;

use AlizHarb\Modular\ModuleRegistry;
use AlizHarb\Modular\Tests\TestCase;
use Illuminate\Support\Facades\File;

class CustomStubsTest extends TestCase
{
    private string $modulesPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->modulesPath = base_path('modules');
        File::ensureDirectoryExists($this->modulesPath);
        File::cleanDirectory($this->modulesPath);

        // Delete cache
        File::delete(base_path('bootstrap/cache/modular.php'));
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->modulesPath);
        parent::tearDown();
    }

    public function test_modular_generator_prioritizes_module_specific_stubs()
    {
        // 1. Create a dummy module
        $this->createModule('Shop');

        // 2. Create the stubs directory inside the module
        $stubsPath = "{$this->modulesPath}/Shop/stubs";
        File::ensureDirectoryExists($stubsPath);

        // 3. Put a custom stub inside the module overriding the controller stub
        $customContent = "<?php\n\n// THIS IS A CUSTOM SHOP STUB\nnamespace DummyNamespace;\n\nclass DummyClass {}\n";
        File::put("{$stubsPath}/controller.plain.stub", $customContent);

        // 4. Run the make:controller command
        $this->artisan('make:controller', [
            'name' => 'CustomShopController',
            '--module' => 'Shop'
        ]);

        // 5. Verify the generated file uses the custom stub
        $createdFile = "{$this->modulesPath}/Shop/app/Http/Controllers/CustomShopController.php";
        $this->assertTrue(File::exists($createdFile));

        $generatedContent = File::get($createdFile);
        $this->assertStringContainsString('// THIS IS A CUSTOM SHOP STUB', $generatedContent);
    }

    private function createModule(string $name): void
    {
        $path = "{$this->modulesPath}/{$name}";
        File::ensureDirectoryExists($path);

        File::ensureDirectoryExists($path . '/app/Providers');

        $providerContent = "<?php namespace Modules\\{$name}\\Providers; use Illuminate\\Support\\ServiceProvider; class {$name}ServiceProvider extends ServiceProvider {}";
        File::put($path . "/app/Providers/{$name}ServiceProvider.php", $providerContent);

        File::put("{$path}/module.json", json_encode([
            'name' => $name,
            'provider' => "Modules\\{$name}\\Providers\\{$name}ServiceProvider"
        ]));
    }
}
