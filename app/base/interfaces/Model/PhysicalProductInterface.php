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

namespace App\Base\Interfaces\Model;

interface PhysicalProductInterface extends ProductInterface
{
    public function getWeight(): float; 
    public function getLength(): ?float;
    public function getWidth(): ?float;
    public function getHeight(): ?float;

    public function getVolumetricWeight(float $divisor = 5000): float;
}