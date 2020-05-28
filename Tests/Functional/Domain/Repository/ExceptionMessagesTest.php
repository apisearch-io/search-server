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

use Apisearch\Exception\ResourceNotAvailableException;
use Apisearch\Model\Item;

/**
 * Trait ExceptionMessagesTest.
 */
trait ExceptionMessagesTest
{
    /**
     * Test wrong field exception.
     */
    public function testWrongFieldException()
    {
        try {
            $this->indexItems([
                Item::createFromArray([
                    'uuid' => [
                        'id' => '10',
                        'type' => 'lol',
                    ],
                    'metadata' => [
                        'field' => 'a_text_instead_of_a_bool',
                    ],
                ]),
            ]);
            $this->fail('An exception should be thrown');
        } catch (ResourceNotAvailableException $exception) {
            $this->assertContains('failed to parse', $exception->getMessage());
        }
    }
}
