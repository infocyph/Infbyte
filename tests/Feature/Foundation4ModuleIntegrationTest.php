<?php

declare(strict_types=1);

use Infocyph\Foundation\Config\ConfigRepository;
use Infocyph\Foundation\Module\ModuleArchitectureCatalog;
use Infocyph\Foundation\Module\ModuleFeatureCapabilityPolicy;
use Infocyph\Foundation\Module\ModuleFeatureCommandCatalog;
use Infocyph\Foundation\Module\ModuleFeaturePlanner;

/** @return array<string, mixed> */
function infbyteFeatureSnapshot(array $config = []): array
{
    return [
        'root_known' => true,
        'root_requirements' => ['infocyph/foundation' => 'dev-feature/foundation-3'],
        'installed' => [],
        'enabled' => [],
        'selected_features' => [],
        'config' => $config,
        'extensions' => get_loaded_extensions(),
        'published_configs' => [],
    ];
}

it('exposes exactly eight qualified native features without implicit group installation', function (): void {
    $catalog = new ModuleFeatureCommandCatalog();
    $inventory = $catalog->inventory();

    expect($inventory['schema_version'])->toBe(2)
        ->and(array_column($inventory['modules'], 'name'))->toBe(['data', 'security', 'execution'])
        ->and(array_merge(...array_column($inventory['modules'], 'features')))->toBe([
            'data.database',
            'data.filesystem',
            'security.crypto',
            'security.validation',
            'security.mfa',
            'security.passkey',
            'execution.messaging',
            'execution.communication',
        ])
        ->and($catalog->selection('data.database'))->toBe(['data.database'])
        ->and($catalog->selection('security', ['passkey']))->toBe(['security.passkey']);

    expect(fn() => $catalog->selection('data'))->toThrow(InvalidArgumentException::class)
        ->and(fn() => $catalog->selection('database'))->toThrow(InvalidArgumentException::class)
        ->and(fn() => $catalog->selection('execution.runtime'))->toThrow(InvalidArgumentException::class);
});

it('keeps optional package activation strictly separated from Foundation Core capabilities', function (): void {
    $config = require dirname(__DIR__, 2) . '/config/modules.php';
    $selection = ModuleFeatureCapabilityPolicy::fromConfig(new ConfigRepository(['modules' => $config]));

    expect($config)->toBe(['features' => []])
        ->and($selection)->toBe([])
        ->and(ModuleFeatureCapabilityPolicy::permits('runwire', $selection))->toBeTrue()
        ->and(ModuleFeatureCapabilityPolicy::permits('cache', $selection))->toBeTrue()
        ->and(ModuleFeatureCapabilityPolicy::permits('database', $selection))->toBeFalse()
        ->and(ModuleFeatureCapabilityPolicy::permits('messaging', $selection))->toBeFalse()
        ->and(ModuleFeatureCapabilityPolicy::selected('security.passkey', $selection))->toBeFalse();
});

it('plans selected packages and configurations without implicitly activating them', function (): void {
    $planner = new ModuleFeaturePlanner();
    $plan = $planner->plan('install', ['data.database'], infbyteFeatureSnapshot());

    expect($plan['schema_version'])->toBe(2)
        ->and($plan['requested'])->toBe(['data.database'])
        ->and($plan['effects']['require'])->toBe(['infocyph/dblayer' => '^6.1.1'])
        ->and($plan['effects']['publish'])->toBe(['database.php'])
        ->and($plan['effects']['activate'])->toBe([])
        ->and($plan['effects']['schemas'])->toBe([]);

    $unowned = infbyteFeatureSnapshot();
    $unowned['root_known'] = false;
    $blocked = $planner->plan('install', ['data.database'], $unowned);

    expect($blocked['applicable'])->toBeFalse()
        ->and(implode(' ', $blocked['blockers']))->toContain('Composer ownership');
});

it('honors OTP, passkey, durable messaging and validation prerequisites', function (): void {
    $planner = new ModuleFeaturePlanner();

    $otp = $planner->plan('install', ['security.mfa'], infbyteFeatureSnapshot([
        'auth' => ['drivers' => ['mfa' => 'otp']],
    ]));
    $passkey = $planner->plan('install', ['security.passkey'], infbyteFeatureSnapshot());
    $durable = $planner->plan('install', ['execution.messaging'], infbyteFeatureSnapshot([
        'messaging' => ['durable' => ['enabled' => true]],
    ]));
    $validation = $planner->plan('install', ['security.validation'], infbyteFeatureSnapshot([
        'validation' => ['database_connection' => 'primary'],
    ]));

    expect($otp['required'])->toContain('security.crypto')
        ->and($passkey['required'])->toBe(['security.passkey'])
        ->and($passkey['effects']['require'])->toHaveKeys(['infocyph/otp', 'web-auth/webauthn-lib'])
        ->and($durable['required'])->toContain('data.database')
        ->and($validation['required'])->toContain('data.database');
});

it('preserves shared OTP package ownership when a sibling feature is removed', function (): void {
    $context = infbyteFeatureSnapshot();
    $context['root_requirements']['infocyph/otp'] = '^7.0';
    $context['installed']['infocyph/otp'] = '7.0.0';
    $context['selected_features'] = ['security.mfa', 'security.passkey'];

    $plan = new ModuleFeaturePlanner()->plan('remove', ['security.mfa'], $context);

    expect($plan['schema_version'])->toBe(2)
        ->and($plan['effects']['remove'])->not->toContain('infocyph/otp');
});

it('uses Foundation feature-owned configuration rather than installing Core as a module', function (): void {
    $catalog = new ModuleArchitectureCatalog();
    $modules = $catalog->all();

    expect($modules['data']['features']['database']['config'])->toBe(['database.php'])
        ->and($modules['execution']['features']['messaging']['config'])->toBe(['messaging.php'])
        ->and($modules['security']['features']['mfa']['config'])->toBe([])
        ->and($modules['security']['features']['passkey']['config'])->toBe([])
        ->and(array_keys($modules))->not->toContain('core', 'cache', 'runwire', 'phpforge');
});
