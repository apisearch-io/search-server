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

namespace Apisearch\Server\Domain\Repository\LogRepository;

use Apisearch\Model\TokenUUID;
use Apisearch\Server\Domain\Exception\StorableException;

/**
 * Class LogMapper.
 */
class LogMapper
{
    const INDEX_WAS_CREATED = 'index_created';
    const INDEX_WAS_DELETED = 'index_deleted';
    const INDEX_WAS_CONFIGURED = 'index_configured';
    const INDEX_WAS_RESET = 'index_reset';
    const INDEX_WAS_IMPORTED = 'index_imported';
    const INDEX_WAS_EXPORTED = 'index_exported';

    const TOKEN_WAS_PUT = 'token_put';
    const TOKEN_WAS_DELETED = 'token_deleted';
    const TOKENS_WERE_DELETED = 'tokens_deleted';

    const EXCEPTION_WAS_CACHED = 'exception';

    /**
     * @param Log $log
     *
     * @return string
     */
    public static function getLogText(Log $log): string
    {
        if (self::INDEX_WAS_CREATED === $log->getType()) {
            return \sprintf('index %s was created',
                $log->getIndexUUID()
            );
        }

        if (self::INDEX_WAS_DELETED === $log->getType()) {
            return \sprintf('index %s was deleted',
                $log->getIndexUUID()
            );
        }

        if (self::INDEX_WAS_CONFIGURED === $log->getType()) {
            return \sprintf('index %s was configured',
                $log->getIndexUUID()
            );
        }

        if (self::INDEX_WAS_RESET === $log->getType()) {
            return \sprintf('index %s was reset',
                $log->getIndexUUID()
            );
        }

        if (self::INDEX_WAS_IMPORTED === $log->getType()) {
            return \sprintf('%d items were imported in index %s. Importation version was %s and old items were %s',
                $log->getParam('noi'),
                $log->getIndexUUID(),
                $log->getParam('v'),
                $log->getParam('or')
                    ? 'removed'
                    : 'kept'
            );
        }

        if (self::INDEX_WAS_EXPORTED === $log->getType()) {
            return \sprintf('index %s was exported',
                $log->getIndexUUID()
            );
        }

        if (self::TOKEN_WAS_PUT === $log->getType()) {
            return \sprintf('token %s was created',
                $log->getParam('tk')
            );
        }

        if (self::TOKEN_WAS_DELETED === $log->getType()) {
            return \sprintf('token %s was deleted',
                $log->getParam('tk')
            );
        }

        if (self::TOKENS_WERE_DELETED === $log->getType()) {
            return 'all tokens were deleted';
        }

        if (self::EXCEPTION_WAS_CACHED === $log->getType()) {
            return $log->getParam('txt');
        }

        return '';
    }

    /**
     * @param int    $numberOfItems
     * @param string $version
     * @param bool   $oldItemsWereRemoved
     *
     * @return array
     */
    public static function createIndexWasImportedLogParams(
        int $numberOfItems,
        string $version,
        bool $oldItemsWereRemoved
    ): array {
        return [
            'noi' => $numberOfItems,
            'v' => $version,
            'or' => $oldItemsWereRemoved,
        ];
    }

    /**
     * @param TokenUUID $tokenUUID
     *
     * @return array
     */
    public static function createTokenLogParams(TokenUUID $tokenUUID): array
    {
        return [
            'tk' => $tokenUUID->composeUUID(),
        ];
    }

    /**
     * @param StorableException $storableException
     *
     * @return array
     */
    public static function createExceptionLogParams(StorableException $storableException): array
    {
        return [
            'txt' => $storableException->getMessage(),
            'code' => \strval($storableException->getCode()),
        ];
    }
}
