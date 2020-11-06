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
use Apisearch\Model\Token;
use Apisearch\Server\Tests\Functional\HttpFunctionalTest;

/**
 * Class GodTokenTest.
 */
class GodTokenTest extends HttpFunctionalTest
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
            $result = $this->teapot($this->createTokenByIdAndAppId($token));
        } catch (TransportableException $exception) {
            $this->assertEquals(
                $responseCode,
                $exception->getTransportableHTTPError()
            );

            return;
        }

        $this->assertEquals(418, $result['code']);
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
            [$_ENV['APISEARCH_HEALTH_CHECK_TOKEN'], 401],
            [$_ENV['APISEARCH_PING_TOKEN'], 401],
            ['non-existing-key', 401],
        ];
    }

    /**
     * Test that anyone can add health_check in token permissions.
     */
    public function testHealthCheckCantBeAddedInToken()
    {
        $token = $this->createTokenByIdAndAppId('another_token');
        $token->setEndpoints(['teapot', 'apisearch_teapot']);
        $this->putToken($token);
        try {
            $this->checkHealth($token);
            $this->fail('Should throw token not found exception');
        } catch (TransportableException $exception) {
            $this->assertEquals(
                401,
                $exception->getTransportableHTTPError()
            );
        }
    }

    /**
     * @param Token $token
     *
     * @return array
     */
    private function teapot(Token $token): array
    {
        return $this->request('teapot', [], $token);
    }
}
