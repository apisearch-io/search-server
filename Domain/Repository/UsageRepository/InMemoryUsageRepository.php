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

        $indexUUID = $repositoryReference->getIndexUUID();
        $this->useLines[] = new UseLine(
            $eventName,
            $appUUID->composeUUID(),
            $indexUUID instanceof IndexUUID ? $indexUUID->composeUUID() : null,
            $when,
            $n
        );

        return resolve();
    }

    /**
     * Get registered events.
     *
     * @param RepositoryReference $repositoryReference
     * @param string|null         $eventType
     * @param DateTime            $from
     * @param DateTime|null       $to
     *
     * @return PromiseInterface
     */
    public function getRegisteredEvents(
        RepositoryReference $repositoryReference,
        ?string $eventType,
        DateTime $from,
        ?DateTime $to = null
    ): PromiseInterface {
        $appUUID = $repositoryReference->getAppUUID();
        if (!$appUUID instanceof AppUUID) {
            return resolve([]);
        }

        $indexUUID = $repositoryReference->getIndexUUID();
        $finalUses = [];

        foreach ($this->useLines as $useLine) {
            $event = $useLine->getEvent();
            $when = $useLine->getWhen()->getTimestamp();

            if (!(
                (
                    \is_null($eventType) ||
                    $event === $eventType
                ) &&
                $when >= $from->getTimestamp() &&
                $appUUID->composeUUID() === $useLine->getAppUUID() &&
                (
                    \is_null($indexUUID) ||
                    '' === $indexUUID->composeUUID() ||
                    '*' === $indexUUID->composeUUID() ||
                    $indexUUID->composeUUID() === $useLine->getIndexUUID()
                ) &&
                (
                    \is_null($to) ||
                    $to->getTimestamp() > $when
                )
            )) {
                continue;
            }

            if (!\array_key_exists($event, $finalUses)) {
                $finalUses[$event] = 0;
            }

            $finalUses[$event] += $useLine->getN();
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
