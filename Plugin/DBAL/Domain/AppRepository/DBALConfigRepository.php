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
use Apisearch\Repository\RepositoryReference;
use Apisearch\Server\Domain\Repository\AppRepository\ConfigRepository;
use Drift\DBAL\Connection;
use React\Promise\PromiseInterface;

/**
 * Class DBALConfigRepository.
 */
class DBALConfigRepository extends ConfigRepository
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
     * @param Connection $connection
     * @param string     $configsTable
     * @param bool       $enabled
     */
    public function __construct(
        Connection $connection,
        string $configsTable
    ) {
        $this->connection = $connection;
        $this->table = $configsTable;
    }

    /**
     * @inheritDoc
     */
    public function putConfig(
        RepositoryReference $repositoryReference,
        Config $config
    ): PromiseInterface
    {
        return $this
            ->connection
            ->upsert(
                $this->table,
                ['repository_reference_uuid' => $repositoryReference->compose()],
                [
                    'content' => json_encode($config->toArray()),
                ]
            );
    }

    /**
     * @inheritDoc
     */
    public function deleteConfig(RepositoryReference $repositoryReference): PromiseInterface
    {
        return $this
            ->connection
            ->delete($this->table, [
                'repository_reference_uuid' => $repositoryReference->compose()
            ]);
    }

    /**
     * @inheritDoc
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
                        $content = json_decode(
                            $result['content'],
                            true
                        );
                    } catch (\Exception $exception) {
                        $content = [];
                    }

                    $content = is_array($content) ? $content : [];
                    $resultsWithKey[$result['repository_reference_uuid']] = Config::createFromArray($content);
                }

                return $resultsWithKey;
            });
    }
}
