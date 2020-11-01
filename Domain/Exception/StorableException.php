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

namespace Apisearch\Server\Domain\Exception;

/**
 * Class StorableException.
 */
class StorableException
{
    /**
     * @var string
     *
     * Message
     */
    private $message;

    /**
     * @var int
     *
     * Code
     */
    private $code;

    /**
     * @var string
     *
     * Trace as string
     */
    private $traceAsString;

    /**
     * @var string
     *
     * File
     */
    private $file;

    /**
     * @var int
     *
     * Line
     */
    private $line;

    /**
     * StorableException constructor.
     *
     * @param string $message
     * @param int    $code
     * @param string $traceAsString
     * @param string $file
     * @param int    $line
     */
    public function __construct(
        string $message,
        int $code,
        string $traceAsString,
        string $file,
        int $line
    ) {
        $this->message = $message;
        $this->code = $code;
        $this->traceAsString = $traceAsString;
        $this->file = $file;
        $this->line = $line;
    }

    /**
     * Get message.
     *
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * Get code.
     *
     * @return int
     */
    public function getCode(): int
    {
        return $this->code;
    }

    /**
     * Get trace as string.
     *
     * @return string
     */
    public function getTraceAsString(): string
    {
        return $this->traceAsString;
    }

    /**
     * Get file.
     *
     * @return string
     */
    public function getFile(): string
    {
        return $this->file;
    }

    /**
     * Get line.
     *
     * @return mixed
     */
    public function getLine()
    {
        return $this->line;
    }
}
