<?php

declare(strict_types=1);

namespace Ridwans2\RajaModularCore\Commands\Laravel;

use Ridwans2\RajaModularCore\Concerns\ModularCommand;
use Ridwans2\RajaModularCore\Concerns\ModularGenerator;
use Illuminate\Foundation\Console\InterfaceMakeCommand;

/**
 * Console command to create a new modular interface.
 */
final class ModularInterfaceMakeCommand extends InterfaceMakeCommand
{
    use ModularCommand, ModularGenerator;
}
