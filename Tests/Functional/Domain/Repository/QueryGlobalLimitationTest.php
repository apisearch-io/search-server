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

namespace Apisearch\Server\Tests\Functional\Domain\Repository;

use Apisearch\Query\Query;
use Apisearch\Server\Tests\Functional\ServiceFunctionalTest;

/**
 * Class QueryGlobalLimitationTest.
 */
abstract class QueryGlobalLimitationTest extends ServiceFunctionalTest
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
        $configuration['apisearch_server']['limitations']['number_of_results'] = 3;

        return $configuration;
    }

    /**
     * Test simple usage.
     */
    public function testSimpleUsage()
    {
        /*
         * Number of results limitation
         */
        $this->assertCount(3, $this->query(
            Query::createMatchAll()
        )->getItems());
    }
}
