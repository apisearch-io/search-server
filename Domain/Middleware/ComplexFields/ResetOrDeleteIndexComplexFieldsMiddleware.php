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

namespace Apisearch\Server\Domain\Middleware\ComplexFields;

use Apisearch\Server\Domain\Command\DeleteIndex;
use Apisearch\Server\Domain\Command\ResetIndex;
use Apisearch\Server\Domain\CommandWithRepositoryReferenceAndTokenAndIndexUUID;
use Apisearch\Server\Domain\ImperativeEvent\LoadMetadata;
use Drift\CommandBus\Middleware\DiscriminableMiddleware;

/**
 * Class ResetOrDeleteIndexComplexFieldsMiddleware.
 */
class ResetOrDeleteIndexComplexFieldsMiddleware extends ComplexFieldsMiddleware implements DiscriminableMiddleware
{
    /**
     * @param object   $command
     * @param callable $next
     *
     * @return mixed
     */
    public function execute($command, callable $next)
    {
        /**
         * @var CommandWithRepositoryReferenceAndTokenAndIndexUUID $command
         */
        $repositoryReference = $command->getRepositoryReference();

        return $next($command)
            ->then(function () use ($repositoryReference) {
                return $this
                    ->metadataRepository
                    ->delete(
                        $repositoryReference->changeIndex(
                            $repositoryReference->getIndexUUID()
                        ),
                        static::COMPLEX_FIELDS_METADATA
                    )
                    ->then(function () use ($repositoryReference) {
                        return $this
                            ->eventBus
                            ->dispatch(new LoadMetadata($repositoryReference));
                    });
            });
    }

    /**
     * Only handle.
     *
     * @return string[]
     */
    public function onlyHandle(): array
    {
        return [
            ResetIndex::class,
            DeleteIndex::class
        ];
    }
}
