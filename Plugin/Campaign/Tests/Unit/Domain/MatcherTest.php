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

namespace Apisearch\Plugin\Campaign\Tests\Unit\Domain;

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
     *
     * @return void
     */
    public function testByTimestamp(): void
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
     *
     * @return void
     */
    public function testByQueryMatchNoMatch(): void
    {
        $this->assertTrue($this->queriesMatch('Marc', CampaignCriteria::MATCH_TYPE_NONE, 'Marc'));
        $this->assertTrue($this->queriesMatch('Another', CampaignCriteria::MATCH_TYPE_NONE, 'Marc'));
    }

    /**
     * Test by query match exact.
     *
     * @return void
     */
    public function testByQueryMatchExact(): void
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
     *
     * @return void
     */
    public function testByQueryMatchIncludesExact(): void
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
     *
     * @return void
     */
    public function testByQueryMatchSimilar(): void
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
     *
     * @return void
     */
    public function testByQueryMatchIncludesSimilar(): void
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

    public function testByQueryMatchWithSeveralValues()
    {
        $this->assertTrue($this->queriesMatch('marc', CampaignCriteria::MATCH_TYPE_EXACT, 'MARC, Àlex'));
        $this->assertTrue($this->queriesMatch('ÀLEX', CampaignCriteria::MATCH_TYPE_EXACT, 'MARC, Àlex'));
        $this->assertTrue($this->queriesMatch('Hola, sóc el morc', CampaignCriteria::MATCH_TYPE_INCLUDES_SIMILAR, 'MARC, Àlex'));
        $this->assertTrue($this->queriesMatch('Hola, sóc el alex', CampaignCriteria::MATCH_TYPE_INCLUDES_SIMILAR, 'MARC, alex'));
        $this->assertTrue($this->queriesMatch('Hola, sóc el alex', CampaignCriteria::MATCH_TYPE_INCLUDES_EXACT, 'MARC, alex'));
        $this->assertTrue($this->queriesMatch('Hola, sóc el alex', CampaignCriteria::MATCH_TYPE_INCLUDES_EXACT, 'aleX, not found'));

        $this->assertFalse($this->queriesMatch('Hola, sóc el alex', CampaignCriteria::MATCH_TYPE_INCLUDES_EXACT, 'MARC, not found'));
    }

    /**
     * Test by field filters match.
     *
     * @return void
     */
    public function testByFieldFilterMatches(): void
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

        $this->assertTrue($this->filtersMatch(
            [Filter::create('category', ['2'], Filter::AT_LEAST_ONE, Filter::TYPE_FIELD)],
            [Filter::create('category', ['2', '3'], Filter::AT_LEAST_ONE, Filter::TYPE_FIELD)]
        ));

        $this->assertTrue($this->filtersMatch(
            [Filter::create('category', ['2'], Filter::MUST_ALL, Filter::TYPE_FIELD)],
            [Filter::create('category', ['2', '3'], Filter::AT_LEAST_ONE, Filter::TYPE_FIELD)]
        ));

        $this->assertTrue($this->filtersMatch(
            [Filter::create('category', ['2'], Filter::AT_LEAST_ONE, Filter::TYPE_FIELD)],
            [Filter::create('category', ['2', '3', '4'], Filter::AT_LEAST_ONE, Filter::TYPE_FIELD)]
        ));

        $this->assertTrue($this->filtersMatch(
            [Filter::create('category', ['2', '10'], Filter::AT_LEAST_ONE, Filter::TYPE_FIELD)],
            [Filter::create('category', ['2', '3', '4'], Filter::AT_LEAST_ONE, Filter::TYPE_FIELD)]
        ));

        $this->assertTrue($this->filtersMatch(
            [Filter::create('category', ['2', '3'], Filter::AT_LEAST_ONE, Filter::TYPE_FIELD)],
            [Filter::create('category', ['2', '3', '4'], Filter::AT_LEAST_ONE, Filter::TYPE_FIELD)]
        ));

        $this->assertTrue($this->filtersMatch(
            [Filter::create('category', ['2', '3', '4'], Filter::AT_LEAST_ONE, Filter::TYPE_FIELD)],
            [Filter::create('category', ['2', '3', '4'], Filter::AT_LEAST_ONE, Filter::TYPE_FIELD)]
        ));
    }

    /**
     * Test by field filters match.
     *
     * @return void
     */
    public function testByRangeFilterMatches(): void
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
     *
     * @return void
     */
    public function testRepositoryReferenceMatchesCampaign(): void
    {
        $matcher = new Matcher();

        $this->assertTrue($matcher->repositoryReferenceMatchesCampaign(
            RepositoryReference::createFromComposed('123_ABC'),
            new Campaign(new CampaignUID('1'), null, null, IndexUUID::createById('ABC'), [], Campaign::MATCH_CRITERIA_MODE_MUST_ALL, [], CampaignModifiers::createFromArray([]))
        ));

        $this->assertTrue($matcher->repositoryReferenceMatchesCampaign(
            RepositoryReference::createFromComposed('123_ABC'),
            new Campaign(new CampaignUID('1'), null, null, IndexUUID::createById('ABC'), [], Campaign::MATCH_CRITERIA_MODE_MUST_ALL, [], CampaignModifiers::createFromArray([]))
        ));

        $this->assertTrue($matcher->repositoryReferenceMatchesCampaign(
            RepositoryReference::createFromComposed('123_ABC,DEF'),
            new Campaign(new CampaignUID('1'), null, null, IndexUUID::createById('ABC'), [], Campaign::MATCH_CRITERIA_MODE_MUST_ALL, [], CampaignModifiers::createFromArray([]))
        ));

        $this->assertTrue($matcher->repositoryReferenceMatchesCampaign(
            RepositoryReference::createFromComposed('123_'),
            new Campaign(new CampaignUID('1'), null, null, IndexUUID::createById('ABC'), [], Campaign::MATCH_CRITERIA_MODE_MUST_ALL, [], CampaignModifiers::createFromArray([]))
        ));

        $this->assertFalse($matcher->repositoryReferenceMatchesCampaign(
            RepositoryReference::createFromComposed('123_ABC'),
            new Campaign(new CampaignUID('1'), null, null, IndexUUID::createById('EFG'), [], Campaign::MATCH_CRITERIA_MODE_MUST_ALL, [], CampaignModifiers::createFromArray([]))
        ));

        $this->assertFalse($matcher->repositoryReferenceMatchesCampaign(
            RepositoryReference::createFromComposed('123_ABC,CDE'),
            new Campaign(new CampaignUID('1'), null, null, IndexUUID::createById('HIJ'), [], Campaign::MATCH_CRITERIA_MODE_MUST_ALL, [], CampaignModifiers::createFromArray([]))
        ));
    }

    public function testComposedMatch()
    {
        $query = Query::createMatchAll();
        $matcher = new Matcher();
        $now = new DateTime();

        $campaign = new Campaign(new CampaignUID('1'), null, null, IndexUUID::createById(''), [
            new CampaignCriteria(
                CampaignCriteria::MATCH_TYPE_NONE,
                '',
                [Filter::create('category', ['2', '3'], Filter::MUST_ALL, Filter::TYPE_FIELD)]
            ),
        ], Campaign::MATCH_CRITERIA_MODE_MUST_ALL, [], CampaignModifiers::createFromArray([]));
        $this->assertFalse($matcher->queryMatchesCampaign($query, $campaign, $now));

        $campaign = new Campaign(new CampaignUID('1'), null, null, IndexUUID::createById(''), [
            new CampaignCriteria(
                CampaignCriteria::MATCH_TYPE_NONE,
                null,
                [Filter::create('category', ['2', '3'], Filter::MUST_ALL, Filter::TYPE_FIELD)]
            ),
        ], Campaign::MATCH_CRITERIA_MODE_MUST_ALL, [], CampaignModifiers::createFromArray([]));
        $this->assertFalse($matcher->queryMatchesCampaign($query, $campaign, $now));

        $campaign = new Campaign(new CampaignUID('1'), null, null, IndexUUID::createById(''), [
            new CampaignCriteria(
                CampaignCriteria::MATCH_TYPE_NONE,
                null,
                []
            ),
        ], Campaign::MATCH_CRITERIA_MODE_MUST_ALL, [], CampaignModifiers::createFromArray([]));
        $this->assertTrue($matcher->queryMatchesCampaign($query, $campaign, $now));

        $query = Query::createMatchAll()->filterBy('category', 'category', ['2']);
        $campaign = new Campaign(new CampaignUID('1'), null, null, IndexUUID::createById(''), [
            new CampaignCriteria(
                CampaignCriteria::MATCH_TYPE_NONE,
                null,
                [Filter::create('indexed_metadata.category', ['2', '3'], Filter::AT_LEAST_ONE, Filter::TYPE_FIELD)]
            ),
        ], Campaign::MATCH_CRITERIA_MODE_MUST_ALL, [], CampaignModifiers::createFromArray([]));
        $this->assertTrue($matcher->queryMatchesCampaign($query, $campaign, $now));
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
     * @param Filter[]    $queryFilters
     * @param Filter[]    $criteriaFilters
     * @param Filter[]    $anotherCriteriaFilters
     * @param string      $criteriaFiltersMode
     * @param string|null $queryString
     * @param string|null $exactMatchingQuery
     *
     * @return bool
     */
    private function filtersMatch(
        array $queryFilters,
        array $criteriaFilters,
        array $anotherCriteriaFilters = [],
        string $criteriaFiltersMode = Campaign::MATCH_CRITERIA_MODE_MUST_ALL,
        string $queryString = null,
        string $exactMatchingQuery = null
    ): bool {
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
