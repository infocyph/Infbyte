<?php

declare(strict_types=1);

namespace App\Http\Controllers;

final class HomeController
{
    /**
     * @return array<string, string>
     */
    public function index(): array
    {
        return [
            'framework' => 'Infbyte',
            'status' => 'ready',
        ];
    }
}
