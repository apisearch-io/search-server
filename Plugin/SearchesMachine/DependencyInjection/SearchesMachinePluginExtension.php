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

namespace Apisearch\Plugin\SearchesMachine\DependencyInjection;

use Apisearch\Server\DependencyInjection\Env;
use Mmoreram\BaseBundle\DependencyInjection\BaseExtension;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Class SearchesMachinePluginExtension.
 */
class SearchesMachinePluginExtension extends BaseExtension
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
        return 'apisearch_plugin_searches_machine';
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
        return new SearchesMachinePluginConfiguration($this->getAlias());
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
        return [
            'domain',
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
        return [
            'apisearch_plugin.searches_machine.redis_configuration' => [
                'host' => Env::get('REDIS_HOST', $config['redis_host'] ?? null, true),
                'port' => Env::get('REDIS_PORT', $config['redis_port'], false),
                'database' => Env::get('REDIS_DATABASE', $config['redis_database'] ?? null, false),
                'password' => Env::get('REDIS_PASSWORD', $config['redis_password'] ?? null, false),
                'preload' => true,
            ],
            'apisearch_plugin.searches_machine.redis_key' => Env::get(
                'SEARCHES_MACHINE_REDIS_KEY',
                $config['redis_key']
            ),
            'apisearch_plugin.searches_machine.minutes_interval_between_processing' => Env::get(
                'SEARCHES_MACHINE_MINUTES_INTERVAL_BETWEEN_PROCESSING',
                $config['minutes_interval_between_processing']
            ),
        ];
    }
}
