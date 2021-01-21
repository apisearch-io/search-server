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

namespace Apisearch\Plugin\Elasticsearch\Tests;

use Apisearch\Exception\InvalidFormatException;
use Apisearch\Model\Item;
use Apisearch\Server\Tests\Functional\ServiceFunctionalTest;

/**
 * Class ExceptionMessagesTest.
 */
class ExceptionMessagesTest extends ServiceFunctionalTest
{
    use ElasticFunctionalTestTrait;

    /**
     * Test wrong field exception.
     *
     * @return void
     */
    public function testWrongFieldException(): void
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
        } catch (InvalidFormatException $exception) {
            $this->assertContains('failed to parse field', $exception->getMessage());
        }
    }
}
