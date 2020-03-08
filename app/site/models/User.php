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
namespace App\Site\Models;

use \App\Base\Abstracts\Models\BaseModel;

/**
 * User Model
 *
 * @method int getId()
 * @method string getUsername()
 * @method string getPassword()
 * @method int getRoleId()
 * @method string getEmail()
 * @method string getNickname()
 * @method \DateTime getCreatedAt()
 * @method \DateTime getUpdatedAt()
 */
class User extends BaseModel
{
    /**
     * @var Role role object
     */
    protected $roleObj;

    /**
     * gets user role
     *
     * @return Role
     */
    public function getRole()
    {
        if ($this->roleObj instanceof Role) {
            return $this->roleObj;
        }

        return $this->roleObj = $this->getContainer()->make(Role::class)->fill($this->role()->fetch());
    }

    /**
     * sets user role
     *
     * @param Role|integer|string $role
     */
    public function setRole($role)
    {
        if ($role instanceof Role) {
            $this->role_id = $role->id;
        } elseif (is_int($role)) {
            $this->role_id = $role;
        } elseif (is_string($role)) {
            $role = $this->getDb()->table('role')->where(['name' => $role])->fetch();
            if ($role) {
                $this->role_id = $role->id;
            }
        }
    }

    /**
     * checks if user has permission
     *
     * @param  string $permission_name
     * @return boolean
     */
    public function checkPermission($permission_name)
    {
        $this->checkLoaded();

        return $this->getRole()->checkPermission($permission_name);
    }
}
