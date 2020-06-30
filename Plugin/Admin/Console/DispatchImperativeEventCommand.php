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

use Apisearch\Plugin\Admin\Domain\ImperativeEvents;
use Apisearch\Server\Console\ApisearchServerCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class SendImperativeEventCommand.
 */
class DispatchImperativeEventCommand extends ApisearchServerCommand
{
    /**
     * @var string
     */
    protected static $defaultName = 'apisearch-server:dispatch-imperative-event';

    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this
            ->setDescription('Dispatch imperative events. Available events: load_configs, load_tokens')
            ->addOption('event', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL, 'Events');
    }

    /**
     * {@inheritdoc}
     */
    protected function runCommand(InputInterface $input, OutputInterface $output)
    {
        $events = $input->getOption('event');

        foreach ($events as $eventName) {
            if (!\array_key_exists($eventName, ImperativeEvents::ALIASES)) {
                static::printMessageFail($output, 'EventBus', "Event $eventName not found");
                continue;
            }

            $event = ImperativeEvents::ALIASES[$eventName];
            $this->dispatchAndWait(new $event());
            static::printMessage($output, 'EventBus', "Event $eventName dispatched");
        }
    }
}
