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

namespace Apisearch\Plugin\Elasticsearch\Adapter;

/**
 * Class AsyncAdapter.
 */
class AsyncAdapter
{
    /**
     * @var AsyncClient
     */
    private $asyncClient;

    /**
     * @param AsyncClient $asyncClient
     */
    public function __construct(AsyncClient $asyncClient)
    {
        $this->asyncClient = $asyncClient;
    }

    /**
     * @return AsyncClient
     */
    public function getAsyncClient(): AsyncClient
    {
        return $this->asyncClient;
    }
}
