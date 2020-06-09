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
 * Class GetTopSearches.
 */
class GetTopSearches extends CommandWithRepositoryReferenceAndToken
{
    /**
     * @var DateTime|null
     */
    private $from;

    /**
     * @var DateTime|null
     */
    private $to;

    /**
     * @var string|null
     */
    private $platform;

    /**
     * @var string|null
     */
    private $user;

    /**
     * @var bool
     */
    private $excludeWithResults;

    /**
     * @var bool
     */
    private $excludeWithoutResults;

    /**
     * @var int|null
     */
    private $n;

    /**
     * @param RepositoryReference $repositoryReference
     * @param Token               $token
     * @param DateTime|null       $from
     * @param DateTime|null       $to
     * @param string|null         $platform
     * @param string|null         $user
     * @param bool                $excludeWithResults
     * @param bool                $excludeWithoutResults
     * @param int                 $n
     */
    public function __construct(
        RepositoryReference $repositoryReference,
        Token $token,
        ?DateTime $from,
        ?DateTime $to,
        ?string $platform,
        ?string $user,
        bool $excludeWithResults,
        bool $excludeWithoutResults,
        ?int $n
    ) {
        parent::__construct($repositoryReference, $token);

        $this->from = $from;
        $this->to = $to;
        $this->platform = $platform;
        $this->user = $user;
        $this->excludeWithResults = $excludeWithResults;
        $this->excludeWithoutResults = $excludeWithoutResults;
        $this->n = $n;
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
    public function getPlatform(): ?string
    {
        return $this->platform;
    }

    /**
     * @return string|null
     */
    public function getUser(): ?string
    {
        return $this->user;
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
     * @return int|null
     */
    public function getN(): ?int
    {
        return $this->n;
    }
}
