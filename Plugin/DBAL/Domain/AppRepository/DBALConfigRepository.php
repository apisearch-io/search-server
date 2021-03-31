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

use Apisearch\Config\Config;
use Apisearch\Plugin\DBAL\Domain\DBALException;
use Apisearch\Plugin\DBAL\Domain\Encrypter\Encrypter;
use Apisearch\Repository\RepositoryReference;
use Apisearch\Server\Domain\Repository\AppRepository\ConfigRepository;
use Doctrine\DBAL\Exception as ExternalDBALException;
use Drift\DBAL\Connection;
use React\Promise\PromiseInterface;

/**
 * Class DBALConfigRepository.
 */
final class DBALConfigRepository extends ConfigRepository
{
    private Connection $connection;
    private Encrypter $encrypter;
    private string $table;

    /**
     * TokenRedisRepository constructor.
     *
     * @param Connection $dbalPluginConnection
     * @param Encrypter  $encrypter
     * @param string     $configsTable
     */
    public function __construct(
        Connection $dbalPluginConnection,
        Encrypter $encrypter,
        string $configsTable
    ) {
        $this->connection = $dbalPluginConnection;
        $this->encrypter = $encrypter;
        $this->table = $configsTable;
    }

    /**
     * {@inheritdoc}
     */
    public function putConfig(
        RepositoryReference $repositoryReference,
        Config $config
    ): PromiseInterface {
        return $this
            ->connection
            ->upsert(
                $this->table,
                ['repository_reference_uuid' => $repositoryReference->compose()],
                [
                    'content' => $this->encrypter->encrypt(\json_encode($config->toArray())),
                ]
            )
            ->otherwise(function (ExternalDBALException $exception) {
                throw new DBALException($exception->getMessage(), $exception->getCode(), $exception->getPrevious());
            });
    }

    /**
     * {@inheritdoc}
     */
    public function deleteConfig(RepositoryReference $repositoryReference): PromiseInterface
    {
        return $this
            ->connection
            ->delete($this->table, [
                'repository_reference_uuid' => $repositoryReference->compose(),
            ])
            ->otherwise(function (ExternalDBALException $exception) {
                throw new DBALException($exception->getMessage(), $exception->getCode(), $exception->getPrevious());
            });
    }

    /**
     * {@inheritdoc}
     */
    public function findAllConfigs(): PromiseInterface
    {
        return $this
            ->connection
            ->findBy($this->table)
            ->then(function ($results) {
                $resultsWithKey = [];
                foreach ($results as $result) {
                    try {
                        $content = \json_decode($this->encrypter->decrypt(
                            $result['content']
                        ), true);
                    } catch (\Exception $exception) {
                        $content = [];
                    }

                    $content = \is_array($content) ? $content : [];
                    $resultsWithKey[$result['repository_reference_uuid']] = Config::createFromArray($content);
                }

                return $resultsWithKey;
            })
            ->otherwise(function (ExternalDBALException $exception) {
                throw new DBALException($exception->getMessage(), $exception->getCode(), $exception->getPrevious());
            });
    }
}
