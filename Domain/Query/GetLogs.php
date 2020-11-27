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
use Apisearch\Server\Domain\Repository\LogRepository\LogFilter;

/**
 * Class GetLogs.
 */
class GetLogs extends CommandWithRepositoryReferenceAndToken
{
    private LogFilter $logFilter;

    /**
     * @param RepositoryReference $repositoryReference
     * @param Token               $token
     * @param LogFilter           $logFilter
     */
    public function __construct(
        RepositoryReference $repositoryReference,
        Token $token,
        LogFilter $logFilter
    ) {
        parent::__construct($repositoryReference, $token);

        $this->logFilter = $logFilter;
    }

    /**
     * @return LogFilter
     */
    public function getLogFilter(): LogFilter
    {
        return $this->logFilter;
    }
}
