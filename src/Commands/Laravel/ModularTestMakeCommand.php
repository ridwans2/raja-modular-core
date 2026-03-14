<?php

declare(strict_types=1);

namespace Ridwans2\RajaModularCore\Commands\Laravel;

use Ridwans2\RajaModularCore\Concerns\ModularCommand;
use Ridwans2\RajaModularCore\Concerns\ModularGenerator;
use Illuminate\Foundation\Console\TestMakeCommand;
use Illuminate\Support\Str;

/**
 * Console command to create a new modular test class.
 */
final class ModularTestMakeCommand extends TestMakeCommand
{
    use ModularCommand, ModularGenerator;

    /**
     * Get the destination class path.
     *
     * @param string $name
     */
    protected function getPath($name): string
    {
        if ($this->isModular()) {
            $module = $this->getModule();
            $name = Str::replaceFirst($this->rootNamespace(), '', $name);

            return $this->getModuleRegistry()->resolvePath((string) $module, 'tests/'.str_replace('\\', '/', $name).'.php');
        }

        return parent::getPath($name);
    }

    /**
     * Get the root namespace for the class.
     */
    protected function rootNamespace(): string
    {
        if ($this->isModular()) {
            return "Modules\\{$this->getModule()}\\Tests\\";
        }

        return parent::rootNamespace();
    }
}
