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

namespace App\Base\Crud\Admin;

use App\Base\Abstracts\Controllers\BaseJsonPage;
use App\Base\Exceptions\NotAllowedException;
use App\Base\Models\User;
use Exception;
use Symfony\Component\HttpFoundation\Request;

/**
 * Users REST endpoint
 */
class Token extends BaseJsonPage
{
    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public static function isEnabled() : bool
    {
        return boolval(\App\App::getInstance()->getEnvironment()->getVariable('CRUD'));
    }

    /**
     * return route path
     *
     * @return string
     */
    public static function getRoutePath(): string
    {
        return 'token';
    }

    protected function getJsonData(): mixed
    {
        return ['token' => $this->getToken($this->getRequest())];
    }

    protected function getToken(Request $request) : ?string
    {
        $username = (string) $request->request->get('username');
        $password = (string) $request->request->get('password');

        $token = null;
        try {
            /** @var User $user */
            $user = User::getCollection()->where([
                'username' => $username,
                'password' => $this->getUtils()->getEncodedPass($password),
            ])->getFirst();

            if (!$user) {
                throw new Exception("No user found");
            }

            // dispatch "user_logged_in" event
            $this->getApp()->event('user_logged_in', [
                'logged_user' => $user
            ]);

            $user->getUserSession()->addSessionData('last_login', new \DateTime())->persist();
            $jwt = "" . $user->getJWT();
    
            /** @var Parser $parser */
            $parser = $this->getContainer()->get('jwt:configuration')->parser();
            $token = $parser->parse($jwt)->toString();
        } catch (\Exception $e) {
            throw new NotAllowedException($this->getUtils()->translate("Invalid username / password", locale: $this->getCurrentLocale()));
        }

        return $token;
    }
}
