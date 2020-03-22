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

use Apisearch\Http\Endpoints;
use Apisearch\Model\AppUUID;
use Apisearch\Model\Token;
use Apisearch\Model\TokenUUID;
use Apisearch\Repository\RepositoryReference;
use Apisearch\Server\Domain\Command\PutToken;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class GenerateBasicTokensCommand.
 */
class GenerateBasicTokensCommand extends CommandWithCommandBusAndGodToken
{
    /**
     * @var string
     */
    protected static $defaultName = 'apisearch-server:generate-basic-tokens';

    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this
            ->setDescription('Generate a basic tokens ring')
            ->addArgument(
                'app-id',
                InputArgument::REQUIRED,
                'App id'
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

        foreach ([
            'admin' => [],
            'query only' => Endpoints::queryOnly(),
            'interaction' => Endpoints::interactionOnly(),
                 ] as $tokenName => $endpoints) {
            $this->generateToken(
                $objects['app_uuid'],
                $endpoints,
                $tokenName,
                $objects['token'],
                $output
            );
        }
    }

    /**
     * Generate token.
     *
     * @param AppUUID         $appUUID
     * @param string[]        $endpoints
     * @param string          $name
     * @param Token           $godToken
     * @param OutputInterface $output
     */
    protected function generateToken(
        AppUUID $appUUID,
        array $endpoints,
        string $name,
        Token $godToken,
        OutputInterface $output
    ) {
        $tokenId = Uuid::uuid4()->toString();

        $this->executeCommand(new PutToken(
            RepositoryReference::create($appUUID),
            $godToken,
            new Token(
                TokenUUID::createById($tokenId),
                $appUUID,
                [],
                $endpoints
            )
        ));

        $this->printInfoMessage(
            $output,
            $this->getHeader(),
            "Token with UUID <strong>$tokenId</strong> generated for $name"
        );
    }

    /**
     * Dispatch domain event.
     *
     * @return string
     */
    protected static function getHeader(): string
    {
        return 'Create basic tokens';
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
        return 'Tokens created properly';
    }
}
