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

use Apisearch\Exception\TransportableException;
use Apisearch\Model\AppUUID;
use Apisearch\Model\Token;
use Apisearch\Model\TokenUUID;
use Apisearch\Plugin\Elastica\ElasticaPluginBundle;
use Apisearch\Plugin\RedisStorage\RedisStoragePluginBundle;
use Apisearch\Server\Tests\Functional\CurlFunctionalTest;

/**
 * Class HealthTest.
 */
class HealthTest extends CurlFunctionalTest
{
    /**
     * Test check health with different tokens.
     *
     * @param string $token
     * @param int    $responseCode
     *
     * @dataProvider dataCheckHealth
     */
    public function testCheckHealth(
        string $token,
        int $responseCode
    ) {
        try {
            $result = static::makeCurl(
                'check_health',
                [],
                new Token(
                    TokenUUID::createById($token),
                    AppUUID::createById(self::$appId)
                )
            );
        } catch (TransportableException $exception) {
            $this->assertEquals(
                $responseCode,
                $exception->getTransportableHTTPError()
            );

            return;
        }

        $this->assertEquals(
            $responseCode,
            $result['code']
        );

        if (200 === $responseCode) {
            $content = $result['body'];
            $this->assertTrue($content['healthy']);
            $this->assertTrue(
                in_array(
                    $content['status']['elasticsearch'],
                    ['green', 'yellow']
                )
            );
            $this->assertEquals(
                [
                    'elastica' => ElasticaPluginBundle::class,
                    'redis_storage' => RedisStoragePluginBundle::class,
                ],
                $content['info']['plugins']
            );
        }
    }

    /**
     * Data for check health testing.
     *
     * @return array
     */
    public function dataCheckHealth(): array
    {
        return [
            [$_ENV['APISEARCH_GOD_TOKEN'], 200],
            [$_ENV['APISEARCH_PING_TOKEN'], 200],
            ['non-existing-key', 401],
        ];
    }
}
