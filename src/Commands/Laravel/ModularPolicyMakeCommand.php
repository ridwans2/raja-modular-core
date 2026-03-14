<?php

declare(strict_types=1);

namespace Ridwans2\RajaModularCore\Commands\Laravel;

use Ridwans2\RajaModularCore\Concerns\ModularCommand;
use Ridwans2\RajaModularCore\Concerns\ModularGenerator;
use Illuminate\Foundation\Console\PolicyMakeCommand;

/**
 * Console command to create a new modular policy class.
 */
final class ModularPolicyMakeCommand extends PolicyMakeCommand
{
    use ModularCommand, ModularGenerator;
}
