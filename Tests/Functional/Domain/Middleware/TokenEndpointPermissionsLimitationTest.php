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
use Apisearch\Query\Query;
use Apisearch\Server\Tests\Functional\HttpFunctionalTest;

/**
 * Class TokenEndpointPermissionsLimitationTest.
 */
class TokenEndpointPermissionsLimitationTest extends HttpFunctionalTest
{
    /**
     * Decorate configuration.
     *
     * @param array $configuration
     *
     * @return array
     */
    protected static function decorateConfiguration(array $configuration): array
    {
        $configuration = parent::decorateConfiguration($configuration);
        $configuration['apisearch_server']['limitations']['token_endpoint_permissions'] = [
            'v1_query_all_indices',
            'v1_get_indices',
        ];

        return $configuration;
    }

    /**
     * @param array $endpoints
     *
     * @dataProvider dataAvailableEndpointsFail
     */
    public function testAvailableEndpointsFail(array $endpoints)
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
     */
    public function testAvailableEndpoints(array $endpoints)
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

    /**
     * Test that god is unlimited.
     */
    public function testGodIsUnlimited()
    {
        $token = $this->createTokenByIdAndAppId('tok1');
        $token->setEndpoints(['v1_query']);
        $this->expectNotToPerformAssertions();
        $this->putToken($token);
    }

    /**
     * Test default endpoints when empty.
     */
    public function testDefaultEndpointsWhenEmpty()
    {
        $masterToken = $this->createTokenByIdAndAppId('tok_master');
        $this->putToken($masterToken);

        $token = $this->createTokenByIdAndAppId('tok1');
        $this->putToken($token, null, $masterToken);
        $this->query(Query::createMatchAll(), self::$appId, '', $token);
        $this->expectException(InvalidTokenException::class);
        $this->query(Query::createMatchAll(), self::$appId, self::$index, $token);
    }
}
