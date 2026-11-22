<?php

declare(strict_types=1);

namespace Perkora\SDK\Auth;

use InvalidArgumentException;

final class SignatureGenerator
{
    public function generate(
        string $method,
        string $path,
        int $timestamp,
        string $nonce,
        string $body,
        string $secret
    ): string {
        if ($secret === '') {
            throw new InvalidArgumentException('The Perkora API secret cannot be empty.');
        }

        if ($path === '' || $path[0] !== '/' || str_contains($path, '?')) {
            throw new InvalidArgumentException('The request path must begin with / and cannot contain a query string.');
        }

        if (!preg_match('/^[a-fA-F0-9]{32,100}$/', $nonce)) {
            throw new InvalidArgumentException('The nonce must contain 32 to 100 hexadecimal characters.');
        }

        $canonical = implode("\n", [
            strtoupper($method),
            $path,
            (string) $timestamp,
            strtolower($nonce),
            hash('sha256', $body),
        ]);

        return hash_hmac('sha256', $canonical, $secret);
    }

    public function generateNonce(): string
    {
        return bin2hex(random_bytes(32));
    }

    public function generateTimestamp(): int
    {
        return time();
    }
}
