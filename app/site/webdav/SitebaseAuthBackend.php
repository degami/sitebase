<?php

namespace App\Site\Webdav;

use Sabre\DAV\Auth\Backend\AbstractBasic;
use App\Base\Models\User;
use App\App;

class SitebaseAuthBackend extends AbstractBasic
{
    public function __construct(protected App $app, protected ?int $currentUserId = null) {}

    protected function validateUserPass($username, $password): bool
    {
        /** @var User|null $user */
        $user = $this->app->getUtils()->getUserByCredentials($username, $password);

        if ($user) {
            $_SESSION['webdav_userid'] = $user->getId();
            return true;
        }

        return false;
    }
}
