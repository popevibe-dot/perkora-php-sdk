<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Perkora\SDK\Auth\SignatureGenerator;

final class SignatureGeneratorTest extends TestCase
{
    public function test_it_generates_the_expected_signature(): void
    {
        $signer = new SignatureGenerator();

        $signature = $signer->generate(
            'POST',
            '/api/integrations/link-account',
            1760000000,
            'abcdef0123456789abcdef0123456789',
            '{"link_code":"TEST","external_user_id":"user-1"}',
            'test-secret'
        );

        $this->assertSame(
            'ec87db79da60096a616d72c9d23d54fee555d376daa41e1368b8e183b86b7104',
            $signature
        );
    }

    public function test_nonce_is_64_hexadecimal_characters(): void
    {
        $nonce = (new SignatureGenerator())->generateNonce();

        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $nonce);
    }

    public function test_timestamp_is_an_integer(): void
    {
        $timestamp = (new SignatureGenerator())->generateTimestamp();

        $this->assertIsInt($timestamp);
        $this->assertGreaterThan(0, $timestamp);
    }

    public function test_it_rejects_an_empty_secret(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new SignatureGenerator())->generate(
            'POST',
            '/api/integrations/link-account',
            1760000000,
            'abcdef0123456789abcdef0123456789',
            '{}',
            ''
        );
    }

    public function test_it_rejects_query_strings_in_paths(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new SignatureGenerator())->generate(
            'GET',
            '/api/integrations/offers?page=2',
            1760000000,
            'abcdef0123456789abcdef0123456789',
            '',
            'test-secret'
        );
    }
}
