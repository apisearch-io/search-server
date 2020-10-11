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

namespace Apisearch\Plugin\DBAL\Domain\Middleware;

use Apisearch\Server\Domain\Plugin\PluginMiddleware;
use Apisearch\Server\Domain\Query\CheckHealth;
use Drift\DBAL\Connection;
use Drift\DBAL\Result;
use function React\Promise\all;
use React\Promise\PromiseInterface;

/**
 * Class CheckHealthMiddleware.
 */
class CheckHealthMiddleware implements PluginMiddleware
{
    private $connection;
    private string $interactionsTable;
    private string $usageLinesTable;
    private string $searchLinesTable;

    /**
     * @param Connection $dbalPluginConnection
     * @param string     $interactionsTable
     * @param string     $usageLinesTable
     * @param string     $searchLinesTable
     */
    public function __construct(
        Connection $dbalPluginConnection,
        string $interactionsTable,
        string $usageLinesTable,
        string $searchesTable
    ) {
        $this->connection = $dbalPluginConnection;
        $this->interactionsTable = $interactionsTable;
        $this->usageLinesTable = $usageLinesTable;
        $this->searchLinesTable = $searchesTable;
    }

    /**
     * Execute middleware.
     *
     * @param mixed    $command
     * @param callable $next
     *
     * @return PromiseInterface
     */
    public function execute(
        $command,
        $next
    ): PromiseInterface {
        return
            $next($command)
                ->then(function ($data) {
                    return $this
                        ->getClientStatus()
                        ->then(function (bool $isHealth) use ($data) {
                            $data['status']['dbal'] = $isHealth;
                            $data['healthy'] = $data['healthy'] && $isHealth;

                            return all([
                                $this->getInteractionRows(),
                                $this->getUsageLinesRows(),
                                $this->getSearchLinesRows(),
                            ])
                                ->then(function (array $results) use ($data) {
                                    $data['info']['dbal'] = [
                                        'interactions' => $results[0],
                                        'usage_lines' => $results[1],
                                        'search_lines' => $results[2],
                                    ];

                                    return $data;
                                });
                        });
                });
    }

    /**
     * Get client status.
     *
     * @return PromiseInterface<bool>
     */
    private function getClientStatus(): PromiseInterface
    {
        return $this
            ->connection
            ->queryBySQL('SELECT ?', ['.'])
            ->then(function () {
                return true;
            })
            ->otherwise(function (\Exception $e) {
                return false;
            });
    }

    /**
     * Get interaction rows.
     *
     * @return PromiseInterface<int>
     */
    private function getInteractionRows(): PromiseInterface
    {
        return $this
            ->connection
            ->queryBySQL('SELECT count(*) as count from '.$this->interactionsTable)
            ->then(function (Result $result) {
                return $result->fetchFirstRow()['count'];
            })
            ->otherwise(function (\Exception $e) {
                return false;
            });
    }

    /**
     * Get usage lines rows.
     *
     * @return PromiseInterface<int>
     */
    private function getUsageLinesRows(): PromiseInterface
    {
        return $this
            ->connection
            ->queryBySQL('SELECT count(*) as count from '.$this->usageLinesTable)
            ->then(function (Result $result) {
                return $result->fetchFirstRow()['count'];
            })
            ->otherwise(function (\Exception $e) {
                return false;
            });
    }

    /**
     * Get search lines rows.
     *
     * @return PromiseInterface<int>
     */
    private function getSearchLinesRows(): PromiseInterface
    {
        return $this
            ->connection
            ->queryBySQL('SELECT count(*) as count from '.$this->searchLinesTable)
            ->then(function (Result $result) {
                return $result->fetchFirstRow()['count'];
            })
            ->otherwise(function (\Exception $e) {
                return false;
            });
    }

    /**
     * {@inheritdoc}
     */
    public function onlyHandle(): array
    {
        return [
            CheckHealth::class,
        ];
    }
}
