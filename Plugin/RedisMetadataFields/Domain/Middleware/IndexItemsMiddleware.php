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
use Apisearch\Server\Domain\Command\IndexItems;
use Apisearch\Server\Domain\Plugin\PluginMiddleware;
use React\Promise\PromiseInterface;

/**
 * Class IndexItemsMiddleware.
 */
class IndexItemsMiddleware implements PluginMiddleware
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
        /**
         * @var IndexItems
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
            ->saveItemsMetadata(
                $filteredRepository,
                $command->getItems()
            )
            ->then(function () use ($next, $command) {
                return $next($command);
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
        return [IndexItems::class];
    }
}
