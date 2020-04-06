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

use Apisearch\Command\PrintIndicesCommand as BasePrintIndicesCommand;
use Apisearch\Server\Domain\ImperativeEvent\LoadConfigs;
use Apisearch\Server\Domain\Query\GetIndices;
use Clue\React\Block;
use Drift\CommandBus\Bus\QueryBus;
use Drift\HttpKernel\AsyncEventDispatcherInterface;
use React\EventLoop\LoopInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class PrintTokensCommand.
 */
class PrintIndicesCommand extends CommandWithQueryBusAndGodToken
{
    /**
     * @var string
     */
    protected static $defaultName = 'apisearch-server:print-indices';

    /**
     * @var AsyncEventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * Controller constructor.
     *
     * @param QueryBus                      $queryBus
     * @param LoopInterface                 $loop
     * @param string                        $godToken
     * @param AsyncEventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        QueryBus $queryBus,
        LoopInterface $loop,
        string $godToken,
        AsyncEventDispatcherInterface $eventDispatcher
    ) {
        parent::__construct($queryBus, $loop, $godToken);

        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this
            ->setDescription('Print all indices')
            ->addArgument(
                'app-id',
                InputArgument::REQUIRED,
                'App id'
            )
            ->addOption(
                'with-fields',
                null,
                InputOption::VALUE_NONE,
                'Print the fields'
            )
            ->addOption(
                'with-metadata',
                null,
                InputOption::VALUE_NONE,
                'Print the metadata'
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
        Block\await($this
            ->eventDispatcher
            ->asyncDispatch(new LoadConfigs(
                $objects['app_uuid']
            )), $this->loop);

        $indices = $this->askQuery(new GetIndices(
            $objects['repository_reference'],
            $objects['token']
        ));

        BasePrintIndicesCommand::printIndices(
            $input,
            $output,
            $indices
        );

        return;
    }

    /**
     * Dispatch domain event.
     *
     * @return string
     */
    protected static function getHeader(): string
    {
        return 'Get indices';
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
