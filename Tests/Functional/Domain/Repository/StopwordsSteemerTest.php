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

use Apisearch\Config\Config;
use Apisearch\Query\Query;

/**
 * Class StopwordsSteemerTest.
 */
trait StopwordsSteemerTest
{
    /**
     * test finding without stopwords language.
     *
     * @return void
     */
    public function testSearchWithoutStopwords(): void
    {
        $this->assertNotEmpty(
            $this->query(
                Query::create('de', 1, 1)
                    ->disableAggregations()
            )->getItems()
        );
    }

    /**
     * test finding without stopwords language.
     *
     * @return void
     */
    public function testSearchWithStopwords(): void
    {
        $this->configureIndex(new Config('es'));

        $this->assertEmpty(
            $this->query(
                Query::create('de', 1, 1)
                    ->disableAggregations()
            )->getItems()
        );

        self::resetScenario();
    }

    /**
     * Test finding with another language stopwords.
     *
     * @return void
     */
    public function testSearchWithAnotherStopwords(): void
    {
        $this->configureIndex(new Config('en'));

        $this->assertNotEmpty(
            $this->query(
                Query::create('de', 1, 1)
                    ->disableAggregations()
            )->getItems()
        );

        self::resetScenario();
    }
}
