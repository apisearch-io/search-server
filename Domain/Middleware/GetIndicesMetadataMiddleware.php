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

namespace Apisearch\Server\Domain\Middleware;

use Apisearch\Model\Index;
use Apisearch\Server\Domain\Query\GetIndices;
use Apisearch\Server\Domain\Repository\MetadataRepository\MetadataRepository;
use Drift\CommandBus\Middleware\DiscriminableMiddleware;

/**
 * Class GetIndicesMetadataMiddleware.
 */
class GetIndicesMetadataMiddleware implements DiscriminableMiddleware
{
    /**
     * @var MetadataRepository
     */
    protected $metadataRepository;

    /**
     * @param MetadataRepository $metadataRepository
     */
    public function __construct(MetadataRepository $metadataRepository)
    {
        $this->metadataRepository = $metadataRepository;
    }

    /**
     * @param object   $command
     * @param callable $next
     *
     * @return mixed
     */
    public function execute($command, callable $next)
    {
        $repositoryReference = $command->getRepositoryReference();

        return $next($command)
            ->then(function (array $indices) use ($repositoryReference) {
                /**
                 * @var Index
                 */
                foreach ($indices as $index) {
                    $indexRepositoryReference = $repositoryReference->changeIndex($index->getUUID());
                    $storedMetadata = $this
                        ->metadataRepository
                        ->all($indexRepositoryReference);

                    $index->withMetadataValue('stored_metadata', $storedMetadata);
                }

                return $indices;
            });
    }

    /**
     * @return string[]
     */
    public function onlyHandle(): array
    {
        return [
            GetIndices::class,
        ];
    }
}
