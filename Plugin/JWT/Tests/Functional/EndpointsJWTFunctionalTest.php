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

namespace Apisearch\Plugin\JWT\Tests\Functional;

use Apisearch\Query\Query;

/**
 * Class EndpointsJWTFunctionalTest.
 */
class EndpointsJWTFunctionalTest extends JWTFunctionalTest
{
    /**
     * Get JWT configuration.
     *
     * @return array
     */
    protected static function getJWTConfiguration(): array
    {
        return [
            'private_key' => self::PRIVATE_KEY,
            'allowed_algorithms' => self::ALGORITHM,
            'ttl' => self::TTL,
            'endpoints' => ['index'],
        ];
    }

    /**
     * Test not authenticated.
     */
    public function testNotAuthenticated()
    {
        $this->expectNotToPerformAssertions();
        $query = Query::createMatchAll();
        $this->query($query);
    }
}
