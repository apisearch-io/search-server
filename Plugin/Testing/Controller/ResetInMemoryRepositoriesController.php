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

namespace Apisearch\Plugin\Testing\Controller;

use Apisearch\Server\Domain\Repository\AppRepository\InMemoryTokenRepository;
use Apisearch\Server\Domain\Repository\InMemoryRepository;
use Apisearch\Server\Domain\Repository\InteractionRepository\InMemoryInteractionRepository;
use Apisearch\Server\Domain\Repository\MetadataRepository\InMemoryMetadataRepository;
use Apisearch\Server\Domain\Repository\UsageRepository\InMemoryUsageRepository;
use function React\Promise\all;
use React\Http\Response;
use React\Promise\PromiseInterface;

/**
 * Class ResetInMemoryRepositoriesController.
 */
class ResetInMemoryRepositoriesController
{
    /**
     * @var InMemoryRepository
     */
    private $repository;

    /**
     * @var InMemoryUsageRepository
     */
    private $usageRepository;

    /**
     * @var InMemoryMetadataRepository
     */
    private $metadataRepository;

    /**
     * @var InMemoryTokenRepository
     */
    private $tokenRepository;

    /**
     * @var InMemoryInteractionRepository
     */
    private $inMemoryInteractionRepository;

    /**
     * @param InMemoryRepository            $repository
     * @param InMemoryUsageRepository       $usageRepository
     * @param InMemoryMetadataRepository    $metadataRepository
     * @param InMemoryTokenRepository       $tokenRepository
     * @param InMemoryInteractionRepository $inMemoryInteractionRepository
     */
    public function __construct(
        InMemoryRepository $repository,
        InMemoryUsageRepository $usageRepository,
        InMemoryMetadataRepository $metadataRepository,
        InMemoryTokenRepository $tokenRepository,
        InMemoryInteractionRepository $inMemoryInteractionRepository
    ) {
        $this->repository = $repository;
        $this->usageRepository = $usageRepository;
        $this->metadataRepository = $metadataRepository;
        $this->tokenRepository = $tokenRepository;
        $this->inMemoryInteractionRepository = $inMemoryInteractionRepository;
    }

    /**
     * @return PromiseInterface<Response>
     */
    public function __invoke(): PromiseInterface
    {
        return
            all([
                $this->repository->reset(),
                $this->usageRepository->reset(),
                $this->metadataRepository->reset(),
                $this->tokenRepository->reset(),
                $this->inMemoryInteractionRepository->reset(),
            ])
            ->then(function () {
                return new Response();
            });
    }
}
