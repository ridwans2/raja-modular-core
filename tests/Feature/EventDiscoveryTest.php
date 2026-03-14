<?php

use Ridwans2\RajaModularCore\ModuleRegistry;
use Illuminate\Support\Facades\File;

afterEach(function () {
    File::deleteDirectory(base_path('modules'));
    app()->forgetInstance(ModuleRegistry::class);
});

it('registers explicit event→listener pairs defined in module.json', function () {
    $modulePath = base_path('modules/EventMapModule');
    File::ensureDirectoryExists($modulePath);

    File::put($modulePath . '/module.json', json_encode([
        'name'      => 'EventMapModule',
        'namespace' => 'Modules\\EventMapModule\\',
        'events'    => [
            'Modules\\EventMapModule\\Events\\PostCreated' => [
                'Modules\\EventMapModule\\Listeners\\SendWelcomeEmail',
            ],
        ],
    ]));

    app()->forgetInstance(ModuleRegistry::class);

    $registry = app(ModuleRegistry::class);
    $module   = $registry->getModule('EventMapModule');

    expect($module)->not->toBeNull()
        ->and($module['events'])->toHaveKey('Modules\\EventMapModule\\Events\\PostCreated');
});

it('convention-based subscriber discovery resolves classes with a subscribe method', function () {
    $modulePath = base_path('modules/SubscriberModule');
    File::ensureDirectoryExists($modulePath . '/app/Listeners');

    File::put($modulePath . '/module.json', json_encode([
        'name'      => 'SubscriberModule',
        'namespace' => 'Modules\\SubscriberModule\\',
        'events'    => [],
    ]));

    File::put($modulePath . '/app/Listeners/PostSubscriber.php', <<<'PHP'
<?php
namespace Modules\SubscriberModule\Listeners;

class PostSubscriber
{
    public function subscribe($events): void {}
}
PHP);

    app()->forgetInstance(ModuleRegistry::class);

    $registry = app(ModuleRegistry::class);
    expect($registry->getModule('SubscriberModule'))->not->toBeNull();
});

it('skips event registation when module has no events key and no listeners directory', function () {
    $modulePath = base_path('modules/EmptyEventsModule');
    File::ensureDirectoryExists($modulePath);
    File::put($modulePath . '/module.json', json_encode([
        'name'   => 'EmptyEventsModule',
        'events' => [],
    ]));

    app()->forgetInstance(ModuleRegistry::class);

    $registry = app(ModuleRegistry::class);
    expect($registry->getModule('EmptyEventsModule'))->not->toBeNull()
        ->and($registry->getDiscoveryInfo('EmptyEventsModule')['events'])->toBeEmpty();
});
