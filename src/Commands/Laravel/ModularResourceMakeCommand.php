<?php

declare(strict_types=1);

namespace Ridwans2\RajaModularCore\Commands\Laravel;

use Ridwans2\RajaModularCore\Concerns\ModularCommand;
use Ridwans2\RajaModularCore\Concerns\ModularGenerator;
use Illuminate\Foundation\Console\ResourceMakeCommand;

/**
 * Console command to create a new modular resource class.
 */
final class ModularResourceMakeCommand extends ResourceMakeCommand
{
    use ModularCommand, ModularGenerator;
}
