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

namespace Apisearch\Plugin\SearchesMachine\Tests\Functional;

use Apisearch\Model\User;
use Apisearch\Plugin\SearchesMachine\Domain\Command\ProcessSearchesMachine;
use Apisearch\Query\Query;
use Apisearch\Server\Domain\ImperativeEvent\FlushSearches;
use Apisearch\Server\Domain\Model\Origin;

class RedisStoreFunctionalTest extends SearchesMachineFunctionalTest
{
    /**
     * Test that searches are stored in Redis.
     */
    public function testSearchesAreStoredInRedis()
    {
        $this->flushRedis();
        $this->query(Query::create('Code da vinci')->byUser(new User('u1')), null, null, null, [], new Origin('', '', Origin::TABLET));
        $this->query(Query::create('No results 1')->byUser(new User('u1')), null, null, null, [], new Origin('', '', Origin::TABLET));
        $this->query(Query::create('Stylestep')->byUser(new User('u1')), null, null, null, [], new Origin('', '', Origin::PHONE));
        $this->query(Query::create('Code da vinci')->byUser(new User('u2')), null, null, null, [], Origin::createEmpty());
        $this->query(Query::create('Code da vinci')->byUser(new User('u2')), null, null, null, [], new Origin('', '', Origin::DESKTOP));
        $this->query(Query::create('No results 3')->byUser(new User('u1')), null, null, null, [], Origin::createEmpty());
        $this->query(Query::create('No results 2')->byUser(new User('u1')), null, null, null, [], Origin::createEmpty());
        $this->query(Query::create('No results 2')->byUser(new User('u1')), null, null, null, [], new Origin('', '', Origin::TABLET));
        $this->query(Query::create('Code da vinci')->byUser(new User('u1')), null, null, null, [], Origin::createEmpty());
        $this->query(Query::create('Matutano')->byUser(new User('u1')), null, null, null, [], Origin::createEmpty());
        $this->query(Query::create('badalona')->byUser(new User('u1')), null, null, null, [], new Origin('', '', Origin::DESKTOP));
        $this->query(Query::create('No results 2')->byUser(new User('u2')), null, null, null, [], new Origin('', '', Origin::TABLET));
        $this->query(Query::create('Code da vinci')->byUser(new User('u2')), null, null, null, [], Origin::createEmpty());
        self::usleep(100000);
        $this->dispatchImperative(new FlushSearches());
        self::usleep(100000);

        $numberOfSavedSearches = $this->await($this->getRedisClient()->llen($this->getRedisKey()));
        $this->assertEquals(13, $numberOfSavedSearches);
    }

    /**
     * Test that searches are stored in Redis.
     */
    public function testSearchCompilationQuality()
    {
        $this->flushRedis();
        $searches = [];
        $searches = \array_merge($searches, ['s', 'se', 'sea', 'sear', 'sea', 'se', 'sea', 'sear', 'searc', 'searche', 'searches']);
        $searches = \array_merge($searches, ['a', 'an', 'ano', 'anot', 'anoth', 'anothe', 'another']);
        foreach ($searches as $search) {
            $this->query(Query::create($search)->byUser(new User('u1')), null, null, null, [], new Origin('', '', Origin::TABLET));
        }

        self::usleep(100000);
        $this->dispatchImperative(new FlushSearches());
        self::usleep(100000);
        $this->executeCommand(new ProcessSearchesMachine());
        self::usleep(100000);

        $storedSearches = $this->getTopSearches();
        $this->assertEquals(2, \count($storedSearches));
    }

    /**
     */
    public function testMassiveUsage()
    {
        $this->flushRedis();
        $this->generateSearchBehaviour('1', self::$index, ['casa', 'casi', 'vaixell', 'vaixella', 'baix', 'baixa', 'baixà'], '0.0.0.0');
        $this->generateSearchBehaviour('1', self::$index, ['Cuixart', 'Dante Fachin', 'puigdemont'], '0.0.0.1');
        $this->generateSearchBehaviour('2', self::$index, ['nobita', 'suneo', 'Geganteta', 'Geganta'], '0.0.0.0');
        $this->generateSearchBehaviour('2', self::$anotherIndex, ['nobita', 'suneo', 'Gegant', 'Geganta'], '0.0.0.0');

        self::usleep(300000);
        $this->dispatchImperative(new FlushSearches());
        self::usleep(300000);
        $this->executeCommand(new ProcessSearchesMachine());
        self::usleep(300000);

        $this->assertEquals(7, \count($this->getTopSearches(1000, null, null, null, '1')));
        $this->assertEquals(4, \count($this->getTopSearches(1000, null, null, null, '2')));
        $this->assertEquals(4, \count($this->getTopSearches(1000, null, null, null, '2', false, false, null, self::$index)));
        $this->assertEquals(3, \count($this->getTopSearches(1000, null, null, null, '2', false, false, null, self::$anotherIndex)));
        $this->assertEquals(13, \count($this->getTopSearches(1000)));

        $this->generateSearchBehaviour('3', self::$index, ['una', 'dues', 'tres', 'quatre', 'cinc', 'Cinc', 'CINC', 'ÇÌÑç'], '0.0.0.0');

        self::usleep(100000);
        $this->dispatchImperative(new FlushSearches());
        self::usleep(100000);
        $this->executeCommand(new ProcessSearchesMachine());
        self::usleep(100000);

        $this->assertEquals(5, \count($this->getTopSearches(1000, null, null, null, '3')));
        $this->assertEquals(18, \count($this->getTopSearches(1000)));

        $numberOfSavedSearches = $this->await($this->getRedisClient()->llen($this->getRedisKey()));
        $this->assertEquals(0, $numberOfSavedSearches);
    }

    /**
     * @param string $userId
     * @param string $indexId
     * @param array  $words
     * @param string $ip
     *
     * @return void
     */
    private function generateSearchBehaviour(
        string $userId,
        string $indexId,
        array $words,
        string $ip
    ): void {
        for ($j = 0; $j < 3; ++$j) {
            \shuffle($words);
            foreach ($words as $word) {
                $partialWords = [];
                for ($l = 0; $l <= \strlen($word); ++$l) {
                    $partialWords[] = \mb_substr($word, 0, $l);
                }

                for ($i = 0; $i < 3; ++$i) {
                    \shuffle($partialWords);
                    foreach ($partialWords as $partialWord) {
                        $this->query(Query::create($partialWord)->byUser(new User($userId)), self::$appId, $indexId, null, [], new Origin('', $ip, Origin::TABLET));
                    }
                }
            }
        }
    }
}
