<?php

declare(strict_types=1);

namespace Perkora\SDK\Http;

use InvalidArgumentException;
use Perkora\SDK\Auth\SignatureGenerator;
use Perkora\SDK\Exceptions\PerkoraApiException;

final class ApiTransport
{
    private string $baseUrl;

    public function __construct(
        string $baseUrl,
        private readonly string $keyId,
        private readonly string $secret,
        private readonly int $timeout = 20,
        private readonly ?SignatureGenerator $signer = null
    ) {
        $baseUrl = rtrim(trim($baseUrl), '/');

        if (!filter_var($baseUrl, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException('A valid Perkora API base URL is required.');
        }

        $scheme = strtolower((string) parse_url($baseUrl, PHP_URL_SCHEME));

        if (!in_array($scheme, ['http', 'https'], true)) {
            throw new InvalidArgumentException('The API URL must use HTTP or HTTPS.');
        }

        if ($keyId === '' || $secret === '') {
            throw new InvalidArgumentException('Perkora API credentials are required.');
        }

        if ($timeout < 1) {
            throw new InvalidArgumentException('The timeout must be at least one second.');
        }

        $this->baseUrl = $baseUrl;
    }

    public function request(
        string $method,
        string $path,
        string $body = ''
    ): array {
        $method = strtoupper($method);

        if (!in_array($method, ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            throw new InvalidArgumentException('Unsupported HTTP method.');
        }

        if (
            $path === ''
            || $path[0] !== '/'
            || str_contains($path, '?')
            || str_contains($path, '#')
        ) {
            throw new InvalidArgumentException('The API path must begin with / and cannot contain a query string or fragment.');
        }

        $timestamp = time();
        $nonce = bin2hex(random_bytes(32));

        $signer = $this->signer ?? new SignatureGenerator();

        $signature = $signer->generate(
            $method,
            $path,
            $timestamp,
            $nonce,
            $body,
            $this->secret
        );

        $headers = [
            'Accept: application/json',
            'Content-Type: application/json',
            'X-Perkora-Key-Id: '.$this->keyId,
            'X-Perkora-Timestamp: '.$timestamp,
            'X-Perkora-Nonce: '.$nonce,
            'X-Perkora-Signature: '.$signature,
        ];

        $handle = curl_init($this->baseUrl.$path);

        if ($handle === false) {
            throw new PerkoraApiException('Unable to initialize the HTTP connection.');
        }

        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CONNECTTIMEOUT => min(10, $this->timeout),
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ];

        if ($body !== '') {
            $options[CURLOPT_POSTFIELDS] = $body;
        }

        curl_setopt_array($handle, $options);

        $rawResponse = curl_exec($handle);
        $curlError = curl_error($handle);
        $httpStatus = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);

        curl_close($handle);

        if ($rawResponse === false) {
            throw new PerkoraApiException(
                'Perkora connection failed: '.$curlError
            );
        }

        try {
            $decoded = json_decode(
                $rawResponse,
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (\JsonException) {
            throw new PerkoraApiException(
                'Perkora returned an invalid JSON response.',
                $httpStatus,
                [],
                $rawResponse
            );
        }

        if (!is_array($decoded)) {
            throw new PerkoraApiException(
                'Perkora returned an unexpected response format.',
                $httpStatus,
                [],
                $rawResponse
            );
        }

        if ($httpStatus < 200 || $httpStatus >= 300) {
            $message = $decoded['message']
                ?? $decoded['error']
                ?? 'Perkora API request failed.';

            throw new PerkoraApiException(
                (string) $message,
                $httpStatus,
                $decoded,
                $rawResponse
            );
        }

        return $decoded;
    }
}
