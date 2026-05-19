<?php
/**
 * Lightweight .env loader (no Composer dependency).
 * Place .env in the project root (same folder as this file).
 */

if (!function_exists('load_env')) {
    function load_env(): void
    {
        static $loaded = false;
        if ($loaded) {
            return;
        }
        $loaded = true;

        $envFile = __DIR__ . DIRECTORY_SEPARATOR . '.env';
        if (!is_readable($envFile)) {
            return;
        }

        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') {
                continue;
            }
            if (strpos($line, '=') === false) {
                continue;
            }

            [$name, $value] = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            $len = strlen($value);
            if (
                $len >= 2
                && (
                    ($value[0] === '"' && $value[$len - 1] === '"')
                    || ($value[0] === "'" && $value[$len - 1] === "'")
                )
            ) {
                $value = substr($value, 1, -1);
            }

            if ($name === '' || array_key_exists($name, $_ENV)) {
                continue;
            }

            putenv("$name=$value");
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

if (!function_exists('env')) {
    function env(string $key, ?string $default = null): ?string
    {
        load_env();
        $value = getenv($key);
        if ($value === false) {
            return $default;
        }
        return $value;
    }
}
