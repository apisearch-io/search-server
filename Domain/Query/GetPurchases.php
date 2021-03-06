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
 * Class GetPurchases.
 */
class GetPurchases extends CommandWithRepositoryReferenceAndToken
{
    private ?DateTime $from;
    private ?DateTime $to;
    private bool $perDay;
    private ?string $user;
    private ?string $itemId;
    private ?string $count;

    /**
     * @param RepositoryReference $repositoryReference
     * @param Token               $token
     * @param DateTime|null       $from
     * @param DateTime|null       $to
     * @param bool                $perDay
     * @param string|null         $user
     * @param string|null         $itemId
     * @param string|null         $count
     */
    public function __construct(
        RepositoryReference $repositoryReference,
        Token $token,
        ?DateTime $from,
        ?DateTime $to,
        bool $perDay,
        ?string $user,
        ?string $itemId,
        ?string $count
    ) {
        parent::__construct($repositoryReference, $token);

        $this->from = $from;
        $this->to = $to;
        $this->perDay = $perDay;
        $this->user = $user;
        $this->itemId = $itemId;
        $this->count = $count;
    }

    /**
     * @return DateTime|null
     */
    public function getFrom(): ?DateTime
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
     * @return bool
     */
    public function isPerDay(): bool
    {
        return $this->perDay;
    }

    /**
     * @return string|null
     */
    public function getUser(): ?string
    {
        return $this->user;
    }

    /**
     * @return string|null
     */
    public function getItemId(): ?string
    {
        return $this->itemId;
    }

    /**
     * @return string|null
     */
    public function getCount(): ?string
    {
        return $this->count;
    }
}
