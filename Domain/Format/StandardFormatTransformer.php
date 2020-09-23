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

use Apisearch\Exception\InvalidFormatException;
use Apisearch\Model\Coordinate;
use Apisearch\Model\Item;
use Apisearch\Model\Metadata;

/**
 * Class StandardFormatTransformer.
 */
class StandardFormatTransformer extends FormatTransformer
{
    /**
     * @var string[]
     */
    const HEADERS = [
        'uid' => [],
        'type' => [],
        'title' => [['metadata', 'searchable_metadata'], ['strval']], // string
        'description' => [['metadata', 'searchable_metadata'], ['strval']], // string
        'extra_description' => [['metadata', 'searchable_metadata'], ['strval']], // string
        'link' => [['metadata'], ['strval']], // string
        'image' => [['metadata'], ['strval']], // string
        'brand' => [['metadata', 'searchable_metadata'], ['strval']], // string
        'keyword' => [], // string
        'suggest' => [], // string

        /*
         * Categories have not tree structure.
         * Instead of that, you can add as many categories as you want, telling
         * the ID (must), the name (optional, by default ID) and the level (optional, by default 1)
         *
         * 1~shoes~1
         * 34~shirt~2
         * shirt~2 => Default ID = shirt
         * shirt => Default level = 1
         */
        'categories' => [], // ID~cat~1 && ID2~cat2~2 ,,, custom processing
        'alternative_categories' => [], // ID~cat~1 && ID2~cat2~2 ,,, custom processing

        'reference' => [['indexed_metadata'], ['strval']], // string (can be ean13, isbn, mpn...)
        'alternative_reference' => [['indexed_metadata'], ['strval']], // string (can be ean13, isbn, mpn...)
        'price' => [['indexed_metadata'], ['intval']], // int
        'reduced_price' => [['indexed_metadata'], ['intval']], // int
        'reduced_price_percent' => [['indexed_metadata'], ['intval']], // int (0-100)
        'stock' => [['indexed_metadata'], ['intval']], // int
        'on_offer' => [['indexed_metadata'], ['boolval']], // bool

        'coordinate' => [],
        'attributes' => [],
    ];

    /**
     * Get headers.
     *
     * @return array
     */
    public function headers(): array
    {
        return \array_keys(self::HEADERS);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'standard';
    }

    /**
     * Given the header line, does this transformer belongs to it?
     *
     * @param array $headers
     *
     * @return bool
     */
    public function belongsFromHeader(array $headers): bool
    {
        return
            !\in_array('id', $headers) &&
            \in_array('title', $headers) &&
            \in_array('link', $headers) &&
            \in_array('uid', $headers);
    }

    /**
     * Item to line.
     *
     * @param Item $item
     *
     * @return string
     */
    public function itemToLine(Item $item): string
    {
        $coordinate = $item->getCoordinate();
        $fields = \array_combine(
            \array_keys(self::HEADERS),
            \array_fill(0, \count(self::HEADERS), null)
        );
        $fields['uid'] = $item->getUUID()->getId();
        $fields['type'] = $this->escapeSpecialChars($item->getUUID()->getType());
        $fields['coordinate'] = $coordinate instanceof Coordinate
            ? $coordinate->getLatitude().','.$coordinate->getLongitude()
            : null;

        $fields['keyword'] = $this->formatArrayOfValuesToLine($item->getExactMatchingMetadata());
        $fields['suggest'] = $this->formatArrayOfValuesToLine($item->getSuggest());
        $attributes = [];

        $this->addItemFieldsToArray($fields, $attributes, $item->getMetadata(), 'm');
        $this->addItemFieldsToArray($fields, $attributes, $item->getIndexedMetadata(), 'i');
        $this->addItemFieldsToArray($fields, $attributes, $item->getSearchableMetadata(), 's');

        $attributes = \array_map(function (array $parts) {
            $modifier = empty($parts['m']) || 'm' === $parts['m']
                ? ''
                : "[{$parts['m']}]";

            return "$modifier{$parts['f']}={$parts['v']}";
        }, $attributes);

        $fields['attributes'] = \implode(' %% ', $attributes);

        return \implode(static::getLineSeparator(), $fields);
    }

    /**
     * Array to Item.
     *
     * @param string[] $headers
     * @param array    $fields
     *
     * @return Item
     *
     * @throws InvalidFormatException
     */
    public function arrayToItem(
        array $headers,
        array $fields
    ): Item {
        $values = \array_combine($headers, $fields);
        $coordinate = null;
        if (
            \array_key_exists('coordinate', $values) &&
            !empty($values['coordinate'])
        ) {
            $coordinateParts = \explode(',', $values['coordinate']);
            if (2 === \count($coordinateParts)) {
                $coordinate = [
                    'lat' => \floatval($coordinateParts[0]),
                    'lon' => \floatval($coordinateParts[1]),
                ];
            }
        }

        $itemAsArray = [
            'uuid' => [
                'id' => $values['uid'],
                'type' => $values['type'],
            ],
            'metadata' => [],
            'indexed_metadata' => [],
            'searchable_metadata' => [],
            'exact_matching_metadata' => $this->formatLinetoArrayOfValues($values['keyword'] ?? ''),
            'suggest' => $this->formatLinetoArrayOfValues($values['suggest'] ?? ''),
            'coordinate' => $coordinate,
        ];

        foreach ($values as $field => $value) {
            $value = \str_replace('\\'.static::getLineSeparator(), static::getLineSeparator(), $value);
            $this->addItemFieldFromArray($itemAsArray, $field, $value);
        }

        $itemAsArray['exact_matching_metadata'] = \array_values($itemAsArray['exact_matching_metadata']);

        return Item::createFromArray($itemAsArray);
    }

    /**
     * Add fields from array.
     *
     * @param array  $fields
     * @param array  $attributes
     * @param array  $array
     * @param string $modifiers
     */
    private function addItemFieldsToArray(
        array &$fields,
        array &$attributes,
        array $array,
        string $modifiers
    ) {
        $headers = \array_keys($fields);
        foreach ($array as $field => $value) {
            $fieldModifiers = $modifiers;
            $value = $this->escapeSpecialChars($value);

            if (\in_array($field, ['price', 'reduced_price', 'reduced_price_percent', 'stock']) && (0 === $value)) {
                $value = '';
            }

            if (\in_array($field, $headers)) {
                if (\is_array($value) && \in_array($field, ['categories', 'alternative_categories'])) {
                    $fields[$field] = $this->generateCategoryLine($value);
                    continue;
                }

                if (!\is_array($value)) {
                    $fields[$field] = (string) $value;
                    continue;
                }
            }

            if (\is_array($value)) {
                $fieldModifiers .= 'j';
                $value = \json_encode($value, JSON_UNESCAPED_UNICODE);
            }

            if (\array_key_exists($field, $attributes)) {
                $attributes[$field]['m'] .= $fieldModifiers;
            } else {
                $attributes[$field] = [
                    'm' => $fieldModifiers,
                    'f' => $field,
                    'v' => $value,
                ];
            }
        }
    }

    /**
     * @param array  $itemAsArray
     * @param string $field
     * @param string $value
     */
    private function addItemFieldFromArray(
        array &$itemAsArray,
        string $field,
        string $value
    ) {
        if (\in_array($field, ['uid', 'type', 'coordinate', 'keyword', 'suggest'])) {
            return;
        }

        if (\in_array($field, ['categories', 'alternative_categories'])) {
            $structuredLine = $this->generateFromStructuredLine($value);
            $structuredLine = $this->unescapeSpecialChars($structuredLine);
            $itemAsArray['indexed_metadata'][$field] = $structuredLine;

            return;
        }

        if (
            \array_key_exists($field, self::HEADERS) &&
            !empty(self::HEADERS[$field][1])
        ) {
            foreach (self::HEADERS[$field][1] as $action) {
                $value = $action($value);
            }
        }

        if (
            \array_key_exists($field, self::HEADERS) &&
            !empty(self::HEADERS[$field][0])
        ) {
            foreach (self::HEADERS[$field][0] as $type) {
                $itemAsArray[$type][$field] = $this->unescapeSpecialChars($value);
            }

            return;
        }

        // This is attributes
        $attributeParts = \preg_split('~(?<!\\\)'.\preg_quote('%%', '~').'~', $value);

        if (
            1 == \count($attributeParts) &&
            empty($attributeParts[0])
        ) {
            return;
        }

        foreach ($attributeParts as $attributeValue) {
            $attributeValue = \trim($attributeValue);
            $attributeValue = $this->unescapeSpecialChars($attributeValue);
            $parts = \explode('=', $attributeValue, 2);
            $attributeField = $parts[0];
            $attributeValue = $parts[1];
            $modifiers = '';

            if (\preg_match('~\[[a-z]*\]~', $attributeField)) {
                list($modifiers, $attributeField) = \explode(']', $attributeField, 2);
                $modifiers = \ltrim($modifiers, '[');
            }

            if (empty($modifiers)) {
                $itemAsArray['metadata'][$attributeField] = $attributeValue;

                continue;
            }

            if (false !== \strpos($modifiers, 'n')) {
                $attributeValue = \intval($attributeValue);
            }

            if (false !== \strpos($modifiers, 'f')) {
                $attributeValue = \floatval($attributeValue);
            }

            if (false !== \strpos($modifiers, 'j')) {
                $attributeValue = \json_decode($attributeValue, true);
            }

            if (false !== \strpos($modifiers, 'm')) {
                $itemAsArray['metadata'][$attributeField] = $attributeValue;
            }

            if (false !== \strpos($modifiers, 'i')) {
                // we should check if is a complex structure

                $itemAsArray['indexed_metadata'][$attributeField] = (\is_string($attributeValue) && false !== \strpos($attributeValue, 'id##'))
                    ? $this->generateFromStructuredLine($attributeValue)
                    : $attributeValue;
            }

            if (false !== \strpos($modifiers, 's')) {
                $itemAsArray['searchable_metadata'][$attributeField] = $attributeValue;
            }
        }
    }

    /**
     * Generate category line.
     *
     * @param array $categories
     *
     * @return string
     */
    private function generateCategoryLine(array $categories): string
    {
        $lines = \array_map(function ($category) {
            if (
                !isset($category['id']) ||
                !isset($category['level'])
            ) {
                return false;
            }

            if (!isset($category['name'])) {
                $category['name'] = $category['id'];
            }

            return Metadata::toMetadata($category);
        }, $categories);

        $lines = \array_filter($lines, function ($line) {
            return !empty($line);
        });

        return \implode(' && ', $lines);
    }

    /**
     * Generate from structured line.
     *
     * @param string $structuredLine
     *
     * @return array
     */
    private function generateFromStructuredLine(string $structuredLine): array
    {
        $lines = \preg_split('~(?<!\\\)'.\preg_quote('&&', '~').'~', $structuredLine);

        return \array_map(function (string $line) {
            $line = \trim($line);

            return Metadata::fromMetadata($line);
        }, $lines);
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

        $value = \str_replace('&&', '\&&', $value);
        $value = \str_replace('%%', '\%%', $value);
        $value = \str_replace('|', '\|', $value);

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

        $value = \str_replace('\&&', '&&', $value);
        $value = \str_replace('\%%', '%%', $value);

        return $value;
    }

    /**
     * @param array $array
     *
     * @return string
     */
    private function formatArrayOfValuesToLine(array $array): string
    {
        $formatted = \array_map('strval', $array);
        $escaped = \array_map([$this, 'escapeSpecialChars'], $formatted);

        return \implode(' && ', $escaped);
    }

    /**
     * @param string $string
     *
     * @return array
     */
    private function formatLinetoArrayOfValues(string $string): array
    {
        $unescaped = $this->unescapeSpecialChars($string);

        return \explode(' && ', $unescaped);
    }
}
