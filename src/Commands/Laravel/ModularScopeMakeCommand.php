<?php

declare(strict_types=1);

namespace Ridwans2\RajaModularCore\Commands\Laravel;

use Ridwans2\RajaModularCore\Concerns\ModularCommand;
use Ridwans2\RajaModularCore\Concerns\ModularGenerator;
use Illuminate\Foundation\Console\ScopeMakeCommand;

/**
 * Console command to create a new modular query scope.
 */
final class ModularScopeMakeCommand extends ScopeMakeCommand
{
    use ModularCommand, ModularGenerator;
}
