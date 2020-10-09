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

namespace Apisearch\Plugin\Elasticsearch\Tests\Http;

use Apisearch\Http\Http;
use Apisearch\Plugin\Elasticsearch\Tests\ElasticFunctionalTestTrait;
use Apisearch\Server\Tests\Functional\HttpFunctionalTest;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class HealthCheckTest.
 */
class HealthTest extends HttpFunctionalTest
{
    use ElasticFunctionalTestTrait;

    /**
     * Test health check.
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
                $this->assertTrue(
                    \in_array(
                        $content['status']['elasticsearch'],
                        ['green', 'yellow']
                    )
                );

                $this->assertNotEmpty($content['info']['plugins']['elasticsearch']);
                $this->assertNotEmpty($content['info']['elasticsearch']);
                $this->assertEquals(3, $content['info']['elasticsearch']['number_of_indices']);
            });

        $this->await($promise);
    }
}
