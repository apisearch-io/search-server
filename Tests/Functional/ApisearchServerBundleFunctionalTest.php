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

namespace Apisearch\Server\Tests\Functional;

use Apisearch\ApisearchBundle;
use Apisearch\Config\Config;
use Apisearch\Exception\ResourceNotAvailableException;
use Apisearch\Model\AppUUID;
use Apisearch\Model\Changes;
use Apisearch\Model\Index;
use Apisearch\Model\Item;
use Apisearch\Model\ItemUUID;
use Apisearch\Model\Token;
use Apisearch\Model\TokenUUID;
use Apisearch\Query\Query as QueryModel;
use Apisearch\Result\Result;
use Apisearch\Server\ApisearchPluginsBundle;
use Apisearch\Server\ApisearchServerBundle;
use Apisearch\Server\Domain\Model\Origin;
use Apisearch\Server\Exception\ErrorException;
use DateTime;
use Drift\CommandBus\CommandBusBundle;
use Drift\EventBus\EventBusBundle;
use Drift\PHPUnit\BaseDriftFunctionalTest;
use Drift\React;
use Exception;
use Mmoreram\BaseBundle\Kernel\DriftBaseKernel;
use React\EventLoop\Factory;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;

\set_error_handler(function ($code, $message, $file, $line, $context = null) {
    if (0 == \error_reporting()) {
        return;
    }

    throw new ErrorException($message, $code);
});

/**
 * Class ApisearchServerBundleFunctionalTest.
 */
abstract class ApisearchServerBundleFunctionalTest extends BaseDriftFunctionalTest
{
    /**
     * @var string
     *
     * External server port
     */
    const HTTP_TEST_SERVICE_PORT = '8888';

    /**
     * Get container service.
     *
     * @param string $serviceName
     *
     * @return mixed
     */
    public static function getStatic(string $serviceName)
    {
        return self::$container->get($serviceName);
    }

    /**
     * Container has service.
     *
     * @param string $serviceName
     *
     * @return bool
     */
    public static function hasStatic(string $serviceName): bool
    {
        return self::$container->has($serviceName);
    }

    /**
     * Get container parameter.
     *
     * @param string $parameterName
     *
     * @return mixed
     */
    public static function getParameterStatic(string $parameterName)
    {
        return self::$container->getParameter($parameterName);
    }

    /**
     * Get kernel.
     *
     * @return KernelInterface
     */
    protected static function getKernel(): KernelInterface
    {
        self::$godToken = $_ENV['APISEARCH_GOD_TOKEN'];
        self::$pingToken = $_ENV['APISEARCH_PING_TOKEN'];
        self::$readonlyToken = $_ENV['APISEARCH_READONLY_TOKEN'];
        $imports = [
            ['resource' => '@ApisearchServerBundle/Resources/config/command_bus.yml'],
            ['resource' => '@ApisearchServerBundle/Resources/test/command_bus.yml'],
            ['resource' => '@ApisearchServerBundle/Resources/test/event_bus.yml'],
            ['resource' => '@ApisearchServerBundle/Resources/test/services.yml'],
        ];

        $bundles = [
            FrameworkBundle::class,
            ApisearchServerBundle::class,
            ApisearchBundle::class,
            ApisearchPluginsBundle::class,
            CommandBusBundle::class,
            EventBusBundle::class,
        ];

        $composedIndex = self::$index.','.self::$anotherIndex;
        $configuration = [
            'imports' => $imports,
            'parameters' => [
                'kernel.secret' => 'sdhjshjkds',
            ],
            'framework' => [
                'form' => false,
                'assets' => false,
                'session' => false,
                'translator' => false,
                'php_errors' => [
                    'log' => false,
                ],
            ],
            'apisearch_server' => [
                'god_token' => self::$godToken,
                'ping_token' => self::$pingToken,
                'readonly_token' => self::$readonlyToken,
            ],
            'apisearch' => [
                'repositories' => [
                    'search_http' => [
                        'adapter' => 'http_test',
                        'endpoint' => '~',
                        'app_id' => self::$appId,
                        'token' => '~',
                        'test' => true,
                        'indices' => [
                            self::$index => self::$index,
                            self::$anotherIndex => self::$anotherIndex,
                            $composedIndex => $composedIndex,
                            '*' => '*',
                            self::$yetAnotherIndex => self::$yetAnotherIndex,
                        ],
                    ],
                    'search_inaccessible' => [
                        'adapter' => 'http',
                        'endpoint' => 'http://127.0.0.1:9999',
                        'app_id' => self::$appId,
                        'token' => self::$godToken,
                        'test' => true,
                        'indices' => [
                            self::$index => self::$index,
                            self::$anotherIndex => self::$anotherIndex,
                            $composedIndex => $composedIndex,
                            '*' => '*',
                            self::$yetAnotherIndex => self::$yetAnotherIndex,
                        ],
                    ],
                ],
            ],
        ];

        return new DriftBaseKernel(
            static::decorateBundles($bundles),
            static::decorateConfiguration($configuration),
            static::decorateRoutes([
                '@ApisearchServerBundle/Resources/config/routes.yml',
            ]),
            'prod', false
        );
    }

    /**
     * Decorate bundles.
     *
     * @param array $bundles
     *
     * @return array
     */
    protected static function decorateBundles(array $bundles): array
    {
        return $bundles;
    }

    /**
     * Decorate configuration.
     *
     * @param array $configuration
     *
     * @return array
     */
    protected static function decorateConfiguration(array $configuration): array
    {
        return $configuration;
    }

    /**
     * Decorate routes.
     *
     * @param array $routes
     *
     * @return array
     */
    protected static function decorateRoutes(array $routes): array
    {
        return $routes;
    }

    /**
     * @var string
     *
     * God token
     */
    public static $godToken;

    /**
     * @var string
     *
     * Readonly token
     */
    public static $readonlyToken;

    /**
     * @var string
     *
     * Ping token
     */
    public static $pingToken;

    /**
     * @var string
     *
     * App id
     */
    public static $appId = '26178621test';

    /**
     * @var string
     *
     * App id
     */
    public static $index = 'default';

    /**
     * @var string
     *
     * Another App id
     */
    public static $anotherAppId = '26178621testanother';

    /**
     * @var string
     *
     * Another not created App id
     */
    public static $anotherInexistentAppId = '26178621testnotexists';

    /**
     * @var string
     *
     * Another index
     */
    public static $anotherIndex = 'anotherindex';

    /**
     * @var string
     *
     * Yet another index
     */
    public static $yetAnotherIndex = 'yetanotherindex';

    /**
     * @var Process
     */
    protected static $lastServer;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        static::runApisearchServer();
        static::resetScenario();
    }

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    public static function tearDownAfterClass()
    {
        static::deleteEverything();
    }

    /**
     * Reset scenario.
     */
    public static function resetScenario()
    {
        static::deleteEverything();

        static::createIndex(self::$appId);
        static::createIndex(self::$appId, self::$anotherIndex);
        static::deleteTokens(self::$appId);
        static::createIndex(self::$anotherAppId);
        static::deleteTokens(self::$anotherAppId);

        static::indexTestingItems();
    }

    /**
     * Run server.
     */
    protected static function runApisearchServer()
    {
        if (!static::needsServer()) {
            return;
        }

        /*
         * Let's wait for oldest process
         */
        \sleep(2);
        if (static::$lastServer instanceof Process) {
            static::$lastServer->stop();
            static::$lastServer = null;
        }

        static::$lastServer = static::runServer(
            __DIR__.'/../../vendor/bin',
            static::HTTP_TEST_SERVICE_PORT, [
                '--quiet',
            ]
        );
        \sleep(2);
    }

    /**
     * Debug apisearch server.
     */
    protected static function debugLastApisearchServer()
    {
        if (!static::$lastServer instanceof Process) {
            return;
        }

        \var_dump(static::$lastServer->getOutput());
        \var_dump(static::$lastServer->getErrorOutput());
    }

    /**
     * Create a new kernel.
     *
     * @return KernelInterface
     */
    protected static function createNewKernel(): KernelInterface
    {
        $clusterKernel = static::getKernel();
        $clusterKernel->boot();
        $clusterContainer = $clusterKernel->getContainer();
        $eventLoop = Factory::create();
        $clusterContainer->set('reactphp.event_loop', $eventLoop);
        static::await(
            $clusterKernel->preload(),
            $eventLoop
        );

        return $clusterKernel;
    }

    /**
     * @return bool
     */
    protected static function needsServer(): bool
    {
        return false;
    }

    /**
     * Index test data.
     *
     * @param string $appId
     * @param string $index
     */
    protected static function indexTestingItems(
        string $appId = null,
        string $index = null
    ) {
        $items = Yaml::parse(\file_get_contents(static::getItemsFilePath()));
        $itemsInstances = [];
        foreach ($items['items'] as $item) {
            if (isset($item['indexed_metadata']['created_at'])) {
                $date = new DateTime($item['indexed_metadata']['created_at']);
                $item['indexed_metadata']['created_at'] = $date->format(DATE_ATOM);
            }
            $itemsInstances[] = Item::createFromArray($item);
        }
        static::indexItems($itemsInstances, $appId, $index);
    }

    /**
     * Get items file path.
     *
     * @return string
     */
    public static function getItemsFilePath(): string
    {
        return __DIR__.'/../items.yml';
    }

    /**
     * Clean all tests data.
     */
    public static function deleteEverything()
    {
        static::safeDeleteIndex(self::$appId);
        static::safeDeleteIndex(self::$appId, self::$anotherIndex);
        static::safeDeleteIndex(self::$anotherAppId);
    }

    /**
     * Query using the bus.
     *
     * @param QueryModel $query
     * @param string     $appId
     * @param string     $index
     * @param Token      $token
     * @param array      $parameters
     * @param Origin     $origin
     *
     * @return Result
     */
    abstract public function query(
        QueryModel $query,
        string $appId = null,
        string $index = null,
        Token $token = null,
        array $parameters = [],
        Origin $origin = null
    ): Result;

    /**
     * Preflight CORS query.
     *
     * @param Origin $origin
     * @param string $appId
     * @param string $index
     *
     * @return string
     */
    abstract public function getCORSPermissions(
        Origin $origin,
        string $appId = null,
        string $index = null
    ): string;

    /**
     * Export index.
     *
     * @param bool   $closeImmediately
     * @param string $appId
     * @param string $index
     * @param Token  $token
     *
     * @return Item[]
     */
    abstract public function exportIndex(
        bool $closeImmediately = false,
        string $appId = null,
        string $index = null,
        Token $token = null
    ): array;

    /**
     * Delete using the bus.
     *
     * @param ItemUUID[] $itemsUUID
     * @param string     $appId
     * @param string     $index
     * @param Token      $token
     */
    abstract public function deleteItems(
        array $itemsUUID,
        string $appId = null,
        string $index = null,
        Token $token = null
    );

    /**
     * @param QueryModel $query
     * @param string     $appId
     * @param string     $index
     * @param Token      $token
     */
    abstract public function deleteItemsByQuery(
        QueryModel $query,
        string $appId = null,
        string $index = null,
        Token $token = null
    );

    /**
     * Add items using the bus.
     *
     * @param Item[] $items
     * @param string $appId
     * @param string $index
     * @param Token  $token
     */
    abstract public static function indexItems(
        array $items,
        ?string $appId = null,
        ?string $index = null,
        ?Token $token = null
    );

    /**
     * Update using the bus.
     *
     * @param QueryModel $query
     * @param Changes    $changes
     * @param string     $appId
     * @param string     $index
     * @param Token      $token
     */
    abstract public function updateItems(
        QueryModel $query,
        Changes $changes,
        string $appId = null,
        string $index = null,
        Token $token = null
    );

    /**
     * Reset index using the bus.
     *
     * @param string $appId
     * @param string $index
     * @param Token  $token
     */
    abstract public function resetIndex(
        string $appId = null,
        string $index = null,
        Token $token = null
    );

    /**
     * Create index using the bus.
     *
     * @param string $appId
     * @param string $index
     * @param Token  $token
     * @param Config $config
     */
    abstract public static function createIndex(
        string $appId = null,
        string $index = null,
        Token $token = null,
        Config $config = null
    );

    /**
     * Configure index using the bus.
     *
     * @param Config $config
     * @param bool   $forceReindex
     * @param string $appId
     * @param string $index
     * @param Token  $token
     */
    abstract public function configureIndex(
        Config $config,
        bool $forceReindex = false,
        string $appId = null,
        string $index = null,
        Token $token = null
    );

    /**
     * @param string|null $appId
     * @param Token       $token
     *
     * @return Index[]
     */
    abstract public function getIndices(
        string $appId = null,
        Token $token = null
    ): array;

    /**
     * @param string $fieldToCheck
     *
     * @return Index|null
     */
    protected function getPrincipalIndex(string $fieldToCheck = 'indexed_metadata.brand')
    {
        $indices = $this->getIndices(self::$appId);
        $indices = \array_filter($indices, function (Index $index) use ($fieldToCheck) {
            return \array_key_exists($fieldToCheck, $index->getFields());
        });

        return 1 === \count($indices)
            ? \reset($indices)
            : null;
    }

    /**
     * Check index.
     *
     * @param string $appId
     * @param string $index
     * @param Token  $token
     *
     * @return bool
     */
    abstract public function checkIndex(
        string $appId = null,
        string $index = null,
        Token $token = null
    ): bool;

    /**
     * Delete index using the bus.
     *
     * @param string $appId
     * @param string $index
     * @param Token  $token
     */
    abstract public static function deleteIndex(
        string $appId = null,
        string $index = null,
        Token $token = null
    );

    /**
     * Delete index using the bus.
     *
     * @param string $appId
     * @param string $index
     * @param Token  $token
     */
    public static function safeDeleteIndex(
        string $appId = null,
        string $index = null,
        Token $token = null
    ) {
        try {
            static::deleteIndex(
                $appId,
                $index,
                $token
            );
        } catch (ResourceNotAvailableException $_) {
            // Silent pass
        }
    }

    /**
     * Add token.
     *
     * @param Token  $newToken
     * @param string $appId
     * @param Token  $token
     */
    abstract public static function putToken(
        Token $newToken,
        string $appId = null,
        Token $token = null
    );

    /**
     * Delete token.
     *
     * @param TokenUUID $tokenUUID
     * @param string    $appId
     * @param Token     $token
     */
    abstract public static function deleteToken(
        TokenUUID $tokenUUID,
        string $appId = null,
        Token $token = null
    );

    /**
     * Get tokens.
     *
     * @param string $appId
     * @param Token  $token
     *
     * @return Token[]
     */
    abstract public static function getTokens(
        string $appId = null,
        Token $token = null
    );

    /**
     * Get tokens.
     *
     * @param string $appId
     * @param Token  $token
     *
     * @return Token[]
     */
    public static function getTokensById(
        string $appId = null,
        Token $token = null
    ) {
        $tokens = static::getTokens($appId, $token);
        $tokensById = [];
        foreach ($tokens as $token) {
            $tokensById[$token->getTokenUUID()->composeUUID()] = $token;
        }

        return $tokensById;
    }

    /**
     * Delete token.
     *
     * @param string $appId
     * @param Token  $token
     */
    abstract public static function deleteTokens(
        string $appId,
        Token $token = null
    );

    /**
     * @param string|null $appId
     * @param Token       $token
     *
     * @return array
     */
    abstract public function getUsage(
        string $appId = null,
        Token $token = null
    ): array;

    /**
     * @param string $userId
     * @param string $itemId
     * @param Origin $origin
     * @param string $appId
     * @param string $indexId
     * @param Token  $token
     */
    abstract public function click(
        string $userId,
        string $itemId,
        Origin $origin,
        string $appId = null,
        string $indexId = null,
        Token $token = null
    );

    /**
     * @param bool          $perDay
     * @param DateTime|null $from
     * @param DateTime|null $to
     * @param string|null   $userId
     * @param string|null   $platform
     * @param string|null   $itemId
     * @param string|null   $type
     * @param string        $appId
     * @param string        $indexId
     * @param Token         $token
     *
     * @return int|int[]
     */
    abstract public function getInteractions(
        bool $perDay,
        ?DateTime $from = null,
        ?DateTime $to = null,
        ?string $userId = null,
        ?string $platform = null,
        ?string $itemId = null,
        ?string $type = null,
        string $appId = null,
        string $indexId = null,
        Token $token = null
    );

    /**
     * @param int|null      $n
     * @param DateTime|null $from
     * @param DateTime|null $to
     * @param string|null   $userId
     * @param string|null   $platform
     * @param string        $appId
     * @param string        $indexId
     * @param Token         $token
     *
     * @return int|int[]
     */
    abstract public function getTopClicks(
        ?int $n = null,
        ?DateTime $from = null,
        ?DateTime $to = null,
        ?string $userId = null,
        ?string $platform = null,
        string $appId = null,
        string $indexId = null,
        Token $token = null
    );

    /**
     * @param bool          $perDay
     * @param DateTime|null $from
     * @param DateTime|null $to
     * @param string|null   $userId
     * @param string|null   $platform
     * @param bool          $excludeWithResults
     * @param bool          $excludeWithoutResults
     * @param string        $appId
     * @param string        $indexId
     * @param Token         $token
     *
     * @return int|int[]
     */
    abstract public function getSearches(
        bool $perDay,
        ?DateTime $from = null,
        ?DateTime $to = null,
        ?string $userId = null,
        ?string $platform = null,
        bool $excludeWithResults = false,
        bool $excludeWithoutResults = false,
        string $appId = null,
        string $indexId = null,
        Token $token = null
    );

    /**
     * @param int|null
     * @param DateTime|null $from
     * @param DateTime|null $to
     * @param string|null   $platform
     * @param bool          $excludeWithResults
     * @param bool          $excludeWithoutResults
     * @param string        $appId
     * @param string        $indexId
     * @param Token         $token
     */
    abstract public function getTopSearches(
        ?int $n = null,
        ?DateTime $from = null,
        ?DateTime $to = null,
        ?string $platform = null,
        bool $excludeWithResults = false,
        bool $excludeWithoutResults = false,
        string $appId = null,
        string $indexId = null,
        Token $token = null
    );

    /**
     * Ping.
     *
     * @param Token $token
     *
     * @return bool
     */
    abstract public function ping(Token $token = null): bool;

    /**
     * Check health.
     *
     * @param Token $token
     *
     * @return array
     */
    abstract public function checkHealth(Token $token = null): array;

    /**
     * Configure environment.
     */
    abstract public static function configureEnvironment();

    /**
     * Clean environment.
     */
    abstract public static function cleanEnvironment();

    /**
     * Create token by id and app_id.
     *
     * @param string $tokenId
     * @param string $appId
     *
     * @return Token
     */
    protected function createTokenByIdAndAppId(
        string $tokenId,
        string $appId
    ): Token {
        return new Token(
            TokenUUID::createById($tokenId),
            AppUUID::createById($appId)
        );
    }

    /**
     * Print item results with score.
     *
     * @param Result $result
     */
    protected function printItemResultsWithScore(Result $result)
    {
        echo PHP_EOL;
        foreach ($result->getItems() as $item) {
            echo \sprintf('[ %s ] - %f', $item->composeUUID(), $item->getScore()).PHP_EOL;
        }
        echo PHP_EOL;
    }

    /**
     * USleep n microseconds.
     *
     * @param int $microseconds
     *
     * @return mixed
     *
     * @throws Exception
     */
    protected static function usleep(int $microseconds)
    {
        $loop = static::getStatic('reactphp.event_loop');

        return static::await(
            React\usleep($microseconds, $loop),
            $loop
        );
    }

    /**
     * Get GOD token.
     *
     * @param string $appId
     *
     * @return Token
     */
    protected function getGodToken(string $appId = null): Token
    {
        return new Token(
            TokenUUID::createById(static::$godToken),
            AppUUID::createById($appId ?? static::$appId)
        );
    }

    /**
     * Dispatch imperative event.
     *
     * @param object $event
     */
    protected function dispatchImperative($event): void
    {
        static::await(
            $this->get('drift.event_bus.test')->dispatch($event)
        );
        static::usleep(10000);
    }
}
