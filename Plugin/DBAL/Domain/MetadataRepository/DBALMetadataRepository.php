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

namespace Apisearch\Plugin\DBAL\Domain\MetadataRepository;

use Apisearch\Model\HttpTransportable;
use Apisearch\Plugin\DBAL\Domain\DBALException;
use Apisearch\Plugin\DBAL\Domain\Encrypter\Encrypter;
use Apisearch\Repository\RepositoryReference;
use Apisearch\Server\Domain\Repository\MetadataRepository\MetadataRepository;
use Doctrine\DBAL\Exception as ExternalDBALException;
use Drift\DBAL\Connection;
use React\Promise\PromiseInterface;

/**
 * Class DBALMetadataRepository.
 */
final class DBALMetadataRepository extends MetadataRepository
{
    private Connection $connection;
    private Encrypter $encrypter;
    private string $table;

    /**
     * @param Connection $connection
     * @param Encrypter  $encrypter
     * @param string     $metadataTable
     */
    public function __construct(
        Connection $connection,
        Encrypter $encrypter,
        string $metadataTable
    ) {
        $this->connection = $connection;
        $this->encrypter = $encrypter;
        $this->table = $metadataTable;
    }

    /**
     * @param RepositoryReference $repositoryReference
     * @param string              $key
     * @param mixed               $value
     *
     * @return PromiseInterface
     */
    public function set(
        RepositoryReference $repositoryReference,
        string $key,
        $value
    ): PromiseInterface {
        $isObject = ($value instanceof HttpTransportable);
        $objectNamespace = null;
        if ($isObject) {
            $objectNamespace = \get_class($value);
            $value = $value->toArray();
        }

        return $this
            ->connection
            ->upsert($this->table, [
                'repository_reference_uuid' => $repositoryReference->compose(),
                '`key`' => $key,
            ], [
                'val' => $this->encrypter->encrypt(\json_encode($value)),
                'factory' => $objectNamespace,
            ])
            ->otherwise(function (ExternalDBALException $exception) {
                throw new DBALException($exception->getMessage(), $exception->getCode(), $exception->getPrevious());
            });
    }

    /**
     * @param RepositoryReference $repositoryReference
     * @param string              $key
     *
     * @return PromiseInterface
     */
    public function delete(RepositoryReference $repositoryReference, string $key): PromiseInterface
    {
        return $this
            ->connection
            ->delete($this->table, [
                'repository_reference_uuid' => $repositoryReference->compose(),
                '`key`' => $key,
            ])
            ->otherwise(function (ExternalDBALException $exception) {
                throw new DBALException($exception->getMessage(), $exception->getCode(), $exception->getPrevious());
            });
    }

    /**
     * @param RepositoryReference $repositoryReference
     *
     * @return PromiseInterface
     */
    public function findMetadata(RepositoryReference $repositoryReference): PromiseInterface
    {
        return $this
            ->connection
            ->findBy($this->table, [
                'repository_reference_uuid' => $repositoryReference->compose(),
            ])
            ->then(function (array $rows) {
                $formattedRows = [];
                foreach ($rows as $row) {
                    $formattedRows[$row['key']] = $this->unserializeRow($row);
                }

                return $formattedRows;
            })
            ->otherwise(function (ExternalDBALException $exception) {
                throw new DBALException($exception->getMessage(), $exception->getCode(), $exception->getPrevious());
            });
    }

    /**
     * @return PromiseInterface
     */
    public function findAllMetadata(): PromiseInterface
    {
        return $this
            ->connection
            ->findBy($this->table)
            ->then(function (array $rows) {
                $formattedMetadata = [];
                foreach ($rows as $row) {
                    if (!\array_key_exists($row['repository_reference_uuid'], $formattedMetadata)) {
                        $formattedMetadata[$row['repository_reference_uuid']] = [];
                    }

                    $formattedMetadata[$row['repository_reference_uuid']][$row['key']] = $this->unserializeRow($row);
                }

                return $formattedMetadata;
            })
            ->otherwise(function (ExternalDBALException $exception) {
                throw new DBALException($exception->getMessage(), $exception->getCode(), $exception->getPrevious());
            });
    }

    /**
     * @param array $row
     *
     * @return mixed
     */
    private function unserializeRow(array $row)
    {
        $factory = $row['factory'] ?? null;
        $value = \json_decode($this->encrypter->decrypt($row['val']), true);

        return \is_null($factory)
            ? $value
            : $factory::createFromArray($value);
    }
}
