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

namespace Apisearch\Server\Domain\Repository\LogRepository;

/**
 * Class LogWithText.
 */
final class LogWithText
{
    private Log $log;
    private string $text;

    /**
     * @param Log    $log
     * @param string $text
     */
    public function __construct(Log $log, string $text)
    {
        $this->log = $log;
        $this->text = $text;
    }

    /**
     * @param Log $log
     *
     * @return $this
     */
    public static function createFromLog(Log $log): self
    {
        return new self($log, LogMapper::getLogText($log));
    }

    /**
     * @return Log
     */
    public function getLog(): Log
    {
        return $this->log;
    }

    /**
     * @return string
     */
    public function getText(): string
    {
        return $this->text;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $log = $this->log;

        return [
            'app_uuid' => $log->getAppUUID(),
            'index_uuid' => $log->getIndexUUID(),
            'n' => $log->getN(),
            'when' => $log->getWhen()->format('U'),
            'text' => $this->getText(),
            'code' => $log->getParam('code') ?? '200',
        ];
    }
}
