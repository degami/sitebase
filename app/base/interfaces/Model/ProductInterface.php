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

interface ProductInterface
{
    public function getId(): int;

    public function getSku() : string;

    public function getName() : ?string;

    public function getPrice() : float;

    public function getTaxClassId() : ?int;

    public function isPhysical() : bool;

    public function getFrontendUrl(): string;
}