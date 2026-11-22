<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Perkora\SDK\PerkoraClient;

final class PerkoraClientTest extends TestCase
{
    private function client(): PerkoraClient
    {
        return PerkoraClient::create(
            'http://localhost:8000',
            'test-key-id',
            'test-secret'
        );
    }

    public function test_it_can_initialize(): void
    {
        $this->assertInstanceOf(PerkoraClient::class, $this->client());
    }

    public function test_it_rejects_an_empty_link_code(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->client()->linkAccount('', 'external-user-1');
    }

    public function test_it_rejects_an_empty_external_user_id(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->client()->linkAccount('TEST-CODE', '');
    }

    public function test_it_rejects_an_empty_completion_event_id(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->client()->completeOffer(
            '',
            'external-user-1',
            'offer-1'
        );
    }

    public function test_it_rejects_invalid_completion_status_ids(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->client()->getCompletionStatus('event id with spaces');
    }
}
