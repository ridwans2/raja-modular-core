<?php

declare(strict_types=1);

namespace Ridwans2\RajaModularCore\Commands\Laravel;

use Ridwans2\RajaModularCore\Concerns\ModularCommand;
use Ridwans2\RajaModularCore\Concerns\ModularGenerator;
use Illuminate\Foundation\Console\ChannelMakeCommand;

/**
 * Console command to create a new modular broadcast channel class.
 */
final class ModularChannelMakeCommand extends ChannelMakeCommand
{
    use ModularCommand, ModularGenerator;
}
