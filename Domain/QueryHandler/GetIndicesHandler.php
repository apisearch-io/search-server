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

namespace Apisearch\Server\Domain\QueryHandler;

use Apisearch\Config\Config;
use Apisearch\Model\Index;
use Apisearch\Repository\RepositoryReference;
use Apisearch\Server\Domain\Query\GetIndices;
use Apisearch\Server\Domain\Repository\AppRepository\ConfigRepository;
use Apisearch\Server\Domain\Repository\AppRepository\Repository as AppRepository;
use Apisearch\Server\Domain\WithAppRepository;
use React\Promise\PromiseInterface;

/**
 * Class GetIndicesHandler.
 */
class GetIndicesHandler extends WithAppRepository
{
    /**
     * @var ConfigRepository
     */
    private $configRepository;

    /**
     * @param AppRepository    $appRepository
     * @param ConfigRepository $configRepository
     */
    public function __construct(
        AppRepository $appRepository,
        ConfigRepository $configRepository
    ) {
        parent::__construct($appRepository);

        $this->configRepository = $configRepository;
    }

    /**
     * Get indices handler method.
     *
     * @param GetIndices $getIndices
     *
     * @return PromiseInterface<Index[]>
     */
    public function handle(GetIndices $getIndices): PromiseInterface
    {
        return $this
            ->appRepository
            ->getIndices($getIndices->getRepositoryReference())
            ->then(function (array $indices) use ($getIndices) {
                return \array_map(function (Index $index) {
                    $config = $this->configRepository->getConfig(
                        RepositoryReference::create(
                            $index->getAppUUID(),
                            $index->getUUID()
                        )
                    );
                    $indexAsArray = $index->toArray();
                    $currentMetadata = $indexAsArray['metadata'];
                    $newMetadata = $config instanceof Config
                        ? $config->getMetadata()
                        : [];

                    $indexAsArray['metadata'] = \array_merge(
                        $currentMetadata,
                        $newMetadata
                    );

                    return Index::createFromArray($indexAsArray);
                }, $indices);
            });
    }
}
