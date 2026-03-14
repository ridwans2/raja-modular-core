<?php

declare(strict_types=1);

namespace Ridwans2\RajaModularCore\Commands\Laravel;

use Ridwans2\RajaModularCore\Concerns\ModularCommand;
use Ridwans2\RajaModularCore\Concerns\ModularGenerator;
use Illuminate\Routing\Console\MiddlewareMakeCommand;

/**
 * Console command to create a new modular middleware class.
 */
final class ModularMiddlewareMakeCommand extends MiddlewareMakeCommand
{
    use ModularCommand, ModularGenerator;
}
