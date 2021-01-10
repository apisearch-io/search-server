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

namespace Apisearch\Server\Domain\Listener;

use Apisearch\Model\Token;
use Apisearch\Server\Domain\Event;
use Apisearch\Server\Domain\Event\DomainEvent;
use Apisearch\Server\Domain\Repository\UsageRepository\UsageRepository;
use DateTime;
use DateTimeZone;
use Drift\HttpKernel\Event\DomainEventEnvelope;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class UsageRegister.
 */
class UsageRegister implements EventSubscriberInterface
{
    private UsageRepository $usageRepository;
    private string $godToken;
    private bool $registerGodUsage;

    /**
     * @param UsageRepository $usageRepository
     * @param string          $godToken
     * @param bool            $registerGodUsage
     */
    public function __construct(
        UsageRepository $usageRepository,
        string $godToken,
        bool $registerGodUsage
    ) {
        $this->usageRepository = $usageRepository;
        $this->godToken = $godToken;
        $this->registerGodUsage = $registerGodUsage;
    }

    /**
     * Register query event usage.
     *
     * @param DomainEventEnvelope $domainEventEnvelope
     */
    public function registerQueryEventUsage(DomainEventEnvelope $domainEventEnvelope)
    {
        /**
         * @var DomainEvent
         */
        $event = $domainEventEnvelope->getDomainEvent();
        if ($this->eventCanBeRegistered($event)) {
            $this->registerEventUsage($event, 'query');
        }
    }

    /**
     * Register admin event usage.
     *
     * @param DomainEventEnvelope $domainEventEnvelope
     */
    public function registerAdminEventUsage(DomainEventEnvelope $domainEventEnvelope)
    {
        /**
         * @var DomainEvent
         */
        $event = $domainEventEnvelope->getDomainEvent();
        if ($this->eventCanBeRegistered($event)) {
            $this->registerEventUsage($event, 'admin');
        }
    }

    /**
     * Register event by name.
     *
     * @param DomainEvent $event
     * @param string      $eventName
     */
    public function registerEventUsage(
        DomainEvent $event,
        string $eventName
    ) {
        $today = new DateTime('now', new DateTimeZone('UTC'));
        $today->setTime(0, 0, 0);

        $this
            ->usageRepository
            ->registerEvent(
                $event->getRepositoryReference(),
                $eventName,
                $today
            );
    }

    /**
     * @param DomainEvent $event
     *
     * @return bool
     */
    private function eventCanBeRegistered(DomainEvent $event)
    {
        $token = $event->getDispatchedBy();
        if ($token instanceof Token) {
            return
                $this->registerGodUsage ||
                $token->getTokenUUID()->composeUUID() !== $this->godToken;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        $queryEvents = [
            Event\QueryWasMade::class,
        ];

        $eventsStructure = [];
        foreach ($queryEvents as $event) {
            $eventsStructure[$event] = [
                ['registerQueryEventUsage', 0],
            ];
        }

        $adminEvents = [
            Event\ItemsWereIndexed::class,
            Event\ItemsWereUpdated::class,
            Event\ItemsWereDeleted::class,

            Event\IndexWasCreated::class,
            Event\IndexWasConfigured::class,
            Event\IndexWasDeleted::class,
            Event\IndexWasReset::class,

            Event\TokenWasPut::class,
            Event\TokenWasDeleted::class,
            Event\TokensWereDeleted::class,
        ];

        foreach ($adminEvents as $event) {
            $eventsStructure[$event] = [
                ['registerAdminEventUsage', 0],
            ];
        }

        return $eventsStructure;
    }
}
