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

use Apisearch\Server\Domain\Helper\Str;
use Apisearch\Server\Domain\Repository\SearchesRepository\Search;

/**
 * Class SearchesMachine.
 */
class SearchesMachine
{
    /**
     * @var SearchTree[]
     */
    private array $searchesTree = [];

    /**
     * @var Search[]
     */
    private array $searchCompiledLeaves = [];

    /**
     * @param Search $search
     */
    public function addSearch(Search $search): void
    {
        $sessionHash = $this->getSearchSessionHash($search);
        if (!isset($this->searchesTree[$sessionHash])) {
            $this->searchesTree[$sessionHash] = new SearchTree();
        }

        $text = $search->getText();
        $text = \trim($text);
        $text = Str::toAscii($text);
        $text = \strtolower($text);
        $this->searchesTree[$sessionHash]->add($text, $search);
    }

    /**
     * @return array
     */
    public function debugTrees()
    {
        return \array_map(function (SearchTree $searchTree) {
            return $searchTree->debugTree();
        }, $this->searchesTree);
    }

    /**
     * Compile the machine.
     */
    public function compile(): void
    {
        $this->searchCompiledLeaves = [];
        foreach ($this->searchesTree as $hash => $searchTree) {
            $this->searchCompiledLeaves = \array_merge(
                $this->searchCompiledLeaves,
                $searchTree->leaves()
            );
        }
    }

    /**
     * @return Search[]
     */
    public function getSearches(): array
    {
        return $this->searchCompiledLeaves;
    }

    /**
     * @param Search $search
     *
     * @return string
     */
    private function getSearchSessionHash(Search $search): string
    {
        return \md5(\json_encode([
            $search->getUser(),
            $search->getAppUUID(),
            $search->getIndexUUID(),
            $search->getIp(),
            $search->getHost(),
            $search->getPlatform(),
        ]));
    }
}
