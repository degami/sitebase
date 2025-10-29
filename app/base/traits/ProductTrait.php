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

use App\Base\Interfaces\Model\ProductInterface;
use RuntimeException;
use App\Base\GraphQl\GraphQLExport;

trait ProductTrait
{
    #[GraphQLExport]
    public function getId(): int
    {
        if (!($this instanceof ProductInterface)) {
            throw new RuntimeException("Current object is not an instance of ProductInterface");
        }

        return $this->getData('id');
    }

    #[GraphQLExport]
    public function getPrice(): float
    {
        if (!($this instanceof ProductInterface)) {
            throw new RuntimeException("Current object is not an instance of ProductInterface");
        }

        return $this->getData('price') ?? 0.0;
    }

    #[GraphQLExport]
    public function getTaxClassId(): ?int
    {
        if (!($this instanceof ProductInterface)) {
            throw new RuntimeException("Current object is not an instance of ProductInterface");
        }

        return $this->getData('tax_class_id');
    }

    #[GraphQLExport]
    public function getName() : ?string
    {
        return $this->getData('title');
    }
}