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

namespace Apisearch\Plugin\DBAL\DependencyInjection;

use Apisearch\Server\DependencyInjection\Env;
use Mmoreram\BaseBundle\DependencyInjection\BaseExtension;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Class DBALPluginExtension.
 */
class DBALPluginExtension extends BaseExtension
{
    /**
     * Returns the recommended alias to use in XML.
     *
     * This alias is also the mandatory prefix to use when using YAML.
     *
     * @return string The alias
     */
    public function getAlias()
    {
        return 'apisearch_plugin_dbal';
    }

    /**
     * Return a new Configuration instance.
     *
     * If object returned by this method is an instance of
     * ConfigurationInterface, extension will use the Configuration to read all
     * bundle config definitions.
     *
     * Also will call getParametrizationValues method to load some config values
     * to internal parameters.
     *
     * @return ConfigurationInterface|null
     */
    protected function getConfigurationInstance(): ? ConfigurationInterface
    {
        return new DBALPluginConfiguration($this->getAlias());
    }

    /**
     * Get the Config file location.
     *
     * @return string
     */
    protected function getConfigFilesLocation(): string
    {
        return __DIR__.'/../Resources/config';
    }

    /**
     * Config files to load.
     *
     * Each array position can be a simple file name if must be loaded always,
     * or an array, with the filename in the first position, and a boolean in
     * the second one.
     *
     * As a parameter, this method receives all loaded configuration, to allow
     * setting this boolean value from a configuration value.
     *
     * return array(
     *      'file1.yml',
     *      'file2.yml',
     *      ['file3.yml', $config['my_boolean'],
     *      ...
     * );
     *
     * @param array $config Config definitions
     *
     * @return array Config files
     */
    protected function getConfigFiles(array $config): array
    {
        $encryptIsEnabled = \boolval(Env::get(
            'DBAL_ENCRYPT_ENABLED',
            $config['encrypt']['enabled'] ?? false
        ));

        return [
            'domain',
            ['openssl_encrypter', $encryptIsEnabled],
        ];
    }

    /**
     * Load Parametrization definition.
     *
     * return array(
     *      'parameter1' => $config['parameter1'],
     *      'parameter2' => $config['parameter2'],
     *      ...
     * );
     *
     * @param array $config Bundles config values
     *
     * @return array
     */
    protected function getParametrizationValues(array $config): array
    {
        $encryptIsEnabled = \boolval(Env::get(
            'DBAL_ENCRYPT_ENABLED',
            $config['encrypt']['enabled'] ?? false
        ));

        return [
            'apisearch_plugin.dbal.configuration' => [
                'driver' => Env::get(
                    'DBAL_DRIVER',
                    $config['driver'] ?? null,
                    true
                ),
                'host' => Env::get(
                    'DBAL_HOST',
                    $config['host'] ?? null,
                    true
                ),
                'port' => Env::get(
                    'DBAL_PORT',
                    $config['port'] ?? null,
                    true),
                'user' => Env::get(
                    'DBAL_USER',
                    $config['user'] ?? null,
                    true),
                'password' => Env::get(
                    'DBAL_PASSWORD',
                    $config['password'] ?? null,
                    true),
                'dbname' => Env::get(
                    'DBAL_DBNAME',
                    $config['dbname'] ?? null,
                    true),
            ],

            'apisearch_plugin.dbal.index_configs_table' => Env::get(
                'DBAL_INDEX_CONFIGS_TABLE',
                $config['index_configs_table'] ?? null,
                true
            ),
            'apisearch_plugin.dbal.tokens_table' => Env::get(
                'DBAL_TOKENS_TABLE',
                $config['tokens_table'] ?? null,
                true
            ),
            'apisearch_plugin.dbal.usage_lines_table' => Env::get(
                'DBAL_USAGE_LINES_TABLE',
                $config['usage_lines_table'] ?? null,
                true
            ),
            'apisearch_plugin.dbal.metadata_table' => Env::get(
                'DBAL_METADATA_TABLE',
                $config['metadata_table'] ?? null,
                true
            ),
            'apisearch_plugin.dbal.interactions_table' => Env::get(
                'DBAL_INTERACTIONS_TABLE',
                $config['interaction_table'] ?? null,
                true
            ),
            'apisearch_plugin.dbal.searches_table' => Env::get(
                'DBAL_SEARCHES_TABLE',
                $config['searches_table'] ?? null,
                true
            ),
            'apisearch_plugin.dbal.logs_table' => Env::get(
                'DBAL_LOGS_TABLE',
                $config['logs_table'] ?? null,
                true
            ),
            'apisearch_plugin.dbal.purchases_table' => Env::get(
                'DBAL_PURCHASES_TABLE',
                $config['purchases_table'] ?? null,
                true
            ),
            'apisearch_plugin.dbal.purchase_items_table' => Env::get(
                'DBAL_PURCHASE_ITEMS_TABLE',
                $config['purchase_items_table'] ?? null,
                true
            ),

            'apisearch_plugin.dbal.encrypt_private_key' => Env::get(
                'DBAL_ENCRYPT_PRIVATE_KEY',
                $config['encrypt']['private_key'] ?? null,
                $encryptIsEnabled
            ),
            'apisearch_plugin.dbal.encrypt_method' => Env::get(
                'DBAL_ENCRYPT_METHOD',
                $config['encrypt']['method']
            ),
            'apisearch_plugin.dbal.encrypt_iv' => Env::get(
                'DBAL_ENCRYPT_IV',
                $config['encrypt']['iv'] ?? null,
                $encryptIsEnabled
            ),

            'apisearch_plugin.dbal.loop_push_interval' => \floatval(Env::get(
                'DBAL_LOOP_PUSH_INTERVAL',
                $config['loop_push_interval']
            )),
            'apisearch_plugin.dbal.locator_enabled' => $config['locator_enabled'],
        ];
    }
}
