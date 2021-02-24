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

use Apisearch\Exception\PayloadTooLargeException;
use Apisearch\Model\Item;
use Apisearch\Model\ItemUUID;
use Apisearch\Server\Tests\Functional\CurlFunctionalTest;

/**
 * Class BodyTooLargeTest.
 */
class BodyTooLargeTest extends CurlFunctionalTest
{
    /**
     * @return string[]
     */
    protected static function serverConfiguration(): array
    {
        return [
            '--request-body-buffer=10',
        ];
    }

    public function testBodyTooLarge()
    {
        $item = Item::create(new ItemUUID('1', 'type1'), [
            'k1' => 'v1',
        ]);

        $items = [];
        for ($i = 0; $i < 1000; ++$i) {
            $items[] = $item;
        }

        $this->expectException(PayloadTooLargeException::class);
        $this->indexItems($items);
    }
}
