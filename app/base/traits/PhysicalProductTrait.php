<?php

/**
 * SiteBase
 * PHP Version 8.3
 *
 * @category CMS / Framework
 * @package  Degami\Sitebase
 * @author   Mirko De Grandis <degami@github.com>
 * @license  MIT https://opensource.org/licenses/mit-license.php
 * @link     https://github.com/degami/sitebase
 */

namespace App\Base\Traits;

use App\Base\Interfaces\Model\PhysicalProductInterface;
use RuntimeException;
use App\Base\GraphQl\GraphQLExport;

trait PhysicalProductTrait
{
    public function getVolumetricWeight(float $divisor = 5000): float
    {
        if (!($this instanceof PhysicalProductInterface)) {
            throw new RuntimeException("Current object is not an instance of PhysicalProductInterface");
        }

        $L = $this->getLength() ?: 0;
        $W = $this->getWidth() ?: 0;
        $H = $this->getHeight() ?: 0;
        return ($L * $W * $H) / $divisor;
    }

    #[GraphQLExport]
    public function getWeight(): float
    {
        if (!($this instanceof PhysicalProductInterface)) {
            throw new RuntimeException("Current object is not an instance of PhysicalProductInterface");
        }

        return $this->getData('weight');
    }

    #[GraphQLExport]
    public function getLength(): ?float
    {
        if (!($this instanceof PhysicalProductInterface)) {
            throw new RuntimeException("Current object is not an instance of PhysicalProductInterface");
        }

        return $this->getData('length');
    }

    #[GraphQLExport]
    public function getWidth(): ?float
    {
        if (!($this instanceof PhysicalProductInterface)) {
            throw new RuntimeException("Current object is not an instance of PhysicalProductInterface");
        }

        return $this->getData('width');
    }

    #[GraphQLExport]
    public function getHeight(): ?float
    {
        if (!($this instanceof PhysicalProductInterface)) {
            throw new RuntimeException("Current object is not an instance of PhysicalProductInterface");
        }

        return $this->getData('height');
    }
}