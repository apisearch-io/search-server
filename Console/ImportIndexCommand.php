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

use Apisearch\Command\ImportIndexCommand as BaseImportIndexCommand;
use Apisearch\Server\Domain\Command\IndexItems;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * ImportIndexCommand.
 */
class ImportIndexCommand extends CommandWithBusAndGodToken
{
    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this
            ->setDescription('Import items from a file to your index')
            ->addArgument(
                'app-id',
                InputArgument::REQUIRED,
                'App id'
            )
            ->addArgument(
                'index',
                InputArgument::REQUIRED,
                'Index name'
            )
            ->addArgument(
                'file',
                InputArgument::REQUIRED,
                'File'
            );
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
        $objects = $this->getAppIndexToken($input, $output);
        $file = $input->getArgument('file');

        return BaseImportIndexCommand::importFromFile(
            $file,
            $output,
            function (array $items, bool $lastIteration) use ($objects) {
                $this->handleSynchronously(new IndexItems(
                    $objects['repository_reference'],
                    $objects['token'],
                    $items
                ));
            }
        );
    }

    /**
     * Dispatch domain event.
     *
     * @return string
     */
    protected static function getHeader(): string
    {
        return 'Import index';
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
        return sprintf('Imported %d items into index', $result);
    }
}
