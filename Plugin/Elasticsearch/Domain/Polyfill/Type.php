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

namespace Apisearch\Plugin\Elasticsearch\Domain\Polyfill;

use Elasticsearch\Endpoints\AbstractEndpoint;

/**
 * Class Type.
 */
class Type
{
    /**
     * Set type to endpoint.
     *
     * @param AbstractEndpoint $endpoint
     * @param string           $version
     *
     * @return void
     */
    public static function setEndpointType(
        AbstractEndpoint $endpoint,
        string $version
    ): void {
        if ('6' == $version) {
            $endpoint->setType('item');
        }
    }
}
