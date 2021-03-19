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
        string $firstResultId = null,
        string $secondResultId = null
    ) {
        $result = $this->query(Query::create($query)
            ->setMetadataValue('progressive_exact_matching_metadata', true)
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
            ['branda subwha', 2, '4', '5'],
            ['subw branda subwha', 2, '4', '5'],
            ['brandA subwhatever', 2, '4', '5'],
            ['brandA', 2, '4', '5'],
            ['brandA subwhateverA', 1, '5'],
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
        ];
    }
}
