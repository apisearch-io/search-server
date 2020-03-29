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

/**
 * Class Elasticsearch68Test.
 */
class Elasticsearch68Test extends ServiceRepositoryTest
{
    /**
     * Get elasticsearch config.
     *
     * @return array
     */
    protected static function getElasticsearchConfig(): array
    {
        return [
            'host' => 'apisearch.elasticsearch.6.8',
            'version' => '6',
        ];
    }
}
