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

/**
 * Class AllAsynchronousTests.
 */
trait AllSearchTests
{
    use ComplexFieldsTest;
    use IndexConfigurationTest;
    use IndexTest;
    // use UpdateItemsTest; => Complex Data fields have to be fixed
    use ScoreStrategyTest;
    use QueryTest;
    // use SearchNestedTest; => Complex Data fields have to be fixed
    use ItemsDeletionByQueryTest;
    use IndexStatusTest;
    use ErrorRecoveryTest;
    use SynonymsTest;
    use RepositoryResetTest;
    use HighlightTest;
    use AggregationsTest;
    use UniverseFilterTest;
    use FiltersTest;
    use RangeFiltersTest;
    use LocationFiltersTest;
    use ExcludeReferencesTest;
    use ExactMatchingMetadataTest;
    use DeletionTest;
    use SortTest;
    use SuggestTest;
    use StopwordsSteemerTest;
    use FuzzinessTest;
    use GetIndicesTest;
    use TokenQueriesTest;
    use MultiqueryTest;
    use IndicesTest;
    use FieldTypesTest;
}
