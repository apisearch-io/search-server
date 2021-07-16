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
use Apisearch\Config\Synonym;
use Apisearch\Query\Query;
use Apisearch\Query\SortBy;

/**
 * Class ExactMatchingMetadataTest.
 */
trait ExactMatchingMetadataTest
{
    /**
     * @param string      $query
     * @param int         $numberOfResults
     * @param string|null $firstResultId
     * @param string|null $secondResultId
     *
     * @return void
     *
     * @dataProvider dataProgressiveExactMatchingMultiQuery
     */
    public function testProgressiveExactMatchingMultiQuery(
        string $query,
        int $numberOfResults,
        ?string $firstResultId = null,
        ?string $secondResultId = null,
        ?bool $allowFuzzy = false
    ) {
        $this->configureIndex(Config::createEmpty()
            ->addSynonym(Synonym::createByWords([
                'SubwhateverA',
                'SynonymX',
                'SynonymZ',
                'Lavidaesunatombola',
                'nobita nobi',
            ]))
        );

        $result = $this->query(Query::create($query)
            ->setMetadataValue('progressive_exact_matching_metadata', true)
            ->setMetadataValue('fuzzy_progressive_exact_matching_metadata', $allowFuzzy)
            ->sortBy(SortBy::create()->byValue(SortBy::ID_ASC))
        );

        $items = $result->getItems();
        $this->assertCount($numberOfResults, $items);
        if ($firstResultId) {
            $this->assertEquals($firstResultId, $items[0]->getId());
        }
        if ($secondResultId) {
            $this->assertEquals($secondResultId, $items[1]->getId());
        }
    }

    public function dataProgressiveExactMatchingMultiQuery()
    {
        return [
            ['brandA subwhateverA', 1, '5'],
            ['branda subwha', 2, '4', '5'],
            ['subw branda subwha', 2, '4', '5'],
            ['brandA subwhatever', 2, '4', '5'],
            ['brandA', 2, '4', '5'],
            ['brandA subwhateverB', 1, '4'],
            ['branda subwhateverb', 1, '4'],
            ['brandA subwhateverb', 1, '4'],
            ['branda subwhateverB', 1, '4'],
            ['branda subwhateverb alamo', 1, '4'],
            ['alamo branda subwhateverb alamo', 1, '4'],
            ['alamo branda subwhateverb', 1, '4'],
            ['alamo subwhateverb brandA', 1, '4'],
            ['subwhateverb álam brandA', 1, '4'],
            ['branda subwhateverb NOEXISTE', 0],
            ['subwhateverb NOEXISTE', 0],
            ['subwhateverb', 3, '2'],
            ['subwhateverb cod', 1, '3'],
            ['subwhatever cod', 4],

            // Strange characters
            ['bránda sübwhatëvërB', 1, '4'],
            ['bránda    sübwhatëvërB', 1, '4'],
            ['   bránda    sübwhatëvërB   ', 1, '4'],
            ['   bránda sübwhatëvërB   ', 1, '4'],

            // Synonyms + typos
            ['brand SynonymX subwhatever', 1, '5'],
            ['brand SynonymZ subwhatever', 1, '5'],
            ['brand Lavidaesunatombola subwhatever', 1, '5'],
            ['brand Lavidaesunatombol subwhatever', 1, '5', null, true],
            ['brand Lavidaesunatombol subwhatever', 4, null, null, false],
            ['brand Lavidaesunacombola subwhatever', 1, '5', null, true],
            ['brand Lavidaesunacombola subwhatever', 4, null, null, false],
            ['brand nobita nobi subwhatever', 1, '5'],
            ['brand nobita novi subwhatever', 1, '5', null, true],
            ['brand nobita novi subwhatever', 4, null, null, false],
            ['brand nobita nob subwhatever', 1, '5', null, true],
            ['brand nobita nob subwhatever', 4, null, null, false],

            ['nobita nob NOEXISTE', 0, null, null, true],
            ['nobita nob NOEXISTE', 1, null, null, false],
        ];
    }

    public function testProgressiveExactMatchingCollisions()
    {
        $result = $this->query(Query::create('exact1')
            ->setMetadataValue('progressive_exact_matching_metadata', true)
            ->setMetadataValue('fuzzy_progressive_exact_matching_metadata', false)
            ->sortBy(SortBy::create()->byValue(SortBy::ID_ASC))
        );

        $this->assertCount(2, $result->getItems());
        $this->assertEquals('4', $result->getFirstItem()->getUUID()->getId());

        $result = $this->query(Query::create('exact1 specific')
            ->setMetadataValue('progressive_exact_matching_metadata', true)
            ->setMetadataValue('fuzzy_progressive_exact_matching_metadata', false)
            ->sortBy(SortBy::create()->byValue(SortBy::ID_ASC))
        );

        $this->assertCount(1, $result->getItems());
        $this->assertEquals('4', $result->getFirstItem()->getUUID()->getId());

        $result = $this->query(Query::create('exact2')
            ->setMetadataValue('progressive_exact_matching_metadata', true)
            ->setMetadataValue('fuzzy_progressive_exact_matching_metadata', false)
            ->sortBy(SortBy::create()->byValue(SortBy::ID_ASC))
        );

        $this->assertCount(2, $result->getItems());
        $this->assertEquals('4', $result->getFirstItem()->getUUID()->getId());

        $result = $this->query(Query::create('exact2 more specific')
            ->setMetadataValue('progressive_exact_matching_metadata', true)
            ->setMetadataValue('fuzzy_progressive_exact_matching_metadata', false)
            ->sortBy(SortBy::create()->byValue(SortBy::ID_ASC))
        );

        $this->assertCount(1, $result->getItems());
        $this->assertEquals('5', $result->getFirstItem()->getUUID()->getId());

        $result = $this->query(Query::create('exact3')
            ->setMetadataValue('progressive_exact_matching_metadata', true)
            ->setMetadataValue('fuzzy_progressive_exact_matching_metadata', false)
            ->sortBy(SortBy::create()->byValue(SortBy::ID_ASC))
        );

        $this->assertCount(1, $result->getItems());
        $this->assertEquals('5', $result->getFirstItem()->getUUID()->getId());

        $result = $this->query(Query::create('exact3 more specific')
            ->setMetadataValue('progressive_exact_matching_metadata', true)
            ->setMetadataValue('fuzzy_progressive_exact_matching_metadata', false)
            ->sortBy(SortBy::create()->byValue(SortBy::ID_ASC))
        );

        $this->assertCount(1, $result->getItems());
        $this->assertEquals('4', $result->getFirstItem()->getUUID()->getId());

        $result = $this->query(Query::create('exact4')
            ->setMetadataValue('progressive_exact_matching_metadata', true)
            ->setMetadataValue('fuzzy_progressive_exact_matching_metadata', false)
            ->sortBy(SortBy::create()->byValue(SortBy::ID_ASC))
        );

        $this->assertCount(1, $result->getItems());
        $this->assertEquals('4', $result->getFirstItem()->getUUID()->getId());

        $result = $this->query(Query::create('exact4 more specific')
            ->setMetadataValue('progressive_exact_matching_metadata', true)
            ->setMetadataValue('fuzzy_progressive_exact_matching_metadata', false)
            ->sortBy(SortBy::create()->byValue(SortBy::ID_ASC))
        );

        $this->assertCount(1, $result->getItems());
        $this->assertEquals('5', $result->getFirstItem()->getUUID()->getId());

        $result = $this->query(Query::create('exact4 more specific exact2 more specific')
            ->setMetadataValue('progressive_exact_matching_metadata', true)
            ->setMetadataValue('fuzzy_progressive_exact_matching_metadata', false)
            ->sortBy(SortBy::create()->byValue(SortBy::ID_ASC))
        );

        $this->assertCount(1, $result->getItems());
        $this->assertEquals('5', $result->getFirstItem()->getUUID()->getId());

        $result = $this->query(Query::create('exact4 more specific barcelon exact2 more specific')
            ->setMetadataValue('progressive_exact_matching_metadata', true)
            ->setMetadataValue('fuzzy_progressive_exact_matching_metadata', false)
            ->sortBy(SortBy::create()->byValue(SortBy::ID_ASC))
        );

        $this->assertCount(1, $result->getItems());
        $this->assertEquals('5', $result->getFirstItem()->getUUID()->getId());

        $result = $this->query(Query::create('exact4 more specific exact2')
            ->setMetadataValue('progressive_exact_matching_metadata', true)
            ->setMetadataValue('fuzzy_progressive_exact_matching_metadata', false)
            ->sortBy(SortBy::create()->byValue(SortBy::ID_ASC))
        );

        $this->assertCount(1, $result->getItems());
        $this->assertEquals('5', $result->getFirstItem()->getUUID()->getId());
    }

    public function testProgressiveExactMatchingCollisionsSynonyms()
    {
        $result = $this->query(Query::create('exact5')
            ->setMetadataValue('progressive_exact_matching_metadata', true)
            ->setMetadataValue('fuzzy_progressive_exact_matching_metadata', false)
            ->sortBy(SortBy::create()->byValue(SortBy::ID_ASC))
        );

        $this->assertCount(1, $result->getItems());
        $this->assertEquals('4', $result->getFirstItem()->getUUID()->getId());

        $result = $this->query(Query::create('exact5 more specific')
            ->setMetadataValue('progressive_exact_matching_metadata', true)
            ->setMetadataValue('fuzzy_progressive_exact_matching_metadata', false)
            ->sortBy(SortBy::create()->byValue(SortBy::ID_ASC))
        );

        $this->assertCount(1, $result->getItems());
        $this->assertEquals('5', $result->getFirstItem()->getUUID()->getId());

        $this->configureIndex(Config::createEmpty()
            ->addSynonym(Synonym::createByWords([
                'exact5',
                'exact5 more specific',
            ]))
        );

        $result = $this->query(Query::create('exact5')
            ->setMetadataValue('progressive_exact_matching_metadata', true)
            ->setMetadataValue('fuzzy_progressive_exact_matching_metadata', false)
            ->sortBy(SortBy::create()->byValue(SortBy::ID_ASC))
        );

        $this->assertCount(2, $result->getItems());

        $result = $this->query(Query::create('exact5 more specific')
            ->setMetadataValue('progressive_exact_matching_metadata', true)
            ->setMetadataValue('fuzzy_progressive_exact_matching_metadata', false)
            ->sortBy(SortBy::create()->byValue(SortBy::ID_ASC))
        );

        $this->assertCount(2, $result->getItems());
    }
}
