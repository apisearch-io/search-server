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

/**
 * Class PostgresTokenRepositoryTest.
 */
class DBALTokenRepositoryTest extends TokenTest
{
    use DBALFunctionalTestTrait;

    /**
     * Is distributed token respository.
     */
    public function isDistributedTokenRepository(): bool
    {
        return true;
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
}
