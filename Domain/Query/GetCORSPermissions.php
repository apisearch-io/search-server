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

use Apisearch\Repository\RepositoryReference;
use Apisearch\Repository\WithRepositoryReferenceTrait;
use Apisearch\Server\Domain\IndexRequiredCommand;
use Apisearch\Server\Domain\Model\Origin;

/**
 * Class GetCORSPermissions.
 */
class GetCORSPermissions implements IndexRequiredCommand
{
    use WithRepositoryReferenceTrait;

    /**
     * @var Origin
     */
    private $origin;

    /**
     * @param RepositoryReference $repositoryReference
     * @param Origin              $origin
     */
    public function __construct(
        RepositoryReference $repositoryReference,
        Origin $origin
    ) {
        $this->repositoryReference = $repositoryReference;
        $this->origin = $origin;
    }

    /**
     * @return Origin
     */
    public function getOrigin(): Origin
    {
        return $this->origin;
    }
}
