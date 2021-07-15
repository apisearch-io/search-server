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

namespace Apisearch\Server\Domain\Repository\PurchaseRepository;

use Apisearch\Model\ItemUUID;
use Apisearch\Repository\RepositoryReference;
use DateTime;

class PurchaseFilter
{
    const LINES = 'lines';
    const UNIQUE_USERS = 'unique_users';

    private RepositoryReference $repositoryReference;
    private bool $perDay;
    private ?DateTime $from = null;
    private ?DateTime $to = null;
    private ?ItemUUID $itemUUID = null;
    private ?string $user = null;
    private ?string $count = null;

    /**
     * @param RepositoryReference $repositoryReference
     *
     * @return self
     */
    public static function create(RepositoryReference $repositoryReference): self
    {
        $filter = new self();
        $filter->repositoryReference = $repositoryReference;
        $filter->perDay = false;
        $filter->count = null;

        return $filter;
    }

    /**
     * @param bool $perDay
     *
     * @return self
     */
    public function perDay(bool $perDay = true): self
    {
        $this->perDay = $perDay;

        return $this;
    }

    /**
     * @param DateTime|null $from
     *
     * @return self
     */
    public function from(?DateTime $from): self
    {
        $this->from = $from;

        return $this;
    }

    /**
     * @param DateTime|null $to
     *
     * @return self
     */
    public function to(?DateTime $to): self
    {
        $this->to = $to;

        return $this;
    }

    /**
     * @var string|null
     *
     * @return self
     */
    public function byUser(?string $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @var ItemUUID|null
     *
     * @return self
     */
    public function byItem(?ItemUUID $itemUUID): self
    {
        $this->itemUUID = $itemUUID;

        return $this;
    }

    /**
     * @var string|null
     *
     * @return self
     */
    public function count(?string $count): self
    {
        $this->count = $count;

        return $this;
    }

    /**
     * @return RepositoryReference
     */
    public function getRepositoryReference(): RepositoryReference
    {
        return $this->repositoryReference;
    }

    /**
     * @return bool
     */
    public function isPerDay(): bool
    {
        return $this->perDay;
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
     * @return ItemUUID|null
     */
    public function getItemUUID(): ?ItemUUID
    {
        return $this->itemUUID;
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
    public function getCount(): ?string
    {
        return $this->count;
    }
}
