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

namespace App\Base\Auth;

use App\Base\Abstracts\ContainerAwareObject;
use App\Base\Abstracts\Models\AccountModel;
use App\Base\Models\User;
use App\Base\Models\GuestUser;
use Exception;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\Validator;

/**
 * Auth Manager
 * 
 * This class manages application logs and provides different logging levels.
 */
class Manager extends ContainerAwareObject
{
    public const ADMIN_WEBSITE_ID = 0;

    /**
     * @var array|object|null current user data
     */
    protected array|object|null $current_user = null;

    /**
     * @var AccountModel|null current user model
     */
    protected ?AccountModel $current_user_model = null;


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

    public function currentUserHasPassed2FA($onAdmin = false) : bool
    {
        if (!$this->getCurrentUser()) {
            return false;
        }

        try {
            if (is_object($this->getTokenData()) && ($this->getTokenData()?->passed2fa ?? false) == true) {
                return true;
            }

            if ($onAdmin) {
                return ($this->getCurrentUser()?->getUser2Fa(self::ADMIN_WEBSITE_ID) != null);
            }

            return ($this->getCurrentUser()?->getUser2Fa(self::ADMIN_WEBSITE_ID) != null);
        } catch (Exception $e) {}

        return false;
    }

    /**
     * gets token data
     *
     * @return mixed
     */
    public function getTokenData(): mixed
    {
        $userDataClaim = $this->getUtils()->getTokenUserDataClaim();
        if ($userDataClaim !== false && !empty($userDataClaim)) {
            $this->current_user = (object)$userDataClaim;
            return $this->current_user;
        }

        return false;
    }

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
}
