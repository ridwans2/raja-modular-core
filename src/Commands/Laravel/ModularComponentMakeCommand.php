<?php

declare(strict_types=1);

namespace Ridwans2\RajaModularCore\Commands\Laravel;

use Ridwans2\RajaModularCore\Concerns\ModularCommand;
use Ridwans2\RajaModularCore\Concerns\ModularGenerator;
use Illuminate\Foundation\Console\ComponentMakeCommand;

/**
 * Console command to create a new modular Blade component.
 */
final class ModularComponentMakeCommand extends ComponentMakeCommand
{
    use ModularCommand, ModularGenerator;

    /**
     * Get the destination view path.
     *
     * @param string $path
     */
    protected function viewPath($path = ''): string
    {
        if ($this->isModular()) {
            return $this->getModuleRegistry()->resolvePath($this->getModule(), 'resources/views/'.str_replace('.', '/', $path).'.blade.php');
        }

        return parent::viewPath($path);
    }
}
