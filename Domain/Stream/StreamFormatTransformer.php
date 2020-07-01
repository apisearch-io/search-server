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

namespace Apisearch\Server\Domain\Stream;

use Apisearch\Model\Item;
use Apisearch\Server\Domain\Format\FormatTransformer;
use React\Stream\TransformerStream;
use React\Stream\WritableStreamInterface;

/**
 * Class ItemToArrayTransformerStream.
 */
final class StreamFormatTransformer extends TransformerStream
{
    /**
     * @var FormatTransformer
     */
    private $formatTransformer;

    /**
     * @param WritableStreamInterface $stream
     * @param FormatTransformer       $formatTransformer
     */
    public function __construct(
        WritableStreamInterface $stream,
        FormatTransformer $formatTransformer
    ) {
        parent::__construct($stream);
        $this->formatTransformer = $formatTransformer;
    }

    /**
     * @param Item $data
     */
    public function write($data)
    {
        if ($this->closed) {
            return false;
        }

        return $this->writeToOutput(
            $this
                ->formatTransformer
                ->itemToLine($data)."\n"
        );
    }
}
