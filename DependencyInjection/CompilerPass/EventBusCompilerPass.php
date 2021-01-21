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

use Apisearch\Server\Domain\Event\IndexWasConfigured;
use Apisearch\Server\Domain\Event\IndexWasCreated;
use Apisearch\Server\Domain\Event\IndexWasDeleted;
use Apisearch\Server\Domain\Event\TokensWereDeleted;
use Apisearch\Server\Domain\Event\TokenWasDeleted;
use Apisearch\Server\Domain\Event\TokenWasPut;
use Apisearch\Server\Domain\ImperativeEvent\FlushInteractions;
use Apisearch\Server\Domain\ImperativeEvent\FlushSearches;
use Apisearch\Server\Domain\ImperativeEvent\FlushUsageLines;
use Apisearch\Server\Domain\ImperativeEvent\LoadAllMetadata;
use Apisearch\Server\Domain\ImperativeEvent\LoadConfigs;
use Apisearch\Server\Domain\ImperativeEvent\LoadMetadata;
use Apisearch\Server\Domain\ImperativeEvent\LoadTokens;
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
     *
     * @return void
     */
    public function process(ContainerBuilder $container)
    {
        $passThrough = true;
        $asyncAdapterEnabled = $container->getParameter('apisearch_server.async_events_enabled');

        $distribution = Bus::DISTRIBUTION_INLINE;
        $events = [
            FlushUsageLines::class,
            FlushInteractions::class,
            FlushSearches::class,
            LoadConfigs::class,
            LoadMetadata::class,
            LoadAllMetadata::class,
            LoadTokens::class,

            IndexWasConfigured::class,
            IndexWasCreated::class,
            IndexWasDeleted::class,

            TokensWereDeleted::class,
            TokenWasPut::class,
            TokenWasDeleted::class,
        ];

        $eventBusRouter = \array_combine(
            $events,
            \array_values(
                \array_fill(0, \count($events), 'events')
            )
        );

        EventBusBuilder::createBuses(
            $container,
            $asyncAdapterEnabled,
            $passThrough,
            $distribution,
            []
        );

        if ($asyncAdapterEnabled) {
            $eventBusExchanges = [
                'events' => $container->getParameter('apisearch_server.async_events_exchange_name'),
            ];

            $asyncAdapter = [
                'type' => 'amqp',
                'host' => $container->getParameter('apisearch_server.async_events_amqp_host'),
                'port' => $container->getParameter('apisearch_server.async_events_amqp_port'),
                'user' => $container->getParameter('apisearch_server.async_events_amqp_user'),
                'password' => $container->getParameter('apisearch_server.async_events_amqp_password'),
                'vhost' => $container->getParameter('apisearch_server.async_events_amqp_vhost'),
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
