<?php

declare(strict_types=1);

namespace Ridwans2\RajaModularCore\Commands\Laravel;

use Ridwans2\RajaModularCore\Concerns\ModularCommand;
use Ridwans2\RajaModularCore\Concerns\ModularGenerator;
use Illuminate\Foundation\Console\CastMakeCommand;

/**
 * Console command to create a new modular Eloquent cast class.
 */
final class ModularCastMakeCommand extends CastMakeCommand
{
    use ModularCommand, ModularGenerator;
}
