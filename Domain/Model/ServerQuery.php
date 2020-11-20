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

namespace Apisearch\Server\Domain\Model;

use Apisearch\Query\Query;

/**
 * Class ServerQuery.
 */
class ServerQuery extends Query
{
    private array $likeItemUUIDs;

    /**
     * @param array $likeItemUUIDs
     */
    public function likeItemUUIDs(array $likeItemUUIDs): void
    {
        $this->likeItemUUIDs = $likeItemUUIDs;
    }

    /**
     * @return array
     */
    public function getLikeItems(): array
    {
        return $this->likeItemUUIDs;
    }
}
