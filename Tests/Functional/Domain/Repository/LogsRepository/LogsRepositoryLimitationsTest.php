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

namespace Apisearch\Server\Tests\Functional\Domain\Repository\LogsRepository;

use Apisearch\Model\AppUUID;
use Apisearch\Model\Token;
use Apisearch\Model\TokenUUID;
use Apisearch\Server\Domain\Repository\LogRepository\InMemoryLogRepository;
use Apisearch\Server\Domain\Repository\LogRepository\LogRepository;
use Apisearch\Server\Tests\Functional\ServiceFunctionalTest;

/**
 * Class LogsRepositoryLimitationsTest.
 */
class LogsRepositoryLimitationsTest extends ServiceFunctionalTest
{
    /**
     * Decorate configuration.
     *
     * @param array $configuration
     *
     * @return array
     */
    protected static function decorateConfiguration(array $configuration): array
    {
        parent::decorateConfiguration($configuration);
        $configuration['apisearch_server']['limitations']['number_of_logs_per_page'] = 2;
        $configuration['services'][LogRepository::class] = [
            'alias' => InMemoryLogRepository::class,
        ];

        return $configuration;
    }

    /**
     * Test pagination.
     *
     * @return void
     */
    public function testLimitedPagination(): void
    {
        static::putToken(new Token(TokenUUID::createById('aaa'), AppUUID::createById(self::$appId)));
        static::putToken(new Token(TokenUUID::createById('aaa'), AppUUID::createById(self::$appId)));
        static::putToken(new Token(TokenUUID::createById('aaa'), AppUUID::createById(self::$appId)));

        $this->assertCount(2, $this->getLogs(null, null, null, null, null, [], 0, 0));
        $this->assertCount(1, $this->getLogs(null, null, null, null, null, [], 1, 1));
        $this->assertCount(2, $this->getLogs(null, null, null, null, null, [], 5, 1));
        $this->assertCount(2, $this->getLogs(null, null, null, null, null, [], 5, 2));
        $this->assertCount(2, $this->getLogs(null, null, null, null, null, [], 10, 4));
        $this->assertCount(0, $this->getLogs(null, null, null, null, null, [], 10, 5));
    }
}
