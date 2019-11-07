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

namespace Apisearch\Plugin\ELK\Domain\Event;

use Apisearch\Plugin\Redis\Domain\RedisWrapper;
use Apisearch\Server\Domain\Event\DomainEventWithRepositoryReference;
use Apisearch\Server\Domain\Event\EventSubscriber;
use Apisearch\Server\Domain\Event\ExceptionWasCached;
use Apisearch\Server\Domain\Formatter\TimeFormatBuilder;
use Monolog\Logger;
use React\Promise\PromiseInterface;

/**
 * Class DomainEventSubscriber.
 */
class DomainEventSubscriber implements EventSubscriber
{
    /**
     * @var RedisWrapper
     *
     * RedisWrapper
     */
    private $redisWrapper;

    /**
     * @var TimeFormatBuilder
     *
     * Time format builder
     */
    private $timeFormatBuilder;

    /**
     * @var string
     *
     * Key
     */
    private $key;

    /**
     * @var string
     *
     * Service
     */
    private $service;

    /**
     * @var string
     *
     * Environment
     */
    private $environment;

    /**
     * RedisMetadataRepository constructor.
     *
     * @param RedisWrapper      $redisWrapper
     * @param TimeFormatBuilder $timeFormatBuilder
     * @param string            $key
     * @param string            $service
     * @param string            $environment
     */
    public function __construct(
        RedisWrapper $redisWrapper,
        TimeFormatBuilder $timeFormatBuilder,
        string $key,
        string $service,
        string $environment
    ) {
        $this->redisWrapper = $redisWrapper;
        $this->timeFormatBuilder = $timeFormatBuilder;
        $this->key = $key;
        $this->service = $service;
        $this->environment = $environment;
    }

    /**
     * Subscriber should handle event.
     *
     * @param DomainEventWithRepositoryReference $domainEventWithRepositoryReference
     *
     * @return bool
     */
    public function shouldHandleEvent(DomainEventWithRepositoryReference $domainEventWithRepositoryReference): bool
    {
        return true;
    }

    /**
     * Handle event.
     *
     * @param DomainEventWithRepositoryReference $domainEventWithRepositoryReference
     *
     * @return PromiseInterface
     */
    public function handle(DomainEventWithRepositoryReference $domainEventWithRepositoryReference): PromiseInterface
    {
        $event = $domainEventWithRepositoryReference->getDomainEvent();
        $level = $event instanceof ExceptionWasCached
            ? Logger::ERROR
            : Logger::INFO;
        $reducedArray = $event->toLogger();
        $reducedArray['occurred_on'] = $this
            ->timeFormatBuilder
            ->formatTimeFromMillisecondsToBasicDateTime(
                $event->occurredOn()
            );

        $data = json_encode([
            'environment' => $this->environment,
            'service' => $this->service,
            'repository_reference' => $domainEventWithRepositoryReference
                ->getRepositoryReference()
                ->compose(),
            'time_cost' => $domainEventWithRepositoryReference->getTimeCost(),
        ] + $reducedArray);

        return $this
            ->redisWrapper
            ->getClient()
            ->rpush($this->key, json_encode([
                '@fields' => [
                    'channel' => 'apisearch_to_logstash',
                    'level' => $level,
                    'memory_usage' => memory_get_usage(true),
                    'memory_peak_usage' => memory_get_peak_usage(true),
                ],
                '@message' => $data,
                '@type' => 'apisearch',
                '@tags' => [
                    'apisearch_to_logstash',
                ],
            ]));
    }
}
