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

namespace Plugin\Campaign\Tests\Unit\Domain;

use Apisearch\Model\IndexUUID;
use Apisearch\Plugin\Campaign\Domain\Matcher;
use Apisearch\Plugin\Campaign\Domain\Model\Campaign;
use Apisearch\Plugin\Campaign\Domain\Model\CampaignCriteria;
use Apisearch\Plugin\Campaign\Domain\Model\CampaignModifiers;
use Apisearch\Plugin\Campaign\Domain\Model\CampaignUID;
use Apisearch\Query\Filter;
use Apisearch\Query\Query;
use Apisearch\Repository\RepositoryReference;
use Apisearch\Server\Tests\Unit\BaseUnitTest;
use DateTime;

/**
 * Class MatcherTest.
 */
class MatcherTest extends BaseUnitTest
{
    /**
     * Test match by timestamp.
     */
    public function testByTimestamp()
    {
        $query = Query::createMatchAll();
        $matcher = new Matcher();
        $now = new DateTime();

        $campaign = new Campaign(new CampaignUID('1'), null, null, IndexUUID::createById(''), [], Campaign::MATCH_CRITERIA_MODE_MUST_ALL, [], CampaignModifiers::createFromArray([]));
        $this->assertTrue($matcher->queryMatchesCampaign($query, $campaign, $now));

        $campaign = new Campaign(new CampaignUID('1'), (new DateTime())->modify('-1 day'), null, IndexUUID::createById(''), [], Campaign::MATCH_CRITERIA_MODE_MUST_ALL, [], CampaignModifiers::createFromArray([]));
        $this->assertTrue($matcher->queryMatchesCampaign($query, $campaign, $now));

        $campaign = new Campaign(new CampaignUID('1'), $now, null, IndexUUID::createById(''), [], Campaign::MATCH_CRITERIA_MODE_MUST_ALL, [], CampaignModifiers::createFromArray([]));
        $this->assertTrue($matcher->queryMatchesCampaign($query, $campaign, $now));

        $campaign = new Campaign(new CampaignUID('1'), (new DateTime())->modify('-1 day'), (new DateTime())->modify('+1 day'), IndexUUID::createById(''), [], Campaign::MATCH_CRITERIA_MODE_MUST_ALL, [], CampaignModifiers::createFromArray([]));
        $this->assertTrue($matcher->queryMatchesCampaign($query, $campaign, $now));

        $campaign = new Campaign(new CampaignUID('1'), null, (new DateTime())->modify('+1 day'), IndexUUID::createById(''), [], Campaign::MATCH_CRITERIA_MODE_MUST_ALL, [], CampaignModifiers::createFromArray([]));
        $this->assertTrue($matcher->queryMatchesCampaign($query, $campaign, $now));

        $campaign = new Campaign(new CampaignUID('1'), null, $now, IndexUUID::createById(''), [], Campaign::MATCH_CRITERIA_MODE_MUST_ALL, [], CampaignModifiers::createFromArray([]));
        $this->assertFalse($matcher->queryMatchesCampaign($query, $campaign, $now));

        $campaign = new Campaign(new CampaignUID('1'), null, (new DateTime())->modify('-1 day'), IndexUUID::createById(''), [], Campaign::MATCH_CRITERIA_MODE_MUST_ALL, [], CampaignModifiers::createFromArray([]));
        $this->assertFalse($matcher->queryMatchesCampaign($query, $campaign, $now));

        $campaign = new Campaign(new CampaignUID('1'), (new DateTime())->modify('+1 day'), null, IndexUUID::createById(''), [], Campaign::MATCH_CRITERIA_MODE_MUST_ALL, [], CampaignModifiers::createFromArray([]));
        $this->assertFalse($matcher->queryMatchesCampaign($query, $campaign, $now));

        $campaign = new Campaign(new CampaignUID('1'), (new DateTime())->modify('+1 day'), (new DateTime())->modify('-1 day'), IndexUUID::createById(''), [], Campaign::MATCH_CRITERIA_MODE_MUST_ALL, [], CampaignModifiers::createFromArray([]));
        $this->assertFalse($matcher->queryMatchesCampaign($query, $campaign, $now));
    }

    /**
     * Test by query match exact.
     */
    public function testByQueryMatchExact()
    {
        $this->assertTrue($this->queriesMatch('Marc', CampaignCriteria::MATCH_TYPE_EXACT, 'Marc'));
        $this->assertTrue($this->queriesMatch('MARC', CampaignCriteria::MATCH_TYPE_EXACT, 'Marc'));
        $this->assertTrue($this->queriesMatch('  MARC   ', CampaignCriteria::MATCH_TYPE_EXACT, 'Marc'));
        $this->assertTrue($this->queriesMatch('marc', CampaignCriteria::MATCH_TYPE_EXACT, 'MARC'));
        $this->assertTrue($this->queriesMatch('', CampaignCriteria::MATCH_TYPE_EXACT, ''));
        $this->assertTrue($this->queriesMatch('Whatever', CampaignCriteria::MATCH_TYPE_EXACT, null));

        $this->assertFalse($this->queriesMatch('Hola, sóc el Marc', CampaignCriteria::MATCH_TYPE_EXACT, 'Marc'));
        $this->assertFalse($this->queriesMatch('', CampaignCriteria::MATCH_TYPE_EXACT, 'MARC'));
        $this->assertFalse($this->queriesMatch('Marc', CampaignCriteria::MATCH_TYPE_EXACT, ''));
    }

    /**
     * Test by query match includes exact.
     */
    public function testByQueryMatchIncludesExact()
    {
        $this->assertTrue($this->queriesMatch('', CampaignCriteria::MATCH_TYPE_INCLUDES_EXACT, ''));
        $this->assertTrue($this->queriesMatch('Hola, sóc el Marc', CampaignCriteria::MATCH_TYPE_INCLUDES_EXACT, 'MARC'));
        $this->assertTrue($this->queriesMatch('Hola, sóc el Marc', CampaignCriteria::MATCH_TYPE_INCLUDES_EXACT, 'marc'));
        $this->assertTrue($this->queriesMatch('Marc', CampaignCriteria::MATCH_TYPE_INCLUDES_EXACT, 'marc'));
        $this->assertTrue($this->queriesMatch('marc', CampaignCriteria::MATCH_TYPE_INCLUDES_EXACT, 'MARC'));
        $this->assertTrue($this->queriesMatch('   marc  ', CampaignCriteria::MATCH_TYPE_INCLUDES_EXACT, 'MARC'));
        $this->assertTrue($this->queriesMatch('Whatever', CampaignCriteria::MATCH_TYPE_INCLUDES_EXACT, null));

        $this->assertFalse($this->queriesMatch('Hola, sóc el MORC', CampaignCriteria::MATCH_TYPE_INCLUDES_EXACT, 'MARC'));
        $this->assertFalse($this->queriesMatch('Morc', CampaignCriteria::MATCH_TYPE_INCLUDES_EXACT, 'MARC'));
        $this->assertFalse($this->queriesMatch('', CampaignCriteria::MATCH_TYPE_INCLUDES_EXACT, 'MARC'));
        $this->assertFalse($this->queriesMatch('Marc', CampaignCriteria::MATCH_TYPE_INCLUDES_EXACT, ''));
    }

    /**
     * Test by query match similar.
     */
    public function testByQueryMatchSimilar()
    {
        $this->assertTrue($this->queriesMatch('', CampaignCriteria::MATCH_TYPE_SIMILAR, ''));
        $this->assertTrue($this->queriesMatch('morc', CampaignCriteria::MATCH_TYPE_SIMILAR, 'MARC'));
        $this->assertTrue($this->queriesMatch('mrc', CampaignCriteria::MATCH_TYPE_SIMILAR, 'MARC'));
        $this->assertTrue($this->queriesMatch('marc', CampaignCriteria::MATCH_TYPE_SIMILAR, 'MARC'));
        $this->assertTrue($this->queriesMatch('   marc  ', CampaignCriteria::MATCH_TYPE_SIMILAR, 'MARC'));

        $this->assertFalse($this->queriesMatch('mrk', CampaignCriteria::MATCH_TYPE_SIMILAR, 'MARC'));
    }

    /**
     * Test by query match includes similar.
     */
    public function testByQueryMatchIncludesSimilar()
    {
        $this->assertTrue($this->queriesMatch('', CampaignCriteria::MATCH_TYPE_INCLUDES_SIMILAR, ''));
        $this->assertTrue($this->queriesMatch('Hola, sóc el morc', CampaignCriteria::MATCH_TYPE_INCLUDES_SIMILAR, 'MARC'));
        $this->assertTrue($this->queriesMatch('Hola, sóc el mrc', CampaignCriteria::MATCH_TYPE_INCLUDES_SIMILAR, 'MARC'));
        $this->assertTrue($this->queriesMatch('Hola, sóc el marc', CampaignCriteria::MATCH_TYPE_INCLUDES_SIMILAR, 'MARC'));
        $this->assertTrue($this->queriesMatch('Hola MARC, sóc el marc', CampaignCriteria::MATCH_TYPE_INCLUDES_SIMILAR, 'MARC'));
        $this->assertTrue($this->queriesMatch('   marc  ', CampaignCriteria::MATCH_TYPE_INCLUDES_SIMILAR, 'MARC'));
        $this->assertTrue($this->queriesMatch(' morc mrk  marc  ', CampaignCriteria::MATCH_TYPE_INCLUDES_SIMILAR, 'MARC'));

        $this->assertFalse($this->queriesMatch('mrk', CampaignCriteria::MATCH_TYPE_INCLUDES_SIMILAR, 'MARC'));
        $this->assertFalse($this->queriesMatch('Hola, sóc el mrk', CampaignCriteria::MATCH_TYPE_INCLUDES_SIMILAR, 'MARC'));
        $this->assertFalse($this->queriesMatch('Hola ark, sóc el mrk', CampaignCriteria::MATCH_TYPE_INCLUDES_SIMILAR, 'MARC'));
    }

    /**
     * Test by field filters match.
     */
    public function testByFieldFilterMatches()
    {
        $this->assertTrue($this->filtersMatch(
            [Filter::create('category', ['1', '2'], Filter::MUST_ALL, Filter::TYPE_FIELD)],
            []
        ));

        $this->assertTrue($this->filtersMatch(
            [Filter::create('category', ['1', '2'], Filter::MUST_ALL, Filter::TYPE_FIELD)],
            [Filter::create('category', ['1'], Filter::MUST_ALL, Filter::TYPE_FIELD)]
        ));

        $this->assertTrue($this->filtersMatch(
            [Filter::create('category', ['2', '3'], Filter::MUST_ALL, Filter::TYPE_FIELD)],
            [Filter::create('category', [], Filter::MUST_ALL, Filter::TYPE_FIELD)]
        ));

        $this->assertTrue($this->filtersMatch(
            [Filter::create('category', ['2', '3'], Filter::MUST_ALL, Filter::TYPE_FIELD)],
            [Filter::create('category', ['2', '3'], Filter::MUST_ALL, Filter::TYPE_FIELD)]
        ));

        $this->assertTrue($this->filtersMatch(
            [
                Filter::create('category', ['2', '3'], Filter::MUST_ALL, Filter::TYPE_FIELD),
                Filter::create('carrier', ['2', '3'], Filter::MUST_ALL, Filter::TYPE_FIELD),
            ],
            [Filter::create('category', ['2', '3'], Filter::MUST_ALL, Filter::TYPE_FIELD)],
        ));

        $this->assertTrue($this->filtersMatch(
            [
                Filter::create('category', ['2', '3'], Filter::MUST_ALL, Filter::TYPE_FIELD),
                Filter::create('carrier', ['2', '3'], Filter::MUST_ALL, Filter::TYPE_FIELD),
            ],
            [Filter::create('carrier', ['2', '3'], Filter::MUST_ALL, Filter::TYPE_FIELD)],
        ));

        $this->assertTrue($this->filtersMatch(
            [
                Filter::create('category', ['2', '3'], Filter::MUST_ALL, Filter::TYPE_FIELD),
                Filter::create('carrier', ['2', '3'], Filter::MUST_ALL, Filter::TYPE_FIELD),
            ],
            [
                Filter::create('category', ['2', '3'], Filter::MUST_ALL, Filter::TYPE_FIELD),
                Filter::create('carrier', ['2', '3'], Filter::MUST_ALL, Filter::TYPE_FIELD),
            ],
        ));

        $this->assertTrue($this->filtersMatch(
            [
                Filter::create('category', ['2', '3'], Filter::MUST_ALL, Filter::TYPE_FIELD),
                Filter::create('carrier', ['2', '3'], Filter::MUST_ALL, Filter::TYPE_FIELD),
            ],
            [
                Filter::create('category', ['2'], Filter::MUST_ALL, Filter::TYPE_FIELD),
                Filter::create('carrier', ['2'], Filter::MUST_ALL, Filter::TYPE_FIELD),
                Filter::create('another', [], Filter::MUST_ALL, Filter::TYPE_FIELD),
            ],
        ));

        $this->assertFalse($this->filtersMatch(
            [],
            [Filter::create('category', ['1'], Filter::MUST_ALL, Filter::TYPE_FIELD)]
        ));

        $this->assertFalse($this->filtersMatch(
            [Filter::create('category', ['2', '3'], Filter::MUST_ALL, Filter::TYPE_FIELD)],
            [Filter::create('category', ['1'], Filter::MUST_ALL, Filter::TYPE_FIELD)]
        ));

        $this->assertFalse($this->filtersMatch(
            [Filter::create('category', ['2', '3'], Filter::MUST_ALL, Filter::TYPE_FIELD)],
            [Filter::create('category', ['2', '3', '4'], Filter::MUST_ALL, Filter::TYPE_FIELD)]
        ));

        $this->assertFalse($this->filtersMatch(
            [Filter::create('category', ['2', '3'], Filter::MUST_ALL, Filter::TYPE_FIELD)],
            [Filter::create('carrier', ['2', '3'], Filter::MUST_ALL, Filter::TYPE_FIELD)]
        ));

        $this->assertFalse($this->filtersMatch(
            [Filter::create('category', ['2', '3'], Filter::MUST_ALL, Filter::TYPE_FIELD)],
            [
                Filter::create('category', ['2', '3'], Filter::MUST_ALL, Filter::TYPE_FIELD),
                Filter::create('carrier', ['2', '3'], Filter::MUST_ALL, Filter::TYPE_FIELD),
            ]
        ));
    }

    /**
     * Test by field filters match.
     */
    public function testByRangeFilterMatches()
    {
        $this->assertTrue($this->filtersMatch(
            [Filter::create('year', ['0..1000'], Filter::MUST_ALL, Filter::TYPE_RANGE)],
            []
        ));

        $this->assertTrue($this->filtersMatch(
            [Filter::create('year', ['0..1000'], Filter::MUST_ALL, Filter::TYPE_RANGE)],
            [Filter::create('year', ['0..1000'], Filter::MUST_ALL, Filter::TYPE_RANGE)]
        ));

        $this->assertTrue($this->filtersMatch(
            [Filter::create('year', ['0..1000'], Filter::MUST_ALL, Filter::TYPE_RANGE)],
            [Filter::create('year', ['..'], Filter::MUST_ALL, Filter::TYPE_RANGE)]
        ));

        $this->assertTrue($this->filtersMatch(
            [Filter::create('year', ['0..1000'], Filter::MUST_ALL, Filter::TYPE_RANGE)],
            [Filter::create('year', ['0..1001'], Filter::MUST_ALL, Filter::TYPE_RANGE)]
        ));

        $this->assertTrue($this->filtersMatch(
            [Filter::create('year', ['0..1000'], Filter::MUST_ALL, Filter::TYPE_RANGE)],
            [Filter::create('year', ['-1..1001'], Filter::MUST_ALL, Filter::TYPE_RANGE)]
        ));

        $this->assertTrue($this->filtersMatch(
            [Filter::create('year', ['0..1000'], Filter::MUST_ALL, Filter::TYPE_RANGE)],
            [Filter::create('year', ['-1..'], Filter::MUST_ALL, Filter::TYPE_RANGE)]
        ));

        $this->assertTrue($this->filtersMatch(
            [Filter::create('year', ['..1000'], Filter::MUST_ALL, Filter::TYPE_RANGE)],
            [Filter::create('year', ['..'], Filter::MUST_ALL, Filter::TYPE_RANGE)]
        ));

        $this->assertTrue($this->filtersMatch(
            [Filter::create('year', ['..'], Filter::MUST_ALL, Filter::TYPE_RANGE)],
            [Filter::create('year', ['..'], Filter::MUST_ALL, Filter::TYPE_RANGE)]
        ));

        $this->assertTrue($this->filtersMatch(
            [
                Filter::create('year', ['2000..2010'], Filter::MUST_ALL, Filter::TYPE_RANGE),
                Filter::create('price', ['50..100'], Filter::MUST_ALL, Filter::TYPE_RANGE),
            ],
            [
                Filter::create('year', ['1990..2020'], Filter::MUST_ALL, Filter::TYPE_RANGE),
                Filter::create('price', ['25..200'], Filter::MUST_ALL, Filter::TYPE_RANGE),
            ]
        ));

        $this->assertTrue($this->filtersMatch(
            [
                Filter::create('year', ['2000..2010'], Filter::MUST_ALL, Filter::TYPE_RANGE),
                Filter::create('price', ['50..100'], Filter::MUST_ALL, Filter::TYPE_RANGE),
            ],
            [
                Filter::create('year', ['1990..2020'], Filter::MUST_ALL, Filter::TYPE_RANGE),
                Filter::create('price', ['25..200'], Filter::MUST_ALL, Filter::TYPE_RANGE),
            ], [],
            Campaign::MATCH_CRITERIA_MODE_AT_LEAST_ONE
        ));

        $this->assertTrue($this->filtersMatch(
            [
                Filter::create('year', ['2000..2010'], Filter::MUST_ALL, Filter::TYPE_RANGE),
                Filter::create('price', ['50..100'], Filter::MUST_ALL, Filter::TYPE_RANGE),
            ],
            [
                Filter::create('year', ['1990..2020'], Filter::MUST_ALL, Filter::TYPE_RANGE),
            ], [],
            Campaign::MATCH_CRITERIA_MODE_AT_LEAST_ONE
        ));

        $this->assertTrue($this->filtersMatch(
            [
                Filter::create('year', ['2000..2010'], Filter::MUST_ALL, Filter::TYPE_RANGE),
                Filter::create('price', ['50..100'], Filter::MUST_ALL, Filter::TYPE_RANGE),
            ],
            [
                Filter::create('price', ['25..200'], Filter::MUST_ALL, Filter::TYPE_RANGE),
            ], [],
            Campaign::MATCH_CRITERIA_MODE_AT_LEAST_ONE
        ));

        $this->assertTrue($this->filtersMatch(
            [
                Filter::create('year', ['2000..2010'], Filter::MUST_ALL, Filter::TYPE_RANGE),
                Filter::create('price', ['50..100'], Filter::MUST_ALL, Filter::TYPE_RANGE),
            ],
            [
                Filter::create('year', ['1990..2020'], Filter::MUST_ALL, Filter::TYPE_RANGE),
            ],
            [
                Filter::create('price', ['25..200'], Filter::MUST_ALL, Filter::TYPE_RANGE),
            ],
            Campaign::MATCH_CRITERIA_MODE_AT_LEAST_ONE
        ));

        $this->assertTrue($this->filtersMatch(
            [
                Filter::create('year', ['2000..2010'], Filter::MUST_ALL, Filter::TYPE_RANGE),
                Filter::create('price', ['50..100'], Filter::MUST_ALL, Filter::TYPE_RANGE),
            ],
            [
                Filter::create('year', ['1990..2020'], Filter::MUST_ALL, Filter::TYPE_RANGE),
            ],
            [
                Filter::create('price', ['100..200'], Filter::MUST_ALL, Filter::TYPE_RANGE),
            ],
            Campaign::MATCH_CRITERIA_MODE_AT_LEAST_ONE
        ));

        $this->assertTrue($this->filtersMatch(
            [
                Filter::create('year', ['2000..2010'], Filter::MUST_ALL, Filter::TYPE_RANGE),
                Filter::create('price', ['50..100'], Filter::MUST_ALL, Filter::TYPE_RANGE),
            ],
            [
                Filter::create('year', ['1990..2020'], Filter::MUST_ALL, Filter::TYPE_RANGE),
            ],
            [
                Filter::create('price', ['100..200'], Filter::MUST_ALL, Filter::TYPE_RANGE),
            ],
            Campaign::MATCH_CRITERIA_MODE_AT_LEAST_ONE,
            'Hola',
            'Shirt'
        ));

        $this->assertTrue($this->filtersMatch(
            [
                Filter::create('year', ['2000..2010'], Filter::MUST_ALL, Filter::TYPE_RANGE),
                Filter::create('price', ['50..100'], Filter::MUST_ALL, Filter::TYPE_RANGE),
            ],
            [
                Filter::create('year', ['1990..2020'], Filter::MUST_ALL, Filter::TYPE_RANGE),
            ],
            [
                Filter::create('price', ['25..200'], Filter::MUST_ALL, Filter::TYPE_RANGE),
            ],
            Campaign::MATCH_CRITERIA_MODE_MUST_ALL,
            'Shirt',
            'Shirt'
        ));

        $this->assertFalse($this->filtersMatch(
            [Filter::create('year', ['..'], Filter::MUST_ALL, Filter::TYPE_RANGE)],
            [Filter::create('year', ['0..'], Filter::MUST_ALL, Filter::TYPE_RANGE)]
        ));

        $this->assertFalse($this->filtersMatch(
            [Filter::create('year', ['..'], Filter::MUST_ALL, Filter::TYPE_RANGE)],
            [Filter::create('year', ['..1000'], Filter::MUST_ALL, Filter::TYPE_RANGE)]
        ));

        $this->assertFalse($this->filtersMatch(
            [Filter::create('year', ['..'], Filter::MUST_ALL, Filter::TYPE_RANGE)],
            [Filter::create('year', ['0..1000'], Filter::MUST_ALL, Filter::TYPE_RANGE)]
        ));

        $this->assertFalse($this->filtersMatch(
            [Filter::create('year', ['4..'], Filter::MUST_ALL, Filter::TYPE_RANGE)],
            [Filter::create('year', ['0..1000'], Filter::MUST_ALL, Filter::TYPE_RANGE)]
        ));

        $this->assertFalse($this->filtersMatch(
            [],
            [Filter::create('year', ['0..1000'], Filter::MUST_ALL, Filter::TYPE_RANGE)]
        ));

        $this->assertFalse($this->filtersMatch(
            [Filter::create('year', ['10..100'], Filter::MUST_ALL, Filter::TYPE_RANGE)],
            [
                Filter::create('year', ['0..1000'], Filter::MUST_ALL, Filter::TYPE_RANGE),
                Filter::create('another', ['0..1000'], Filter::MUST_ALL, Filter::TYPE_RANGE),
            ]
        ));

        $this->assertFalse($this->filtersMatch(
            [
                Filter::create('year', ['2000..2010'], Filter::MUST_ALL, Filter::TYPE_RANGE),
                Filter::create('price', ['50..100'], Filter::MUST_ALL, Filter::TYPE_RANGE),
            ],
            [
                Filter::create('year', ['1990..2020'], Filter::MUST_ALL, Filter::TYPE_RANGE),
                Filter::create('price', ['100..200'], Filter::MUST_ALL, Filter::TYPE_RANGE),
            ]
        ));

        $this->assertFalse($this->filtersMatch(
            [
                Filter::create('year', ['2000..2010'], Filter::MUST_ALL, Filter::TYPE_RANGE),
                Filter::create('price', ['50..100'], Filter::MUST_ALL, Filter::TYPE_RANGE),
            ],
            [
                Filter::create('bla_bla', ['1990..2020'], Filter::MUST_ALL, Filter::TYPE_RANGE),
            ],
            [
                Filter::create('bla_bla', ['100..200'], Filter::MUST_ALL, Filter::TYPE_RANGE),
            ],
            'another_non_existing_mode'
        ));

        $this->assertFalse($this->filtersMatch(
            [
                Filter::create('year', ['2000..2010'], Filter::MUST_ALL, Filter::TYPE_RANGE),
                Filter::create('price', ['50..100'], Filter::MUST_ALL, Filter::TYPE_RANGE),
            ],
            [
                Filter::create('year', ['1990..2020'], Filter::MUST_ALL, Filter::TYPE_RANGE),
            ],
            [
                Filter::create('price', ['25..200'], Filter::MUST_ALL, Filter::TYPE_RANGE),
            ],
            Campaign::MATCH_CRITERIA_MODE_MUST_ALL,
            'Hola',
            'Shirt'
        ));

        $this->assertFalse($this->filtersMatch(
            [
                Filter::create('year', ['2000..2010'], Filter::MUST_ALL, Filter::TYPE_RANGE),
                Filter::create('price', ['50..100'], Filter::MUST_ALL, Filter::TYPE_RANGE),
            ],
            [
                Filter::create('year', ['2010..2020'], Filter::MUST_ALL, Filter::TYPE_RANGE),
            ],
            [
                Filter::create('price', ['25..200'], Filter::MUST_ALL, Filter::TYPE_RANGE),
            ],
            Campaign::MATCH_CRITERIA_MODE_AT_LEAST_ONE,
            'Shirt',
            'Hola'
        ));
    }

    /**
     * Test repository reference matches campaign.
     */
    public function testRepositoryReferenceMatchesCampaign()
    {
        $matcher = new Matcher();

        $this->assertTrue($matcher->repositoryReferenceMatchesCampaign(
            RepositoryReference::createFromComposed('123_ABC'),
            $campaign = new Campaign(new CampaignUID('1'), null, null, IndexUUID::createById('ABC'), [], Campaign::MATCH_CRITERIA_MODE_MUST_ALL, [], CampaignModifiers::createFromArray([]))
        ));

        $this->assertTrue($matcher->repositoryReferenceMatchesCampaign(
            RepositoryReference::createFromComposed('123_ABC'),
            $campaign = new Campaign(new CampaignUID('1'), null, null, IndexUUID::createById('ABC'), [], Campaign::MATCH_CRITERIA_MODE_MUST_ALL, [], CampaignModifiers::createFromArray([]))
        ));

        $this->assertTrue($matcher->repositoryReferenceMatchesCampaign(
            RepositoryReference::createFromComposed('123_ABC,DEF'),
            $campaign = new Campaign(new CampaignUID('1'), null, null, IndexUUID::createById('ABC'), [], Campaign::MATCH_CRITERIA_MODE_MUST_ALL, [], CampaignModifiers::createFromArray([]))
        ));

        $this->assertTrue($matcher->repositoryReferenceMatchesCampaign(
            RepositoryReference::createFromComposed('123_'),
            $campaign = new Campaign(new CampaignUID('1'), null, null, IndexUUID::createById('ABC'), [], Campaign::MATCH_CRITERIA_MODE_MUST_ALL, [], CampaignModifiers::createFromArray([]))
        ));

        $this->assertFalse($matcher->repositoryReferenceMatchesCampaign(
            RepositoryReference::createFromComposed('123_ABC'),
            $campaign = new Campaign(new CampaignUID('1'), null, null, IndexUUID::createById('EFG'), [], Campaign::MATCH_CRITERIA_MODE_MUST_ALL, [], CampaignModifiers::createFromArray([]))
        ));

        $this->assertFalse($matcher->repositoryReferenceMatchesCampaign(
            RepositoryReference::createFromComposed('123_ABC,CDE'),
            $campaign = new Campaign(new CampaignUID('1'), null, null, IndexUUID::createById('HIJ'), [], Campaign::MATCH_CRITERIA_MODE_MUST_ALL, [], CampaignModifiers::createFromArray([]))
        ));
    }

    /**
     * This assert query matches.
     *
     * @param string      $queryFound
     * @param string      $matchCriteriaType
     * @param string|null $queryToMatch
     *
     * @return bool
     */
    private function queriesMatch(
        string $queryFound,
        string $matchCriteriaType,
        ?string $queryToMatch
    ): bool {
        $query = Query::create($queryFound);
        $matcher = new Matcher();
        $now = new DateTime();

        $campaign = new Campaign(new CampaignUID('1'), null, null, IndexUUID::createById(''), [
            new CampaignCriteria(
                $matchCriteriaType,
                $queryToMatch,
                []
            ),
        ], Campaign::MATCH_CRITERIA_MODE_MUST_ALL, [], CampaignModifiers::createFromArray([]));

        return $matcher->queryMatchesCampaign($query, $campaign, $now);
    }

    /**
     * @param Filter[] $queryFilters
     * @param Filter[] $criteriaFilters
     * @param Filter[] $anotherCriteriaFilters
     * @param string   $criteriaFiltersMode
     * @param string   $queryString
     * @param string   $exactMatchingQuery
     */
    private function filtersMatch(
        array $queryFilters,
        array $criteriaFilters,
        array $anotherCriteriaFilters = [],
        string $criteriaFiltersMode = Campaign::MATCH_CRITERIA_MODE_MUST_ALL,
        string $queryString = null,
        string $exactMatchingQuery = null
    ) {
        $filters = [];
        foreach ($queryFilters as $queryFilter) {
            $filters[$queryFilter->getField()] = $queryFilter->toArray();
        }

        $query = Query::createFromArray([
            'q' => $queryString,
            'filters' => $filters,
        ]);

        $matcher = new Matcher();
        $now = new DateTime();

        $campaign = new Campaign(new CampaignUID('1'), null, null, IndexUUID::createById(''), [
            new CampaignCriteria(
                '', null, $criteriaFilters
            ),
            new CampaignCriteria(
                $exactMatchingQuery ? CampaignCriteria::MATCH_TYPE_EXACT : '',
                $exactMatchingQuery,
                $anotherCriteriaFilters
            ),
        ], $criteriaFiltersMode, [], CampaignModifiers::createFromArray([]));

        return $matcher->queryMatchesCampaign($query, $campaign, $now);
    }
}
