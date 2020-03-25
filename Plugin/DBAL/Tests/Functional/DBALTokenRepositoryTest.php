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

namespace Apisearch\Plugin\DBAL\Tests\Functional;

use Apisearch\Server\Tests\Functional\Domain\Token\TokenTest;
use Doctrine\DBAL\Exception\TableNotFoundException;

/**
 * Class PostgresTokenRepositoryTest.
 */
class DBALTokenRepositoryTest extends TokenTest
{
    /**
     * Is distributed token respository.
     */
    public function isDistributedTokenRepository(): bool
    {
        return true;
    }

    /**
     * Reset database.
     */
    public static function resetScenario()
    {
        $mainConnection = static::getStatic('dbal.main_connection');
        $promise = $mainConnection
            ->dropTable('tokens')
            ->otherwise(function (TableNotFoundException $_) {
                // Silent pass
            })
            ->then(function () use ($mainConnection) {
                return $mainConnection->createTable('tokens', [
                    'token_uuid' => 'string',
                    'app_uuid' => 'string',
                    'content' => 'string',
                ]);
            });

        static::await($promise);

        parent::resetScenario();
    }

    /**
     * Truncate the table.
     */
    protected function setUp()
    {
        $mainConnection = $this->get('dbal.main_connection');
        $promise = $mainConnection->truncateTable('tokens');

        static::await($promise);

        parent::setUp();
    }

    use DBALFunctionalTestTrait;
}
