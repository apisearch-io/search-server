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

namespace Apisearch\Server\Tests\Functional\Http;

use Apisearch\Query\Query;
use Apisearch\Server\Tests\Functional\HttpFunctionalTest;

/**
 * Class CacheTest.
 */
class CacheTest extends HttpFunctionalTest
{
    /**
     * test http cache headers.
     */
    public function testHttpCacheHeaders(): void
    {
        $this->query(Query::createMatchAll());
        $this->assertEquals('max-age=0, private, s-maxage=0', self::$lastResponse['headers']['cache-control'][0]);
        $token = $this->createTokenByIdAndAppId('123', self::$appId, 60);
        $this->putToken($token);

        $this->query(Query::createMatchAll(), null, null, $token);
        $this->assertEquals('max-age=60, public, s-maxage=60', self::$lastResponse['headers']['cache-control'][0]);
    }
}
