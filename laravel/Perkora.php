<?php

declare(strict_types=1);

namespace Perkora\SDK\Laravel;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array linkAccount(string $linkCode, string $externalUserId)
 * @method static array getOffers()
 * @method static array completeOffer(string $externalEventId, string $externalUserId, string $externalOfferId, ?array $metadata = null)
 * @method static array getCompletionStatus(string $externalEventId)
 * @method static array getLinkStatus(string $externalUserId)
 *
 * @see \Perkora\SDK\PerkoraClient
 */
final class Perkora extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'perkora';
    }
}
