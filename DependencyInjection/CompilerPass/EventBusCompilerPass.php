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

namespace Apisearch\Server\DependencyInjection\CompilerPass;

use Drift\EventBus\Bus\Bus;
use Drift\EventBus\DependencyInjection\CompilerPass\EventBusCompilerPass as EventBusBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class EventBusCompilerPass.
 */
class EventBusCompilerPass implements CompilerPassInterface
{
    /**
     * You can modify the container here before it is dumped to PHP code.
     */
    public function process(ContainerBuilder $container)
    {
        $passThrough = true;
        $asyncAdapterEnabled = (bool) ($_ENV['APISEARCH_ASYNC_EVENTS_ENABLED'] ?? false);

        $distribution = Bus::DISTRIBUTION_NEXT_TICK;
        $middlewares = [];
        $eventBusRouter = [
            'TokensWereDeleted' => 'events, tokens_update',
            'TokensWereAdded' => 'events, tokens_update',
            'TokensWasDeleted' => 'events, tokens_update',
        ];

        EventBusBuilder::createBuses(
            $container,
            $asyncAdapterEnabled,
            $passThrough,
            $distribution,
            $middlewares
        );

        if ($asyncAdapterEnabled) {
            $eventBusExchanges = [
                'events' => $_ENV['APISEARCH_EVENTS_EXCHANGE'] ?? 'events',
                'tokens_update' => $_ENV['APISEARCH_TOKENS_UPDATE_EXCHANGE'] ?? 'tokens_update',
            ];

            $asyncAdapter = [
                'type' => 'amqp',
                'host' => $_ENV['AMQP_HOST'],
                'user' => $_ENV['AMQP_USER'],
                'password' => $_ENV['AMQP_PASSWORD'],
                'vhost' => $_ENV['AMQP_VHOST'],
            ];

            EventBusBuilder::createAsyncBus(
                $container,
                $asyncAdapter,
                $passThrough,
                $eventBusRouter,
                $eventBusExchanges
            );
        }
    }
}
