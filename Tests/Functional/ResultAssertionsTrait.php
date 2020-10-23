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

namespace Apisearch\Server\Tests\Functional;

use Apisearch\Result\Result;

/**
 * Trait ResultAssertionsTrait.
 */
trait ResultAssertionsTrait
{
    /**
     * Assert IDS sequence.
     *
     * ["1", "2", "?3", "*4" "!999"]
     *
     * Positions are relative, and will be checked, so at this case we
     * are asserting that 1 and 2 exists, and 1 is scored higher than 2. We
     * assert as well that 999 is not a result
     *
     * An id with a ? before means that should only check that the element
     * exists, but the relativity is not checked
     *
     * An id with a * before means that should only check relativity and not
     * element existence
     *
     * Grouping can also be managed by using { and }
     *
     * ["1", "{2", "3}", "5"]
     *
     * This means that will be checked
     *
     * * 1 > 2
     * * 1 > 3
     * * 3 > 5
     *
     * Only one group nesting is allowing
     *
     * @param Result   $result
     * @param string[] $ids
     */
    protected function assertResults(
        Result $result,
        array $ids
    ) {
        $lastIdFound = false;
        $inGroup = false;
        foreach ($ids as $id) {
            $idWithoutGrouping = \trim($id, '{}');
            $mustCheckExistence = 0 !== \strpos($idWithoutGrouping, '*');
            $mustExist = 0 !== \strpos($idWithoutGrouping, '!');
            $mustCheckRelativity = (0 !== \strpos($idWithoutGrouping, '?')) && $mustExist;
            $cleanId = \trim($idWithoutGrouping, '?*!');

            if ($mustCheckExistence) {
                $this->assertSame(
                    $mustExist,
                    $this->idFoundInResults($result, $cleanId)
                );
            }

            if (
                $mustCheckRelativity &&
                \is_string($lastIdFound)
            ) {
                $this->assertId1MatchesBetterThanId2(
                    $result,
                    $lastIdFound,
                    $cleanId
                );
            }

            if (\strlen($id) !== \strlen(\ltrim($id, '{'))) {
                $inGroup = true;
            }

            if (\strlen($id) !== \strlen(\rtrim($id, '}'))) {
                $inGroup = false;
            }

            if (!$inGroup) {
                $lastIdFound = $cleanId;
            }
        }
    }

    /**
     * Assert that the position *n* contains the type element desired, and the
     * id specified.
     *
     * If position is null, will assert if the entry does not exists
     *
     * @param Result $result
     * @param int    $position
     * @param string $id
     */
    protected function assertNTypeElementId(
        Result $result,
        int $position,
        string $id
    ) {
        $elements = $result->getItems();
        if (!\array_key_exists($position, $elements)) {
            $this->fail("Element $position not found in results stack");
        } else {
            $this->assertSame(
                $id,
                $elements[$position]->getUUID()->getId()
            );
        }
    }

    /**
     * Assert that id1 matches better than id2 in a result, given a result set
     * and a type.
     *
     * @param Result $result
     * @param string $id1
     * @param string $id2
     */
    protected function assertId1MatchesBetterThanId2(
        Result $result,
        string $id1,
        string $id2
    ) {
        $foundId1 = false;
        foreach ($result->getItems() as $element) {
            $foundId = $element->getUUID()->getId();
            if ($id1 === $foundId) {
                $foundId1 = true;
                continue;
            }

            if ($id2 === $foundId) {
                $this->assertTrue($foundId1, "Item $id2 was not found after Item $id1");

                return;
            }
        }

        $this->assertTrue($foundId1, "Item $id2 was not found after Item $id1");
    }

    /**
     * Print item results with score.
     *
     * @param Result $result
     */
    protected function printItemResultsWithScore(Result $result)
    {
        echo PHP_EOL;
        foreach ($result->getItems() as $item) {
            echo \sprintf('[ %s ] - %f', $item->composeUUID(), $item->getScore()).PHP_EOL;
        }
        echo PHP_EOL;
    }

    /**
     * Id is found in results.
     *
     * @param Result $result
     * @param string $id
     *
     * @return bool
     */
    protected function idFoundInResults(
        Result $result,
        string $id
    ): bool {
        $found = false;
        foreach ($result->getItems() as $element) {
            if ($element->getUUID()->getId() === $id) {
                $found = true;
            }
        }

        return $found;
    }
}
