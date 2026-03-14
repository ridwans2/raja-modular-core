<?php

declare(strict_types=1);

namespace Ridwans2\RajaModularCore\Commands\Laravel;

use Ridwans2\RajaModularCore\Concerns\ModularCommand;
use Ridwans2\RajaModularCore\Concerns\ModularGenerator;
use Illuminate\Foundation\Console\TraitMakeCommand;

/**
 * Console command to create a new modular trait.
 */
final class ModularTraitMakeCommand extends TraitMakeCommand
{
    use ModularCommand, ModularGenerator;
}
