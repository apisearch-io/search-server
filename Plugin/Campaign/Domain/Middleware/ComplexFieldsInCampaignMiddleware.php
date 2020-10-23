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

namespace Apisearch\Plugin\Campaign\Domain\Middleware;

use Apisearch\Plugin\Campaign\Domain\Command\PutCampaign;
use Apisearch\Query\Filter;
use Apisearch\Server\Domain\Middleware\ComplexFields\ComplexFieldsMiddleware;
use Apisearch\Server\Domain\Plugin\PluginMiddleware;
use Drift\CommandBus\Middleware\DiscriminableMiddleware;
use React\Promise\PromiseInterface;

/**
 * Class ComplexFieldsInCampaignMiddleware.
 */
class ComplexFieldsInCampaignMiddleware extends ComplexFieldsMiddleware implements DiscriminableMiddleware, PluginMiddleware
{
    /**
     * Execute middleware.
     *
     * @param mixed    $command
     * @param callable $next
     *
     * @return PromiseInterface
     */
    public function execute($command, $next): PromiseInterface
    {
        /**
         * @var PutCampaign
         */
        $campaign = $command->getCampaign();
        $repositoryReference = $command->getRepositoryReference();

        $complexFields = $this
            ->metadataRepository
            ->get($repositoryReference, static::COMPLEX_FIELDS_METADATA);

        if (empty($complexFields)) {
            return $next($command);
        }

        foreach ($campaign->getMatchCriteria() as $matchCriteria) {
            $filters = $matchCriteria->getFilters();
            foreach ($filters as $filterId => $filter) {
                $field = $filter->getField();
                if (\in_array($field, $complexFields)) {
                    $filters[$filterId] = Filter::create(
                        $field.'_id',
                        $filter->getValues(),
                        $filter->getApplicationType(),
                        $filter->getFilterType(),
                        $filter->getFilterTerms()
                    );
                }
            }

            $matchCriteria->setFilters($filters);
        }

        foreach ($campaign->getBoostingFilters() as $boostingFilter) {
            $filter = $boostingFilter->getFilter();
            $field = $filter->getField();
            if (\in_array($field, $complexFields)) {
                $filter = Filter::create(
                    $field.'_id',
                    $filter->getValues(),
                    $filter->getApplicationType(),
                    $filter->getFilterType(),
                    $filter->getFilterTerms()
                );

                $boostingFilter->setFilter($filter);
            }
        }

        return $next($command);
    }

    /**
     * Only handle.
     *
     * @return string[]
     */
    public function onlyHandle(): array
    {
        return [
            PutCampaign::class,
        ];
    }
}
