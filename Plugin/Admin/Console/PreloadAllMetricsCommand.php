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

namespace Apisearch\Plugin\Admin\Console;

use Apisearch\Plugin\Admin\Domain\Command\PreloadAllMetrics;
use Apisearch\Server\Console\CommandWithCommandAndEventBusAndGodToken;
use Apisearch\Server\Domain\ImperativeEvent\LoadAllMetadata;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class PreloadAllMetricsCommand.
 */
class PreloadAllMetricsCommand extends CommandWithCommandAndEventBusAndGodToken
{
    const NAME = 'apisearch-server:preload-all-metrics';

    /**
     * @var string
     */
    protected static $defaultName = self::NAME;

    /**
     * Configures the current command.
     *
     * @return void
     */
    protected function configure()
    {
        $this->setDescription('Preload all metrics');
    }

    /**
     * {@inheritdoc}
     */
    protected function runCommand(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Preloaded all');
        $this->executeAndWait(new PreloadAllMetrics());
        $this->dispatchAndWait(new LoadAllMetadata());

        return 0;
    }
}
