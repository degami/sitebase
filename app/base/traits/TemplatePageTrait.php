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


trait TemplatePageTrait
{
    /**
     * @var array template data
     */
    protected array $template_data = [];

    /**
     * @var string|null locale
     */
    protected ?string $locale = null;
}