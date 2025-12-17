<?php declare(strict_types=1);

// SPDX-FileCopyrightText: 2012-2026 Open Assessment Technologies S.A.
// Copyright (C) 2019 (original work) Open Assessment Technologies SA;
//
// SPDX-License-Identifier: AGPL-3.0-only OR LicenseRef-TAO-Commercial-License
namespace OAT\Library\CorrelationIdsHttpClient\Gateway;

use OAT\Library\CorrelationIds\Provider\CorrelationIdsHeaderNamesProvider;
use OAT\Library\CorrelationIds\Provider\CorrelationIdsHeaderNamesProviderInterface;
use OAT\Library\CorrelationIds\Registry\CorrelationIdsRegistryInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

abstract class AbstractGateway
{
    /** @var HttpClientInterface */
    protected $client;

    /** @var CorrelationIdsRegistryInterface */
    protected $registry;

    /** @var CorrelationIdsHeaderNamesProviderInterface */
    protected $provider;

    public function __construct(
        HttpClientInterface $client,
        CorrelationIdsRegistryInterface $registry,
        CorrelationIdsHeaderNamesProviderInterface $provider = null
    ) {
        $this->client = $client;
        $this->registry = $registry;
        $this->provider = $provider ?? new CorrelationIdsHeaderNamesProvider();
    }

    /**
     * @throws TransportExceptionInterface
     */
    protected function request(string $method, string $url, array $options = []): ResponseInterface
    {
        $options['headers'] = array_merge($options['headers'] ?? [], $this->prepareCorrelationIdsHeaders());

        return $this->client->request($method, $url, $options);
    }

    private function prepareCorrelationIdsHeaders(): array
    {
        return [
            $this->provider->provideParentCorrelationIdHeaderName() => $this->registry->getCurrentCorrelationId(),
            $this->provider->provideRootCorrelationIdHeaderName() => $this->determinateRootCorrelationId(),
        ];
    }

    private function determinateRootCorrelationId(): string
    {
        $candidates = array_filter([
            $this->registry->getRootCorrelationId(),
            $this->registry->getParentCorrelationId(),
            $this->registry->getCurrentCorrelationId(),
        ]);

        return array_shift($candidates) ?? '';
    }
}
