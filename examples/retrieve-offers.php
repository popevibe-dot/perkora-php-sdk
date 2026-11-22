<?php
require dirname(__DIR__) . '/vendor/autoload.php';

use Perkora\SDK\PerkoraClient;
use Perkora\SDK\Exceptions\PerkoraApiException;

$client = PerkoraClient::create(
    getenv('PERKORA_BASE_URL') ?: '',
    getenv('PERKORA_KEY_ID') ?: '',
    getenv('PERKORA_SECRET') ?: ''
);

try {
    $result = $client->getOffers();

    // Inspect the response structure returned by your Perkora API.
    print_r($result);
} catch (PerkoraApiException $exception) {
    echo "Perkora API error: {$exception->getMessage()}\n";
    echo "HTTP status: {$exception->getHttpStatus()}\n";
}
