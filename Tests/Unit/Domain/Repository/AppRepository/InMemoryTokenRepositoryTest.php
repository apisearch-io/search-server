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

namespace Apisearch\Server\Tests\Unit\Domain\Repository\AppRepository;

use Apisearch\Model\AppUUID;
use Apisearch\Model\IndexUUID;
use Apisearch\Model\Token;
use Apisearch\Model\TokenUUID;
use Apisearch\Repository\RepositoryReference;
use Apisearch\Server\Domain\Repository\AppRepository\InMemoryTokenRepository;
use Apisearch\Server\Tests\Unit\BaseUnitTest;

/**
 * Class InMemoryTokenRepositoryTest.
 */
class InMemoryTokenRepositoryTest extends BaseUnitTest
{
    /**
     * Test add and remove token.
     */
    public function testAddRemoveToken()
    {
        $repository = new InMemoryTokenRepository();
        $appUUID = AppUUID::createById('yyy');
        $indexUUID = IndexUUID::createById('index');
        $repositoryReference = RepositoryReference::create(
            $appUUID,
            $indexUUID
        );
        $tokenUUID = TokenUUID::createById('xxx');
        $token = new Token($tokenUUID, $appUUID);
        $promise1 = $repository
            ->putToken($repositoryReference, $token)
            ->then(function () use ($repository) {
                return $repository->forceLoadAllTokens();
            })
            ->then(function () use ($repository, $appUUID, $tokenUUID) {
                return $repository->getTokenByUUID(
                    $appUUID,
                    $tokenUUID
                );
            })
            ->then(function (Token $foundToken) use ($token) {
                $this->assertEquals(
                    $token,
                    $foundToken
                );
            })
            ->then(function () use ($repository, $repositoryReference, $tokenUUID) {
                return $repository
                    ->deleteToken(
                        $repositoryReference,
                        $tokenUUID
                    );
            })
            ->then(function () use ($repository) {
                return $repository->forceLoadAllTokens();
            })
            ->then(function () use ($repository, $appUUID, $tokenUUID) {
                return $repository
                    ->getTokenByUUID(
                        $appUUID,
                        $tokenUUID
                    );
            })
            ->then(function ($token) {
                $this->assertNull($token);
            });

        $promise2 = $repository
            ->getTokenByUUID($appUUID, TokenUUID::createById('lll'))
            ->then(function ($null) {
                $this->assertNull($null);
            });

        $this->awaitAll([
            $promise1,
            $promise2,
        ]);
    }

    /**
     * Test delete tokens.
     */
    public function testDeleteTokens()
    {
        $repository = new InMemoryTokenRepository();
        $appUUID = AppUUID::createById('yyy');
        $indexUUID = IndexUUID::createById('index');

        $mainRepositoryReference = RepositoryReference::create(
            $appUUID,
            $indexUUID
        );

        $zzzRepositoryReference = RepositoryReference::create(
            AppUUID::createById('zzz'),
            $indexUUID
        );

        $tokenUUID = TokenUUID::createById('xxx');
        $token = new Token($tokenUUID, $appUUID);

        $promise = $repository
            ->putToken($mainRepositoryReference, $token)
            ->then(function () use ($repository) {
                return $repository->forceLoadAllTokens();
            })
            ->then(function () use ($appUUID, $mainRepositoryReference, $repository) {
                $tokenUUID2 = TokenUUID::createById('xxx2');
                $token2 = new Token($tokenUUID2, $appUUID);

                return $repository->putToken($mainRepositoryReference, $token2);
            })
            ->then(function () use ($repository) {
                return $repository->forceLoadAllTokens();
            })
            ->then(function () use ($indexUUID, $repository, $zzzRepositoryReference) {
                $tokenUUID3 = TokenUUID::createById('xxx3');
                $token3 = new Token($tokenUUID3, AppUUID::createById('zzz'));

                return $repository->putToken($zzzRepositoryReference, $token3);
            })
            ->then(function () use ($repository) {
                return $repository->forceLoadAllTokens();
            })
            ->then(function () use ($repository, $mainRepositoryReference) {
                return $repository->getTokens($mainRepositoryReference);
            })
            ->then(function (array $tokens) {
                $this->assertCount(2, $tokens);
            })
            ->then(function () use ($repository, $mainRepositoryReference) {
                $repository->deleteTokens($mainRepositoryReference);
            })
            ->then(function () use ($repository) {
                return $repository->forceLoadAllTokens();
            })
            ->then(function () use ($repository, $mainRepositoryReference) {
                return $repository->getTokens($mainRepositoryReference);
            })
            ->then(function (array $tokens) {
                $this->assertCount(0, $tokens);
            })
            ->then(function () use ($repository, $zzzRepositoryReference) {
                return $repository->getTokens($zzzRepositoryReference);
            })
            ->then(function (array $tokens) {
                $this->assertCount(1, $tokens);
            })
            ->then(function () use ($repository, $mainRepositoryReference) {
                $tokenUUID3 = TokenUUID::createById('xxx3');
                $repository->deleteToken($mainRepositoryReference, $tokenUUID3);
            })
            ->then(function () use ($repository) {
                return $repository->forceLoadAllTokens();
            })
            ->then(function () use ($repository, $zzzRepositoryReference) {
                return $repository->getTokens($zzzRepositoryReference);
            })
            ->then(function (array $tokens) {
                $this->assertCount(1, $tokens);
            })
            ->then(function () use ($repository, $zzzRepositoryReference) {
                $tokenUUID3 = TokenUUID::createById('xxx3');
                $repository->deleteToken($zzzRepositoryReference, $tokenUUID3);
            })
            ->then(function () use ($repository) {
                return $repository->forceLoadAllTokens();
            })
            ->then(function () use ($repository, $zzzRepositoryReference) {
                return $repository->getTokens($zzzRepositoryReference);
            })
            ->then(function (array $tokens) {
                $this->assertCount(0, $tokens);
            });

        $this->await($promise);
    }
}
