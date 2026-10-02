<?php

declare(strict_types=1);

namespace SmartAssert\SymfonyRemoteEventRequestFactory\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SmartAssert\SymfonyRemoteEventRequestFactory\Factory;
use Symfony\Component\RemoteEvent\RemoteEvent;
use Symfony\Component\Webhook\Server\HeadersConfigurator;
use Symfony\Component\Webhook\Server\HeaderSignatureConfigurator;
use Symfony\Component\Webhook\Server\JsonBodyConfigurator;
use Symfony\Component\Webhook\Server\NativeJsonPayloadSerializer;

class FactoryTest extends TestCase
{
    private Factory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $payloadSerializer = new NativeJsonPayloadSerializer();

        $headersConfigurator = new HeadersConfigurator();
        $jsonBodyConfigurator = new JsonBodyConfigurator($payloadSerializer);
        $headerSignatureConfigurator = new HeaderSignatureConfigurator();

        $this->factory = new Factory(
            $headersConfigurator,
            $jsonBodyConfigurator,
            $headerSignatureConfigurator,
        );
    }

    /**
     * @param array<mixed> $expected
     */
    #[DataProvider('createHeadersDataProvider')]
    public function testCreateHeaders(RemoteEvent $remoteEvent, string $secret, array $expected): void
    {
        $headers = $this->factory->createHeaders($remoteEvent, $secret);

        self::assertEquals($expected, $headers);
    }

    /**
     * @return array<mixed>
     */
    public static function createHeadersDataProvider(): array
    {
        return [
            'default' => [
                'remoteEvent' => new RemoteEvent(
                    'event.name',
                    'event.id',
                    []
                ),
                'secret' => 'non-empty secret',
                'expected' => [
                    'Webhook-Event' => 'event.name',
                    'Webhook-Id' => 'event.id',
                    'Content-Type' => 'application/json',
                    'Webhook-Signature' => 'sha256=92a997c19de305f148caee219975a23de53a503ad65e79e8d962353af91f140e',
                ],
            ],
        ];
    }

    #[DataProvider('createBodyDataProvider')]
    public function testCreateBody(RemoteEvent $remoteEvent, string $secret, string $expected): void
    {
        $body = $this->factory->createBody($remoteEvent, $secret);

        self::assertSame($expected, $body);
    }

    /**
     * @return array<mixed>
     */
    public static function createBodyDataProvider(): array
    {
        return [
            'empty payload' => [
                'remoteEvent' => new RemoteEvent(
                    'event.foo',
                    'event.foo.id',
                    []
                ),
                'secret' => 'non-empty secret',
                'expected' => '[]',
            ],
            'non-empty payload' => [
                'remoteEvent' => new RemoteEvent(
                    'event.foo',
                    'event.foo.id',
                    [
                        'key1' => 'value1',
                        'key2' => 'value2',
                        'key3' => 'value3',
                    ]
                ),
                'secret' => 'non-empty secret',
                'expected' => json_encode([
                    'key1' => 'value1',
                    'key2' => 'value2',
                    'key3' => 'value3',
                ]),
            ],
        ];
    }
}
