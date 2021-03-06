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

namespace Apisearch\Server\Domain\Repository\SearchesRepository;

use Apisearch\Repository\RepositoryReference;
use DateTime;

/**
 * Class SearchesFilter.
 */
class SearchesFilter
{
    const LINES = 'lines';
    const UNIQUE_USERS = 'unique_users';

    private RepositoryReference $repositoryReference;
    private bool $perDay;
    private ?DateTime $from = null;
    private ?DateTime $to = null;
    private ?string $user = null;
    private ?string $platform = null;
    private bool $excludeWithResults;
    private bool $excludeWithoutResults;

    /**
     * @var string|null
     */
    private ?string $count;

    private function __construct()
    {
    }

    /**
     * @param RepositoryReference $repositoryReference
     *
     * @return SearchesFilter
     */
    public static function create(RepositoryReference $repositoryReference): SearchesFilter
    {
        $filter = new self();
        $filter->repositoryReference = $repositoryReference;
        $filter->perDay = false;
        $filter->excludeWithResults = false;
        $filter->excludeWithoutResults = false;
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
     * @var bool
     *
     * @return self
     */
    public function excludeWithResults(bool $exclude = true): self
    {
        $this->excludeWithResults = $exclude;

        return $this;
    }

    /**
     * @var bool
     *
     * @return self
     */
    public function excludeWithoutResults(bool $exclude = true): self
    {
        $this->excludeWithoutResults = $exclude;

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
     * @return bool
     */
    public function withResultsAreExcluded(): bool
    {
        return $this->excludeWithResults;
    }

    /**
     * @return bool
     */
    public function withoutResultsAreExcluded(): bool
    {
        return $this->excludeWithoutResults;
    }

    /**
     * @return string
     */
    public function getCount(): string
    {
        return $this->count ?? self::LINES;
    }
}
