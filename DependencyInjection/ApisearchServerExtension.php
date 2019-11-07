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

namespace Apisearch\Server\DependencyInjection;

use Mmoreram\BaseBundle\DependencyInjection\BaseExtension;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Class ApisearchServerExtension.
 */
class ApisearchServerExtension extends BaseExtension
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
        return 'apisearch_server';
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
            'controllers',
            'console',
            'reactphp',
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
        $domainEventsAdapter = Env::get('APISEARCH_DOMAIN_EVENTS_ADAPTER', $config['domain_events_adapter']);
        $commandsAdapter = Env::get('APISEARCH_COMMANDS_ADAPTER', $config['commands_adapter']);

        return [
            'apisearch_server.environment' => Env::get('APISEARCH_ENV', $config['environment']),
            'apisearch_server.event_publisher_service' => [
                'inline' => 'apisearch_server.inline_event_publisher',
                'enqueue' => 'apisearch_server.enqueue_event_publisher',
                'ignore' => 'apisearch_server.ignore_event_publisher',
            ][$domainEventsAdapter],
            'apisearch_server.command_bus_service' => [
                'inline' => 'apisearch_server.command_bus.inline',
                'enqueue' => 'apisearch_server.command_bus.asynchronous',
            ][$commandsAdapter],

            'apisearch_server.god_token' => Env::get('APISEARCH_GOD_TOKEN', $config['god_token']),
            'apisearch_server.readonly_token' => Env::get('APISEARCH_READONLY_TOKEN', $config['readonly_token']),
            'apisearch_server.ping_token' => Env::get('APISEARCH_PING_TOKEN', $config['ping_token']),

            /*
             * Limitations
             */
            'apisearch_server.limitations_number_of_results' => $config['limitations']['number_of_results'],
            'apisearch_server.commands_are_asynchronous' => 'enqueue' === $commandsAdapter,
        ];
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
        return new ApisearchServerConfiguration($this->getAlias());
    }
}
