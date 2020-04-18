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

use Apisearch\Command\QueryCommand as BaseQueryCommand;
use Apisearch\Query\Query as ModelQuery;
use Apisearch\Server\Domain\Query\Query;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class QueryCommand.
 */
class QueryCommand extends CommandWithQueryBusAndGodToken
{
    /**
     * @var string
     */
    protected static $defaultName = 'apisearch-server:query';

    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this
            ->setDescription('Query an index')
            ->addArgument(
                'app-id',
                InputArgument::REQUIRED,
                'App id'
            )
            ->addArgument(
                'index',
                InputArgument::REQUIRED,
                'Index'
            )
            ->addArgument(
                'query',
                InputArgument::OPTIONAL,
                'Query text',
                ''
            )
            ->addOption(
                'page',
                null,
                InputOption::VALUE_OPTIONAL,
                'Page',
                ModelQuery::DEFAULT_PAGE
            )
            ->addOption(
                'size',
                null,
                InputOption::VALUE_OPTIONAL,
                'Number of results',
                ModelQuery::DEFAULT_SIZE
            )
            ->addOption(
                'parameter',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Query parameters',
                []
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
        $parameters = $input->getOption('parameter');

        BaseQueryCommand::makeQueryAndPrintResults(
            $input,
            $output,
            function (ModelQuery $query) use ($objects, $parameters) {
                return $this->askAndWait(new Query(
                    $objects['repository_reference'],
                    $objects['token'],
                    $query,
                    $parameters
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
        return 'Query index';
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
