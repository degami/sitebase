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
 * Link to user area Block
 */
class LinkToUserArea extends BaseCodeBlock
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
//            $website = $this->containerCall([Website::class, 'load'], ['id' => $this->getSiteData()->getCurrentWebsiteId()]);

            if (!$this->getEnv('ENABLE_LOGGEDPAGES')) {
                return "";
            }

            if (in_array($current_page->getRouteInfo()->getRouteName(), ['frontend.users.twofa', 'frontend.users.login'])) {
                return "";
            }

            if (strpos($current_page->getRouteInfo()->getRouteName(), 'frontend.users') === 0) {
                return '';
            }

            $class = 'block user';

            $locale = $current_page->getRouteInfo()->getVar('locale');

            $targetHref = $this->getWebRouter()->getUrl("frontend.users.login");
            $linkText = $this->getUtils()->translate('Profile', locale: $locale);
            if ($current_page->hasLoggedUser()) {
                $current_user = $current_page->getCurrentUser();
                $targetHref = $this->getWebRouter()->getUrl("frontend.users.profile");
                $linkText = $this->getUtils()->translate('Hello', locale: $locale) . ' ' . $current_user?->getNickname() . ' !';
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
                        'text' => $this->getIcons()->get('user', echo: false) . ' ' .$linkText,
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
