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

namespace Apisearch\Plugin\Security\Domain\Middleware;

use Apisearch\Model\Token;
use Apisearch\Query\Query as ModelQuery;
use Apisearch\Server\Domain\Plugin\PluginMiddleware;
use Apisearch\Server\Domain\Query\Query;
use React\Promise\PromiseInterface;

/**
 * Class RestrictedFieldsMiddleware.
 */
class RestrictedFieldsMiddleware implements PluginMiddleware
{
    /**
     * Execute middleware.
     *
     * @param mixed    $command
     * @param callable $next
     *
     * @return PromiseInterface
     */
    public function execute(
        $command,
        $next
    ): PromiseInterface {
        /**
         * @var Query
         */
        $token = $command->getToken();
        $query = $command->getQuery();
        $this->makeRestrictionsInQuery($query, $token);
        foreach ($query->getSubqueries() as $subquery) {
            $this->makeRestrictionsInQuery($subquery, $token);
        }

        return $next($command);
    }

    /**
     * Make restrictions in query.
     *
     * @param ModelQuery $query
     * @param Token      $token
     *
     * @return void
     */
    private function makeRestrictionsInQuery(
        ModelQuery $query,
        Token $token
    ): void {
        $restrictedFields = $token->getMetadataValue('restricted_fields', []);
        $allowedFields = $token->getMetadataValue('allowed_fields', []);
        $fields = $query->getFields();

        foreach ($restrictedFields as $restrictedField) {
            $fields[] = '!'.$restrictedField;
        }

        foreach ($allowedFields as $allowedField) {
            $fields[] = $allowedField;
        }

        $query->setFields($fields);
    }

    /**
     * {@inheritdoc}
     */
    public function onlyHandle(): array
    {
        return [Query::class];
    }
}
