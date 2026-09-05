<?php

declare(strict_types=1);

it('keeps native HTTP adaptation and emission out of the Infbyte entrypoint', function (): void {
    $entrypoint = file_get_contents(dirname(__DIR__, 2) . '/public/index.php');

    expect($entrypoint)->toBeString()
        ->and($entrypoint)->toContain('FoundationReleaseBootstrap::fromEnvironment')
        ->and($entrypoint)->toContain('->web($config)->server->handle()')
        ->and($entrypoint)->not->toContain('Request::fromGlobals')
        ->and($entrypoint)->not->toContain('AutoEmitter')
        ->and($entrypoint)->not->toContain('->emit(');
});
