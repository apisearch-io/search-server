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

namespace Apisearch\Server\Domain\Query;

use Apisearch\Model\Token;
use Apisearch\Repository\RepositoryReference;
use Apisearch\Server\Domain\CommandWithRepositoryReferenceAndToken;
use DateTime;

/**
 * Class GetUsage.
 */
class GetUsage extends CommandWithRepositoryReferenceAndToken
{
    private ?DateTime $from;
    private ?DateTime $to;
    private ?string $eventName;
    private bool $perDay;

    /**
     * @param RepositoryReference $repositoryReference
     * @param Token               $token
     * @param DateTime            $from
     * @param DateTime|null       $to
     * @param string|null         $eventName
     * @param bool                $perDay
     */
    public function __construct(
        RepositoryReference $repositoryReference,
        Token $token,
        DateTime $from,
        ?DateTime $to,
        ?string $eventName,
        bool $perDay
    ) {
        parent::__construct($repositoryReference, $token);

        $this->from = $from;
        $this->to = $to;
        $this->eventName = $eventName;
        $this->perDay = $perDay;
    }

    /**
     * @return DateTime
     */
    public function getFrom(): DateTime
    {
        return $this->from;
    }

    /**
     * @return DateTime|null
     */
    public function getTo(): ?DateTime
    {
        return $this->to;
    }

    /**
     * @return string|null
     */
    public function getEventName(): ?string
    {
        return $this->eventName;
    }

    /**
     * @return bool
     */
    public function isPerDay(): bool
    {
        return $this->perDay;
    }
}
