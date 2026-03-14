<?php

declare(strict_types=1);

namespace Ridwans2\RajaModularCore\Commands\Laravel;

use Ridwans2\RajaModularCore\Concerns\ModularCommand;
use Ridwans2\RajaModularCore\Concerns\ModularGenerator;
use Illuminate\Foundation\Console\RequestMakeCommand;

/**
 * Console command to create a new modular form request class.
 */
final class ModularRequestMakeCommand extends RequestMakeCommand
{
    use ModularCommand, ModularGenerator;
}
