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

namespace Apisearch\Server\Domain\Repository\InteractionRepository;

use Apisearch\Model\ItemUUID;
use Apisearch\Repository\RepositoryReference;
use DateTime;

/**
 * Class InteractionFilter.
 */
final class InteractionFilter
{
    const LINES = 'lines';
    const UNIQUE_USERS = 'unique_users';

    private RepositoryReference $repositoryReference;
    private bool $perDay;
    private ?DateTime $from = null;
    private ?DateTime $to = null;
    private ?string $user = null;
    private ?string $platform = null;
    private ?string $context = null;
    private ?ItemUUID $itemUUID = null;
    private ?string $type = null;
    private ?string $count = null;

    private function __construct()
    {
    }

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
        $filter->count = self::LINES;

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
     * @var string|null
     *
     * @return self
     */
    public function byPlatform(?string $platform): self
    {
        $this->platform = $platform;

        return $this;
    }

    /**
     * @var string|null
     *
     * @return self
     */
    public function fromContext(?string $context): self
    {
        $this->context = $context;

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
    public function byType(?string $type): self
    {
        $this->type = $type;

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
     * @return RepositoryReference|null
     */
    public function getRepositoryReference(): ?RepositoryReference
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
     * @return string|null
     */
    public function getUser(): ?string
    {
        return $this->user;
    }

    /**
     * @return string|null
     */
    public function getPlatform(): ?string
    {
        return $this->platform;
    }

    /**
     * @return string|null
     */
    public function getContext(): ?string
    {
        return $this->context;
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
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getCount(): string
    {
        return $this->count ?? self::LINES;
    }
}
