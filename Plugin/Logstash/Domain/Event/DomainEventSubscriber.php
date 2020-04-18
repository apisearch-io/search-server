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

namespace Apisearch\Plugin\Logstash\Domain\Event;

use Apisearch\Server\Domain\Event\DomainEvent;
use Apisearch\Server\Domain\Event\ExceptionWasCached;
use Apisearch\Server\Domain\Event\IndexWasConfigured;
use Apisearch\Server\Domain\Event\IndexWasCreated;
use Apisearch\Server\Domain\Event\IndexWasDeleted;
use Apisearch\Server\Domain\Event\ItemsWereDeleted;
use Apisearch\Server\Domain\Event\ItemsWereIndexed;
use Apisearch\Server\Domain\Event\ItemsWereUpdated;
use Apisearch\Server\Domain\Event\QueryWasMade;
use Apisearch\Server\Domain\Event\TokensWereDeleted;
use Apisearch\Server\Domain\Event\TokenWasDeleted;
use Apisearch\Server\Domain\Event\TokenWasPut;
use Apisearch\Server\Domain\Formatter\TimeFormatBuilder;
use Clue\React\Redis\Client;
use Drift\HttpKernel\AsyncKernel;
use Drift\HttpKernel\Event\DomainEventEnvelope;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class DomainEventSubscriber.
 */
class DomainEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var Client
     */
    private $redisClient;

    /**
     * @var TimeFormatBuilder
     */
    private $timeFormatBuilder;

    /**
     * @var string
     */
    private $key;

    /**
     * @var string
     */
    private $service;

    /**
     * @var string
     */
    private $environment;

    /**
     * @var string
     */
    private $kernelUID;

    /**
     * RedisMetadataRepository constructor.
     *
     * @param Client            $redisClient
     * @param TimeFormatBuilder $timeFormatBuilder
     * @param AsyncKernel       $kernel
     * @param string            $key
     * @param string            $service
     * @param string            $environment
     */
    public function __construct(
        Client $redisClient,
        TimeFormatBuilder $timeFormatBuilder,
        AsyncKernel $kernel,
        string $key,
        string $service,
        string $environment
    ) {
        $this->redisClient = $redisClient;
        $this->timeFormatBuilder = $timeFormatBuilder;
        $this->key = $key;
        $this->service = $service;
        $this->environment = $environment;
        $this->kernelUID = $kernel->getUID();
    }

    /**
     * Handle event.
     *
     * @param DomainEventEnvelope $envelopedEvent
     */
    public function handle(DomainEventEnvelope $envelopedEvent)
    {
        $event = $envelopedEvent->getDomainEvent();

        if (
            !$event instanceof DomainEvent ||
            \is_null($event->getRepositoryReference())
        ) {
            return;
        }

        $level = $event instanceof ExceptionWasCached
            ? 400
            : 200;
        $reducedArray = $event->toLogger();
        $reducedArray['occurred_on'] = $this
            ->timeFormatBuilder
            ->formatTimeFromMillisecondsToBasicDateTime(
                $event->occurredOn()
            );

        $data = \json_encode([
            'environment' => $this->environment,
            'kernel_uid' => $this->kernelUID,
            'service' => $this->service,
            'repository_reference' => $event
                ->getRepositoryReference()
                ->compose(),
        ] + $reducedArray);

        $this
            ->redisClient
            ->rpush($this->key, \json_encode([
                '@fields' => [
                    'channel' => 'apisearch_to_logstash',
                    'level' => $level,
                    'memory_usage' => \memory_get_usage(true),
                    'memory_peak_usage' => \memory_get_peak_usage(true),
                ],
                '@message' => $data,
                '@type' => 'apisearch',
                '@tags' => [
                    'apisearch_to_logstash',
                ],
            ]));
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            ItemsWereIndexed::class => ['handle', 0],
            ItemsWereUpdated::class => ['handle', 0],
            ItemsWereDeleted::class => ['handle', 0],

            QueryWasMade::class => ['handle', 0],

            IndexWasConfigured::class => ['handle', 0],
            IndexWasCreated::class => ['handle', 0],
            IndexWasDeleted::class => ['handle', 0],

            TokenWasPut::class => ['handle', 0],
            TokenWasDeleted::class => ['handle', 0],
            TokensWereDeleted::class => ['handle', 0],

            ExceptionWasCached::class => ['handle', 0],
        ];
    }
}
