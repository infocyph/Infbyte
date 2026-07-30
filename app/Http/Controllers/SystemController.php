<?php

declare(strict_types=1);

namespace App\Http\Controllers;

final readonly class SystemController
{
    /** @phpstan-return array{status:string} */
    public static function health(): array
    {
        return ['status' => 'ok'];
    }

    /** @phpstan-return array{memory:int} */
    public static function json(): array
    {
        return ['memory' => memory_get_usage(true)];
    }
}
