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

namespace App\Base\Traits;

use App\Base\Abstracts\ContainerAwareObject;
use App\Site\Controllers\Frontend\Page;
use App\Site\Models\Page as PageModel;
use App\Base\Abstracts\Models\AccountModel;
use App\Site\Models\GuestUser;
use App\Site\Models\User;
use App\Site\Routing\RouteInfo;
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
     * calculates JWT token id
     *
     * @param int $uid
     * @param string $username
     * @return string
     */
    public function calcTokenId(int $uid, string $username): string
    {
        $string = $uid . $username;
        if ($this instanceof ContainerAwareObject) {
            $string = $this->getContainer()->get('jwt_id') . $string;
        }

        return substr(sha1($string), 0, 10);
    }

    /**
     * checks if token is still active
     *
     * @param Token $token
     * @return bool
     */
    public function tokenIsActive(Token $token): bool
    {
        /** @var Validator $validator */
        $validator = $this->getContainer()->get('jwt:configuration')->validator();
        $constraints = $this->getContainer()->get('jwt:configuration')->validationConstraints();
        if ($validator->validate($token, ...$constraints) && !$token->isExpired(new \DateTime())) {
            return true;
        }

        return false;
    }

    /**
     * gets token data
     *
     * @return mixed
     */
    protected function getTokenData(): mixed
    {
        $userDataClaim = $this->getContainer()->get('utils')->getTokenUserDataClaim();
        if ($userDataClaim !== false && !empty($userDataClaim)) {
            $this->current_user = (object)$userDataClaim;
            return $this->current_user;
        }

        return false;
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
        if (($this->current_user_model instanceof AccountModel) && $reset != true) {
            return $this->current_user_model;
        }

        if (!$this->current_user && !$this->getTokenData()) {
            if ($this->current_user_model instanceof AccountModel) {
                return $this->current_user_model;
            }
            return ($this->current_user_model = $this->containerMake(GuestUser::class));
        }

        if (!$this->current_user) {
            $this->getTokenData();
        }

        if (is_array($this->current_user)) {
            $this->current_user = (object)$this->current_user;
        }

        if (is_object($this->current_user) && property_exists($this->current_user, 'id')) {
            $this->current_user_model = $this->containerCall([User::class, 'load'], ['id' => $this->current_user->id]);
        }

        return $this->current_user_model;
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
        return is_object($this->getCurrentUser()) && isset($this->getCurrentUser()->id) && $this->getCurrentUser()->id > 0;
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
