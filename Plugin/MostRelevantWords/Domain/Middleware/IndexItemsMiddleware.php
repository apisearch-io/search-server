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

namespace Apisearch\Plugin\MostRelevantWords\Domain\Middleware;

use Apisearch\Plugin\MostRelevantWords\Domain\ItemRelevantWords;
use Apisearch\Server\Domain\Command\IndexItems;
use Apisearch\Server\Domain\Plugin\PluginMiddleware;
use React\Promise\PromiseInterface;

/**
 * Class IndexItemsMiddleware.
 */
class IndexItemsMiddleware implements PluginMiddleware
{
    /**
     * @var ItemRelevantWords
     *
     * ItemRelevantWords
     */
    private $itemRelevantWords;

    /**
     * IndexItemsMiddleware constructor.
     *
     * @param ItemRelevantWords $itemRelevantWords
     */
    public function __construct(ItemRelevantWords $itemRelevantWords)
    {
        $this->itemRelevantWords = $itemRelevantWords;
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
        array_map(
            [$this->itemRelevantWords, 'reduceItemSearchableFields'],
            $command->getItems()
        );

        return $next($command);
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
