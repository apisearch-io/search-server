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
use Apisearch\Server\Tests\Functional\CurlFunctionalTest;

/**
 * Class PingTest.
 */
class PingTest extends CurlFunctionalTest
{
    /**
     * Test ping with different tokens.
     *
     * @param string $token
     * @param int    $responseCode
     *
     * @dataProvider dataPing
     */
    public function testPing(
        string $token,
        int $responseCode
    ) {
        try {
            static::makeCurl(
                'ping',
                [],
                new Token(
                    TokenUUID::createById($token),
                    AppUUID::createById(self::$appId)
                )
            );

            $this->assertTrue(200 === $responseCode);
        } catch (TransportableException $exception) {
            $this->assertTrue(200 !== $responseCode);
        }
    }

    /**
     * Data for ping testing.
     *
     * @return array
     */
    public function dataPing(): array
    {
        return [
            [$_ENV['APISEARCH_GOD_TOKEN'], 401],
            [$_ENV['APISEARCH_PING_TOKEN'], 200],
            ['1234', 401],
        ];
    }
}
