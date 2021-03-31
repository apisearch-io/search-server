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

namespace Apisearch\Server\Http;

use Apisearch\Exception\InvalidFormatException;
use Apisearch\Exception\TransportableException;
use Apisearch\Http\Http;
use Apisearch\Model\AppUUID;
use Apisearch\Model\IndexUUID;
use Apisearch\Model\Token;
use Apisearch\Query\Query;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class RequestAccessor.
 */
final class RequestAccessor
{
    /**
     * Get element from body, having a default value and a possible exception to
     * throw if this element is not accessible.
     *
     * @param Request                $request
     * @param string                 $field
     * @param TransportableException $exception
     * @param array|null             $default
     *
     * @return array
     */
    public static function extractRequestContentObject(
        Request $request,
        string $field,
        TransportableException $exception,
        array $default = null
    ): array {
        $requestContent = $request->getContent();
        $requestBody = \json_decode($requestContent, true);

        if (
            !empty($requestContent) &&
            \is_null($requestBody)
        ) {
            throw $exception;
        }

        if (
            !\is_array($requestBody) ||
            (
                !empty($field) &&
                (
                    !isset($requestBody[$field]) ||
                    !\is_array($requestBody[$field])
                )
            )
        ) {
            if (\is_null($default)) {
                throw $exception;
            }

            return $default;
        }

        return empty($field)
            ? $requestBody
            : $requestBody[$field];
    }

    /**
     * Extract query from request.
     *
     * @param Request $request
     * @param string  $requestField
     *
     * @return Query
     *
     * @throws InvalidFormatException
     */
    public static function extractQuery(
        Request $request,
        string $requestField = ''
    ): Query {
        if ($request->attributes->has(Http::QUERY_FIELD)) {
            return $request->attributes->get(Http::QUERY_FIELD);
        }

        $queryAsArray = self::extractRequestContentObject(
            $request,
            $requestField,
            InvalidFormatException::queryFormatNotValid($request->getContent()),
            []
        );

        /*
         * We accept queries as well by GET in order to be able to cache them in
         * CDNs by using Cache headers
         */
        if ([] === $queryAsArray) {
            $possibleQuery = $request->query->get(Http::QUERY_FIELD);
            if (\is_string($possibleQuery)) {
                $queryAsArray = self::decodeQuery($possibleQuery);
            }
        }

        if (empty($queryAsArray)) {
            $queryAsArray = [];
        }

        $query = Query::createFromArray($queryAsArray);
        $request->attributes->set(Http::QUERY_FIELD, $query);

        return $query;
    }

    /**
     * @param string $query
     *
     * @return array
     *
     * @throws InvalidFormatException
     */
    private static function decodeQuery(string $query): array
    {
        $response = \json_decode($query, true);
        if (JSON_ERROR_NONE !== \json_last_error()) {
            throw InvalidFormatException::queryFormatNotValid($query);
        }

        return $response;
    }

    /**
     * @param Request $request
     * @param Query   $query
     */
    public static function setQuery(
        Request $request,
        Query $query
    ): void {
        $request->attributes->set(Http::QUERY_FIELD, $query);
    }

    /**
     * Get token uuid from request.
     *
     * @param Request $request
     *
     * @return Token|null
     */
    public static function getTokenFromRequest(Request $request): ? Token
    {
        return $request->attributes->get(Http::TOKEN_FIELD);
    }

    /**
     * Get app uuid from request.
     *
     * @param Request $request
     *
     * @return AppUUID|null
     */
    public static function getAppUUIDFromRequest(Request $request): ? AppUUID
    {
        $appId = $request->get('app_id', null);

        return $appId
            ? AppUUID::createById($appId)
            : null;
    }

    /**
     * Get index uuid from request.
     *
     * @param Request $request
     *
     * @return IndexUUID|null
     */
    public static function getIndexUUIDFromRequest(Request $request): ? IndexUUID
    {
        $indexId = $request->get('index_id', null);

        return $indexId
            ? IndexUUID::createById($indexId)
            : null;
    }
}
