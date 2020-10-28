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

namespace Apisearch\Plugin\DBAL\Domain\AppRepository;

use Apisearch\Model\AppUUID;
use Apisearch\Model\IndexUUID;
use Apisearch\Model\Token;
use Apisearch\Model\TokenUUID;
use Apisearch\Plugin\DBAL\Domain\Encrypter\Encrypter;
use Apisearch\Repository\RepositoryReference;
use Apisearch\Server\Domain\Repository\AppRepository\TokenRepository;
use Drift\DBAL\Connection;
use React\Promise\PromiseInterface;

/**
 * Class DBALTokenRepository.
 */
final class DBALTokenRepository extends TokenRepository
{
    private Connection $connection;
    private Encrypter $encrypter;
    private string $table;
    private bool $locatorEnabled;

    /**
     * @param Connection $connection
     * @param Encrypter  $encrypter
     * @param string     $tokensTable
     * @param bool       $locatorEnabled
     */
    public function __construct(
        Connection $connection,
        Encrypter $encrypter,
        string $tokensTable,
        bool $locatorEnabled
    ) {
        $this->connection = $connection;
        $this->encrypter = $encrypter;
        $this->table = $tokensTable;
        $this->locatorEnabled = $locatorEnabled;
    }

    /**
     * Add token.
     *
     * @param RepositoryReference $repositoryReference
     * @param Token               $token
     *
     * @return PromiseInterface
     */
    public function putToken(
        RepositoryReference $repositoryReference,
        Token $token
    ): PromiseInterface {
        $tokenUUIDComposed = $token
            ->getTokenUUID()
            ->composeUUID();

        return $this
            ->connection
            ->upsert(
                $this->table,
                ['token_uuid' => $this->encrypter->encrypt($tokenUUIDComposed)],
                [
                    'app_uuid' => $repositoryReference->getAppUUID()->composeUUID(),
                    'content' => $this->encrypter->encrypt(\json_encode($this->tokenContentToArray(
                        $token
                    ))),
                ]
            );
    }

    /**
     * Delete token.
     *
     * @param RepositoryReference $repositoryReference
     * @param TokenUUID           $tokenUUID
     *
     * @return PromiseInterface
     */
    public function deleteToken(
        RepositoryReference $repositoryReference,
        TokenUUID $tokenUUID
    ): PromiseInterface {
        return $this
            ->connection
            ->delete($this->table, [
                'app_uuid' => $repositoryReference->getAppUUID()->composeUUID(),
                'token_uuid' => $this->encrypter->encrypt($tokenUUID->composeUUID()),
            ]);
    }

    /**
     * Delete all tokens.
     *
     * @param RepositoryReference $repositoryReference
     *
     * @return PromiseInterface
     */
    public function deleteTokens(RepositoryReference $repositoryReference): PromiseInterface
    {
        return $this
            ->connection
            ->delete($this->table, [
                'app_uuid' => $repositoryReference->getAppUUID()->composeUUID(),
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function findAllTokens(): PromiseInterface
    {
        return $this
            ->connection
            ->findBy($this->table)
            ->then(function ($results) {
                return \array_map(function ($result) {
                    return $this->getTokenFromContentArray(
                        TokenUUID::createById($this->encrypter->decrypt($result['token_uuid'])),
                        AppUUID::createById($result['app_uuid']),
                        \json_decode($this->encrypter->decrypt($result['content']), true)
                    );
                }, $results);
            });
    }

    /**
     * Token content to array.
     *
     * @param Token $token
     *
     * @return array
     */
    private function tokenContentToArray(Token $token): array
    {
        return [
            'i' => \array_map(function (IndexUUID $indexUUID) {
                return $indexUUID->composeUUID();
            }, $token->getIndices()),
            'e' => $token->getEndpoints(),
            'p' => $token->getPlugins(),
            't' => $token->getTtl(),
            'm' => $token->getMetadata(),
        ];
    }

    /**
     * Array to token content.
     *
     * @param TokenUUID $tokenUUID
     * @param AppUUID   $appUUID
     * @param array     $content
     *
     * @return Token
     */
    private function getTokenFromContentArray(
        TokenUUID $tokenUUID,
        AppUUID $appUUID,
        array $content
    ): Token {
        return new Token(
            $tokenUUID,
            $appUUID,
            \array_map(function (string $indexUUIDComposed) {
                return IndexUUID::createById($indexUUIDComposed);
            }, $content['i']),
            $content['e'],
            $content['p'],
            $content['t'],
            $content['m']
        );
    }

    /**
     * Locator is enabled.
     *
     * @return bool
     */
    public function isValid(): bool
    {
        return $this->locatorEnabled;
    }
}
