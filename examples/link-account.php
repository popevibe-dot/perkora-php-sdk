<?php
require dirname(__DIR__) . '/vendor/autoload.php';

use Perkora\SDK\PerkoraClient;
use Perkora\SDK\Exceptions\PerkoraApiException;

$baseUrl = getenv('PERKORA_BASE_URL') ?: '';
$keyId = getenv('PERKORA_KEY_ID') ?: '';
$secret = getenv('PERKORA_SECRET') ?: '';

$client = PerkoraClient::create($baseUrl, $keyId, $secret);

// Supply these values from your application's account-linking flow.
function linkExternalAccount(
    PerkoraClient $client,
    string $linkCode,
    string $externalUserId
): array {
    return $client->linkAccount($linkCode, $externalUserId);
}

try {
    // Example:
    // $result = linkExternalAccount($client, $linkCode, $externalUserId);
    // print_r($result);

    echo "Client initialized. Call linkExternalAccount() with your user's link code and external ID.\n";
} catch (PerkoraApiException $exception) {
    echo "Perkora API error: {$exception->getMessage()}\n";
    echo "HTTP status: {$exception->getHttpStatus()}\n";
}
