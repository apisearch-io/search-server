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

namespace Apisearch\Plugin\DBAL\Tests\Functional;

use Apisearch\Http\Http;
use Apisearch\Server\Tests\Functional\HttpFunctionalTest;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class HealthTest.
 */
class HealthTest extends HttpFunctionalTest
{
    use DBALFunctionalTestTrait;

    /**
     * Test if health check has redis.
     */
    public function testCheckHealth()
    {
        $request = new Request();
        $request->setMethod('GET');
        $request->server->set('REQUEST_URI', '/health');
        $request->headers->set(Http::TOKEN_ID_HEADER, self::$godToken);
        $promise = static::$kernel
            ->handleAsync($request)
            ->then(function ($response) {
                $content = \json_decode($response->getContent(), true);
                $this->assertTrue($content['status']['dbal']);
            });

        $this->await($promise);
    }
}
