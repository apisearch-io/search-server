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

namespace Apisearch\Server\Domain\QueryHandler;

use Apisearch\Server\Domain\Model\CrontabLine;
use Apisearch\Server\Domain\Query\GetCrontab;
use function React\Promise\resolve;
use React\Promise\PromiseInterface;

/**
 * Class GetCrontabHandler.
 */
class GetCrontabHandler
{
    /**
     * @param GetCrontab $getCrontab
     *
     * @return PromiseInterface<CrontabLine[]>
     */
    public function handle(GetCrontab $getCrontab): PromiseInterface
    {
        return resolve($getCrontab->getLines());
    }
}
