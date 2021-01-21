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

use Apisearch\Geo\CoordinateAndDistance;
use Apisearch\Geo\Polygon;
use Apisearch\Geo\Square;
use Apisearch\Model\Coordinate;
use Apisearch\Query\Query;

/**
 * Class LocationFiltersTest.
 */
trait LocationFiltersTest
{
    /**
     * Test location filter with a simple coordinate and a distance.
     *
     * @return void
     */
    public function testLocationFilterCoordinateAndDistance(): void
    {
        $this->assertResults(
            $this->query($this->createLocatedQuery()->filterUniverseByLocation(
                new CoordinateAndDistance(
                    new Coordinate(45.0, 45.0),
                    '100km'
                ),
                [],
                []
            )),
            ['!1', '!2', '!3', '!4', '!5']
        );

        $this->assertResults(
            $this->query($this->createLocatedQuery()->filterUniverseByLocation(
                new CoordinateAndDistance(
                    new Coordinate(45.0, 45.0),
                    '557km'
                ),
                [],
                []
            )),
            ['!1', '!2', '?3', '!4', '!5']
        );

        $this->assertResults(
            $this->query($this->createLocatedQuery()->filterUniverseByLocation(
                new CoordinateAndDistance(
                    new Coordinate(45.0, 45.0),
                    '1180km'
                ),
                [],
                []
            )),
            ['!1', '!2', '?3', '?4', '!5']
        );

        $this->assertResults(
            $this->query($this->createLocatedQuery()->filterUniverseByLocation(
                new CoordinateAndDistance(
                    new Coordinate(45.0, 45.0),
                    '1320km'
                ),
                [],
                []
            )),
            ['!1', '?2', '?3', '?4', '!5']
        );

        $this->assertResults(
            $this->query($this->createLocatedQuery()->filterUniverseByLocation(
                new CoordinateAndDistance(
                    new Coordinate(45.0, 45.0),
                    '2123km'
                ),
                [],
                []
            )),
            ['?1', '?2', '?3', '?4', '!5']
        );

        $this->assertResults(
            $this->query($this->createLocatedQuery()->filterUniverseByLocation(
                new CoordinateAndDistance(
                    new Coordinate(45.0, 45.0),
                    '2350km'
                ),
                [],
                []
            )),
            ['?1', '?2', '?3', '?4', '?5']
        );
    }

    /**
     * Test location filter with a Square filter.
     *
     * @return void
     */
    public function testLocationFilterSquare(): void
    {
        $this->assertResults(
            $this->query($this->createLocatedQuery()->filterUniverseByLocation(
                new Square(
                    new Coordinate(46.0, 44.0),
                    new Coordinate(44.0, 46.0)
                ),
                [],
                []
            )),
            ['!1', '!2', '!3', '!4', '!5']
        );

        $this->assertResults(
            $this->query($this->createLocatedQuery()->filterUniverseByLocation(
                new Square(
                    new Coordinate(61.0, 29.0),
                    new Coordinate(29.0, 61.0)
                ),
                [],
                []
            )),
            ['?1', '?2', '?3', '?4', '!5']
        );

        $this->assertResults(
            $this->query($this->createLocatedQuery()->filterUniverseByLocation(
                new Square(
                    new Coordinate(61.0, 29.0),
                    new Coordinate(29.0, 71.0)
                ),
                [],
                []
            )),
            ['?1', '?2', '?3', '?4', '?5']
        );
    }

    /**
     * Test location filter with a polygon filter.
     *
     * @return void
     */
    public function testLocationFilterPolygon(): void
    {
        $this->assertResults(
            $this->query($this->createLocatedQuery()->filterUniverseByLocation(
                new Polygon([
                    new Coordinate(46.0, 44.0),
                    new Coordinate(44.0, 44.0),
                    new Coordinate(44.0, 46.0),
                    new Coordinate(46.0, 46.0),
                ]),
                [],
                []
            )),
            ['!1', '!2', '!3', '!4', '!5']
        );

        $this->assertResults(
            $this->query($this->createLocatedQuery()->filterUniverseByLocation(
                new Polygon([
                    new Coordinate(61.0, 29.0),
                    new Coordinate(29.0, 29.0),
                    new Coordinate(29.0, 61.0),
                    new Coordinate(61.0, 61.0),
                ]),
                [],
                []
            )),
            ['?1', '?2', '?3', '?4', '!5']
        );

        $this->assertResults(
            $this->query($this->createLocatedQuery()->filterUniverseByLocation(
                new Polygon([
                    new Coordinate(61.0, 29.0),
                    new Coordinate(29.0, 29.0),
                    new Coordinate(60.5, 72.0),
                    new Coordinate(70.0, 45.0),
                ]),
                [],
                []
            )),
            ['?1', '?2', '!3', '!4', '?5']
        );
    }

    /**
     * Create located query with [45,45].
     *
     * @return Query
     */
    private function createLocatedQuery(): Query
    {
        return Query::createLocated(new Coordinate(45.0, 45.0), '');
    }
}
