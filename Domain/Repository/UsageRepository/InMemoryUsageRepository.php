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
use Apisearch\Server\Domain\Repository\ResetableRepository;
use DateTime;
use React\Promise\PromiseInterface;
use function React\Promise\resolve;

/**
 * Class InMemoryUsageRepository.
 */
class InMemoryUsageRepository implements TemporaryUsageRepository, ResetableRepository
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
            $this->useLines[$useLineHash]->increaseBy($n);
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
                (
                    '*' === $appUUID->composeUUID() ||
                    $appUUID->composeUUID() === $useLine->getAppUUID()
                ) &&
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
     * @param DateTime $from
     * @param DateTime $to
     *
     * @return PromiseInterface
     */
    public function optimize(
        DateTime $from,
        DateTime $to
    ): PromiseInterface {
        $fromFormatted = \intval($from->format('Ymd'));
        $toFormatted = \intval($to->format('Ymd'));
        $optimizedLines = [];

        \array_walk($this->useLines, function (UseLine $line, $key) use ($fromFormatted, $toFormatted, &$optimizedLines) {
            $whenFormatted = \intval($line->getWhen()->format('Ymd'));

            if (
                $whenFormatted < $fromFormatted ||
                $whenFormatted >= $toFormatted
            ) {
                return;
            }

            $useLineHash = \sprintf('%s_%s_%s_%d', $line->getEvent(), $line->getAppUUID(), $line->getIndexUUID(), $line->getWhen()->format('Ymd'));
            $n = $line->getN();

            if (!\array_key_exists($useLineHash, $optimizedLines)) {
                $optimizedLines[$useLineHash] = $line;
            } else {
                $optimizedLines[$useLineHash]->increaseBy($n);
            }

            unset($this->useLines[$key]);
        });

        $this->useLines = \array_merge(
            $this->useLines,
            $optimizedLines
        );

        return resolve();
    }

    /**
     * Get number of rows.
     *
     * @return PromiseInterface<int>
     */
    public function getNumberOfRows(): PromiseInterface
    {
        return resolve(\count($this->useLines));
    }

    /**
     * {@inheritdoc}
     */
    public function getAndResetUseLines(): array
    {
        $useLines = $this->useLines;
        $this->reset();

        return $useLines;
    }

    /**
     * @return PromiseInterface
     */
    public function reset(): PromiseInterface
    {
        $this->useLines = [];

        return resolve();
    }
}
