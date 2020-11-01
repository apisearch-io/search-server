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

use Apisearch\Plugin\Elasticsearch\Tests\ElasticFunctionalTestTrait;
use Apisearch\Server\Tests\Functional\HttpFunctionalTest;

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
        $response = $this->checkHealth();
        $this->assertTrue(
            \in_array(
                $response['status']['elasticsearch'],
                ['green', 'yellow']
            )
        );

        $this->assertNotEmpty($response['info']['plugins']['elasticsearch']);
        $this->assertNotEmpty($response['info']['elasticsearch']);
        $this->assertEquals(3, $response['info']['elasticsearch']['number_of_indices']);
    }
}
