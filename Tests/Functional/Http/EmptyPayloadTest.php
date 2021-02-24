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

namespace Apisearch\Server\Tests\Functional\Http;

use Apisearch\Exception\InvalidFormatException;
use Apisearch\Server\Tests\Functional\CurlFunctionalTest;

/**
 * Class EmptyPayloadTest.
 */
class EmptyPayloadTest extends CurlFunctionalTest
{
    /**
     * @param callable $callable
     *
     * @return void
     *
     * @dataProvider dataNoContent
     * @group empty
     */
    public function testPutItemsNoContent(callable $callable)
    {
        $this->expectNotToPerformAssertions();
        try {
            $callable();

            $this->fail('Should return a 400 when there\'s no content');
        } catch (InvalidFormatException $exception) {
        }
    }

    /**
     * @return array
     */
    public function dataNoContent(): array
    {
        return [
            [fn () => $this->indexItems([])],
            [fn () => $this->deleteItems([])],
        ];
    }
}
