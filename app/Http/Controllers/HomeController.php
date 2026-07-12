<?php

declare(strict_types=1);

namespace App\Http\Controllers;

final class HomeController
{
    /**
     * @phpstan-return array{framework: string, status: string}
     */
    public function index(): array
    {
        return [
            'framework' => 'Infbyte',
            'status' => 'ready',
        ];
    }
}
