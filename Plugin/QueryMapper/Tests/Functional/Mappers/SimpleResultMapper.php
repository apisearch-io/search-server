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

namespace Apisearch\Plugin\QueryMapper\Tests\Functional\Mappers;

use Apisearch\Model\Item;
use Apisearch\Plugin\QueryMapper\Domain\ResultMapper;
use Apisearch\Result\Result;
use Apisearch\Server\Tests\Functional\ApisearchServerBundleFunctionalTest;

/**
 * Class SimpleResultMapper.
 */
class SimpleResultMapper implements ResultMapper
{
    /**
     * Get tokens.
     *
     * @return string[]
     */
    public function getTokens(): array
    {
        return [ApisearchServerBundleFunctionalTest::$readonlyToken];
    }

    /**
     * Build array.
     *
     * @param Result $result
     *
     * @return array
     */
    public function buildArrayFromResult(Result $result): array
    {
        return [
            'item_nb' => \count($result->getItems()),
            'item_ids' => \array_map(function (Item $item) {
                return $item->composeUUID();
            }, $result->getItems()),
        ];
    }
}
