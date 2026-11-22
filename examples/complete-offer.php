<?php
require dirname(__DIR__) . '/vendor/autoload.php';

use Perkora\SDK\PerkoraClient;
use Perkora\SDK\Exceptions\PerkoraApiException;

$client = PerkoraClient::create(
    getenv('PERKORA_BASE_URL') ?: '',
    getenv('PERKORA_KEY_ID') ?: '',
    getenv('PERKORA_SECRET') ?: ''
);

function reportCompletedOffer(
    PerkoraClient $client,
    string $externalEventId,
    string $externalUserId,
    string $externalOfferId
): array {
    return $client->completeOffer(
        $externalEventId,
        $externalUserId,
        $externalOfferId,
        ['source' => 'external-application']
    );
}

try {
    // Call reportCompletedOffer() only after your application
    // has independently verified that the user completed the offer.
    echo "Client initialized. Call reportCompletedOffer() after verifying a completion.\n";
} catch (PerkoraApiException $exception) {
    echo "Perkora API error: {$exception->getMessage()}\n";
    echo "HTTP status: {$exception->getHttpStatus()}\n";
}
