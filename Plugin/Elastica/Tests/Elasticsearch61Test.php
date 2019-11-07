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

namespace Apisearch\Plugin\Elastica;

use Apisearch\Server\Tests\Functional\Domain\Repository\ServiceRepositoryTest;

/**
 * Class Elasticsearch61Test.
 */
class Elasticsearch61Test extends ServiceRepositoryTest
{
    /**
     * Get elasticsearch endpoint.
     *
     * @return array
     */
    protected static function getElasticsearchEndpoint(): array
    {
        return [
            'host' => '127.0.0.1',
            'port' => '9201',
        ];
    }

    /**
     * Get elasticsearch version.
     *
     * @return string
     */
    protected static function getElasticsearchVersion(): string
    {
        return '6';
    }
}
