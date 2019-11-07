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

namespace Apisearch\Server\Console;

use Apisearch\Server\Domain\Query\CheckHealth;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CheckHealthCommand.
 */
class CheckHealthCommand extends CommandWithBusAndGodToken
{
    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this->setDescription('Check health');
    }

    /**
     * Dispatch domain event.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return mixed|null
     */
    protected function runCommand(InputInterface $input, OutputInterface $output)
    {
        $health = $this->handleSynchronously(new CheckHealth());

        $this->printInfoMessage($output, 'Memory Used', $health['process']['memory_used']);
        $this->printInfoMessage($output, 'Plugins', implode(', ', array_keys($health['info']['plugins'])));
        foreach ($health['status'] as $key => $value) {
            $this->printInfoMessage($output, ucfirst($key), $this->getStringRepresentationOfValue($value));
        }

        return true === $health['healthy']
            ? 0
            : 1;
    }

    /**
     * Get string value of mixed.
     *
     * @param mixed $value
     *
     * @return string
     */
    private function getStringRepresentationOfValue($value): string
    {
        switch ($value) {
            case is_bool($value):
                return $value ? 'Ok' : 'Error';
            case is_string($value):
                switch ($value) {
                    case '1':
                        return 'Ok';
                    case '0':
                        return 'Error';
                    default:
                        return $value;
                }
        }

        return (string) $value;
    }

    /**
     * Dispatch domain event.
     *
     * @return string
     */
    protected static function getHeader(): string
    {
        return 'Check health';
    }

    /**
     * Get success message.
     *
     * @param InputInterface $input
     * @param mixed          $result
     *
     * @return string
     */
    protected static function getSuccessMessage(
        InputInterface $input,
        $result
    ): string {
        return '';
    }
}
