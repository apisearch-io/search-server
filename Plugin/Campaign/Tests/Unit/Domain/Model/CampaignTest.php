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

namespace Plugin\Campaign\Tests\Unit\Domain\Model;

use Apisearch\Model\IndexUUID;
use Apisearch\Plugin\Campaign\Domain\Model\Campaign;
use Apisearch\Plugin\Campaign\Domain\Model\CampaignBoostingFilter;
use Apisearch\Plugin\Campaign\Domain\Model\CampaignCriteria;
use Apisearch\Plugin\Campaign\Domain\Model\CampaignModifiers;
use Apisearch\Plugin\Campaign\Domain\Model\CampaignUID;
use Apisearch\Query\Filter;
use DateTime;
use PHPUnit\Framework\TestCase;

/**
 * Class CampaignTest.
 */
class CampaignTest extends TestCase
{
    /**
     * Test from/to array.
     *
     * @return void
     */
    public function testFromToArray(): void
    {
        $campaign = new Campaign(
            new CampaignUID('123'),
            null, null, IndexUUID::createById('A'), [
                new CampaignCriteria(
                    CampaignCriteria::MATCH_TYPE_SIMILAR,
                    'Matutano'
                ),
            ], Campaign::MATCH_CRITERIA_MODE_MUST_ALL, [
                new CampaignBoostingFilter(
                    Filter::create('simple_string', ['hola'], Filter::MUST_ALL, Filter::TYPE_FIELD),
                    2,
                    false
                ),
            ],
            CampaignModifiers::createFromArray([]),
        );

        $campaign2 = Campaign::createFromArray($campaign->toArray());
        $this->assertEquals(
            $campaign->toArray(),
            $campaign2->toArray()
        );

        $campaign = new Campaign(
            new CampaignUID('1000'),
            (new DateTime()), (new DateTime()),
            IndexUUID::createById('A'), [], Campaign::MATCH_CRITERIA_MODE_MUST_ALL, [],
            CampaignModifiers::createFromArray([]),
        );

        $campaign2 = Campaign::createFromArray($campaign->toArray());
        $this->assertEquals(
            $campaign->toArray(),
            $campaign2->toArray()
        );
    }
}
