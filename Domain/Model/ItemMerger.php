<?php


namespace Apisearch\Server\Domain\Model;

/**
 * Class ItemMerger
 */
class ItemMerger
{
    /**
     * Merge queries.
     *
     * @param array  $baseItem
     * @param array  $itemToMerge
     *
     * @return array
     */
    public static function mergeItems(
        array $baseItem,
        array $itemToMerge
    ): array {
        return $baseItem;
    }
}
