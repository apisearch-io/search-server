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

use Apisearch\Server\Domain\Model\EndpointNormalizer;
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
            'services',
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
        $asyncEventsAreEnabled = \boolval(Env::get(
            'APISEARCH_ASYNC_EVENTS_ENABLED',
            $config['async_events']['enabled']
        ));

        return [
            'apisearch_server.environment' => Env::get(
                'APISEARCH_ENV',
                $config['environment']
            ),

            'apisearch_server.god_token' => Env::get(
                'APISEARCH_GOD_TOKEN',
                $config['god_token']
            ),
            'apisearch_server.readonly_token' => Env::get(
                'APISEARCH_READONLY_TOKEN',
                $config['readonly_token']
            ),
            'apisearch_server.health_check_token' => Env::get(
                'APISEARCH_HEALTH_CHECK_TOKEN',
                $config['health_check_token']
            ),
            'apisearch_server.ping_token' => Env::get(
                'APISEARCH_PING_TOKEN',
                $config['ping_token']
            ),

            /*
             * Async events / AMQP
             */
            'apisearch_server.async_events_enabled' => $asyncEventsAreEnabled,
            'apisearch_server.async_events_exchange_name' => Env::get(
                'APISEARCH_EVENTS_EXCHANGE',
                $config['async_events']['events_exchange']
            ),
            'apisearch_server.async_events_amqp_host' => Env::get(
                'AMQP_HOST',
                $config['async_events']['amqp']['host'],
                $asyncEventsAreEnabled
            ),
            'apisearch_server.async_events_amqp_port' => Env::get(
                'AMQP_PORT',
                $config['async_events']['amqp']['port']
            ),
            'apisearch_server.async_events_amqp_user' => Env::get(
                'AMQP_USER',
                $config['async_events']['amqp']['user']
            ),
            'apisearch_server.async_events_amqp_password' => Env::get(
                'AMQP_PASSWORD',
                $config['async_events']['amqp']['password']
            ),
            'apisearch_server.async_events_amqp_vhost' => Env::get(
                'AMQP_VHOST',
                $config['async_events']['amqp']['vhost']
            ),

            /*
             * Limitations
             */
            'apisearch_server.limitations_number_of_results' => Env::get(
                'APISEARCH_NUMBER_OF_RESULTS_LIMITATION',
                $config['limitations']['number_of_results']
            ),
            'apisearch_server.limitations_number_of_logs_per_page' => Env::get(
                'APISEARCH_NUMBER_OF_LOGS_PER_PAGE_LIMITATION',
                $config['limitations']['number_of_logs_per_page']
            ),
            'apisearch_server.limitations_token_endpoint_permissions' => EndpointNormalizer::normalizeEndpoints(Env::getArray(
                'APISEARCH_TOKEN_ENDPOINT_PERMISSIONS_LIMITATION',
                $config['limitations']['token_endpoint_permissions']
            )),
            'apisearch_server.default_number_of_suggestions' => Env::get(
                'APISEARCH_NUMBER_OF_SUGGESTIONS_DEFAULT',
                $config['defaults']['number_of_suggestions']
            ),
            'apisearch_server.number_of_bulk_items_in_a_request' => Env::get(
                'APISEARCH_NUMBER_OF_BULK_ITEMS_IN_A_REQUEST',
                $config['defaults']['number_of_bulk_items_in_a_request']
            ),

            /*
             * Repositories
             */
            'apisearch_server.tokens_repository_enabled' => Env::get(
                'APISEARCH_TOKENS_REPOSITORY_ENABLED',
                $config['repositories']['tokens_repository_enabled']
            ),
            'apisearch_server.interactions_repository_enabled' => Env::get(
                'APISEARCH_INTERACTIONS_REPOSITORY_ENABLED',
                $config['repositories']['interactions_repository_enabled']
            ),
            'apisearch_server.searches_repository_enabled' => Env::get(
                'APISEARCH_SEARCHES_REPOSITORY_ENABLED',
                $config['repositories']['searches_repository_enabled']
            ),
            'apisearch_server.usage_lines_repository_enabled' => Env::get(
                'APISEARCH_USAGE_LINES_REPOSITORY_ENABLED',
                $config['repositories']['usage_lines_repository_enabled']
            ),
            'apisearch_server.logs_repository_enabled' => Env::get(
                'APISEARCH_LOGS_REPOSITORY_ENABLED',
                $config['repositories']['logs_repository_enabled']
            ),
            'apisearch_server.metadata_disk_repository_path' => $config['repositories']['metadata_disk_path'],
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
