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

use Apisearch\Repository\RepositoryReference;
use Apisearch\Server\Domain\Repository\MetadataRepository\MetadataRepository;
use Drift\DBAL\Connection;
use React\Promise\PromiseInterface;

/**
 * Class DBALMetadataRepository.
 */
class DBALMetadataRepository extends MetadataRepository
{
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var string
     */
    private $table;

    /**
     * TokenRedisRepository constructor.
     *
     * @param Connection $dbalPluginConnection
     * @param string     $metadataTable
     */
    public function __construct(
        Connection $dbalPluginConnection,
        string $metadataTable
    ) {
        $this->connection = $dbalPluginConnection;
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
        return $this
            ->connection
            ->upsert($this->table, [
                'repository_reference_uuid' => $repositoryReference->compose(),
                '`key`' => $key,
            ], [
                'val' => \json_encode($value),
            ]);
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
            ]);
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
                    $formattedRows[$row['key']] = \json_decode($row['val'], true);
                }

                return $formattedRows;
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

                    $formattedMetadata[$row['repository_reference_uuid']][$row['key']] = \json_decode($row['val'], true);
                }

                return $formattedMetadata;
            });
    }
}
