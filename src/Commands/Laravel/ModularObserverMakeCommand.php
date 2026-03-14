<?php

declare(strict_types=1);

namespace Ridwans2\RajaModularCore\Commands\Laravel;

use Ridwans2\RajaModularCore\Concerns\ModularCommand;
use Ridwans2\RajaModularCore\Concerns\ModularGenerator;
use Illuminate\Foundation\Console\ObserverMakeCommand;

/**
 * Console command to create a new modular observer class.
 */
final class ModularObserverMakeCommand extends ObserverMakeCommand
{
    use ModularCommand, ModularGenerator;
}
