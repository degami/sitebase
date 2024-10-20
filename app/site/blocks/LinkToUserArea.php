<?php

/**
 * SiteBase
 * PHP Version 8.0
 *
 * @category CMS / Framework
 * @package  Degami\Sitebase
 * @author   Mirko De Grandis <degami@github.com>
 * @license  MIT https://opensource.org/licenses/mit-license.php
 * @link     https://github.com/degami/sitebase
 */

namespace App\Site\Blocks;

use App\Base\Abstracts\Blocks\BaseCodeBlock;
use App\Base\Abstracts\Controllers\BasePage;
use Degami\Basics\Html\TagElement;
use App\Site\Models\Website;
use Exception;

/**
 * Link to user area Block
 */
class LinkToUserArea extends BaseCodeBlock
{
    /**
     * {@inheritdocs}
     *
     * @param BasePage|null $current_page
     * @return string
     */
    public function renderHTML(BasePage $current_page = null): string
    {
        try {
//            $website = $this->containerCall([Website::class, 'load'], ['id' => $this->getSiteData()->getCurrentWebsiteId()]);

            $class = 'block user';

            $locale = $current_page->getRouteInfo()->getVar('locale');

            $targetHref = $this->getWebRouter()->getUrl("frontend.users.profile");
            if (!$current_page->hasLoggedUser()) {
                $targetHref = $this->getWebRouter()->getUrl("frontend.users.login");
            }
            

            return $this->containerMake(TagElement::class, ['options' => [
                'tag' => 'div',
                'attributes' => [
                    'class' => $class,
                ],
                'children' => [
                    $this->containerMake(TagElement::class, ['options' => [
                        'tag' => 'a',
                        'attributes' => [
                            'class' => 'user-area-link',
                            'href' => $targetHref, 
                        ],
                        'text' => $this->getIcons()->get('user', echo: false) . ' ' .$this->getUtils()->translate('Profile', locale: $locale),
                    ]]),
                ],
            ]]);
        } catch (Exception $e) {
        }
        return "";
    }

    public function isCachable() : bool
    {
        return false;
    }
}
