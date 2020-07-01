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
use Apisearch\Model\Item;

/**
 * Class FormatTransformer.
 */
abstract class FormatTransformer
{
    /**
     * @return string
     */
    public static function getLineSeparator(): string
    {
        return '|';
    }

    /**
     * @return string
     */
    public function getHeaderLine(): string
    {
        return \implode(static::getLineSeparator(), $this->headers());
    }

    /**
     * Get headers.
     *
     * @return array
     */
    abstract public function headers(): array;

    /**
     * @return string
     */
    abstract public function getName(): string;

    /**
     * @param string[] $headers
     * @param string   $line
     *
     * @return Item
     *
     * @throws InvalidFormatException
     */
    public function lineToItem(
        array $headers,
        string $line
    ): Item {
        return $this->arrayToItem(
            $headers,
            \preg_split('~(?<!\\\)'.\preg_quote(static::getLineSeparator(), '~').'~', $line)
        );
    }

    /**
     * @param string[] $headers
     * @param array    $fields
     *
     * @return Item
     *
     * @throws InvalidFormatException
     */
    abstract public function arrayToItem(
        array $headers,
        array $fields
    ): Item;

    /**
     * @param Item $item
     *
     * @return string
     */
    abstract public function itemToLine(Item $item): string;

    /**
     * @param array $headers
     *
     * @return bool
     */
    abstract public function belongsFromHeader(array $headers): bool;
}
