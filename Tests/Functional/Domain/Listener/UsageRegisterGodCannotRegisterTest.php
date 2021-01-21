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

namespace Apisearch\Search\Domain\Listener;

use Apisearch\Query\Query;
use Apisearch\Server\Domain\Repository\UsageRepository\InMemoryUsageRepository;
use Apisearch\Server\Domain\Repository\UsageRepository\UsageRepository;
use Apisearch\Server\Tests\Functional\ServiceFunctionalTest;

/**
 * Class UsageRegisterGodCannotRegisterTest.
 */
class UsageRegisterGodCannotRegisterTest extends ServiceFunctionalTest
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
        $configuration['apisearch_server']['settings']['register_god_usage'] = false;
        $configuration['services'][UsageRepository::class] = [
            'alias' => InMemoryUsageRepository::class,
        ];

        return $configuration;
    }

    /**
     * Test simple.
     *
     * @return void
     */
    public function testSimpleUsage(): void
    {
        $this->query(Query::createMatchAll());
        $this->query(Query::createMatchAll());
        $this->query(Query::createMatchAll());
        $usage = $this->getUsage();
        $this->assertEquals([], $usage);

        $token = $this->createTokenByIdAndAppId('lolamen');
        $this->putToken($token);
        $usage = $this->getUsage();
        $this->assertEquals([], $usage);
        $this->query(Query::createMatchAll(), null, null, $token);
        $this->query(Query::createMatchAll(), null, null, $token);
        $anotherToken = $this->createTokenByIdAndAppId('lolamen2');
        $this->putToken($anotherToken, null, $token);

        $usage = $this->getUsage();
        $this->assertEquals([
            'query' => 2,
            'admin' => 1,
        ], $usage);
    }
}
