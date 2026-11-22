# Perkora PHP SDK Installer

This installer allows plain PHP applications to install the Perkora SDK without Composer.

## Requirements

- PHP 8.2 or newer.
- PHP cURL extension.
- PHP ZIP extension.
- Internet connection.

## Usage

Download `install.php` from the official Perkora SDK GitHub repository.

Run:

```bash
php install.php OWNER/REPOSITORY
```

To install a particular release or branch:

```bash
php install.php OWNER/REPOSITORY VERSION
```

To specify the target project directory:

```bash
php install.php OWNER/REPOSITORY VERSION /path/to/project
```

The installer places the SDK in:

```text
vendor/perkora/php-sdk/
```

## Loading the SDK

After installation:

```php
require_once __DIR__ . '/vendor/perkora/php-sdk/autoload.php';

use Perkora\SDK\PerkoraClient;

$perkora = PerkoraClient::create(
    getenv('PERKORA_BASE_URL'),
    getenv('PERKORA_KEY_ID'),
    getenv('PERKORA_SECRET')
);
```

## Important

- Replace OWNER/REPOSITORY with the official GitHub repository.
- Keep API credentials on your server.
- Do not expose your API secret in browser-side code.
- The installer refuses to overwrite an existing SDK installation.
