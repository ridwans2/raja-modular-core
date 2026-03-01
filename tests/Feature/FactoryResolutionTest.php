<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Factories\Factory;

it('customizes eloquent factory names for modular models', function () {
    $factoryName = Factory::resolveFactoryName('Modules\Shop\Models\Order');
    expect($factoryName)->toBe('Modules\Shop\Database\Factories\OrderFactory');

    $factoryNameDefault = Factory::resolveFactoryName('App\Models\User');
    expect($factoryNameDefault)->toBe('Database\Factories\UserFactory');
});
