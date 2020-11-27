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

use Apisearch\Server\Domain\Model\HealthCheckData;
use Apisearch\Server\Domain\Plugin\PluginMiddleware;
use Apisearch\Server\Domain\Query\CheckHealth;
use Drift\DBAL\Connection;
use Drift\DBAL\Result;
use function React\Promise\all;
use React\Promise\PromiseInterface;
use function React\Promise\resolve;

/**
 * Class CheckHealthMiddleware.
 */
final class CheckHealthMiddleware implements PluginMiddleware
{
    private Connection $connection;
    private bool $interactionsRepositoryEnabled;
    private string $interactionsTable;
    private bool $usageLinesRepositoryEnabled;
    private string $usageLinesTable;
    private bool $searchesRepositoryEnabled;
    private string $searchLinesTable;
    private bool $tokensRepositoryEnabled;
    private string $tokensTable;
    private bool $logsRepositoryEnabled;
    private string $logsTable;

    /**
     * @param Connection $connection
     * @param bool       $interactionsRepositoryEnabled
     * @param string     $interactionsTable
     * @param bool       $usageLinesRepositoryEnabled
     * @param string     $usageLinesTable
     * @param bool       $searchesRepositoryEnabled
     * @param string     $searchLinesTable
     * @param bool       $tokensRepositoryEnabled
     * @param string     $tokensTable
     * @param bool       $logsRepositoryEnabled
     * @param string     $logsTable
     */
    public function __construct(
        Connection $connection,
        bool $interactionsRepositoryEnabled,
        string $interactionsTable,
        bool $usageLinesRepositoryEnabled,
        string $usageLinesTable,
        bool $searchesRepositoryEnabled,
        string $searchLinesTable,
        bool $tokensRepositoryEnabled,
        string $tokensTable,
        bool $logsRepositoryEnabled,
        string $logsTable
    ) {
        $this->connection = $connection;
        $this->interactionsRepositoryEnabled = $interactionsRepositoryEnabled;
        $this->interactionsTable = $interactionsTable;
        $this->usageLinesRepositoryEnabled = $usageLinesRepositoryEnabled;
        $this->usageLinesTable = $usageLinesTable;
        $this->searchesRepositoryEnabled = $searchesRepositoryEnabled;
        $this->searchLinesTable = $searchLinesTable;
        $this->tokensRepositoryEnabled = $tokensRepositoryEnabled;
        $this->tokensTable = $tokensTable;
        $this->logsRepositoryEnabled = $logsRepositoryEnabled;
        $this->logsTable = $logsTable;
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
        return $next($command)
            ->then(function (HealthCheckData $healthCheckData) {
                $from = \microtime(true);
                $healthCheckData->addPromise($this
                    ->getClientStatus()
                    ->then(function (bool $isHealth) use ($healthCheckData, $from) {
                        $healthCheckData->setPartialHealth($isHealth);
                        $to = \microtime(true);
                        $statusInMicroseconds = \intval(($to - $from) * 1000000);

                        return all([
                            $this->interactionsRepositoryEnabled ? $this->getInteractionRows() : resolve(0),
                            $this->usageLinesRepositoryEnabled ? $this->getUsageLinesRows() : resolve(0),
                            $this->searchesRepositoryEnabled ? $this->getSearchLinesRows() : resolve(0),
                            $this->tokensRepositoryEnabled ? $this->getTokensRows() : resolve(0),
                            $this->logsRepositoryEnabled ? $this->getLogRows() : resolve(0),
                        ])
                            ->then(function (array $results) use ($healthCheckData, $statusInMicroseconds, $isHealth) {
                                $healthCheckData->mergeData([
                                    'status' => [
                                        'dbal' => $isHealth,
                                    ],
                                    'info' => [
                                        'dbal' => [
                                            'ping_in_microseconds' => $statusInMicroseconds,
                                            'interactions' => $results[0],
                                            'usage_lines' => $results[1],
                                            'search_lines' => $results[2],
                                            'tokens' => $results[3],
                                            'logs' => $results[4],
                                        ],
                                    ],
                                ]);
                            });
                    }));

                return $healthCheckData;
            });
    }

    /**
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
                return -1;
            });
    }

    /**
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
                return -1;
            });
    }

    /**
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
                return -1;
            });
    }

    /**
     * @return PromiseInterface<int>
     */
    private function getTokensRows(): PromiseInterface
    {
        return $this
            ->connection
            ->queryBySQL('SELECT count(*) as count from '.$this->tokensTable)
            ->then(function (Result $result) {
                return $result->fetchFirstRow()['count'];
            })
            ->otherwise(function (\Exception $e) {
                return -1;
            });
    }

    /**
     * @return PromiseInterface<int>
     */
    private function getLogRows(): PromiseInterface
    {
        return $this
            ->connection
            ->queryBySQL('SELECT count(*) as count from '.$this->logsTable)
            ->then(function (Result $result) {
                return $result->fetchFirstRow()['count'];
            })
            ->otherwise(function (\Exception $e) {
                return -1;
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
