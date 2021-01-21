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

namespace Apisearch\Server\Console;

use Apisearch\Command\ApisearchFormattedCommand;
use Apisearch\Server\Domain\Plugin\Plugin;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Class ServerConfigurationCommand.
 */
class ServerConfigurationCommand extends ApisearchFormattedCommand
{
    /**
     * @var string
     */
    protected static $defaultName = 'apisearch-server:server-configuration';

    /**
     * @var KernelInterface
     *
     * Kernel
     */
    private $kernel;

    /**
     * Kernel.
     *
     * @param KernelInterface $kernel
     */
    public function __construct(KernelInterface $kernel)
    {
        parent::__construct();

        $this->kernel = $kernel;
    }

    /**
     * Configures the current command.
     *
     * @return void
     */
    protected function configure()
    {
        $this->setDescription('Print server configuration');
    }

    /**
     * Dispatch domain event.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return mixed|null
     */
    protected function runCommand(InputInterface $input, OutputInterface $output)
    {
        self::printApisearchServer($output);
        self::printMessage($output, '##', 'Server started');
        self::printInfoMessage($output, '##', ' ~~ with');
        self::printInfoMessage($output, '##', \sprintf(' ~~ --env = %s', $this->kernel->getEnvironment()));
        self::printInfoMessage($output, '##', '');
        self::printInfoMessage($output, '##', '$_SERVER values');
        self::printStringsArray($output, $_SERVER);
        self::printInfoMessage($output, '##', '');
        self::printInfoMessage($output, '##', 'Loaded plugins');

        $enabledPlugins = \array_filter($this->kernel->getBundles(), function (BundleInterface $bundle) {
            return $bundle instanceof Plugin;
        });

        $enabledPluginsName = \array_map(function (Plugin $plugin) {
            return $plugin->getPluginName();
        }, $enabledPlugins);

        foreach ($enabledPluginsName as $enabledPluginName) {
            self::printInfoMessage($output, '##', \sprintf(' ~~ %s', $enabledPluginName));
        }
        self::printSystemMessage($output, '##', '');
    }

    /**
     * Dispatch domain event.
     *
     * @return string
     */
    protected static function getHeader(): string
    {
        return 'Server';
    }

    /**
     * Get success message.
     *
     * @param InputInterface $input
     * @param mixed          $result
     *
     * @return string
     */
    protected static function getSuccessMessage(
        InputInterface $input,
        $result
    ): string {
        return '';
    }

    /**
     * @param OutputInterface $output
     *
     * @return void
     */
    private static function printApisearchServer(OutputInterface $output): void
    {
        $logo = '
     _____                                            _
    (  _  )        _                                 ( )
    | (_) | _ _   (_)  ___    __     _ _  _ __   ___ | |__
    |  _  |( \'_`\ | |/\',__) /\'__`\ /\'_` )( \'__)/\'___)|  _ `\
    | | | || (_) )| |\__, \(  ___/( (_| || |  ( (___ | | | |
    (_) (_)| ,__/\'(_)(____/`\____)`\__,_)(_)  `\____)(_) (_)
           | |
           (_)
        ';

        $output->writeln($logo);
    }

    /**
     * Print array string values.
     *
     * @param OutputInterface $output
     * @param array           $array
     *
     * @return void
     */
    private static function printStringsArray(
        OutputInterface $output,
        array $array
    ): void {
        \ksort($array);
        foreach ($array as $item => $value) {
            if (!\is_string($value)) {
                continue;
            }

            self::printInfoMessage($output, '##', \sprintf(' ~~ %s = %s', $item, $value));
        }
    }
}
