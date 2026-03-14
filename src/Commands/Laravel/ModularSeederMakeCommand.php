<?php

declare(strict_types=1);

namespace Ridwans2\RajaModularCore\Commands\Laravel;

use Ridwans2\RajaModularCore\Concerns\ModularCommand;
use Ridwans2\RajaModularCore\Concerns\ModularGenerator;
use Illuminate\Database\Console\Seeds\SeederMakeCommand;
use Illuminate\Support\Str;

/**
 * Console command to create a new modular seeder class.
 */
final class ModularSeederMakeCommand extends SeederMakeCommand
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

            return $this->getModuleRegistry()->resolvePath((string) $module, 'database/seeders/'.str_replace('\\', '/', $name).'.php');
        }

        return parent::getPath($name);
    }
}
