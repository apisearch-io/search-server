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

namespace Apisearch\Server\Domain\Format;

use Apisearch\Model\Coordinate;
use Apisearch\Model\Item;
use Apisearch\Model\ItemUUID;

/**
 * Class SourceFormatTransformer.
 */
class SourceFormatTransformer extends FormatTransformer
{
    /**
     * Get headers.
     *
     * @return array
     */
    public function headers(): array
    {
        return [
            'uuid',
            'metadata',
            'indexed_metadata',
            'searchable_metadata',
            'exact_matching_metadata',
            'suggest',
            'coordinate',
        ];
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'source';
    }

    /**
     * @param array $headers
     * @param array $fields
     *
     * @return Item
     */
    public function arrayToItem(array $headers, array $fields): Item
    {
        $values = \array_combine($headers, $fields);
        $coordinate = \json_decode($values['coordinate'] ?? '{}', true);
        $coordinate = !empty($coordinate) ? $coordinate : null;

        return Item::createFromArray([
            'uuid' => ItemUUID::createByComposedUUID(
                $this->unescapeSpecialChars($values['uuid'])
            )->toArray(),
            'metadata' => $this->unescapeSpecialChars(\json_decode($values['metadata'] ?? '{}', true)),
            'indexed_metadata' => $this->unescapeSpecialChars(\json_decode($values['indexed_metadata'] ?? '{}', true)),
            'searchable_metadata' => $this->unescapeSpecialChars(\json_decode($values['searchable_metadata'] ?? '{}', true)),
            'exact_matching_metadata' => $this->unescapeSpecialChars(\json_decode($values['exact_matching_metadata'] ?? '{}', true)),
            'suggest' => $this->unescapeSpecialChars(\json_decode($values['suggest'] ?? '{}', true)),
            'coordinate' => $coordinate,
        ]);
    }

    /**
     * @param Item $item
     *
     * @return string
     */
    public function itemToLine(Item $item): string
    {
        return \implode(static::getLineSeparator(), [
            $this->escapeSpecialChars($item->getUUID()->composeUUID()),
            \json_encode($this->escapeSpecialChars($this->formatValue($item->getMetadata())), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            \json_encode($this->escapeSpecialChars($this->formatValue($item->getIndexedMetadata())), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            \json_encode($this->escapeSpecialChars($this->formatValue($item->getSearchableMetadata())), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            \json_encode($this->escapeSpecialChars($item->getExactMatchingMetadata()), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            \json_encode($this->escapeSpecialChars($item->getSuggest()), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            ($item->getCoordinate() instanceof Coordinate
                ? \json_encode($item->getCoordinate()->toArray())
                : null),
        ]);
    }

    /**
     * @param array $headers
     *
     * @return bool
     */
    public function belongsFromHeader(array $headers): bool
    {
        return
            \in_array('uuid', $headers) &&
            \in_array('metadata', $headers) &&
            \in_array('indexed_metadata', $headers);
    }

    /**
     * Escape strange chars.
     *
     * @param string|array $value
     *
     * @return array|string
     */
    private function escapeSpecialChars($value)
    {
        if (\is_array($value)) {
            return \array_map(function ($element) {
                return $this->escapeSpecialChars($element);
            }, $value);
        }

        if (!\is_string($value)) {
            return $value;
        }

        $value = \str_replace(static::getLineSeparator(), '\\'.static::getLineSeparator(), $value);

        return $value;
    }

    /**
     * Unescape strange chars.
     *
     * @param string|array $value
     *
     * @return string|array
     */
    private function unescapeSpecialChars($value)
    {
        if (\is_array($value)) {
            return \array_map(function ($element) {
                return $this->unescapeSpecialChars($element);
            }, $value);
        }

        if (!\is_string($value)) {
            return $value;
        }

        $value = \str_replace('\\'.static::getLineSeparator(), static::getLineSeparator(), $value);

        return $value;
    }

    /**
     * @param array|string $value
     *
     * @return mixed
     */
    private function formatValue($value)
    {
        if (\is_array($value)) {
            foreach ($value as $key => $element) {
                $formattedValue = $this->formatValue($element);

                if (
                    \is_string($formattedValue) &&
                    '' === $formattedValue
                ) {
                    unset($value[$key]);
                    continue;
                }

                $value[$key] = $formattedValue;
            }
        }

        return $value;
    }
}
