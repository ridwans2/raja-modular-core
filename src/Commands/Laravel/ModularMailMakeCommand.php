<?php

declare(strict_types=1);

namespace Ridwans2\RajaModularCore\Commands\Laravel;

use Ridwans2\RajaModularCore\Concerns\ModularCommand;
use Ridwans2\RajaModularCore\Concerns\ModularGenerator;
use Illuminate\Foundation\Console\MailMakeCommand;

/**
 * Console command to create a new modular mail class.
 */
final class ModularMailMakeCommand extends MailMakeCommand
{
    use ModularCommand, ModularGenerator;
}
