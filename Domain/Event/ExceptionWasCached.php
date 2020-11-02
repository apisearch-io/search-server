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

namespace Apisearch\Server\Domain\Event;

use Apisearch\Server\Domain\Exception\StorableException;

/**
 * Class ExceptionWasCached.
 */
final class ExceptionWasCached extends DomainEvent
{
    /**
     * @var StorableException
     *
     * Exception
     */
    private $exception;

    /**
     * ExceptionWasCached constructor.
     *
     * @param StorableException $exception
     */
    public function __construct(StorableException $exception)
    {
        parent::__construct();
        $this->exception = $exception;
    }

    /**
     * @return StorableException
     */
    public function getException(): StorableException
    {
        return $this->exception;
    }

    /**
     * to array payload.
     *
     * @return array
     */
    public function toArrayPayload(): array
    {
        return [
            'exception' => [
                'message' => $this->exception->getMessage(),
                'code' => $this->exception->getCode(),
                'trace_as_string' => $this->exception->getTraceAsString(),
                'file' => $this->exception->getFile(),
                'line' => $this->exception->getLine(),
            ],
        ];
    }
}
