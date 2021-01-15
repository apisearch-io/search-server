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

namespace Apisearch\Plugin\Campaign\Domain\Model;

use Apisearch\Model\HttpTransportable;
use Apisearch\Model\IndexUUID;
use Apisearch\Query\Filter;
use DateTime;

/**
 * Class Campaign.
 */
class Campaign implements HttpTransportable
{
    const MATCH_CRITERIA_MODE_MUST_ALL = 'must_all';
    const MATCH_CRITERIA_MODE_AT_LEAST_ONE = 'at_least_one';

    private CampaignUID $uid;
    private ?DateTime $from;
    private ?DateTime $to;
    private IndexUUID $indexUUID;

    private array $matchCriteria;
    private string $matchCriteriaMode;
    private array $boostingFilters;
    private CampaignModifiers $campaignModifiers;

    /**
     * @param CampaignUID              $uid
     * @param DateTime|null            $from
     * @param DateTime|null            $to
     * @param IndexUUID                $indexUUID
     * @param CampaignCriteria[]       $matchCriteria
     * @param string                   $matchCriteriaMode
     * @param CampaignBoostingFilter[] $boostingFilters
     * @param CampaignModifiers        $campaignModifiers
     */
    public function __construct(
        CampaignUID $uid,
        ?DateTime $from,
        ?DateTime $to,
        IndexUUID $indexUUID,
        array $matchCriteria,
        string $matchCriteriaMode,
        array $boostingFilters,
        CampaignModifiers $campaignModifiers
    ) {
        $this->uid = $uid;
        $this->from = $from;
        $this->to = $to;
        $this->indexUUID = $indexUUID;
        $this->matchCriteria = $matchCriteria;
        $this->matchCriteriaMode = $matchCriteriaMode;
        $this->boostingFilters = $boostingFilters;
        $this->campaignModifiers = $campaignModifiers;
    }

    /**
     * @return CampaignUID
     */
    public function getUid(): CampaignUID
    {
        return $this->uid;
    }

    /**
     * @return DateTime|null
     */
    public function getFrom(): ?DateTime
    {
        return $this->from;
    }

    /**
     * @return IndexUUID
     */
    public function getIndexUUID(): IndexUUID
    {
        return $this->indexUUID;
    }

    /**
     * @return int|null
     */
    public function getFromTimestamp(): ?int
    {
        return $this->from
            ? $this->from->getTimestamp()
            : null;
    }

    /**
     * @return DateTime|null
     */
    public function getTo(): ?DateTime
    {
        return $this->to;
    }

    /**
     * @return int|null
     */
    public function getToTimestamp(): ?int
    {
        return $this->to
            ? $this->to->getTimestamp()
            : null;
    }

    /**
     * @return CampaignCriteria[]
     */
    public function getMatchCriteria(): array
    {
        return $this->matchCriteria;
    }

    /**
     * @return string
     */
    public function getMatchCriteriaMode(): string
    {
        return $this->matchCriteriaMode;
    }

    /**
     * @return CampaignBoostingFilter[]
     */
    public function getBoostingFilters(): array
    {
        return $this->boostingFilters;
    }

    /**
     * @return CampaignModifiers
     */
    public function getCampaignModifiers(): CampaignModifiers
    {
        return $this->campaignModifiers;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'uid' => $this->getUid()->getUid(),
            'from' => $this->getFromTimestamp(),
            'to' => $this->getToTimestamp(),
            'index_uuid' => $this->getIndexUUID()->toArray(),
            'match_criteria' => \array_map(function (CampaignCriteria $criteria) {
                return [
                    'type' => $criteria->getMatchType(),
                    'text' => $criteria->getQueryText(),
                    'filters' => \array_map(fn (Filter $filter) => $filter->toArray(), $criteria->getFilters()),
                ];
            }, $this->getMatchCriteria()),
            'match_criteria_mode' => $this->getMatchCriteriaMode(),
            'boosting_filters' => \array_map(function (CampaignBoostingFilter $boostingFilter) {
                return [
                    'filter' => $boostingFilter->getFilter()->toArray(),
                    'factor' => $boostingFilter->getBoostingFactor(),
                    'matching_main_query' => $boostingFilter->isMatchMainQuery(),
                ];
            }, $this->getBoostingFilters()),
            'modifiers' => $this->campaignModifiers->toArray(),
        ];
    }

    /**
     * @param array $array
     *
     * @return HttpTransportable|void
     */
    public static function createFromArray(array $array)
    {
        return new Campaign(
            new CampaignUID($array['uid']),
            $array['from'] ? DateTime::createFromFormat('U', \strval($array['from'])) : null,
            $array['to'] ? DateTime::createFromFormat('U', \strval($array['to'])) : null,
            IndexUUID::createFromArray($array['index_uuid']),
            \array_map(function (array $criteria) {
                return new CampaignCriteria(
                    $criteria['type'],
                    $criteria['text'],
                    \array_map(fn (array $filter) => Filter::createFromArray($filter), $criteria['filters'] ?? [])
                );
            }, $array['match_criteria'] ?? []),
            $array['match_criteria_mode'] ?? self::MATCH_CRITERIA_MODE_MUST_ALL,
            \array_map(function (array $boostingFilter) {
                return new CampaignBoostingFilter(
                    Filter::createFromArray($boostingFilter['filter']),
                    $boostingFilter['factor'],
                    $boostingFilter['matching_main_query'],
                );
            }, $array['boosting_filters'] ?? []),
            CampaignModifiers::createFromArray($array['modifiers'])
        );
    }
}
