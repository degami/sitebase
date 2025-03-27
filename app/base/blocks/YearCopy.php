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

namespace App\Base\Blocks;

use App\Base\Abstracts\Blocks\BaseCodeBlock;
use App\Base\Abstracts\Controllers\BasePage;
use Degami\Basics\Html\TagElement;
use App\Base\Models\Website;
use Exception;

/**
 * Year&Copy Block
 */
class YearCopy extends BaseCodeBlock
{
    /**
     * {@inheritdoc}
     *
     * @param BasePage|null $current_page
     * @param array $data
     * @return string
     */
    public function renderHTML(?BasePage $current_page = null, array $data = []): string
    {
        try {
            $website = $this->containerCall([Website::class, 'load'], ['id' => $this->getSiteData()->getCurrentWebsiteId()]);

            $class = 'block copy';

            return $this->containerMake(TagElement::class, ['options' => [
                'tag' => 'div',
                'attributes' => [
                    'class' => $class,
                ],
                'text' => $website->getSiteName() . " &copy; - " . date('Y'),
            ]]);
        } catch (Exception $e) {
        }
        return "";
    }
}
