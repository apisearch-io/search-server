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

namespace Apisearch\Plugin\SearchesMachine\Tests\Unit\Domain;

use Apisearch\Plugin\SearchesMachine\Domain\SearchesMachine;
use Apisearch\Server\Domain\Repository\SearchesRepository\Search;
use DateTime;
use PHPUnit\Framework\TestCase;

/**
 * Class SearchesMachineTest.
 */
class SearchesMachineTest extends TestCase
{
    public function testEmptySearchesMachine()
    {
        $machine = new SearchesMachine();
        $this->assertEquals([], $machine->getSearches());
        $machine->compile();
        $this->assertEquals([], $machine->getSearches());
    }

    /**
     * @param array $inputSearches
     * @param array $expectedOutputSearches
     *
     * @dataProvider dataForScenarios
     */
    public function testScenarios(
        array $inputSearches,
        array $expectedOutputSearches
    ) {
        $machine = new SearchesMachine();
        $inputSearches = \array_map(fn ($s) => new Search($s[0], $s[1], $s[2], $s[3], $s[4], $s[5], $s[6], $s[7], $s[8], $s[9]), $inputSearches);
        $expectedOutputSearches = \array_map(fn ($s) => new Search($s[0], $s[1], $s[2], $s[3], $s[4], $s[5], $s[6], $s[7], $s[8], $s[9]), $expectedOutputSearches);

        foreach ($inputSearches as $search) {
            $machine->addSearch($search);
        }

        $machine->compile();
        $outputSearches = $machine->getSearches();
        $this->sortSearches($outputSearches);
        $this->sortSearches($expectedOutputSearches);
        $this->assertEquals($outputSearches, $expectedOutputSearches);
    }

    /**
     * @return array
     */
    public function dataForScenarios(): array
    {
        $now = new DateTime();

        return [
            [
                [
                    ['u1', 'a1', 'i1', 't', 10, true, '0.0.0.0', 'localhost', 'p1', $now],
                    ['u1', 'a1', 'i1', 'te', 10, true, '0.0.0.0', 'localhost', 'p1', $now],
                    ['u1', 'a1', 'i1', 'tex', 10, true, '0.0.0.0', 'localhost', 'p1', $now],
                    ['u1', 'a1', 'i1', 'text', 10, true, '0.0.0.0', 'localhost', 'p1', $now],
                ],
                [
                    ['u1', 'a1', 'i1', 'text', 10, true, '0.0.0.0', 'localhost', 'p1', $now],
                ],
            ],

            [
                [
                    ['u1', 'a1', 'i1', 't', 10, true, '0.0.0.0', 'localhost', 'p1', $now],
                    ['u1', 'a1', 'i1', 'te', 10, true, '0.0.0.0', 'localhost', 'p1', $now],
                    ['u1', 'a1', 'i1', 'tex', 10, true, '0.0.0.0', 'localhost', 'p1', $now],
                    ['u1', 'a1', 'i1', 'text', 10, true, '0.0.0.0', 'localhost', 'p1', $now],
                    ['u1', 'a1', 'i1', 'tex', 10, true, '0.0.0.0', 'localhost', 'p1', $now],
                    ['u1', 'a1', 'i1', 'te', 10, true, '0.0.0.0', 'localhost', 'p1', $now],
                    ['u1', 'a1', 'i1', 'tel', 10, true, '0.0.0.0', 'localhost', 'p1', $now],
                    ['u1', 'a1', 'i1', 'tela', 10, true, '0.0.0.0', 'localhost', 'p1', $now],
                ],
                [
                    ['u1', 'a1', 'i1', 'text', 10, true, '0.0.0.0', 'localhost', 'p1', $now],
                    ['u1', 'a1', 'i1', 'tela', 10, true, '0.0.0.0', 'localhost', 'p1', $now],
                ],
            ],

            [
                [
                    ['u1', 'a1', 'i1', 't', 10, true, '0.0.0.0', 'localhost', 'p1', $now],
                    ['u2', 'a1', 'i1', 'azucar', 10, true, '0.0.0.0', 'localhost', 'p1', $now],
                    ['u1', 'a1', 'i1', 'te', 10, true, '0.0.0.0', 'localhost', 'p1', $now],
                    ['u1', 'a1', 'i1', 'tex', 10, true, '0.0.0.0', 'localhost', 'p1', $now],
                    ['u1', 'a1', 'i1', 'text', 10, true, '0.0.0.0', 'localhost', 'p1', $now],
                    ['u2', 'a1', 'i1', 'p', 10, true, '0.0.0.0', 'localhost', 'p1', $now],
                    ['u1', 'a1', 'i1', 'tex', 10, true, '0.0.0.0', 'localhost', 'p1', $now],
                    ['u1', 'a1', 'i1', 'te', 10, true, '0.0.0.0', 'localhost', 'p1', $now],
                    ['u1', 'a1', 'i1', 'tel', 10, true, '0.0.0.0', 'localhost', 'p1', $now],
                    ['u2', 'a1', 'i1', 'po', 10, true, '0.0.0.0', 'localhost', 'p1', $now],
                    ['u2', 'a1', 'i1', 'pol', 10, true, '0.0.0.0', 'localhost', 'p1', $now],
                    ['u2', 'a1', 'i1', 'po', 10, true, '0.0.0.0', 'localhost', 'p1', $now],
                    ['u1', 'a1', 'i1', 'tela', 10, true, '0.0.0.0', 'localhost', 'p1', $now],
                    ['u2', 'a1', 'i1', 'poll', 10, true, '0.0.0.0', 'localhost', 'p1', $now],
                    ['u2', 'a1', 'i1', 'pollo', 10, true, '0.0.0.0', 'localhost', 'p1', $now],
                ],
                [
                    ['u1', 'a1', 'i1', 'text', 10, true, '0.0.0.0', 'localhost', 'p1', $now],
                    ['u1', 'a1', 'i1', 'tela', 10, true, '0.0.0.0', 'localhost', 'p1', $now],
                    ['u2', 'a1', 'i1', 'pollo', 10, true, '0.0.0.0', 'localhost', 'p1', $now],
                    ['u2', 'a1', 'i1', 'azucar', 10, true, '0.0.0.0', 'localhost', 'p1', $now],
                ],
            ],

            [
                [
                    ['u1', 'a1', 'i1', 't', 10, true, '0.0.0.0', 'localhost', 'p1', $now],
                    ['u2', 'a1', 'i1', 'azucar', 10, true, '0.0.0.0', 'localhost', 'p1', $now],
                    ['u1', 'a1', 'i1', 'te', 10, true, '0.0.0.0', 'localhost', 'p1', $now],
                    ['u1', 'a1', 'i1', 'tex', 10, true, '0.0.0.0', 'localhost', 'p1', $now],
                    ['u1', 'a1', 'i1', 'text', 10, true, '0.0.0.0', 'localhost', 'p1', $now],
                    ['u2', 'a1', 'i1', 'p', 10, true, '0.0.0.0', 'localhost', 'p1', $now],
                    ['u1', 'a1', 'i1', 'tex', 10, true, '0.0.0.0', 'localhost', 'p1', $now],
                    ['u1', 'a1', 'i1', 'te', 10, true, '0.0.0.0', 'localhost', 'p1', $now],
                    ['u1', 'a1', 'i1', 'tel', 10, true, '0.0.0.0', 'localhost', 'p1', $now],
                    ['u2', 'a1', 'i1', 'po', 10, true, '0.0.0.0', 'localhost', 'p1', $now],
                    ['u2', 'a1', 'i1', 'pol', 10, true, '0.0.0.0', 'localhost', 'p1', $now],
                    ['u2', 'a1', 'i1', 'po', 10, true, '0.0.0.0', 'localhost', 'p1', $now],
                    ['u1', 'a1', 'i1', 'tela', 10, true, '0.0.0.0', 'localhost', 'p1', $now],
                    ['u2', 'a1', 'i1', 'poll', 10, true, '0.0.0.0', 'localhost', 'p1', $now],
                    ['u2', 'a1', 'i1', 'pollo', 10, true, '0.0.0.0', 'localhost', 'p1', $now],
                    ['u1', 'a1', 'i1', 't', 10, true, '0.0.0.0', 'localhost', 'p1', $now],
                    ['u2', 'a1', 'i1', 'Azúcar', 10, true, '0.0.0.0', 'localhost', 'p1', $now],
                    ['u1', 'a1', 'i1', 'te', 10, true, '0.0.0.0', 'localhost', 'p1', $now],
                    ['u1', 'a1', 'i1', 'tex', 10, true, '0.0.0.0', 'localhost', 'p1', $now],
                    ['u1', 'a1', 'i1', 'text', 10, true, '0.0.0.0', 'localhost', 'p1', $now],
                    ['u2', 'a1', 'i1', 'P', 10, true, '0.0.0.0', 'localhost', 'p1', $now],
                    ['u1', 'a1', 'i1', 'tex', 10, true, '0.0.0.0', 'localhost', 'p1', $now],
                    ['u1', 'a1', 'i1', 'te', 10, true, '0.0.0.0', 'localhost', 'p1', $now],
                    ['u1', 'a1', 'i1', 'tel', 10, true, '0.0.0.0', 'localhost', 'p1', $now],
                    ['u2', 'a1', 'i1', 'PO', 10, true, '0.0.0.0', 'localhost', 'p1', $now],
                    ['u2', 'a1', 'i1', 'POL', 10, true, '0.0.0.0', 'localhost', 'p1', $now],
                    ['u2', 'a1', 'i1', 'PO', 10, true, '0.0.0.0', 'localhost', 'p1', $now],
                    ['u1', 'a1', 'i1', 'tela', 10, true, '0.0.0.0', 'localhost', 'p1', $now],
                    ['u2', 'a1', 'i1', 'POLL', 10, true, '0.0.0.0', 'localhost', 'p1', $now],
                    ['u2', 'a1', 'i1', 'POLLO', 10, true, '0.0.0.0', 'localhost', 'p1', $now],
                ],
                [
                    ['u1', 'a1', 'i1', 'text', 10, true, '0.0.0.0', 'localhost', 'p1', $now],
                    ['u1', 'a1', 'i1', 'tela', 10, true, '0.0.0.0', 'localhost', 'p1', $now],
                    ['u2', 'a1', 'i1', 'pollo', 10, true, '0.0.0.0', 'localhost', 'p1', $now],
                    ['u2', 'a1', 'i1', 'azucar', 10, true, '0.0.0.0', 'localhost', 'p1', $now],
                ],
            ],
        ];
    }

    /**
     * @param array $searches
     */
    private function sortSearches(array &$searches)
    {
        \usort($searches, function (Search $search1, Search $search2) {
            return $this->getSearchSessionHash($search1) >= $this->getSearchSessionHash($search2);
        });
    }

    /**
     * @param Search $search
     *
     * @return string
     */
    private function getSearchSessionHash(Search $search): string
    {
        return \md5(\json_encode([
            $search->getUser(),
            $search->getAppUUID(),
            $search->getIndexUUID(),
            $search->getText(),
            $search->getIp(),
            $search->getHost(),
            $search->getPlatform(),
        ]));
    }
}
