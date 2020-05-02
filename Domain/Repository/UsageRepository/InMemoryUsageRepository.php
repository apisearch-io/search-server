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

namespace Apisearch\Server\Domain\Repository\UsageRepository;

use Apisearch\Model\AppUUID;
use Apisearch\Model\IndexUUID;
use Apisearch\Repository\RepositoryReference;
use DateTime;
use function React\Promise\resolve;
use React\Promise\PromiseInterface;

/**
 * Class InMemoryUsageRepository.
 */
class InMemoryUsageRepository implements UsageRepository, TemporaryUsageRepository
{
    /**
     * @var array
     */
    private $useLines = [];

    /**
     * Register event.
     *
     * @param RepositoryReference $repositoryReference
     * @param string              $eventName
     * @param DateTime            $when
     * @param int                 $n
     *
     * @return PromiseInterface
     */
    public function registerEvent(
        RepositoryReference $repositoryReference,
        string $eventName,
        DateTime $when,
        int $n = 1
    ): PromiseInterface {
        $appUUID = $repositoryReference->getAppUUID();
        if (!$appUUID instanceof AppUUID) {
            return resolve([]);
        }
        $when->setTime(0, 0, 0);
        $indexUUID = $repositoryReference->getIndexUUID();
        $indexUUIDComposed = $indexUUID instanceof IndexUUID ? $indexUUID->composeUUID() : null;
        $appUUIDComposed = $appUUID->composeUUID();
        $useLineHash = \sprintf('%s_%s_%s_%d', $eventName, $appUUIDComposed, $indexUUIDComposed, $when->format('Ymd'));
        if (!\array_key_exists($useLineHash, $this->useLines)) {
            $this->useLines[$useLineHash] = new UseLine(
                $eventName,
                $appUUIDComposed,
                $indexUUIDComposed,
                $when,
                $n
            );
        } else {
            $this->useLines[$useLineHash]->increaseBy(1);
        }

        return resolve();
    }

    /**
     * Get registered events.
     *
     * @param RepositoryReference $repositoryReference
     * @param string|null         $eventType
     * @param DateTime            $from
     * @param DateTime|null       $to
     * @param bool                $perDay
     *
     * @return PromiseInterface
     */
    public function getRegisteredEvents(
        RepositoryReference $repositoryReference,
        ?string $eventType,
        DateTime $from,
        ?DateTime $to = null,
        bool $perDay = false
    ): PromiseInterface {
        $appUUID = $repositoryReference->getAppUUID();
        if (!$appUUID instanceof AppUUID) {
            return resolve([]);
        }

        $indexUUID = $repositoryReference->getIndexUUID();
        $finalUses = [];
        $formattedFrom = \intval($from->format('Ymd'));

        foreach ($this->useLines as $useLine) {
            $event = $useLine->getEvent();
            $whenFormatted = \intval($useLine->getWhen()->format('Ymd'));

            if (!(
                (
                    \is_null($eventType) ||
                    $event === $eventType
                ) &&
                $whenFormatted >= $formattedFrom &&
                $appUUID->composeUUID() === $useLine->getAppUUID() &&
                (
                    \is_null($indexUUID) ||
                    '' === $indexUUID->composeUUID() ||
                    '*' === $indexUUID->composeUUID() ||
                    $indexUUID->composeUUID() === $useLine->getIndexUUID()
                ) &&
                (
                    \is_null($to) ||
                    \intval($to->format('Ymd')) > $whenFormatted
                )
            )) {
                continue;
            }

            $bucket = &$finalUses;
            if ($perDay) {
                $whenFormatted = \strval($whenFormatted);
                if (!\array_key_exists($whenFormatted, $finalUses)) {
                    $finalUses[$whenFormatted] = [];
                }
                $bucket = &$finalUses[$whenFormatted];
            }

            if (!\array_key_exists($event, $bucket)) {
                $bucket[$event] = 0;
            }
            $bucket[$event] += $useLine->getN();
        }

        return resolve($finalUses);
    }

    /**
     * {@inheritdoc}
     */
    public function getAndResetUseLines(): array
    {
        $useLines = $this->useLines;
        $this->useLines = [];

        return $useLines;
    }
}
