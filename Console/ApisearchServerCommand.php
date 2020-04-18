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

use Apisearch\Command\ApisearchFormattedCommand;
use Apisearch\Model\AppUUID;
use Apisearch\Model\IndexUUID;
use Apisearch\Model\Token;
use Apisearch\Model\TokenUUID;
use Apisearch\Repository\RepositoryReference;
use React\EventLoop\LoopInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ApisearchServerCommand.
 */
abstract class ApisearchServerCommand extends ApisearchFormattedCommand
{
    /**
     * @var LoopInterface
     *
     * Event loop
     */
    protected $loop;

    /**
     * @var string
     *
     * God token
     */
    protected $godToken;

    /**
     * Controller constructor.
     *
     * @param LoopInterface $loop
     * @param string        $godToken
     */
    public function __construct(
        LoopInterface $loop,
        string $godToken
    ) {
        parent::__construct();

        $this->loop = $loop;
        $this->godToken = $godToken;
    }

    /**
     * Create token instance.
     *
     * @param TokenUUID $tokenUUID
     * @param AppUUID   $appUUID
     *
     * @return Token
     */
    protected function createToken(
        TokenUUID $tokenUUID,
        AppUUID $appUUID
    ): Token {
        return new Token(
            $tokenUUID,
            $appUUID
        );
    }

    /**
     * Create god token instance.
     *
     * @param AppUUID $appUUID
     *
     * @return Token
     */
    protected function createGodToken(AppUUID $appUUID): Token
    {
        return $this->createToken(
            TokenUUID::createById($this->godToken),
            $appUUID
        );
    }

    /**
     * Get app UUID and index UUID.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return array
     */
    protected function getAppIndexToken(
        InputInterface $input,
        OutputInterface $output
    ): array {
        $appUUID = AppUUID::createById($input->getArgument('app-id'));
        $indexUUID = null;
        $tokenUUID = TokenUUID::createById($this->godToken);

        self::printInfoMessage(
            $output,
            $this->getHeader(),
            "App ID: <strong>{$appUUID->composeUUID()}</strong>"
        );

        if ($input->hasArgument('index')) {
            $indexUUID = IndexUUID::createById($input->getArgument('index'));
            self::printInfoMessage(
                $output,
                $this->getHeader(),
                "Index UUID: <strong>{$indexUUID->composeUUID()}</strong>"
            );
        }

        if ($input->hasArgument('token')) {
            $tokenUUID = TokenUUID::createById($input->getArgument('token'));
            self::printInfoMessage(
                $output,
                $this->getHeader(),
                "Token UUID: <strong>{$tokenUUID->composeUUID()}</strong>"
            );
        }

        if (
            $input->hasOption('token') &&
            !empty($input->getOption('token'))
        ) {
            $tokenUUID = TokenUUID::createById($input->getOption('token'));
            self::printInfoMessage(
                $output,
                $this->getHeader(),
                "Token UUID: <strong>{$tokenUUID->composeUUID()}</strong>"
            );
        }

        return [
            'app_uuid' => $appUUID,
            'index_uuid' => $indexUUID,
            'token_uuid' => $tokenUUID,
            'token' => new Token($tokenUUID, $appUUID),
            'repository_reference' => $indexUUID instanceof IndexUUID
                ? RepositoryReference::create($appUUID, $indexUUID)
                : RepositoryReference::create($appUUID),
        ];
    }

    /**
     * Get app UUID and indices UUID.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return array
     */
    protected function getAppTokenAndIndices(
        InputInterface $input,
        OutputInterface $output
    ): array {
        $appUUID = AppUUID::createById($input->getArgument('app-id'));
        $tokenUUID = TokenUUID::createById($input->getArgument('uuid'));
        $indicesUUID = [];

        if ($input->hasOption('index')) {
            $indicesUUID = \array_map(function (string $index) {
                return IndexUUID::createById($index);
            }, $input->getOption('index'));
        }

        self::printInfoMessage(
            $output,
            $this->getHeader(),
            "App ID: <strong>{$appUUID->composeUUID()}</strong>"
        );

        self::printInfoMessage(
            $output,
            $this->getHeader(),
            "Token UUID: <strong>{$tokenUUID->composeUUID()}</strong>"
        );

        foreach ($indicesUUID as $indexUUID) {
            self::printInfoMessage(
                $output,
                $this->getHeader(),
                "Index UUID: <strong>{$indexUUID->composeUUID()}</strong>"
            );
        }

        return [
            'app_uuid' => $appUUID,
            'token_uuid' => $tokenUUID,
            'indices_uuid' => $indicesUUID,
            'token' => new Token($tokenUUID, $appUUID),
            'repository_reference' => RepositoryReference::create($appUUID),
        ];
    }
}
