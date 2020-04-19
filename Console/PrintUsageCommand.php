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

use Apisearch\Server\Domain\Query\GetUsage;
use DateTime;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class PrintAppUsageCommand.
 */
class PrintUsageCommand extends CommandWithQueryBusAndGodToken
{
    /**
     * @var string
     */
    protected static $defaultName = 'apisearch-server:print-usage';

    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this
            ->setDescription('Print usage')
            ->addArgument(
                'app-id',
                InputArgument::REQUIRED,
                'App id'
            )
            ->addOption(
                'index-id',
                null,
                InputOption::VALUE_OPTIONAL
            )
            ->addOption(
                'from',
                null,
                InputOption::VALUE_OPTIONAL
            )
            ->addOption(
                'to',
                null,
                InputOption::VALUE_OPTIONAL
            )
            ->addOption(
                'event',
                null,
                InputOption::VALUE_OPTIONAL
            )
            ->addOption(
                'per-day',
                null,
                InputOption::VALUE_NONE
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
        $from = $input->getOption('from');
        $from = $from ? DateTime::createFromFormat('U', $from) : new DateTime('first day of this month');
        $to = $input->getOption('to');
        $to = $to ? DateTime::createFromFormat('U', $to) : null;
        $perDay = $input->getOption('per-day');

        $usage = $this->askAndWait(new GetUsage(
            $objects['repository_reference'],
            $objects['token'],
            $from,
            $to,
            $input->getOption('event'),
            $perDay
        ));

        $table = new Table($output);
        if ($perDay) {
            $table->setHeaders(['Day', 'Event', 'Number of times']);
            foreach ($usage as $day => $data) {
                foreach ($data as $event => $numberOfTimes) {
                    $table->addRow([$day, $event, $numberOfTimes]);
                }
            }
        } else {
            $table->setHeaders(['Event', 'Number of times']);
            foreach ($usage as $event => $numberOfTimes) {
                $table->addRow([$event, $numberOfTimes]);
            }
        }
        $table->render();

        return;
    }

    /**
     * Dispatch domain event.
     *
     * @return string
     */
    protected static function getHeader(): string
    {
        return 'Get usage';
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
