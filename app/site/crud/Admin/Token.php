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

namespace App\Site\Crud\Admin;

use App\Base\Abstracts\Controllers\BaseJsonPage;
use App\Base\Exceptions\NotAllowedException;
use App\Site\Models\User;
use Symfony\Component\HttpFoundation\Request;

/**
 * Users REST endpoint
 */
class Token extends BaseJsonPage
{
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
        $username = $request->request->get('username');
        $password = $request->request->get('password');

        $token = null;
        try {
            $user = $this->containerCall([User::class, 'loadByCondition'], ['condition' => [
                'username' => $username,
                'password' => $this->getUtils()->getEncodedPass($password),
            ]]);

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
            throw new NotAllowedException($this->getUtils()->translate("Invalid username / password", $this->getCurrentLocale()));
        }

        return $token;
    }
}
