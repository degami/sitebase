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

use App\Base\Abstracts\ContainerAwareObject;
use App\Base\Abstracts\Controllers\FrontendPage;
use App\Site\Controllers\Frontend\Cms\Page;
use App\Site\Models\Page as PageModel;
use App\Base\Abstracts\Models\AccountModel;
use App\Base\Models\GuestUser;
use App\Base\Models\User;
use App\Base\Models\Website;
use App\Base\Routing\RouteInfo;
use Degami\Basics\Exceptions\BasicException;
use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\Validator;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;

/**
 * Pages Trait
 */
trait PageTrait
{
    /**
     * @var array|object|null current user data
     */
    protected array|object|null $current_user = null;

    /**
     * @var AccountModel|null current user model
     */
    protected ?AccountModel $current_user_model = null;

    /**
     * @var RouteInfo|null route info object
     */
    protected ?RouteInfo $route_info;

    /**
     * @var Website|null current website model
     */
    protected ?Website $current_website = null;

    /**
     * calculates JWT token id
     *
     * @param int $uid
     * @param string $username
     * @return string
     */
    public function calcTokenId(int $uid, string $username): string
    {
        return $this->getAuth()->calcTokenId($uid, $username);
    }

    /**
     * gets current user
     *
     * @param bool $reset
     * @return AccountModel|null
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getCurrentUser(bool $reset = false): ?AccountModel
    {
        return $this->getAuth()->getCurrentUser($reset);
    }

    /**
     * gets token data
     *
     * @return mixed
     */
    public function getTokenData(): mixed
    {
        return $this->getAuth()->getTokenData();
    }

    /**
     * returns current website
     * 
     * @return Website
     */
    public function getCurrentWebsite() : ?Website
    {
        if ($this->current_website == null && $this->getSiteData()->getCurrentWebsiteId()) {
            $this->current_website = $this->containerCall([Website::class, 'load'], ['id' => $this->getSiteData()->getCurrentWebsiteId()]);
        }
        
        return $this->current_website;
    }

    /**
     * checks if current user has specified permission
     *
     * @param string $permission_name
     * @return bool
     * @throws BasicException
     */
    public function checkPermission(string $permission_name): bool
    {
        try {
            return ($this->getCurrentUser() instanceof AccountModel) && $this->getCurrentUser()->checkPermission($permission_name);
        } catch (Exception $e) {
            $this->getUtils()->logException($e);
        }

        return false;
    }

    /**
     * checks if user is logged in
     *
     * @return bool
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function hasLoggedUser(): bool
    {
        $out = is_object($this->getCurrentUser()) && isset($this->getCurrentUser()->id) && $this->getCurrentUser()->id > 0;
        if ($this instanceof FrontendPage) {
            $out &= $this->check2FA();
        }
        return $out;
    }

    /**
     * checks if current is homepage
     *
     * @return bool
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     * @throws PhpfastcacheSimpleCacheException
     */
    public function isHomePage(): bool
    {
        if ($this instanceof Page) {
            if ($this->getObject() instanceof PageModel && $this->getObject()->isLoaded()) {
                $homepage_id = $this->getSiteData()->getHomePageId($this->getSiteData()->getCurrentWebsiteId(), $this->getObject()->getLocale());

                if ($this->getObject()->getId() == $homepage_id) {
                    return true;
                }
            }
        }

        if (is_array($this->route_info->getHandler()) && $this->route_info->getHandler()[1] == 'showFrontPage') {
            return true;
        }

        return false;
    }
}
