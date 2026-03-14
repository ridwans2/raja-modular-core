<?php

declare(strict_types=1);

namespace Ridwans2\RajaModularCore\Commands\Laravel;

use Ridwans2\RajaModularCore\Concerns\ModularCommand;
use Ridwans2\RajaModularCore\Concerns\ModularGenerator;
use Illuminate\Routing\Console\ControllerMakeCommand;
use Symfony\Component\Console\Input\InputOption;

/**
 * Console command to create a new modular controller class.
 */
final class ModularControllerMakeCommand extends ControllerMakeCommand
{
    use ModularCommand, ModularGenerator;

    /**
     * Get the console command options.
     *
     * @return array<int, array|InputOption>
     */
    protected function getOptions(): array
    {
        return array_merge(parent::getOptions(), [
            ['module', null, InputOption::VALUE_REQUIRED, 'The module to create the controller in'],
        ]);
    }
}
