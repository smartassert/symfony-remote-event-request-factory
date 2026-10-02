<?php

declare(strict_types=1);

namespace SmartAssert\SymfonyRemoteEventRequestFactory;

use Symfony\Component\HttpClient\HttpOptions;
use Symfony\Component\RemoteEvent\RemoteEvent;
use Symfony\Component\Webhook\Server\HeadersConfigurator;
use Symfony\Component\Webhook\Server\HeaderSignatureConfigurator;
use Symfony\Component\Webhook\Server\JsonBodyConfigurator;

readonly class Factory
{
    public function __construct(
        private HeadersConfigurator $headersConfigurator,
        private JsonBodyConfigurator $jsonBodyConfigurator,
        private HeaderSignatureConfigurator $headerSignatureConfigurator,
    ) {}

    /**
     * @return array<string, string>
     */
    public function createHeaders(RemoteEvent $event, string $secret): array
    {
        $options = new HttpOptions();

        $this->headersConfigurator->configure($event, $secret, $options);
        $this->jsonBodyConfigurator->configure($event, $secret, $options);
        $this->headerSignatureConfigurator->configure($event, $secret, $options);

        $data = $options->toArray();

        $headers = $data['headers'] ?? [];
        $headers = is_array($headers) ? $headers : [];

        return array_filter($headers, function ($value, $key) {
            return is_string($key) && is_string($value);
        }, ARRAY_FILTER_USE_BOTH);
    }

    public function createBody(RemoteEvent $event, string $secret): string
    {
        $options = new HttpOptions();

        $this->headersConfigurator->configure($event, $secret, $options);
        $this->jsonBodyConfigurator->configure($event, $secret, $options);
        $this->headerSignatureConfigurator->configure($event, $secret, $options);

        $data = $options->toArray();
        $body = $data['body'] ?? '';

        return is_string($body) ? $body : '';
    }
}
