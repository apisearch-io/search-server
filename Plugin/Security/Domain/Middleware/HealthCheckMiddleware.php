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

namespace Apisearch\Plugin\Security\Domain\Middleware;

use Apisearch\Plugin\Redis\Domain\Middleware\HealthCheckMiddleware as RedisHealthCheckMiddleware;

/**
 * Class HealthCheckMiddleware.
 */
class HealthCheckMiddleware extends RedisHealthCheckMiddleware
{
    /**
     * @return string
     */
    protected function getSuffix(): string
    {
        return 'security';
    }
}
