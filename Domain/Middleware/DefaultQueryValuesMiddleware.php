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

namespace Apisearch\Server\Domain\Middleware;

use Apisearch\Result\Result;
use Apisearch\Server\Domain\Query\Query;
use Drift\CommandBus\Middleware\DiscriminableMiddleware;
use React\Promise\PromiseInterface;

/**
 * Class DefaultQueryValuesMiddleware.
 */
final class DefaultQueryValuesMiddleware implements DiscriminableMiddleware
{
    private int $defaultNumberOfSuggestions;

    /**
     * @param int $defaultNumberOfSuggestions
     */
    public function __construct(int $defaultNumberOfSuggestions)
    {
        $this->defaultNumberOfSuggestions = $defaultNumberOfSuggestions;
    }

    /**
     * @param Query    $query
     * @param callable $next
     *
     * @return PromiseInterface<Result>
     */
    public function execute(Query $query, callable $next): PromiseInterface
    {
        $queryModel = $query->getQuery();

        if ($queryModel->areSuggestionsEnabled()) {
            $numberOfSuggestionsField = 'number_of_suggestions';
            $queryModel->setMetadataValue(
                $numberOfSuggestionsField,
                $queryModel->getMetadata()[$numberOfSuggestionsField] ?? $this->defaultNumberOfSuggestions
            );
        }

        return $next($query);
    }

    /**
     * Only handle.
     *
     * @return string[]
     */
    public function onlyHandle(): array
    {
        return [
            Query::class,
        ];
    }
}
