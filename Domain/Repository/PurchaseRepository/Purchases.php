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

namespace Apisearch\Server\Domain\Repository\PurchaseRepository;

/**
 * Class Purchases.
 */
class Purchases
{
    /**
     * @var Purchase[]
     */
    private array $purchases = [];

    /**
     * @param Purchase $purchase
     *
     * @return $this
     */
    public function withPurchase(Purchase $purchase): self
    {
        $this->purchases[] = $purchase;

        return $this;
    }

    /**
     * @return array
     */
    public function getPurchases(): array
    {
        return $this->purchases;
    }
}
