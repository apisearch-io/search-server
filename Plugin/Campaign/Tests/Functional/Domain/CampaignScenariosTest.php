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

namespace Apisearch\Plugin\Campaign\Tests\Functional\Domain;

use Apisearch\Model\IndexUUID;
use Apisearch\Plugin\Campaign\Domain\Model\Campaign;
use Apisearch\Plugin\Campaign\Domain\Model\CampaignBoostingFilter;
use Apisearch\Plugin\Campaign\Domain\Model\CampaignCriteria;
use Apisearch\Plugin\Campaign\Domain\Model\CampaignModifiers;
use Apisearch\Plugin\Campaign\Domain\Model\CampaignUID;
use Apisearch\Query\Filter;
use Apisearch\Query\Query;
use DateTime;

/**
 * Trait CampaignApplicatorTest.
 */
trait CampaignScenariosTest
{
    /**
     * Test simple campaign.
     *
     * @return void
     */
    public function testSimple(): void
    {
        $campaign = new Campaign(
            new CampaignUID('123'),
            null, null, IndexUUID::createById(self::$index),
            [
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
            CampaignModifiers::createFromArray([])
        );

        $this->putCampaign($campaign);
        $result = $this->query(Query::create('Matutano'));

        $this->assertResults(
            $result,
            ['1', '2', '!5', '!4', '!3']
        );
    }

    /**
     * @param DateTime|null $from
     * @param DateTime|null $to
     * @param bool          $matches
     *
     * @dataProvider dataInactiveForDateCampaign
     *
     * @return void
     */
    public function testInactiveForDateCampaign(
        ?DateTime $from,
        ?DateTime $to,
        bool $matches
    ): void {
        $campaign = new Campaign(
            new CampaignUID('123'),
            $from, $to, IndexUUID::createById(self::$index),
            [
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
            CampaignModifiers::createFromArray([])
        );

        $this->putCampaign($campaign);
        $result = $this->query(Query::create('Matutano'));

        $this->assertResults(
            $result,
            [
                ($matches ? '{2' : '2'),
                ($matches ? '1}' : '!1'),
                '!5', '!4', '!3',
            ]
        );
    }

    /**
     * @return array[]
     */
    public function dataInactiveForDateCampaign(): array
    {
        return [
            [
                (new DateTime())->modify('-2 days'),
                (new DateTime())->modify('+2 days'),
                true,
            ],
            [
                (new DateTime())->modify('-2 days'),
                null,
                true,
            ],
            [
                null,
                (new DateTime())->modify('+2 days'),
                true,
            ],
            [
                (new DateTime())->modify('-2 days'),
                (new DateTime())->modify('-1 day'),
                false,
            ],
            [
                (new DateTime()),
                (new DateTime())->modify('-1 day'),
                false,
            ],
            [
                (new DateTime())->modify('+1 day'),
                (new DateTime())->modify('+2 days'),
                false,
            ],
            [
                (new DateTime())->modify('+1 day'),
                (new DateTime()),
                false,
            ],
        ];
    }

    /**
     * Test multiple campaigns.
     *
     * @return void
     */
    public function testMultipleCampaigns(): void
    {
        /**
         * This campaign is overriden by the next one (same IDs).
         */
        $campaign1 = new Campaign(
            new CampaignUID('123'),
            null, null, IndexUUID::createById(self::$index),
            [
                new CampaignCriteria(
                    CampaignCriteria::MATCH_TYPE_SIMILAR,
                    'Matutano'
                ),
            ], Campaign::MATCH_CRITERIA_MODE_MUST_ALL, [
                new CampaignBoostingFilter(
                    Filter::create('relevance', ['0..1000'], Filter::MUST_ALL, Filter::TYPE_RANGE),
                    10,
                    false
                ),
            ],
            CampaignModifiers::createFromArray([])
        );

        $campaign2 = new Campaign(
            new CampaignUID('123'),
            null, null, IndexUUID::createById(self::$index),
            [
                new CampaignCriteria(
                    CampaignCriteria::MATCH_TYPE_SIMILAR,
                    'Matutano'
                ),
            ], Campaign::MATCH_CRITERIA_MODE_MUST_ALL, [
                new CampaignBoostingFilter(
                    Filter::create('simple_string', ['hola'], Filter::MUST_ALL, Filter::TYPE_FIELD),
                    3,
                    false
                ),
            ],
            CampaignModifiers::createFromArray([])
        );

        $campaign3 = new Campaign(
            new CampaignUID('456'),
            null, null, IndexUUID::createById(self::$index),
            [
                new CampaignCriteria(
                    CampaignCriteria::MATCH_TYPE_EXACT,
                    'Matutano'
                ),
            ], Campaign::MATCH_CRITERIA_MODE_MUST_ALL, [
                new CampaignBoostingFilter(
                    Filter::create('id', ['4'], Filter::MUST_ALL, Filter::TYPE_FIELD),
                    5,
                    false
                ),
            ],
            CampaignModifiers::createFromArray([])
        );

        $this->putCampaign($campaign1);
        $this->putCampaign($campaign2);
        $this->putCampaign($campaign3);
        $result = $this->query(Query::create('Matutano'));

        $this->assertResults(
            $result,
            ['4', '1', '2', '!3', '!5']
        );
    }

    /**
     * Test simple campaign.
     *
     * @return void
     */
    public function testIndexNotMatching(): void
    {
        $campaign = new Campaign(
            new CampaignUID('123'),
            null, null, IndexUUID::createById('XXX'),
            [
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
            CampaignModifiers::createFromArray([])
        );

        $this->deleteCampaigns();
        $this->putCampaign($campaign);
        $result = $this->query(Query::create('Matutano'));

        $this->assertResults(
            $result,
            ['2', '!1', '!5', '!4', '!3']
        );
    }

    /**
     * Test simple campaign.
     *
     * @return void
     */
    public function testSpecialFields(): void
    {
        $campaign = new Campaign(
            new CampaignUID('123'),
            null, null, IndexUUID::createById(self::$index),
            [
                new CampaignCriteria(
                    CampaignCriteria::MATCH_TYPE_SIMILAR,
                    'Matutano'
                ),
            ], Campaign::MATCH_CRITERIA_MODE_MUST_ALL, [
                new CampaignBoostingFilter(
                    Filter::create('brand', [1], Filter::MUST_ALL, Filter::TYPE_FIELD),
                    2,
                    false
                ),
            ],
            CampaignModifiers::createFromArray([])
        );

        $this->deleteCampaigns();
        $this->putCampaign($campaign);
        $result = $this->query(Query::create('Matutano'));

        $this->assertResults(
            $result,
            ['{1', '4', '5}', '2', '!3']
        );
    }

    /**
     * Test rest campaigns.
     *
     * @return void
     */
    public function testRestCampaigns(): void
    {
        $this->deleteCampaigns();
        $campaign1 = new Campaign(
            new CampaignUID('123'),
            null, null, IndexUUID::createById(self::$index), [], Campaign::MATCH_CRITERIA_MODE_MUST_ALL, [],
            CampaignModifiers::createFromArray([])
        );

        $campaign2 = new Campaign(
            new CampaignUID('123'),
            null, null, IndexUUID::createById(self::$index), [], Campaign::MATCH_CRITERIA_MODE_MUST_ALL, [],
            CampaignModifiers::createFromArray([])
        );

        $this->putCampaign($campaign2);

        $campaign2 = new Campaign(
            new CampaignUID('456'),
            null, null, IndexUUID::createById(self::$index), [], Campaign::MATCH_CRITERIA_MODE_MUST_ALL, [],
            CampaignModifiers::createFromArray([])
        );

        $this->putCampaign($campaign1);
        $this->putCampaign($campaign2);
        $this->assertCount(2, $this->getCampaigns()->getCampaigns());

        $this->deleteCampaign(new CampaignUID('123'));
        $this->assertCount(1, $this->getCampaigns()->getCampaigns());

        $this->putCampaign($campaign1);
        $this->assertCount(2, $this->getCampaigns()->getCampaigns());

        $this->deleteCampaigns();
        $this->assertCount(0, $this->getCampaigns()->getCampaigns());
    }

    /**
     * Test campaign with min_score.
     *
     * @return void
     */
    public function testCampaignWithMinScore(): void
    {
        $this->deleteCampaigns();
        $campaign1 = new Campaign(
            new CampaignUID('123'),
            null, null, IndexUUID::createById(self::$index), [], Campaign::MATCH_CRITERIA_MODE_MUST_ALL, [],
            CampaignModifiers::createFromArray([])
        );
        $this->putCampaign($campaign1);
        $result = $this->query(Query::create('a'));
        $this->assertCount(4, $result->getItems());

        $result = $this->query(Query::create('a')->setMinScore(0));
        $this->assertCount(4, $result->getItems());

        $campaign1 = new Campaign(
            new CampaignUID('123'),
            null, null, IndexUUID::createById(self::$index), [], Campaign::MATCH_CRITERIA_MODE_MUST_ALL, [],
            CampaignModifiers::createFromArray([
                'min_score' => 1,
            ])
        );
        $this->putCampaign($campaign1);
        $result = $this->query(Query::create('a'));
        $this->assertCount(3, $result->getItems());

        $result = $this->query(Query::create('a')->setMinScore(0));
        $this->assertCount(3, $result->getItems());
    }
}
