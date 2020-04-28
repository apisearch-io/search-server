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

/**
 * Class ExportIndex.
 */
class ExportIndex
{
    /**
     * @var RepositoryReference
     */
    private $repositoryReference;

    /**
     * @param RepositoryReference $repositoryReference
     */
    public function __construct(RepositoryReference $repositoryReference)
    {
        $this->repositoryReference = $repositoryReference;
    }

    /**
     * @return RepositoryReference
     */
    public function getRepositoryReference(): RepositoryReference
    {
        return $this->repositoryReference;
    }
}
