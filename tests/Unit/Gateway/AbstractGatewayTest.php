<?php declare(strict_types=1);

// SPDX-FileCopyrightText: 2012-2026 Open Assessment Technologies S.A.
// Copyright (C) 2019 (original work) Open Assessment Technologies SA;
//
// SPDX-License-Identifier: AGPL-3.0-only OR LicenseRef-TAO-Commercial-License
namespace OAT\Library\CorrelationIdsHttpClient\Tests\Unit\Gateway;

use OAT\Library\CorrelationIds\Provider\CorrelationIdsHeaderNamesProviderInterface;
use OAT\Library\CorrelationIds\Registry\CorrelationIdsRegistryInterface;
use OAT\Library\CorrelationIdsHttpClient\Gateway\AbstractGateway;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class AbstractGatewayTest extends TestCase
{
    public function testItAppendsCorrelationIdsFromRegistryAsHeaders(): void
    {
        $clientMock = $this->createMock(HttpClientInterface::class);
        $registryMock = $this->createMock(CorrelationIdsRegistryInterface::class);
        $providerMock = $this->createMock(CorrelationIdsHeaderNamesProviderInterface::class);

        $providerMock
            ->expects($this->once())
            ->method('provideParentCorrelationIdHeaderName')
            ->willReturn(CorrelationIdsHeaderNamesProviderInterface::DEFAULT_PARENT_CORRELATION_ID_HEADER_NAME);

        $providerMock
            ->expects($this->once())
            ->method('provideRootCorrelationIdHeaderName')
            ->willReturn(CorrelationIdsHeaderNamesProviderInterface::DEFAULT_ROOT_CORRELATION_ID_HEADER_NAME);

        $registryMock
            ->expects($this->exactly(2))
            ->method('getCurrentCorrelationId')
            ->willReturn('current');

        $registryMock
            ->expects($this->once())
            ->method('getParentCorrelationId')
            ->willReturn('parent');

        $registryMock
            ->expects($this->once())
            ->method('getRootCorrelationId')
            ->willReturn('root');

        $clientMock
            ->expects($this->once())
            ->method('request')
            ->with('GET', 'http://example.com', [
                'headers' => [
                    'default' => 'default',
                    CorrelationIdsHeaderNamesProviderInterface::DEFAULT_PARENT_CORRELATION_ID_HEADER_NAME => 'current',
                    CorrelationIdsHeaderNamesProviderInterface::DEFAULT_ROOT_CORRELATION_ID_HEADER_NAME => 'root',
                ]
            ]);

        $subject = $this->buildTestInstance($clientMock, $registryMock, $providerMock);

        $subject->performTestRequest('GET', 'http://example.com', ['headers' => ['default' => 'default']]);
    }

    private function buildTestInstance(
        HttpClientInterface $client,
        CorrelationIdsRegistryInterface $registry,
        CorrelationIdsHeaderNamesProviderInterface $provider
    ): AbstractGateway {
        return new class ($client, $registry, $provider) extends AbstractGateway
        {
            public function performTestRequest(string $method, string $url, array $options = []): ResponseInterface
            {
                return $this->request($method, $url, $options);
            }
        };
    }
}
