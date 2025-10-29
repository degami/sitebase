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

namespace App\Base\Abstracts\Commerce;

use App\App;
use App\Base\Interfaces\Commerce\ShippingMethodInterface;
use App\Base\Models\Cart;

abstract class BaseShippingMethod implements ShippingMethodInterface
{
    /**
     * {@inheritdoc}
     */
    public function isActive(Cart $cart): bool
    {
        return App::getInstance()->getSiteData()->getConfigValue('shipping/' . $this->getCode() . '/active') == true;
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(Cart $cart): bool
    {
        return $cart->requireShipping();
    }

    /**
     * {@inheritdoc}
     */
    public function showEvenIfNotCheapest() : bool
    {
        return false;
    }
}