<?php

declare(strict_types=1);

namespace Ridwans2\RajaModularCore\Commands\Laravel;

use Ridwans2\RajaModularCore\Concerns\ModularCommand;
use Ridwans2\RajaModularCore\Concerns\ModularGenerator;
use Illuminate\Foundation\Console\ViewMakeCommand;

/**
 * Console command to create a new modular Blade view.
 */
final class ModularViewMakeCommand extends ViewMakeCommand
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
            $name = str_replace(['.', '::'], '/', $name);

            return $this->getModuleRegistry()->resolvePath((string) $module, 'resources/views/'.str_replace('\\', '/', $name).'.blade.php');
        }

        return parent::getPath($name);
    }
}
