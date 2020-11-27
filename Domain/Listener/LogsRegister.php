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

use Apisearch\Repository\RepositoryReference;
use Apisearch\Server\Domain\Event;
use Apisearch\Server\Domain\Repository\LogRepository\LogMapper;
use Apisearch\Server\Domain\Repository\LogRepository\LogRepository;
use DateTime;
use DateTimeZone;
use Drift\HttpKernel\Event\DomainEventEnvelope;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class LogsRegister.
 */
class LogsRegister implements EventSubscriberInterface
{
    private LogRepository $logRepository;

    /**
     * @param LogRepository $logRepository
     */
    public function __construct(LogRepository $logRepository)
    {
        $this->logRepository = $logRepository;
    }

    /**
     * @param DomainEventEnvelope $eventEnvelope
     */
    public function logIndexWasCreated(DomainEventEnvelope $eventEnvelope)
    {
        /**
         * @var Event\IndexWasCreated
         */
        $indexWasCreated = $eventEnvelope->getDomainEvent();
        $this->logEvent(
            $indexWasCreated->getRepositoryReference(),
            LogMapper::INDEX_WAS_CREATED
        );
    }

    /**
     * @param DomainEventEnvelope $eventEnvelope
     */
    public function logIndexWasDeleted(DomainEventEnvelope $eventEnvelope)
    {
        /**
         * @var Event\IndexWasDeleted
         */
        $indexWasDeleted = $eventEnvelope->getDomainEvent();
        $this->logEvent(
            $indexWasDeleted->getRepositoryReference(),
            LogMapper::INDEX_WAS_DELETED
        );
    }

    /**
     * @param DomainEventEnvelope $eventEnvelope
     */
    public function logIndexWasConfigured(DomainEventEnvelope $eventEnvelope)
    {
        /**
         * @var Event\IndexWasConfigured
         */
        $indexWasConfigured = $eventEnvelope->getDomainEvent();
        $this->logEvent(
            $indexWasConfigured->getRepositoryReference(),
            LogMapper::INDEX_WAS_CONFIGURED,
        );
    }

    /**
     * @param DomainEventEnvelope $eventEnvelope
     */
    public function logIndexWasReset(DomainEventEnvelope $eventEnvelope)
    {
        /**
         * @var Event\IndexWasReset
         */
        $indexWasReset = $eventEnvelope->getDomainEvent();
        $this->logEvent(
            $indexWasReset->getRepositoryReference(),
            LogMapper::INDEX_WAS_RESET
        );
    }

    /**
     * @param DomainEventEnvelope $eventEnvelope
     */
    public function logIndexWasImported(DomainEventEnvelope $eventEnvelope)
    {
        /**
         * @var Event\IndexWasImported
         */
        $indexWasImported = $eventEnvelope->getDomainEvent();
        $this->logEvent(
            $indexWasImported->getRepositoryReference(),
            LogMapper::INDEX_WAS_IMPORTED,
            LogMapper::createIndexWasImportedLogParams(
                $indexWasImported->getNumberOfItems(),
                $indexWasImported->getVersion(),
                $indexWasImported->wereOldItemsRemoved()
            )
        );
    }

    /**
     * @param DomainEventEnvelope $eventEnvelope
     */
    public function logIndexWasExported(DomainEventEnvelope $eventEnvelope)
    {
        /**
         * @var Event\IndexWasExported
         */
        $indexWasExported = $eventEnvelope->getDomainEvent();
        $this->logEvent(
            $indexWasExported->getRepositoryReference(),
            LogMapper::INDEX_WAS_EXPORTED
        );
    }

    /**
     * @param DomainEventEnvelope $eventEnvelope
     */
    public function logTokenWasPut(DomainEventEnvelope $eventEnvelope)
    {
        /**
         * @var Event\TokenWasPut
         */
        $tokenWasPut = $eventEnvelope->getDomainEvent();
        $this->logEvent(
            $tokenWasPut->getRepositoryReference(),
            LogMapper::TOKEN_WAS_PUT,
            LogMapper::createTokenLogParams($tokenWasPut->getToken()->getTokenUUID())
        );
    }

    /**
     * @param DomainEventEnvelope $eventEnvelope
     */
    public function logTokenWasDeleted(DomainEventEnvelope $eventEnvelope)
    {
        /**
         * @var Event\TokenWasDeleted
         */
        $tokenWasDeleted = $eventEnvelope->getDomainEvent();
        $this->logEvent(
            $tokenWasDeleted->getRepositoryReference(),
            LogMapper::TOKEN_WAS_DELETED,
            LogMapper::createTokenLogParams($tokenWasDeleted->getTokenUUID())
        );
    }

    /**
     * @param DomainEventEnvelope $eventEnvelope
     */
    public function logTokensWereDeleted(DomainEventEnvelope $eventEnvelope)
    {
        /**
         * @var Event\TokensWereDeleted
         */
        $tokenWereDeleted = $eventEnvelope->getDomainEvent();
        $this->logEvent(
            $tokenWereDeleted->getRepositoryReference(),
            LogMapper::TOKENS_WERE_DELETED
        );
    }

    /**
     * @param DomainEventEnvelope $eventEnvelope
     */
    public function logExceptionWasCached(DomainEventEnvelope $eventEnvelope)
    {
        /**
         * @var Event\ExceptionWasCached
         */
        $exceptionWasCached = $eventEnvelope->getDomainEvent();
        $this->logEvent(
            $exceptionWasCached->getRepositoryReference(),
            LogMapper::EXCEPTION_WAS_CACHED,
            LogMapper::createExceptionLogParams($exceptionWasCached->getException())
        );
    }

    /**
     * @param RepositoryReference $repositoryReference
     * @param string              $type
     * @param array               $params
     */
    public function logEvent(
        RepositoryReference $repositoryReference,
        string $type,
        array $params = []
    ) {
        $this
            ->logRepository
            ->log(
                $repositoryReference,
                new DateTime('now', new DateTimeZone('UTC')),
                1,
                $type,
                $params
            );
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Event\IndexWasCreated::class => 'logIndexWasCreated',
            Event\IndexWasDeleted::class => 'logIndexWasDeleted',
            Event\IndexWasConfigured::class => 'logIndexWasConfigured',
            Event\IndexWasReset::class => 'logIndexWasReset',
            Event\IndexWasImported::class => 'logIndexWasImported',
            Event\IndexWasExported::class => 'logIndexWasExported',

            Event\TokenWasPut::class => 'logTokenWasPut',
            Event\TokenWasDeleted::class => 'logTokenWasDeleted',
            Event\TokensWereDeleted::class => 'logTokensWereDeleted',

            Event\ExceptionWasCached::class => 'logExceptionWasCached',
        ];
    }
}
