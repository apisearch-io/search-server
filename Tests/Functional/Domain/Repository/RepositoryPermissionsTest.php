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
use Apisearch\Model\ItemUUID;
use Apisearch\Query\Query;

/**
 * Class RepositoryPermissionsTest.
 */
trait RepositoryPermissionsTest
{
    /**
     * Test events requests without permissions.
     *
     * @dataProvider dataBadPermissions
     *
     * @return void
     */
    public function testBadPermissions($appId, $index, $method, $data = null): void
    {
        $this->expectException(ResourceNotAvailableException::class);
        if (\is_null($data)) {
            $this->$method(
                $appId,
                $index
            );
        } else {
            $this->$method(
                $data,
                $appId,
                $index
            );
        }
    }

    /**
     * Data for testBadPermissions.
     *
     * @return array
     */
    public function dataBadPermissions(): array
    {
        $itemUUID = ItemUUID::createByComposedUUID('1~product');
        $item = Item::create($itemUUID);
        $query = Query::createMatchAll();

        return [
            [self::$anotherAppId, self::$anotherIndex, 'resetIndex'],
            [self::$anotherAppId, self::$anotherIndex, 'deleteItems', [$itemUUID]],
            [self::$anotherAppId, self::$anotherIndex, 'query', $query],
            [self::$anotherAppId, self::$anotherIndex, 'indexItems', [$item]],
            [self::$anotherAppId, self::$anotherIndex, 'deleteIndex'],

            [self::$anotherInexistentAppId, self::$index, 'deleteIndex'],
            [self::$anotherInexistentAppId, self::$index, 'resetIndex'],
            [self::$anotherInexistentAppId, self::$index, 'query', $query],
            [self::$anotherInexistentAppId, self::$index, 'indexItems', [$item]],
            [self::$anotherInexistentAppId, self::$index, 'deleteItems', [$itemUUID]],

            [self::$anotherInexistentAppId, self::$anotherIndex, 'deleteIndex'],
            [self::$anotherInexistentAppId, self::$anotherIndex, 'resetIndex'],
            [self::$anotherInexistentAppId, self::$anotherIndex, 'query', $query],
            [self::$anotherInexistentAppId, self::$anotherIndex, 'indexItems', [$item]],
            [self::$anotherInexistentAppId, self::$anotherIndex, 'deleteItems', [$itemUUID]],
        ];
    }
}
