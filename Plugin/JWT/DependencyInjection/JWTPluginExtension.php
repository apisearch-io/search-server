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

namespace Apisearch\Plugin\JWT\DependencyInjection;

use Apisearch\Server\DependencyInjection\Env;
use Mmoreram\BaseBundle\DependencyInjection\BaseExtension;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Class LogstashPluginExtension.
 */
class JWTPluginExtension extends BaseExtension
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
        return 'apisearch_plugin_jwt';
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
            'apisearch_plugin.jwt.private_key' => \strval(Env::get(
                'JWT_PRIVATE_KEY',
                $config['private_key'] ?? null,
                true
            )),
            'apisearch_plugin.jwt.allowed_algorithms' => \array_map('trim',
                \explode(',', Env::get(
                    'JWT_ALLOWED_ALGORITHMS',
                    $config['allowed_algorithms'] ?? null,
                    true
                ))
            ),
            'apisearch_plugin.jwt.ttl' => \intval(Env::get(
                'JWT_PRIVATE_TTL',
                $config['ttl'] ?? null,
                true
            )),
            'apisearch_plugin.jwt.endpoints' => $config['endpoints'] ?? [],
            'apisearch_plugin.jwt.filters' => $config['filters'] ?? [],
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
        return new JWTPluginConfiguration($this->getAlias());
    }
}
