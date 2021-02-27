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

namespace Apisearch\Server\Tests\Functional\Domain\Middleware;

use Apisearch\Exception\InvalidTokenException;
use Apisearch\Server\Tests\Functional\ServiceFunctionalTest;

/**
 * Class TokenEndpointPermissionsLimitationEnvTest.
 */
class TokenEndpointPermissionsLimitationEnvTest extends ServiceFunctionalTest
{
    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     *
     * @return void
     */
    public static function setUpBeforeClass(): void
    {
        $_ENV['APISEARCH_TOKEN_ENDPOINT_PERMISSIONS_LIMITATION'] = \implode(',', [
            'v1_query_all_indices',
            'v1_get_indices',
        ]);

        parent::setUpBeforeClass();
    }

    /**
     * @param array $endpoints
     *
     * @dataProvider dataAvailableEndpointsFail
     *
     * @return void
     */
    public function testAvailableEndpointsFail(array $endpoints): void
    {
        $masterToken = $this->createTokenByIdAndAppId('tok_master');
        $this->putToken($masterToken);

        $token = $this->createTokenByIdAndAppId('tok1');
        $token->setEndpoints($endpoints);
        $this->expectException(InvalidTokenException::class);
        $this->putToken($token, null, $masterToken);
    }

    public function dataAvailableEndpointsFail(): array
    {
        return [
            [['v1_query']],
            [['v1_query', 'v1_query']],
            [['v1_query', 'apisearch_v1_delete_index', 'apisearch_v1_delete_index']],
            [['v1_query', 'v1_query_all_indices']],
            [['v1_query', 'v1_query_all_indices', 'v1_get_indices']],
        ];
    }

    /**
     * @param array $endpoints
     *
     * @dataProvider dataAvailableEndpoints
     *
     * @return void
     */
    public function testAvailableEndpoints(array $endpoints): void
    {
        $masterToken = $this->createTokenByIdAndAppId('tok_master');
        $this->putToken($masterToken);

        $token = $this->createTokenByIdAndAppId('tok1');
        $token->setEndpoints($endpoints);
        $this->expectNotToPerformAssertions();
        $this->putToken($token, null, $masterToken);
    }

    public function dataAvailableEndpoints(): array
    {
        return [
            [[]],
            [['v1_get_indices']],
            [['v1_get_indices', 'v1_query_all_indices']],
            [['v1_get_indices', 'v1_query_all_indices', 'v1_query_all_indices']],
        ];
    }
}
