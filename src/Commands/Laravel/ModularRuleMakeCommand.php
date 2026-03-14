<?php

declare(strict_types=1);

namespace Ridwans2\RajaModularCore\Commands\Laravel;

use Ridwans2\RajaModularCore\Concerns\ModularCommand;
use Ridwans2\RajaModularCore\Concerns\ModularGenerator;
use Illuminate\Foundation\Console\RuleMakeCommand;

/**
 * Console command to create a new modular validation rule.
 */
final class ModularRuleMakeCommand extends RuleMakeCommand
{
    use ModularCommand, ModularGenerator;
}
