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

namespace Apisearch\Server\Tests\Functional\Domain\Repository\SearchesRepository;

use Apisearch\Model\User;
use Apisearch\Query\Query;
use Apisearch\Server\Domain\ImperativeEvent\FlushSearches;
use Apisearch\Server\Domain\Model\Origin;
use Apisearch\Server\Domain\Repository\SearchesRepository\InMemorySearchesRepository;
use Apisearch\Server\Domain\Repository\SearchesRepository\SearchesRepository;
use DateTime;

/**
 * Trait SearchesRepositoryTest.
 */
trait SearchesRepositoryTest
{
    /**
     * Decorate configuration.
     *
     * @param array $configuration
     *
     * @return array
     */
    protected static function decorateConfiguration(array $configuration): array
    {
        $configuration = parent::decorateConfiguration($configuration);
        $configuration['services'][SearchesRepository::class] = [
            'alias' => InMemorySearchesRepository::class,
        ];

        return $configuration;
    }

    /**
     * Load searches.
     */
    public function testLoadSearches()
    {
        $this->expectNotToPerformAssertions();
        $this->query(Query::create('Code da vinci')->byUser(new User('u1')), null, null, null, [], new Origin('', '', Origin::TABLET));
        $this->query(Query::create('Matutano'), null, null, null, [], Origin::createEmpty());
        $this->query(Query::create('Matutano'), null, null, null, [], Origin::createEmpty());
        $this->query(Query::create('No results 1')->byUser(new User('u1')), null, null, null, [], new Origin('', '', Origin::TABLET));
        $this->query(Query::create('Stylestep')->byUser(new User('u1')), null, null, null, [], new Origin('', '', Origin::PHONE));
        $this->query(Query::create('Stylestep'), null, null, null, [], Origin::createEmpty());
        $this->query(Query::create('No results 1'), null, null, null, [], Origin::createEmpty());
        $this->query(Query::create('Code da vinci')->byUser(new User('u2')), null, null, null, [], Origin::createEmpty());
        $this->query(Query::create('Code da vinci')->byUser(new User('u2')), null, null, null, [], new Origin('', '', Origin::DESKTOP));
        $this->query(Query::create('Matutano'), null, null, null, [], Origin::createEmpty());
        $this->query(Query::create('Code da vinci'), null, null, null, [], Origin::createEmpty());
        $this->query(Query::create('No results 3')->byUser(new User('u1')), null, null, null, [], Origin::createEmpty());
        $this->query(Query::create('No results 2')->byUser(new User('u1')), null, null, null, [], Origin::createEmpty());
        $this->query(Query::create('Stylestep'), null, null, null, [], Origin::createEmpty());
        $this->query(Query::create('No results 2')->byUser(new User('u1')), null, null, null, [], new Origin('', '', Origin::TABLET));
        $this->query(Query::create('Code da vinci'), null, null, null, [], Origin::createEmpty());
        $this->query(Query::create('Matutano'), null, null, null, [], Origin::createEmpty());
        $this->query(Query::create('No results 1'), null, null, null, [], Origin::createEmpty());
        $this->query(Query::create('No results 1'), null, null, null, [], Origin::createEmpty());
        $this->query(Query::create('Code da vinci')->byUser(new User('u1')), null, null, null, [], Origin::createEmpty());
        $this->query(Query::create('Matutano'), null, null, null, [], Origin::createEmpty());
        $this->query(Query::create('Code da vinci'), null, null, null, [], Origin::createEmpty());
        $this->query(Query::create('Matutano'), null, null, null, [], Origin::createEmpty());
        $this->query(Query::create('Matutano')->byUser(new User('u1')), null, null, null, [], Origin::createEmpty());
        $this->query(Query::create('No results 1'), null, null, null, [], Origin::createEmpty());
        $this->query(Query::create('badalona')->byUser(new User('u1')), null, null, null, [], new Origin('', '', Origin::DESKTOP));
        $this->query(Query::create('No results 2')->byUser(new User('u2')), null, null, null, [], new Origin('', '', Origin::TABLET));
        $this->query(Query::create('Code da vinci')->byUser(new User('u2')), null, null, null, [], Origin::createEmpty());
        self::usleep(100000);
        $this->dispatchImperative(new FlushSearches());
        self::usleep(100000);
    }

    /**
     * Test basic.
     */
    public function testBasic()
    {
        $searches = $this->getSearches(false);
        $this->assertEquals(28, $searches);
        $searches = $this->getSearches(true);
        $this->assertCount(1, $searches);
        $this->assertEquals(28, \reset($searches));
    }

    /**
     * Test when.
     */
    public function testWhenFilter()
    {
        $this->assertEquals(28, $this->getSearches(false, (new DateTime())->modify('-1 day')));
        $this->assertEquals(28, $this->getSearches(false, null, (new DateTime())->modify('+1 day')));
        $this->assertEquals(28, $this->getSearches(false, (new DateTime())->modify('-1 day'), (new DateTime())->modify('+1 day')));
    }

    /**
     * Test filter by user.
     */
    public function testFilterByUser()
    {
        $this->assertEquals(9, $this->getSearches(false, null, null, 'u1'));
        $this->assertEquals(4, $this->getSearches(false, null, null, 'u2'));
        $this->assertEquals(15, $this->getSearches(false, null, null, ''));
    }

    /**
     * Test filter by platform.
     */
    public function testFilterByPlatform()
    {
        $this->assertEquals(1, $this->getSearches(false, null, null, null, origin::PHONE));
        $this->assertEquals(2, $this->getSearches(false, null, null, null, origin::DESKTOP));
        $this->assertEquals(4, $this->getSearches(false, null, null, null, origin::TABLET));
        $this->assertEquals(5, $this->getSearches(false, null, null, null, origin::MOBILE));
    }

    /**
     * Filter by results.
     */
    public function testByResults()
    {
        $this->assertEquals(19, $this->getSearches(false, null, null, null, null, false, true));
        $this->assertEquals(9, $this->getSearches(false, null, null, null, null, true, false));
        $this->assertEquals(0, $this->getSearches(false, null, null, null, null, true, true));
    }

    /**
     * Test filter by index.
     */
    public function testFilterByIndex()
    {
        $this->query(Query::create('Code da vinci'), static::$appId, static::$anotherIndex);
        $this->query(Query::create('Code da vinci'), static::$appId, static::$anotherIndex);
        $this->query(Query::create('Code da vinci'), static::$appId, static::$anotherIndex);

        self::usleep(100000);
        $this->dispatchImperative(new FlushSearches());
        self::usleep(100000);

        $this->assertEquals(31, $this->getSearches(false));
        $this->assertEquals(28, $this->getSearches(false, null, null, null, null, false, false, static::$appId, static::$index));
        $this->assertEquals(3, $this->getSearches(false, null, null, null, null, false, false, static::$appId, static::$anotherIndex));
    }

    /**
     * Test filter by index.
     */
    public function testFilterByApp()
    {
        $this->query(Query::create('Code da vinci'), static::$anotherAppId, static::$index);
        $this->query(Query::create('Code da vinci'), static::$anotherAppId, static::$index);
        $this->query(Query::create('Code da vinci'), static::$anotherAppId, static::$index);
        $this->query(Query::create('Code da vinci'), static::$anotherAppId, static::$anotherIndex);

        self::usleep(100000);
        $this->dispatchImperative(new FlushSearches());
        self::usleep(100000);

        $this->assertEquals(31, $this->getSearches(false, null, null, null, null, false, false, static::$appId));
        $this->assertEquals(3, $this->getSearches(false, null, null, null, null, false, false, static::$anotherAppId, static::$index));
        $this->assertEquals(1, $this->getSearches(false, null, null, null, null, false, false, static::$anotherAppId, static::$anotherIndex));
        $this->assertEquals(0, $this->getSearches(false, null, null, null, null, false, false, static::$anotherAppId, static::$yetAnotherIndex));
    }

    /**
     * Test get top searches.
     */
    public function testTopSearches()
    {
        $this->assertEquals([
            'Code da vinci' => 11,
            'Matutano' => 7,
            'No results 1' => 5,
            'Stylestep' => 3,
            'No results 2' => 3,
            'No results 3' => 1,
            'badalona' => 1,
        ], $this->getTopSearches(10));

        $this->assertEquals([
            'No results 2' => 2,
            'Code da vinci' => 1,
            'No results 1' => 1,
        ], $this->getTopSearches(10, null, null, origin::TABLET));

        $this->assertEquals([
            'No results 2' => 2,
            'Code da vinci' => 1,
            'No results 1' => 1,
            'Stylestep' => 1,
        ], $this->getTopSearches(10, null, null, origin::MOBILE));

        $this->assertEquals([
            'Code da vinci' => 11,
            'Matutano' => 7,
            'No results 1' => 5,
            'Stylestep' => 3,
            'No results 2' => 3,
            'No results 3' => 1,
            'badalona' => 1,
        ], $this->getTopSearches(10, null, null, null, null, false, false));

        $this->assertEquals([
            'No results 1' => 5,
            'No results 2' => 3,
            'Code da vinci' => 3,
            'No results 3' => 1,
        ], $this->getTopSearches(10, null, null, null, null, true));

        $this->assertSame([
            'Code da vinci' => 8,
            'Matutano' => 7,
            'Stylestep' => 3,
            'badalona' => 1,
        ], $this->getTopSearches(10, null, null, null, null, false, true));

        $this->assertEquals([], $this->getTopSearches(10, null, null, null, null, true, true));
    }

    /**
     * Test filter by user.
     */
    public function testFilterTopSearchesByUser()
    {
        $this->assertEquals([
            'Code da vinci' => 2,
            'No results 2' => 2,
            'No results 1' => 1,
            'Stylestep' => 1,
            'No results 3' => 1,
            'Matutano' => 1,
            'badalona' => 1,
        ], $this->getTopSearches(10, null, null, null, 'u1'));
    }
}
