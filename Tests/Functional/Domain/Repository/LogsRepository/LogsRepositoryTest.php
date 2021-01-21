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

use Apisearch\Config\Config;
use Apisearch\Model\AppUUID;
use Apisearch\Model\Token;
use Apisearch\Model\TokenUUID;
use Apisearch\Model\User;
use Apisearch\Query\Query;
use Apisearch\Server\Domain\ImperativeEvent\FlushLogs;
use Apisearch\Server\Domain\Model\Origin;
use Apisearch\Server\Domain\Repository\LogRepository\InMemoryLogRepository;
use Apisearch\Server\Domain\Repository\LogRepository\LogMapper;
use Apisearch\Server\Domain\Repository\LogRepository\LogRepository;
use Exception;

/**
 * Trait LogsRepositoryTest.
 */
trait LogsRepositoryTest
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
        $configuration = parent::decorateConfiguration($configuration);
        $configuration['services'][LogRepository::class] = [
            'alias' => InMemoryLogRepository::class,
        ];

        return $configuration;
    }

    /**
     * test logs.
     *
     * @return void
     */
    public function testLogs(): void
    {
        $this->query(Query::create('Code da vinci')->byUser(new User('u1')), null, null, null, [], new Origin('', '', Origin::TABLET));
        $this->query(Query::create('Matutano'), null, null, null, [], Origin::createEmpty());
        static::putToken(new Token(TokenUUID::createById('aaa'), AppUUID::createById(self::$appId)));
        static::deleteToken(TokenUUID::createById('aaa'));
        $this->configureIndex(Config::createEmpty(), false, self::$appId, self::$anotherIndex);

        try {
            static::deleteIndex(self::$anotherInexistentAppId, self::$yetAnotherIndex);
        } catch (Exception $e) {
            // Silent pass
        }

        self::usleep(100000);
        $this->dispatchImperative(new FlushLogs());
        self::usleep(100000);

        $this->assertCount(8, $this->getLogs());
        $this->assertCount(3, $this->getLogs(self::$anotherAppId));
        $this->assertCount(3, $this->getLogs(self::$appId, null, static::$anotherIndex));
        $this->assertCount(2, $this->getLogs(self::$appId, null, static::$index));

        $logs = $this->getLogs();
        $this->assertContains('not found', $logs[0]['text']);
        $this->assertEquals('404', $logs[0]['code']);

        $this->assertContains('not found', $logs[1]['text']);
        $this->assertContains('404', $logs[1]['code']);

        $this->assertContains('was created', $logs[2]['text']);
        $this->assertContains(self::$index, $logs[2]['text']);
        $this->assertContains('200', $logs[2]['code']);

        $this->assertContains('was created', $logs[3]['text']);
        $this->assertContains(self::$anotherIndex, $logs[3]['text']);
        $this->assertContains('200', $logs[3]['code']);

        $this->assertContains('were deleted', $logs[4]['text']);
        $this->assertContains('tokens', $logs[4]['text']);
        $this->assertContains('200', $logs[4]['code']);

        $this->assertContains('was created', $logs[5]['text']);
        $this->assertContains('token ', $logs[5]['text']);
        $this->assertContains('200', $logs[5]['code']);

        $this->assertContains('was deleted', $logs[6]['text']);
        $this->assertContains('token ', $logs[6]['text']);
        $this->assertContains('200', $logs[6]['code']);

        $this->assertContains('was configured', $logs[7]['text']);
        $this->assertContains(self::$anotherIndex, $logs[7]['text']);
        $this->assertContains('200', $logs[7]['code']);
    }

    /**
     * Test type filter.
     *
     * @return void
     */
    public function testTypeFilter(): void
    {
        $this->assertCount(2, $this->getLogs(null, null, null, null, null, [LogMapper::EXCEPTION_WAS_CACHED]));
        $this->assertCount(3, $this->getLogs(null, null, null, null, null, [LogMapper::EXCEPTION_WAS_CACHED, LogMapper::TOKENS_WERE_DELETED]));
    }

    /**
     * Test pagination.
     *
     * @return void
     */
    public function testPagination(): void
    {
        $this->assertCount(8, $this->getLogs(null, null, null, null, null, [], 0, 0));
        $this->assertCount(1, $this->getLogs(null, null, null, null, null, [], 1, 1));
        $this->assertCount(5, $this->getLogs(null, null, null, null, null, [], 5, 1));
        $this->assertCount(3, $this->getLogs(null, null, null, null, null, [], 5, 2));
        $this->assertCount(2, $this->getLogs(null, null, null, null, null, [], 3, 3));
    }
}
