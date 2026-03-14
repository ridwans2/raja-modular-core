<?php

declare(strict_types=1);

namespace Ridwans2\RajaModularCore\Commands\Laravel;

use Ridwans2\RajaModularCore\Concerns\ModularCommand;
use Ridwans2\RajaModularCore\Concerns\ModularGenerator;
use Illuminate\Foundation\Console\ConsoleMakeCommand;

/**
 * Console command to create a new modular Artisan command.
 */
final class ModularConsoleMakeCommand extends ConsoleMakeCommand
{
    use ModularCommand, ModularGenerator;
}
