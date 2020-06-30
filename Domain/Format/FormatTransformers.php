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

use Apisearch\Server\Exception\FormatterException;

/**
 * Class FormatTransformers.
 */
class FormatTransformers
{
    /**
     * @var FormatTransformer[]
     */
    private $formatTransformers = [];

    /**
     * Add format transformer.
     *
     * @param FormatTransformer $formatTransformer
     */
    public function addFormatTransformer(FormatTransformer $formatTransformer)
    {
        $this->formatTransformers[$formatTransformer->getName()] = $formatTransformer;
    }

    /**
     * @param string $name
     *
     * @return FormatTransformer
     *
     * @throws FormatterException
     */
    public function getFormatterByName(string $name): FormatTransformer
    {
        if (!\array_key_exists($name, $this->formatTransformers)) {
            throw new FormatterException(\sprintf('Format %s not found. Please, use one of defined formats: source, standard', $name));
        }

        return $this->formatTransformers[$name];
    }

    /**
     * Guess format transformer by headers.
     *
     * @param array $headers
     *
     * @return FormatTransformer|null
     */
    public function guessFormatTransformerByHeaders(array $headers): ? FormatTransformer
    {
        foreach ($this->formatTransformers as $formatTransformer) {
            if ($formatTransformer->belongsFromHeader($headers)) {
                return $formatTransformer;
            }
        }

        return null;
    }
}
