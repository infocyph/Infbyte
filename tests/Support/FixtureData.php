<?php

declare(strict_types=1);

namespace Tests\Support;

use JsonException;
use PHPUnit\Framework\Assert;

/**
 * Validates untyped fixture input before it reaches a typed test boundary.
 * Shared by the repository's Pest tests, including global-namespace tests.
 */
final class FixtureData
{
    /** @return array<string, mixed> */
    public static function map(mixed $value): array
    {
        Assert::assertIsArray($value);
        $map = [];
        foreach ($value as $key => $entry) {
            Assert::assertIsString($key);
            $map[$key] = $entry;
        }

        return $map;
    }

    /** @return list<array<string, mixed>> */
    public static function rows(mixed $value): array
    {
        Assert::assertIsArray($value);
        Assert::assertTrue(array_is_list($value));

        return array_map(self::map(...), $value);
    }

    public static function text(mixed $value): string
    {
        Assert::assertIsString($value);

        return $value;
    }

    /**
     * @return array<string, mixed>
     * @throws JsonException
     */
    public static function json(string $value): array
    {
        return self::map(json_decode($value, true, flags: JSON_THROW_ON_ERROR));
    }
}
