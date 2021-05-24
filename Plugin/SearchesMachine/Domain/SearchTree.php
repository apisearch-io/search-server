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

use Apisearch\Server\Domain\Repository\SearchesRepository\Search;

class SearchTree
{
    private array $treesOrLeaves = [];

    /**
     * @param string $text
     * @param Search $search
     */
    public function add(
        string $text,
        Search $search
    ) {
        if (empty($text)) {
            return;
        }

        if (1 === \strlen($text)) {
            if (!isset($this->treesOrLeaves[$text])) {
                $this->treesOrLeaves[$text] = $search;
            }

            return;
        }

        $firstChar = \substr($text, 0, 1);
        $otherText = \substr($text, 1);

        if (
            !isset($this->treesOrLeaves[$firstChar]) ||
            !$this->treesOrLeaves[$firstChar] instanceof SearchTree
        ) {
            $this->treesOrLeaves[$firstChar] = new SearchTree();
        }

        $this->treesOrLeaves[$firstChar]->add($otherText, $search);
    }

    /**
     * @return Search[]
     */
    public function leaves(): array
    {
        $leavesArray = \array_map(function ($treesOrLeaf) {
            return $treesOrLeaf instanceof SearchTree
                ? $treesOrLeaf->leaves()
                : [$treesOrLeaf];
        }, $this->treesOrLeaves);

        $leavesMerge = [];
        \array_walk($leavesArray, function ($leaves) use (&$leavesMerge) {
            $leavesMerge = \array_merge($leavesMerge, $leaves);
        });

        return $leavesMerge;
    }

    /**
     * @return array
     */
    public function debugTree(): array
    {
        return \array_map(function ($treesOrLeaf) {
            return $treesOrLeaf instanceof SearchTree
                ? $treesOrLeaf->leaves()
                : 'x';
        }, $this->treesOrLeaves);
    }
}
