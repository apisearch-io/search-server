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
 * Class Elasticsearch70Test.
 */
class Elasticsearch70Test extends ServiceRepositoryTest
{
    /**
     * Get elasticsearch endpoint.
     *
     * @return array
     */
    protected static function getElasticsearchEndpoint(): array
    {
        return [
            'host' => 'apisearch.elasticsearch.7.0',
            'port' => '9200',
            'username' => 'elastic',
            'password' => 'test'
        ];
    }

    /**
     * Get elasticsearch version.
     *
     * @return string
     */
    protected static function getElasticsearchVersion(): string
    {
        return '7';
    }
}
