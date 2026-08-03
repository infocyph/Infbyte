<?php

declare(strict_types=1);

$basePath = dirname(__DIR__);
$environmentPath = $basePath . '/.env';

if (is_file($environmentPath)) {
    fwrite(STDOUT, "[INFO] Kept existing .env file.\n");

    return;
}

$examplePath = $basePath . '/.env.example';
$environment = file_get_contents($examplePath);

if (!is_string($environment)) {
    throw new RuntimeException('Unable to read .env.example.');
}

$replacements = 0;
$environment = preg_replace(
    '/^AUTH_TOKEN_SECRET=.*$/m',
    'AUTH_TOKEN_SECRET=' . bin2hex(random_bytes(32)),
    $environment,
    1,
    $replacements,
);

if (!is_string($environment) || $replacements !== 1) {
    throw new RuntimeException('Unable to provision AUTH_TOKEN_SECRET in .env.');
}

$previousUmask = umask(0077);

try {
    $handle = fopen($environmentPath, 'x');
} finally {
    umask($previousUmask);
}

if (!is_resource($handle)) {
    throw new RuntimeException('Unable to create .env.');
}

try {
    $length = strlen($environment);
    $written = 0;

    while ($written < $length) {
        $bytes = fwrite($handle, substr($environment, $written));
        if ($bytes === false || $bytes === 0) {
            throw new RuntimeException('Unable to write the complete .env file.');
        }
        $written += $bytes;
    }

    if (!fflush($handle)) {
        throw new RuntimeException('Unable to flush the complete .env file.');
    }
} catch (Throwable $exception) {
    fclose($handle);
    if (is_file($environmentPath)) {
        unlink($environmentPath);
    }

    throw $exception;
}

fclose($handle);
if (!chmod($environmentPath, 0600)) {
    unlink($environmentPath);

    throw new RuntimeException('Unable to restrict .env permissions to the project owner.');
}

fwrite(STDOUT, "[OK] Created .env with a random authentication token secret.\n");
