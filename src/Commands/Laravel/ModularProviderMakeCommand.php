<?php

declare(strict_types=1);

namespace Ridwans2\RajaModularCore\Commands\Laravel;

use Ridwans2\RajaModularCore\Concerns\ModularCommand;
use Ridwans2\RajaModularCore\Concerns\ModularGenerator;
use Illuminate\Foundation\Console\ProviderMakeCommand;

/**
 * Console command to create a new modular service provider class.
 */
final class ModularProviderMakeCommand extends ProviderMakeCommand
{
    use ModularCommand, ModularGenerator;
}
