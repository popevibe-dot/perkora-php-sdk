# Perkora PHP SDK

Official PHP SDK for connecting external applications to Perkora.

**Current version:** 1.0.0

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
- PHP Hash extension.

## Installation with Composer

The package is currently distributed through GitHub.

Add this repository configuration to your application's `composer.json`:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/popevibe-dot/perkora-php-sdk"
        }
    ],
    "require": {
        "perkora/php-sdk": "^1.0"
    }
}
```

Then run:

```bash
composer update perkora/php-sdk
```

Once the package is registered on Packagist, the repository configuration will no longer be necessary.

## Laravel Installation

The Laravel service provider is automatically discovered by Laravel.

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

Resolve the client through Laravel:

```php
$perkora = app('perkora');

$offers = $perkora->getOffers();
```

Or use the facade:

```php
use Perkora\SDK\Laravel\Perkora;

$offers = Perkora::getOffers();
```

## Standalone Installation (Without Composer)

The standalone installer downloads the SDK directly from GitHub and creates a PSR-4 autoloader.

Requirements:

- PHP 8.2 or newer.
- PHP cURL extension.
- PHP ZipArchive extension.

First, download the installer:

```bash
curl -L https://raw.githubusercontent.com/popevibe-dot/perkora-php-sdk/v1.0.0/installer/install.php -o install.php
```

Run it from your project directory:

```bash
php install.php popevibe-dot/perkora-php-sdk v1.0.0
```

The SDK will be installed into:

```text
vendor/perkora/php-sdk/
```

Load the SDK:

```php
require_once __DIR__ . '/vendor/perkora/php-sdk/autoload.php';

use Perkora\SDK\PerkoraClient;

$perkora = PerkoraClient::create(
    getenv('PERKORA_BASE_URL'),
    getenv('PERKORA_KEY_ID'),
    getenv('PERKORA_SECRET')
);
```

You can also specify a different installation directory:

```bash
php install.php popevibe-dot/perkora-php-sdk v1.0.0 /path/to/project
```

## Plain PHP Usage

If you installed through Composer, load Composer's autoloader:

```php
require_once __DIR__ . '/vendor/autoload.php';

use Perkora\SDK\PerkoraClient;

$perkora = PerkoraClient::create(
    getenv('PERKORA_BASE_URL'),
    getenv('PERKORA_KEY_ID'),
    getenv('PERKORA_SECRET')
);
```

If you used the standalone installer, use its autoloader instead.

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

## Versioning

Releases are published on GitHub:

https://github.com/popevibe-dot/perkora-php-sdk/releases
