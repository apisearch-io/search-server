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

namespace Apisearch\Plugin\DBAL\Tests\Functional;

use Apisearch\Server\Tests\Functional\Domain\Repository\UsageRepository\ServiceUsageRepositoryTest;

/**
 * Class DBALUsageRepositoryTest.
 */
class DBALUsageRepositoryTest extends ServiceUsageRepositoryTest
{
    use DBALFunctionalTestTrait;
}
