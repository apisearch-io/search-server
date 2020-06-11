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

namespace Apisearch\Plugin\Admin\Domain;

use Apisearch\Server\Domain\ImperativeEvent\FlushInteractions;
use Apisearch\Server\Domain\ImperativeEvent\FlushSearches;
use Apisearch\Server\Domain\ImperativeEvent\FlushUsageLines;
use Apisearch\Server\Domain\ImperativeEvent\LoadAllMetadata;
use Apisearch\Server\Domain\ImperativeEvent\LoadConfigs;
use Apisearch\Server\Domain\ImperativeEvent\LoadTokens;

/**
 * Class ImperativeEvents.
 */
class ImperativeEvents
{
    /**
     * @var string[]
     */
    const ALIASES = [
        'load_configs' => LoadConfigs::class,
        'load_tokens' => LoadTokens::class,
        'load_all_metadata' => LoadAllMetadata::class,
        'flush_usage_lines' => FlushUsageLines::class,
        'flush_interactions' => FlushInteractions::class,
        'flush_searches' => FlushSearches::class,
    ];
}
