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

use Apisearch\Model\Token;
use Apisearch\Server\Domain\Command\AddToken;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class AddTokenCommand.
 */
class AddTokenCommand extends CommandWithCommandBusAndGodToken
{
    /**
     * @var string
     */
    protected static $defaultName = 'apisearch-server:add-token';

    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this
            ->setName('apisearch:add-token')
            ->setDescription('Add a token')
            ->addArgument(
                'app-id',
                InputArgument::REQUIRED,
                'App id'
            )
            ->addArgument(
                'uuid',
                InputArgument::OPTIONAL,
                'Token UUID. If none defined, a new one will be generated',
                Uuid::uuid4()->toString()
            )
            ->addOption(
                'index',
                null,
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL,
                'Indices',
                []
            )
            ->addOption(
                'endpoint',
                null,
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL,
                'Endpoints',
                []
            )
            ->addOption(
                'plugin',
                null,
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL,
                'Plugins',
                []
            )
            ->addOption(
                'ttl',
                null,
                InputOption::VALUE_OPTIONAL,
                'TTL',
                Token::DEFAULT_TTL
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
        $objects = $this->getAppTokenAndIndices($input, $output);
        $endpoints = $this->getEndpoints($input);

        $this->executeCommand(new AddToken(
            $objects['repository_reference'],
            $this->createGodToken($objects['app_uuid']),
            new Token(
                $objects['token_uuid'],
                $objects['app_uuid'],
                $objects['indices_uuid'],
                $endpoints,
                $input->getOption('plugin'),
                (int) $input->getOption('ttl')
            )
        ));
    }

    /**
     * Dispatch domain event.
     *
     * @return string
     */
    protected static function getHeader(): string
    {
        return 'Add token';
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
        return 'Token added properly';
    }
}
