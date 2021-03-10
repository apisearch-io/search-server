<?php

/*
 * This file is part of the Apisearch Server
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Feel free to edit as you please, and have fun.
 *
 * @author Marc Morera <yuhu@mmoreram.com>
 */

declare(strict_types=1);

namespace Apisearch\Plugin\Testing\Http;

use Apisearch\Server\Domain\Repository\AppRepository\InMemoryTokenRepository;
use Apisearch\Server\Domain\Repository\InMemoryRepository;
use Apisearch\Server\Domain\Repository\InteractionRepository\InMemoryInteractionRepository;
use Apisearch\Server\Domain\Repository\LogRepository\InMemoryLogRepository;
use Apisearch\Server\Domain\Repository\MetadataRepository\InMemoryMetadataRepository;
use Apisearch\Server\Domain\Repository\UsageRepository\InMemoryUsageRepository;
use React\Http\Message\Response;
use function React\Promise\all;
use React\Promise\PromiseInterface;

/**
 * Class ResetInMemoryRepositoriesController.
 */
class ResetInMemoryRepositoriesController
{
    private ?InMemoryRepository $repository;
    private ?InMemoryUsageRepository $usageRepository;
    private ?InMemoryMetadataRepository $metadataRepository;
    private ?InMemoryTokenRepository $tokenRepository;
    private ?InMemoryInteractionRepository $interactionRepository;
    private ?InMemoryLogRepository $logRepository;

    /**
     * @param InMemoryRepository|null            $repository
     * @param InMemoryUsageRepository|null       $usageRepository
     * @param InMemoryMetadataRepository|null    $metadataRepository
     * @param InMemoryTokenRepository|null       $tokenRepository
     * @param InMemoryInteractionRepository|null $interactionRepository
     * @param InMemoryLogRepository|null         $logRepository
     */
    public function __construct(
        ?InMemoryRepository $repository,
        ?InMemoryUsageRepository $usageRepository,
        ?InMemoryMetadataRepository $metadataRepository,
        ?InMemoryTokenRepository $tokenRepository,
        ?InMemoryInteractionRepository $interactionRepository,
        ?InMemoryLogRepository $logRepository
    ) {
        $this->repository = $repository;
        $this->usageRepository = $usageRepository;
        $this->metadataRepository = $metadataRepository;
        $this->tokenRepository = $tokenRepository;
        $this->interactionRepository = $interactionRepository;
        $this->logRepository = $logRepository;
    }

    /**
     * @return PromiseInterface<Response>
     */
    public function __invoke(): PromiseInterface
    {
        return
            all([
                $this->repository ? $this->repository->reset() : null,
                $this->usageRepository ? $this->usageRepository->reset() : null,
                $this->metadataRepository ? $this->metadataRepository->reset() : null,
                $this->tokenRepository ? $this->tokenRepository->reset() : null,
                $this->interactionRepository ? $this->interactionRepository->reset() : null,
                $this->logRepository ? $this->logRepository->reset() : null,
            ])
            ->then(function () {
                return new Response();
            });
    }
}
