# Perkora PHP SDK

Official PHP SDK for connecting external applications to Perkora.

## Features

- Plain PHP integration.
- Laravel integration through Composer.
- HMAC-SHA256 request authentication.
- Perkora account linking.
- Offer retrieval.
- Automatic completion reporting.
- Completion status lookup.
- Automatic wallet reward processing by Perkora.

## Requirements

- PHP 8.2 or newer.
- PHP cURL extension.
- PHP JSON extension.

## Composer Installation

```bash
composer require perkora/php-sdk
```

## Laravel Installation

Publish the configuration:

```bash
php artisan vendor:publish --tag=perkora-config
```

Add these variables to your `.env` file:

```env
PERKORA_BASE_URL=https://your-perkora-domain.com
PERKORA_KEY_ID=your_api_key_id
PERKORA_SECRET=your_api_secret
PERKORA_TIMEOUT=20
```

## Plain PHP Installation

Include Composer's autoloader:

```php
require_once __DIR__ . '/vendor/autoload.php';

use Perkora\SDK\PerkoraClient;

$perkora = PerkoraClient::create(
    getenv('PERKORA_BASE_URL'),
    getenv('PERKORA_KEY_ID'),
    getenv('PERKORA_SECRET')
);
```

## Available Methods

### Link an account

```php
$result = $perkora->linkAccount(
    $linkCode,
    $externalUserId
);
```

### Retrieve available offers

```php
$result = $perkora->getOffers();
```

### Report a completed offer

```php
$result = $perkora->completeOffer(
    $externalEventId,
    $externalUserId,
    $externalOfferId,
    ['source' => 'your-application']
);
```

### Check completion status

```php
$result = $perkora->getCompletionStatus(
    $externalEventId
);
```

## Laravel Usage

Resolve the client through the service container:

```php
$perkora = app('perkora');

$offers = $perkora->getOffers();
```

Or use the facade:

```php
use Perkora\SDK\Laravel\Perkora;

$offers = Perkora::getOffers();
```

## Important Integration Rules

1. Your project must be approved and registered by the Perkora administrator before API credentials can be issued.
2. Keep your API secret private. Never expose it in frontend JavaScript or public application code.
3. Each external user must link their account to Perkora before completing offers.
4. Your application must generate a unique external event ID for every completion event.
5. Perkora determines the reward amount. External applications must never submit or control reward amounts.
6. Successful verified completions are credited automatically to the linked Perkora user's wallet.
7. Repeating the same completion event is protected by idempotency.

## Error Handling

API errors are represented by `PerkoraApiException`.

```php
use Perkora\SDK\Exceptions\PerkoraApiException;

try {
    $offers = $perkora->getOffers();
} catch (PerkoraApiException $exception) {
    echo $exception->getMessage();
    echo $exception->getHttpStatus();
}
```
