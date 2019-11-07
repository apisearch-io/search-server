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

namespace Apisearch\Plugin\RedisMetadataFields\Domain\Middleware;

use Apisearch\Model\IndexUUID;
use Apisearch\Plugin\RedisMetadataFields\Domain\Repository\RedisMetadataRepository;
use Apisearch\Result\Result;
use Apisearch\Server\Domain\Plugin\PluginMiddleware;
use Apisearch\Server\Domain\Query\Query;
use React\Promise\PromiseInterface;

/**
 * Class QueryMiddleware.
 */
class QueryMiddleware implements PluginMiddleware
{
    /**
     * @var RedisMetadataRepository
     *
     * Metadata repository
     */
    private $metadataRepository;

    /**
     * OnItemsWereIndexed constructor.
     *
     * @param RedisMetadataRepository $metadataRepository
     */
    public function __construct(RedisMetadataRepository $metadataRepository)
    {
        $this->metadataRepository = $metadataRepository;
    }

    /**
     * Execute middleware.
     *
     * @param mixed    $command
     * @param callable $next
     *
     * @return PromiseInterface
     */
    public function execute(
        $command,
        $next
    ): PromiseInterface {
        return $next($command)
            ->then(function (Result $result) use ($command) {
                /**
                 * @var Result
                 * @var Query  $command
                 *
                 * We should strip all possible plugins applied on repository reference
                 */
                $composedIndexUUID = $command
                    ->getIndexUUID()
                    ->composeUUID();

                $composedIndexUUID = preg_replace('~(-plugin.*?(?=-plugin|$))~', '', $composedIndexUUID);
                $filteredRepository = $command
                    ->getRepositoryReference()
                    ->changeIndex(IndexUUID::createById($composedIndexUUID));

                return $this
                    ->metadataRepository
                    ->loadItemsMetadata(
                        $filteredRepository,
                        $result->getItems()
                    )
                    ->then(function () use ($result) {
                        return $result;
                    });
            });
    }

    /**
     * Events subscribed namespace. Can refer to specific class namespace, any
     * parent class or any interface.
     *
     * By returning an empty array, means coupled to all.
     *
     * @return string[]
     */
    public function getSubscribedCommands(): array
    {
        return [Query::class];
    }
}
