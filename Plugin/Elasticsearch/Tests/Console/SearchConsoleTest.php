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

namespace Apisearch\Plugin\Elasticsearch\Tests\Console;

use Apisearch\Plugin\Elasticsearch\Tests\ElasticFunctionalTestTrait;
use Apisearch\Server\Tests\Functional\Console\SearchConsoleTest as BaseSearchConsoleTest;

/**
 * Class SearchConsoleTest.
 */
class SearchConsoleTest extends BaseSearchConsoleTest
{
    use ElasticFunctionalTestTrait;
}
