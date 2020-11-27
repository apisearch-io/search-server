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

namespace Apisearch\Server\Domain\Repository\LogRepository;

use Apisearch\Repository\RepositoryReference;
use DateTime;

/**
 * Class LogFilter.
 */
class LogFilter
{
    private RepositoryReference $repositoryReference;
    private ?DateTime $from = null;
    private ?DateTime $to = null;
    private array $types = [];
    private array $pagination = [];

    /**
     * @param RepositoryReference $repositoryReference
     *
     * @return self
     */
    public static function create(RepositoryReference $repositoryReference): self
    {
        $filter = new self();
        $filter->repositoryReference = $repositoryReference;

        return $filter;
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
     * @param string[] $types
     *
     * @return self
     */
    public function fromTypes(array $types): self
    {
        $this->types = $types;

        return $this;
    }

    /**
     * @param int $limit
     * @param int $page
     *
     * @return $this
     */
    public function paginate(int $limit, int $page): self
    {
        if (
            $limit > 0 &&
            $page > 0
        ) {
            $this->pagination = [$limit, $page];
        }

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
     * @return array
     */
    public function getTypes(): array
    {
        return $this->types;
    }

    /**
     * @return array
     */
    public function getPagination(): array
    {
        return $this->pagination;
    }
}
