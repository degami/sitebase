<?php
/**
 * SiteBase
 * PHP Version 7.0
 *
 * @category CMS / Framework
 * @package  Degami\Sitebase
 * @author   Mirko De Grandis <degami@github.com>
 * @license  MIT https://opensource.org/licenses/mit-license.php
 * @link     https://github.com/degami/sitebase
 */
namespace App\Base\Traits;

use \App\Base\Abstracts\ContainerAwareObject;
use \App\Site\Controllers\Frontend\Page;
use \App\Site\Models\Page as PageModel;
use \App\Base\Abstracts\Models\AccountModel;
use \App\Site\Models\GuestUser;
use \App\Site\Models\User;

/**
 * Pages Trait
 */
trait PageTrait
{
    /**
     * @var array current user data
     */
    protected $current_user = null;

    /**
     * @var User current user model
     */
    protected $current_user_model = null;

    /**
     * @var RouteInfo route info object
     */
    protected $route_info = null;

    /**
     * calculates JWT token id
     *
     * @param  integer $uid
     * @param  string  $usename
     * @return string
     */
    public function calcTokenId($uid, $usename)
    {
        $string = $uid.$usename;
        if ($this instanceof ContainerAwareObject) {
            $string = $this->getContainer()->get('jwt_id').$string;
        }

        return substr(sha1($string), 0, 10);
    }

    /**
     * gets Authorization token header
     *
     * @return string
     */
    protected function getToken()
    {
        $token = $this->getRequest()->headers->get('Authorization');
        return $token ?: $this->getRequest()->cookies->get('Authorization');
    }

    /**
     * checks if token is still active
     *
     * @param  string $token
     * @return boolean
     */
    public function tokenIsActive($token)
    {
        return true;
    }

    /**
     * gets token data
     *
     * @return array|boolean
     */
    protected function getTokenData()
    {
        try {
            $container = $this->getContainer();
            $auth_token = $this->getToken();
            $token = $container->get('jwt:parser')->parse((string) $auth_token);

            $data = new \Lcobucci\JWT\ValidationData();
            $data->setIssuer($container->get('jwt_issuer'));
            $data->setAudience($container->get('jwt_audience'));

            $claimUID = (string) $token->getClaim('uid');
            $claimUserName = (string) $token->getClaim('username');

            $data->setId($this->calcTokenId($claimUID, $claimUserName));
            if ($token->validate($data)) {
                $this->current_user = $token->getClaim('userdata');
                return $this->current_user;
            }
        } catch (\Exception $e) {
            //$this->getUtils()->logException($e);
        }

        return false;
    }

    /**
     * gets current user
     *
     * @return User|GuestUser
     */
    public function getCurrentUser()
    {
        if ($this->current_user_model instanceof User) {
            return $this->current_user_model;
        }

        if (!$this->current_user && !$this->getTokenData()) {
            return $this->getContainer()->make(GuestUser::class);
        }

        if (!$this->current_user) {
            $this->getTokenData();
        }

        if (is_object($this->current_user) && property_exists($this->current_user, 'id') && !$this->current_user_model instanceof User) {
            $this->current_user_model = $this->getContainer()->call([User::class, 'load'], ['id' => $this->current_user->id]);
        }

        return $this->current_user_model;
    }

    /**
     * checks if current user has specified permission
     *
     * @param  string $permission_name
     * @return boolean
     */
    public function checkPermission($permission_name)
    {
        try {
            return ($this->getCurrentUser() instanceof AccountModel) && $this->getCurrentUser()->checkPermission($permission_name);
        } catch (\Exception $e) {
            $this->getUtils()->logException($e);
        }

        return false;
    }

    /**
     * checks if user is logged in
     *
     * @return boolean
     */
    public function hasLoggedUser()
    {
        return is_object($this->getCurrentUser()) && isset($this->getCurrentUser()->id) && $this->getCurrentUser()->id > 0;
    }

    /**
     * checks if current is homepage
     *
     * @return boolean
     */
    public function isHomePage()
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
