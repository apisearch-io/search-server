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

use Apisearch\Model\Item;
use Apisearch\Query\Query;

/**
 * Class ErrorRecoveryTest.
 */
trait ErrorRecoveryTest
{
    /**
     * Test server after fatal error.
     *
     * This error is produced when we index an Item with a malformed field or a
     * different item format.
     *
     * @return void
     */
    public function testAfterFatalError(): void
    {
        try {
            $this->indexItems([
                Item::createFromArray([
                    'uuid' => [
                        'id' => 6743,
                        'type' => 'product',
                    ],
                    'indexed_metadata' => [
                        'price' => 'lala',
                    ],
                ]),
            ]);
            $this->fail('An exception should be thrown here');
        } catch (\Exception $exception) {
            // Silent pass
        }
        // At this point we should be able to make a simple query
        $this->assertCount(
            5,
            $this->query(Query::createMatchAll())->getItems()
        );
    }
}
