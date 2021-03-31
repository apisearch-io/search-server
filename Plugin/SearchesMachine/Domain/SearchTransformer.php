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

namespace Apisearch\Plugin\SearchesMachine\Domain;

use Apisearch\Repository\RepositoryReference;
use Apisearch\Server\Domain\Model\Origin;
use Apisearch\Server\Domain\Repository\SearchesRepository\Search;
use DateTime;

/**
 * Class SearchTransformer.
 */
class SearchTransformer
{
    /**
     * @param RepositoryReference $repositoryReference
     * @param string              $userUUID
     * @param string              $searchText
     * @param int                 $numberOfResults
     * @param Origin              $origin
     * @param DateTime            $when
     *
     * @return string
     */
    public static function toString(
        RepositoryReference $repositoryReference,
        string $userUUID,
        string $searchText,
        int $numberOfResults,
        Origin $origin,
        DateTime $when
    ): string {
        return \implode('|', [
            $userUUID,
            $repositoryReference->getAppUUID()->composeUUID(),
            $repositoryReference->getIndexUUID()->composeUUID(),
            $searchText,
            $numberOfResults,
            $origin->getIp(),
            $origin->getHost(),
            $origin->getPlatform(),
            $when->format('U'),
        ]);
    }

    /**
     * @param string $search
     *
     * @return Search
     */
    public static function fromString(string $search): Search
    {
        $array = \explode('|', $search);
        $numberOfResults = \intval($array[4]);

        return new Search(
            $array[0],
            $array[1],
            $array[2],
            $array[3],
            $numberOfResults,
            ($numberOfResults > 0),
            $array[5],
            $array[6],
            $array[7],
            \DateTime::createFromFormat('U', $array[8]),
        );
    }
}
