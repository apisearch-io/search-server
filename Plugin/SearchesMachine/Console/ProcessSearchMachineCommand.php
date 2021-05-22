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

namespace Apisearch\Plugin\SearchesMachine\Console;

use Apisearch\Plugin\SearchesMachine\Domain\Command\ProcessSearchesMachine;
use Apisearch\Server\Console\CommandWithCommandBusAndGodToken;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ProcessSearchMachineCommand.
 */
class ProcessSearchMachineCommand extends CommandWithCommandBusAndGodToken
{
    const NAME = 'apisearch-server:process-searches-machine';

    /**
     * @var string
     */
    protected static $defaultName = self::NAME;

    /**
     * Configures the current command.
     *
     * @return void
     */
    protected function configure()
    {
        $this->setDescription('Process searches machine from Redis ingestion');
    }

    /**
     * {@inheritdoc}
     */
    protected function runCommand(InputInterface $input, OutputInterface $output)
    {
        try {
            $command = new ProcessSearchesMachine();
            $this->executeAndWait($command);
            self::printMessage($output, 'Search Machine', "Processed and stored {$command->totalFlushed} words");
        } catch (\Throwable $throwable) {
            self::printMessageFail($output, 'Search Machine', $throwable->getMessage());
        }

        return 0;
    }
}
