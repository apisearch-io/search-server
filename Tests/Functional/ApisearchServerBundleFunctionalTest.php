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
use Apisearch\Server\Domain\Exception\ErrorException;
use Apisearch\Server\Domain\Model\Origin;
use Apisearch\Server\Tests\PHPUnitModifierTrait;
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

\error_reporting(E_ALL ^ E_DEPRECATED ^ E_USER_DEPRECATED);
\set_error_handler(function ($code, $message, $file, $line, $context = null) {
    if (0 == \error_reporting()) {
        return;
    }

    throw new ErrorException($message, $code);
}, E_ALL ^ E_DEPRECATED ^ E_USER_DEPRECATED);

/**
 * Class ApisearchServerBundleFunctionalTest.
 */
abstract class ApisearchServerBundleFunctionalTest extends BaseDriftFunctionalTest
{
    use PHPUnitModifierTrait;
    use ResultAssertionsTrait;

    const HTTP_TEST_SERVICE_PORT = '8888';
    public static string $godToken;
    public static string $readonlyToken;
    public static string $healthCheckToken;
    public static string $pingToken;
    public static string $appId = '26178621test';
    public static string $index = 'default';
    public static string $anotherAppId = '26178621testanother';
    public static string $anotherInexistentAppId = '26178621testnotexists';
    public static string $anotherIndex = 'anotherindex';
    public static string $yetAnotherIndex = 'yetanotherindex';
    protected static ?Process $lastServer = null;

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
        self::$healthCheckToken = $_ENV['APISEARCH_HEALTH_CHECK_TOKEN'];
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
                'router' => [
                    'utf8' => true,
                ],
            ],
            'apisearch_server' => [
                'god_token' => self::$godToken,
                'ping_token' => self::$pingToken,
                'readonly_token' => self::$readonlyToken,
                'health_check_token' => self::$healthCheckToken,
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
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     *
     * @return void
     *
     * @throws Exception
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        static::runApisearchServer();
        static::resetScenario();
    }

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     *
     * @return void
     */
    public static function tearDownAfterClass(): void
    {
        static::deleteEverything();
    }

    /**
     * Reset scenario.
     *
     * @return void
     *
     * @throws Exception
     */
    public static function resetScenario()
    {
        static::deleteEverything();

        static::createIndex(self::$appId);
        static::createIndex(self::$appId, self::$anotherIndex);
        static::deleteTokens(self::$appId);
        static::createIndex(self::$anotherAppId);
        static::deleteTokens(self::$anotherAppId);

        if (static::needsInitialItemsIndexation()) {
            static::indexTestingItems();
        }
    }

    /**
     * Run server.
     *
     * @return void
     *
     * @throws Exception
     */
    protected static function runApisearchServer()
    {
        if (!static::needsServer()) {
            return;
        }

        /*
         * Let's wait for oldest process to finish
         */
        \sleep(2);
        if (static::$lastServer instanceof Process) {
            static::$lastServer->stop();
            static::$lastServer = null;
        }

        $serverPath = \is_dir(__DIR__.'/../../vendor/bin')
            ? __DIR__.'/../../vendor/bin'
            : __DIR__.'/../../../../bin';

        static::$lastServer = static::runServer(
            $serverPath,
            static::HTTP_TEST_SERVICE_PORT, \array_merge(static::quietServer() ? [
                '--quiet',
            ] : [], static::serverConfiguration())
        );
        \sleep(2);
    }

    /**
     * Debug apisearch server.
     *
     * @return void
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
     *
     * @throws Exception
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
     * @return bool
     */
    protected static function needsInitialItemsIndexation(): bool
    {
        return true;
    }

    /**
     * @return bool
     */
    protected static function quietServer(): bool
    {
        return true;
    }

    /**
     * @return string[]
     */
    protected static function serverConfiguration(): array
    {
        return [];
    }

    /**
     * Index test data.
     *
     * @param string|null $appId
     * @param string|null $index
     * @param string|null $path
     *
     * @return void
     *
     * @throws Exception
     */
    protected static function indexTestingItems(
        string $appId = null,
        string $index = null,
        string $path = null
    ): void {
        $items = Yaml::parse(\file_get_contents($path ?? static::getItemsFilePath()));
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
     * Get items file path.
     *
     * @return string
     */
    public static function getItemsReducedFilePath(): string
    {
        return __DIR__.'/../items_reduced.yml';
    }

    /**
     * Clean all tests data.
     *
     * @return void
     */
    public static function deleteEverything(): void
    {
        static::safeDeleteIndex(self::$appId);
        static::safeDeleteIndex(self::$appId, self::$anotherIndex);
        static::safeDeleteIndex(self::$anotherAppId);
    }

    /**
     * Query using the bus.
     *
     * @param QueryModel  $query
     * @param string|null $appId
     * @param string|null $index
     * @param Token|null  $token
     * @param array       $parameters
     * @param Origin|null $origin
     * @param array       $headers
     *
     * @return Result
     *
     * @throws Exception
     */
    abstract public function query(
        QueryModel $query,
        ?string $appId = null,
        ?string $index = null,
        ?Token $token = null,
        array $parameters = [],
        ?Origin $origin = null,
        array $headers = []
    ): Result;

    /**
     * Preflight CORS query.
     *
     * @param Origin      $origin
     * @param string|null $appId
     * @param string|null $index
     *
     * @return string
     *
     * @throws Exception
     */
    abstract public function getCORSPermissions(
        Origin $origin,
        string $appId = null,
        string $index = null
    ): string;

    /**
     * Export index.
     *
     * @param string      $format
     * @param bool        $closeImmediately
     * @param string|null $appId
     * @param string|null $index
     * @param Token|null  $token
     *
     * @return Item[]
     *
     * @throws Exception
     */
    abstract public function exportIndex(
        string $format,
        bool $closeImmediately = false,
        string $appId = null,
        string $index = null,
        Token $token = null
    ): array;

    /**
     * Import index by feed.
     *
     * @param string      $feed
     * @param bool        $detached
     * @param bool        $deleteOldVersions
     * @param string|null $version
     * @param string|null $appId
     * @param string|null $index
     * @param Token|null  $token
     *
     * @throws Exception
     */
    abstract public function importIndexByFeed(
        string $feed,
        bool $detached = false,
        bool $deleteOldVersions = false,
        ?string $version = null,
        string $appId = null,
        string $index = null,
        Token $token = null
    );

    /**
     * Delete using the bus.
     *
     * @param ItemUUID[]  $itemsUUID
     * @param string|null $appId
     * @param string|null $index
     * @param Token|null  $token
     *
     * @throws Exception
     */
    abstract public function deleteItems(
        array $itemsUUID,
        string $appId = null,
        string $index = null,
        Token $token = null
    );

    /**
     * @param QueryModel  $query
     * @param string|null $appId
     * @param string|null $index
     * @param Token|null  $token
     *
     * @throws Exception
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
     * @param Item[]      $items
     * @param string|null $appId
     * @param string|null $index
     * @param Token|null  $token
     *
     * @throws Exception
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
     * @param QueryModel  $query
     * @param Changes     $changes
     * @param string|null $appId
     * @param string|null $index
     * @param Token|null  $token
     *
     * @throws Exception
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
     * @param string|null $appId
     * @param string|null $index
     * @param Token|null  $token
     *
     * @throws Exception
     */
    abstract public function resetIndex(
        string $appId = null,
        string $index = null,
        Token $token = null
    );

    /**
     * Create index using the bus.
     *
     * @param string|null $appId
     * @param string|null $index
     * @param Token|null  $token
     * @param Config|null $config
     *
     * @throws Exception
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
     * @param Config      $config
     * @param bool        $forceReindex
     * @param string|null $appId
     * @param string|null $index
     * @param Token|null  $token
     *
     * @throws Exception
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
     * @param Token|null  $token
     *
     * @return Index[]
     *
     * @throws Exception
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
    protected function getPrincipalIndex(string $fieldToCheck = 'indexed_metadata.brand'): ? Index
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
     * @param string|null $appId
     * @param string|null $index
     * @param Token|null  $token
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
     * @param string|null $appId
     * @param string|null $index
     * @param Token|null  $token
     */
    abstract public static function deleteIndex(
        string $appId = null,
        string $index = null,
        Token $token = null
    );

    /**
     * Delete index using the bus.
     *
     * @param string|null $appId
     * @param string|null $index
     * @param Token|null  $token
     *
     * @return void
     */
    public static function safeDeleteIndex(
        string $appId = null,
        string $index = null,
        Token $token = null
    ): void {
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
     * @param Token       $newToken
     * @param string|null $appId
     * @param Token|null  $token
     *
     * @throws Exception
     */
    abstract public static function putToken(
        Token $newToken,
        string $appId = null,
        Token $token = null
    );

    /**
     * Delete token.
     *
     * @param TokenUUID   $tokenUUID
     * @param string|null $appId
     * @param Token|null  $token
     *
     * @throws Exception
     */
    abstract public static function deleteToken(
        TokenUUID $tokenUUID,
        string $appId = null,
        Token $token = null
    );

    /**
     * Get tokens.
     *
     * @param string|null $appId
     * @param Token|null  $token
     *
     * @return Token[]
     *
     * @throws Exception
     */
    abstract public static function getTokens(
        string $appId = null,
        Token $token = null
    );

    /**
     * Get tokens.
     *
     * @param string|null $appId
     * @param Token|null  $token
     *
     * @return Token[]
     *
     * @throws Exception
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
     * @param string|null $appId
     * @param Token|null  $token
     *
     * @throws Exception
     */
    abstract public static function deleteTokens(
        string $appId = null,
        Token $token = null
    );

    /**
     * @param string|null   $appId
     * @param Token|null    $token
     * @param string|null   $index
     * @param DateTime|null $from
     * @param DateTime|null $to
     * @param string|null   $event
     * @param bool|null     $perDay
     *
     * @return array
     *
     * @throws Exception
     */
    abstract public function getUsage(
        string $appId = null,
        ?Token $token = null,
        ?string $index = null,
        ?DateTime $from = null,
        ?DateTime $to = null,
        ?string $event = null,
        ?bool $perDay = false
    ): array;

    /**
     * @param string|null   $appId
     * @param Token|null    $token
     * @param string|null   $index
     * @param DateTime|null $from
     * @param DateTime|null $to
     * @param string[]      $types
     * @param int           $limit
     * @param int           $page
     *
     * @return array
     *
     * @throws Exception
     */
    abstract public function getLogs(
        string $appId = null,
        ?Token $token = null,
        ?string $index = null,
        ?DateTime $from = null,
        ?DateTime $to = null,
        array $types = [],
        int $limit = 0,
        int $page = 0
    ): array;

    /**
     * @param string|null $userId
     * @param string      $itemId
     * @param int         $position
     * @param string|null $context
     * @param Origin      $origin
     * @param string|null $appId
     * @param string|null $index
     * @param Token|null  $token
     *
     * @throws Exception
     */
    abstract public function click(
        ?string $userId,
        string $itemId,
        int $position,
        ?string $context,
        Origin $origin,
        string $appId = null,
        string $index = null,
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
     * @param string|null   $count
     * @param string|null   $context
     * @param string|null   $appId
     * @param string|null   $index
     * @param Token|null    $token
     *
     * @return int|int[]
     *
     * @throws Exception
     */
    abstract public function getInteractions(
        bool $perDay,
        ?DateTime $from = null,
        ?DateTime $to = null,
        ?string $userId = null,
        ?string $platform = null,
        ?string $itemId = null,
        ?string $type = null,
        ?string $count = null,
        ?string $context = null,
        string $appId = null,
        string $index = null,
        Token $token = null
    );

    /**
     * @param string|null $userId
     * @param string[]    $itemsId
     * @param string|null $appId
     * @param string|null $index
     * @param Token|null  $token
     *
     * @throws Exception
     */
    abstract public function purchase(
        ?string $userId,
        array $itemsId,
        string $appId = null,
        string $index = null,
        Token $token = null
    );

    /**
     * @param bool          $perDay
     * @param DateTime|null $from
     * @param DateTime|null $to
     * @param string|null   $userId
     * @param string|null   $itemId
     * @param string|null   $count
     * @param string|null   $appId
     * @param string|null   $index
     * @param Token|null    $token
     *
     * @return int|int[]
     *
     * @throws Exception
     */
    abstract public function getPurchases(
        bool $perDay,
        ?DateTime $from = null,
        ?DateTime $to = null,
        ?string $userId = null,
        ?string $itemId = null,
        ?string $count = null,
        string $appId = null,
        string $index = null,
        Token $token = null
    );

    /**
     * @param int|null      $n
     * @param DateTime|null $from
     * @param DateTime|null $to
     * @param string|null   $userId
     * @param string|null   $platform
     * @param string|null   $appId
     * @param string|null   $index
     * @param Token|null    $token
     *
     * @return int|int[]
     *
     * @throws Exception
     */
    abstract public function getTopClicks(
        ?int $n = null,
        ?DateTime $from = null,
        ?DateTime $to = null,
        ?string $userId = null,
        ?string $platform = null,
        string $appId = null,
        string $index = null,
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
     * @param string|null   $count
     * @param string|null   $appId
     * @param string|null   $index
     * @param Token|null    $token
     *
     * @return int|int[]
     *
     * @throws Exception
     */
    abstract public function getSearches(
        bool $perDay,
        ?DateTime $from = null,
        ?DateTime $to = null,
        ?string $userId = null,
        ?string $platform = null,
        bool $excludeWithResults = false,
        bool $excludeWithoutResults = false,
        ?string $count = null,
        string $appId = null,
        string $index = null,
        Token $token = null
    );

    /**
     * @param int|null
     * @param DateTime|null $from
     * @param DateTime|null $to
     * @param string|null   $platform
     * @param string|null   $userId
     * @param bool          $excludeWithResults
     * @param bool          $excludeWithoutResults
     * @param string|null   $appId
     * @param string|null   $index
     * @param Token|null    $token
     *
     * @return array
     *
     * @throws Exception
     */
    abstract public function getTopSearches(
        ?int $n = null,
        ?DateTime $from = null,
        ?DateTime $to = null,
        ?string $platform = null,
        ?string $userId = null,
        bool $excludeWithResults = false,
        bool $excludeWithoutResults = false,
        string $appId = null,
        string $index = null,
        Token $token = null
    ): array;

    /**
     * Ping.
     *
     * @param Token|null $token
     *
     * @return bool
     *
     * @throws Exception
     */
    abstract public function ping(Token $token = null): bool;

    /**
     * Check health.
     *
     * @param Token|null $token
     *
     * @return array
     *
     * @throws Exception
     */
    abstract public function checkHealth(Token $token = null): array;

    /**
     * Create token by id and app_id.
     *
     * @param string|null $tokenId
     * @param string|null $appId
     * @param int|null    $ttl
     *
     * @return Token
     */
    protected function createTokenByIdAndAppId(
        string $tokenId,
        string $appId = null,
        int $ttl = null
    ): Token {
        return new Token(
            TokenUUID::createById($tokenId),
            AppUUID::createById($appId ?? self::$appId),
            [], [], [], $ttl ?? 0
        );
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
     * @param string|null $appId
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
     * @param $event
     *
     * @throws Exception
     */
    protected function dispatchImperative($event): void
    {
        static::await(
            $this->get('drift.event_bus.test')->dispatch($event)
        );
        static::usleep(10000);
    }

    /**
     * Create import file.
     *
     * @param int $n
     *
     * @return void
     */
    protected function createImportFile(int $n): void
    {
        if (\file_exists("/tmp/dump.$n.apisearch")) {
            \unlink("/tmp/dump.$n.apisearch");
        }

        $data = 'uid|type|title|link|image|categories|attributes'.PHP_EOL;
        $row = 'album|Julie & Carol at Lincoln Center|http://www.allmusic.com/album/julie-carol-at-lincoln-center-mw0000270036|http://cdn-s3.allmusic.com/release-covers/500/0001/149/0001149773.jpg|id##MA0000004432~~name##Stage & Screen~~slug##MA0000004432 && id##MA0000011877~~name##Vocal~~slug##MA0000011877|[in]rating=3 %% [in]year=1989 %% [i]auther=id##julie-andrews-mn0000314113~~name##Julie Andrews~~slug##julie-andrews-mn0000314113~~img##http://cps-static.rovicorp.com/3/JPG_400/MI0001/400/MI0001400285.jpg?partner=allrovi.com'.PHP_EOL;
        for ($i = 0; $i < $n; ++$i) {
            $data .= 'mw0000'.$i.'|'.$row;
        }

        \file_put_contents("/tmp/dump.$n.apisearch", $data);
    }

    /**
     * Load massive index items.
     *
     * @param int $n
     *
     * @return void
     */
    protected function loadMassiveIndexItems(int $n): void
    {
        $ri = $rj = \intval(\sqrt($n));

        for ($i = 0; $i < $ri; ++$i) {
            $items = [];
            for ($j = 0; $j < $rj; ++$j) {
                $id = $i.'a'.$j;
                $items[] = Item::createFromArray([
                    'uuid' => [
                        'id' => $id,
                        'type' => 'type1',
                    ],
                    'metadata' => [
                        'title' => 'value',
                        'title2' => 'value2',
                        'title3' => 'value3',
                        'title4' => 'value4',
                        'title5' => 'value5',
                        'title6' => 'value6',
                        'title7' => 'value7',
                    ],
                ]);
            }

            static::indexItems($items);
        }
    }
}
