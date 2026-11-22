<?php

declare(strict_types=1);

namespace Perkora\SDK;

use InvalidArgumentException;
use Perkora\SDK\Http\ApiTransport;

final class PerkoraClient
{
    public function __construct(
        private readonly ApiTransport $transport
    ) {}

    public static function create(
        string $baseUrl,
        string $keyId,
        string $secret,
        int $timeout = 20
    ): self {
        return new self(
            new ApiTransport(
                $baseUrl,
                $keyId,
                $secret,
                $timeout
            )
        );
    }

    /**
     * Link an external project's account to a Perkora user.
     */
    public function linkAccount(
        string $linkCode,
        string $externalUserId
    ): array {
        $this->requireValue($linkCode, 'Link code');
        $this->requireValue($externalUserId, 'External user ID');

        return $this->transport->request(
            'POST',
            '/api/integrations/link-account',
            $this->encode([
                'link_code' => $linkCode,
                'external_user_id' => $externalUserId,
            ])
        );
    }

    /**
     * Retrieve offers assigned to this integration.
     */
    public function getOffers(): array
    {
        return $this->transport->request(
            'GET',
            '/api/integrations/offers'
        );
    }

    /**
     * Report a completed offer to Perkora.
     *
     * The reward is always determined by Perkora.
     */
    public function completeOffer(
        string $externalEventId,
        string $externalUserId,
        string $externalOfferId,
        ?array $metadata = null
    ): array {
        $this->requireValue($externalEventId, 'External event ID');
        $this->requireValue($externalUserId, 'External user ID');
        $this->requireValue($externalOfferId, 'External offer ID');

        $payload = [
            'external_event_id' => $externalEventId,
            'external_user_id' => $externalUserId,
            'external_offer_id' => $externalOfferId,
        ];

        if ($metadata !== null) {
            $payload['metadata'] = $metadata;
        }

        return $this->transport->request(
            'POST',
            '/api/integrations/completions',
            $this->encode($payload)
        );
    }

    /**
     * Check the processing status of a previously reported completion.
     */
    public function getCompletionStatus(
        string $externalEventId
    ): array {
        $this->requireValue($externalEventId, 'External event ID');

        if (!preg_match('/^[A-Za-z0-9._:-]{1,150}$/', $externalEventId)) {
            throw new InvalidArgumentException(
                'External event ID contains unsupported characters.'
            );
        }

        return $this->transport->request(
            'GET',
            '/api/integrations/completions/'.$externalEventId
        );
    }

    private function encode(array $payload): string
    {
        return json_encode(
            $payload,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );
    }

    private function requireValue(
        string $value,
        string $name
    ): void {
        if (trim($value) === '') {
            throw new InvalidArgumentException($name.' cannot be empty.');
        }
    }
}
