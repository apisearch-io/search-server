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

namespace Apisearch\Plugin\Testing\Console;

use Apisearch\Model\ItemUUID;
use Apisearch\Plugin\DBAL\Domain\InteractionRepository\DBALInteractionRepository;
use Apisearch\Plugin\DBAL\Domain\SearchesRepository\DBALSearchesRepository;
use Apisearch\Plugin\DBAL\Domain\UsageRepository\DBALUsageRepository;
use Apisearch\Repository\RepositoryReference;
use Apisearch\Server\Domain\Model\Origin;
use function Clue\React\Block\awaitAll;
use React\EventLoop\LoopInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class GenerateMetricsCommand.
 */
class GenerateMetricsCommand extends Command
{
    protected static $defaultName = 'generator:metrics';
    private DBALSearchesRepository $searchesRepository;
    private DBALUsageRepository $usageRepository;
    private DBALInteractionRepository $interactionRepository;
    private LoopInterface $loop;

    /**
     * @param DBALSearchesRepository    $searchesRepository
     * @param DBALUsageRepository       $usageRepository
     * @param DBALInteractionRepository $interactionRepository
     * @param LoopInterface             $loop
     */
    public function __construct(
        DBALSearchesRepository $searchesRepository,
        DBALUsageRepository $usageRepository,
        DBALInteractionRepository $interactionRepository,
        LoopInterface $loop
    ) {
        parent::__construct();
        $this->searchesRepository = $searchesRepository;
        $this->usageRepository = $usageRepository;
        $this->interactionRepository = $interactionRepository;
        $this->loop = $loop;
    }

    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this
            ->addArgument('app', InputArgument::REQUIRED)
            ->addArgument('index', InputArgument::REQUIRED)
            ->addOption('days', '', InputOption::VALUE_OPTIONAL, '', 90)
            ->addOption('users', '', InputOption::VALUE_OPTIONAL, '', 100)
        ;
    }

    /**
     * Executes the current command.
     *
     * This method is not abstract because you can use this class
     * as a concrete class. In this case, instead of defining the
     * execute() method, you set the code to execute by passing
     * a Closure to the setCode() method.
     *
     * @return int 0 if everything went fine, or an exit code
     *
     * @throws LogicException When this abstract method is not implemented
     *
     * @see setCode()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $appId = $input->getArgument('app');
        $indexId = $input->getArgument('index');
        $days = $input->getOption('days');
        $users = $input->getOption('users');

        $from = (new \DateTime("$days days ago"));
        $fromAsString = (int) $from->format('Ymd');
        $to = (int) (new \DateTime('today'))->format('Ymd');

        while ($fromAsString <= $to) {
            $output->writeln('Generating day '.$fromAsString);
            $repositoryReference = RepositoryReference::createFromComposed("{$appId}_{$indexId}");
            $promises = [];

            for ($i = 0; $i < \rand(50, 100); ++$i) {
                $promises[] = $this->searchesRepository->registerSearch(
                    $repositoryReference,
                    'user_'.\rand(1, $users),
                    $this->generateSearch(),
                    \rand(0, 10),
                    $this->generateOrigin(),
                    $from
                );
            }

            for ($i = 0; $i < \rand(100, 200); ++$i) {
                $promises[] = $this->interactionRepository->registerInteraction(
                    $repositoryReference,
                    'user_'.\rand(1, $users),
                    ItemUUID::createByComposedUUID('item~'.\rand(1, 500)),
                    \rand(1, 10),
                    $this->generateOrigin(),
                    $this->generateType(),
                    $from
                );
            }

            for ($i = 0; $i < \rand(300, 500); ++$i) {
                $promises[] = $this->usageRepository->registerEvent(
                    $repositoryReference,
                    $this->generateEvent(),
                    $from,
                    \rand(1, 30)
                );
            }

            awaitAll($promises, $this->loop);
            $from = clone $from;
            $from = $from->modify('+1 day');
            $fromAsString = (int) $from->format('Ymd');
        }

        return 0;
    }

    /**
     * @return string
     */
    private function generateSearch(): string
    {
        $array = [
            'search1',
            'search2',
            'search3',
            'search4',
            'search5',
            'search6',
            'search7',
            'search8',
            'search9',
        ];

        $key = \array_rand($array);

        return $array[$key];
    }

    /**
     * @return string
     */
    private function generateType(): string
    {
        $array = ['cli'];
        $key = \array_rand($array);

        return $array[$key];
    }

    /**
     * @return string
     */
    private function generateEvent(): string
    {
        $array = ['query', 'admin'];
        $key = \array_rand($array);

        return $array[$key];
    }

    /**
     * @return Origin
     */
    private function generateOrigin(): Origin
    {
        $platforms = [Origin::DESKTOP, Origin::PHONE, Origin::TABLET];
        $platformKey = \array_rand($platforms);

        $hosts = ['https://localhost.com', 'https://anotherhost.com', 'https://yetanother.com'];
        $hostKey = \array_rand($hosts);

        return new Origin(
            $hosts[$hostKey],
            '127.0.0.'.\rand(1, 255),
            $platforms[$platformKey]
        );
    }
}
