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
use Apisearch\Server\Tests\Functional\HttpFunctionalTest;

/**
 * Class HealthTest.
 */
class HealthTest extends HttpFunctionalTest
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
            $result = $this->checkHealth($this->createTokenByIdAndAppId($token, self::$appId));
        } catch (TransportableException $exception) {
            $this->assertEquals(
                $responseCode,
                $exception->getTransportableHTTPError()
            );

            return;
        }

        $this->assertTrue($result['healthy']);
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
            [$_ENV['APISEARCH_PING_TOKEN'], 401],
            ['non-existing-key', 401],
        ];
    }
}
