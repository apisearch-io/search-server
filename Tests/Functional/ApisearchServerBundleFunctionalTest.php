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
use Apisearch\Plugin\Elastica\ElasticaPluginBundle;
use Apisearch\Query\Query as QueryModel;
use Apisearch\Result\Result;
use Apisearch\Server\ApisearchPluginsBundle;
use Apisearch\Server\ApisearchServerBundle;
use Apisearch\Server\Exception\ErrorException;
use Apisearch\User\Interaction;
use Mmoreram\BaseBundle\Kernel\DriftBaseKernel;
use Mmoreram\BaseBundle\Tests\BaseFunctionalTest;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Yaml\Yaml;

set_error_handler(function ($code, $message, $file, $line, $context) {
    if (0 == error_reporting()) {
        return;
    }

    throw new ErrorException($message, $code);
});

/**
 * Class ApisearchServerBundleFunctionalTest.
 */
abstract class ApisearchServerBundleFunctionalTest extends BaseFunctionalTest
{
    /**
     * @var string
     *
     * External server port
     */
    const HTTP_TEST_SERVICE_PORT = '8100';

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
            ['resource' => '@ApisearchServerBundle/Resources/config/tactician.yml'],
            [
                'resource' => '@ApisearchServerBundle/app_deploy.yml',
                'ignore_errors' => true,
            ],
            ['resource' => '@ApisearchServerBundle/Resources/test/subscribers.yml'],
            ['resource' => '@ApisearchServerBundle/Resources/test/reactphp.yml'],
        ];

        if (!static::logDomainEvents()) {
            $imports[] = ['resource' => '@ApisearchServerBundle/Resources/test/middlewares.yml'];
        }

        $bundles = [
            ApisearchServerBundle::class,
            ApisearchBundle::class,
            ElasticaPluginBundle::class,
            ApisearchPluginsBundle::class,
        ];

        $composedIndex = self::$index.','.self::$anotherIndex;
        $configuration = [
            'imports' => $imports,
            'parameters' => [
                'kernel.secret' => 'sdhjshjkds',
                'apisearch_plugin.elastica.version' => static::getElasticsearchVersion()
            ],
            'framework' => [
                'test' => true,
            ],
            'services' => [
                '_defaults' => [
                    'autowire' => false,
                    'autoconfigure' => false,
                    'public' => true,
                ],
            ],
            'apisearch_server' => [
                'domain_events_adapter' => static::saveEvents()
                    ? (
                        static::asynchronousEvents()
                            ? 'enqueue'
                            : 'inline'
                    )
                    : 'ignore',
                'commands_adapter' => static::asynchronousCommands()
                    ? 'enqueue'
                    : 'inline',
                'god_token' => self::$godToken,
                'ping_token' => self::$pingToken,
                'readonly_token' => self::$readonlyToken,
            ],
            'elastica_plugin' => [
                'cluster' => [
                    'localhost' => static::getElasticsearchEndpoint(),
                ],
                'version' => static::getElasticsearchVersion(),
                'refresh_on_write' => true,
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
                    'search_socket' => [
                        'adapter' => 'http',
                        'endpoint' => 'http://127.0.0.1:'.self::HTTP_TEST_SERVICE_PORT,
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
                '@ApisearchServerBundle/Resources/config/routing.yml',
            ]),
            'prod', false
        );
    }

    /**
     * Log domain events.
     *
     * @return bool
     */
    protected static function logDomainEvents(): bool
    {
        return true;
    }

    /**
     * Use asynchronous commands.
     *
     * @return bool
     */
    protected static function asynchronousCommands(): bool
    {
        return false;
    }

    /**
     * Save events.
     *
     * @return bool
     */
    protected static function saveEvents(): bool
    {
        return true;
    }

    /**
     * Save asynchronous events.
     *
     * @return bool
     */
    protected static function asynchronousEvents(): bool
    {
        return false;
    }

    /**
     * Save events.
     *
     * @return bool
     */
    protected static function tokensInRedis(): bool
    {
        return true;
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
     * Time to wait after write command.
     */
    protected static function waitAfterWriteCommand()
    {
    }

    /**
     * Get elasticsearch endpoint.
     *
     * @return array
     */
    protected static function getElasticsearchEndpoint(): array
    {
        return [
            'host' => $_ENV['ELASTICSEARCH_HOST'],
            'port' => $_ENV['ELASTICSEARCH_PORT'],
        ];
    }

    /**
     * Get elasticsearch version.
     *
     * @return string
     */
    protected static function getElasticsearchVersion(): string
    {
        return '7';
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
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        static::configureEnvironment();
        static::resetScenario();
    }

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    public static function tearDownAfterClass()
    {
        static::deleteEverything();
        static::cleanEnvironment();
    }

    /**
     * Reset scenario.
     */
    public static function resetScenario()
    {
        static::deleteEverything();

        static::createIndex(self::$appId);
        static::deleteTokens(self::$appId);

        static::createIndex(self::$anotherAppId);
        static::deleteTokens(self::$anotherAppId);

        static::indexTestingItems();
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
        $items = Yaml::parse(file_get_contents(static::getItemsFilePath()));
        $itemsInstances = [];
        foreach ($items['items'] as $item) {
            if (isset($item['indexed_metadata']['created_at'])) {
                $date = new \DateTime($item['indexed_metadata']['created_at']);
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
        static::deleteAppIdIndices(self::$appId);
        static::deleteAppIdIndices(self::$anotherAppId);
    }

    /**
     * Delete index and catch.
     *
     * @param string $appId
     */
    private static function deleteAppIdIndices(string $appId)
    {
        try {
            static::deleteIndex($appId);
        } catch (ResourceNotAvailableException $e) {
        }
    }

    /**
     * Query using the bus.
     *
     * @param QueryModel $query
     * @param string     $appId
     * @param string     $index
     * @param Token      $token
     * @param array      $parameters
     *
     * @return Result
     */
    abstract public function query(
        QueryModel $query,
        string $appId = null,
        string $index = null,
        Token $token = null,
        array $parameters = []
    ): Result;

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
     * @param string $appId
     * @param string $index
     * @param Token  $token
     */
    abstract public function configureIndex(
        Config $config,
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
     * Add token.
     *
     * @param Token  $newToken
     * @param string $appId
     * @param Token  $token
     */
    abstract public static function addToken(
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
     * Add interaction.
     *
     * @param Interaction $interaction
     * @param string      $appId
     * @param Token       $token
     */
    abstract public function addInteraction(
        Interaction $interaction,
        string $appId = null,
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
     * Pause consumers.
     *
     * @param string[] $types
     */
    abstract public function pauseConsumers(array $types);

    /**
     * Resume consumers.
     *
     * @param string[] $types
     */
    abstract public function resumeConsumers(array $types);

    /**
     * Get token id.
     *
     * @param Token $token
     *
     * @return string
     */
    protected static function getTokenId(Token $token = null): string
    {
        return ($token instanceof Token)
                ? $token->getTokenUUID()->composeUUID()
                : self::getParameterStatic('apisearch_server.god_token');
    }

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
            echo sprintf('[ %s ] - %f', $item->composeUUID(), $item->getScore()).PHP_EOL;
        }
        echo PHP_EOL;
    }
}
