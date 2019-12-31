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

use \App\Base\Abstracts\Model;
use \App\Base\Exceptions\InvalidValueException;
use \App\Base\Exceptions\BasicException;

/**
 * Role Model
 *
 * @method int getId()
 * @method string getName()
 * @method \DateTime getCreatedAt()
 * @method \DateTime getUpdatedAt()
 */
class Role extends Model
{
    /**
     * @var array permissions
     */
    protected $permissionsArray = [];

    /**
     * returns permissions array
     *
     * @param  boolean $reset
     * @return array
     */
    public function getPermissionsArray($reset = false)
    {
        $this->checkLoaded();

        if (!(is_array($this->permissionsArray) && !empty($this->permissionsArray)) || $reset == true) {
            $this->permissionsArray = array_map(
                function ($el) {
                    return $this->getContainer()->make(Permission::class, ['dbrow' => $el]);
                },
                $this->role_permissionList()->permission()->fetchAll()
            );
        }
        return $this->permissionsArray;
    }

    /**
     * checks if role has permission
     *
     * @param  string  $permission_name
     * @param  boolean $reset
     * @return boolean
     */
    public function checkPermission($permission_name, $reset = false)
    {
        $this->checkLoaded();

        foreach ($this->getPermissionsArray($reset) as $permission) {
            if ($permission->name == $permission_name) {
                return true;
            }
        }

        return false;
    }

    /**
     * grants permission to role
     *
     * @param  string $permission_name
     * @return self
     */
    public function grantPermission($permission_name)
    {
        $pivot_model = $this->getContainer()->call([RolePermission::class, 'new']);

        $permission_model = $this->getContainer()->call([Permission::class, 'loadBy'], ['field' => 'name', 'value' => $permission_name]);
        if (!$permission_model->isLoaded()) {
            throw new InvalidValueException("permission not found: ".$permission_name);
        }

        $pivot_model->permission_id = $permission_model->id;
        $pivot_model->role_id = $this->id;
        $pivot_model->persist();

        return $this;
    }

    /**
     * revokes permission from role
     *
     * @param  string $permission_name
     * @return self
     */
    public function revokePermission($permission_name)
    {
        if (!$this->checkPermission($permission_name)) {
            throw new InvalidValueException("permission not found in role: ".$permission_name);
        }

        $permission_model = $this->getContainer()->call([Permission::class, 'loadBy'], ['field' => 'name', 'value' => $permission_name]);
        if (!$permission_model->isLoaded()) {
            throw new InvalidValueException("permission not found: ".$permission_name);
        }

        $pivot_model = reset($this->getContainer()->call([RolePermission::class, 'where'], ['condition' => [ 'permission_id' => $permission_model->id, 'role_id' => $this->id]]));
        if (!$pivot_model->isLoaded()) {
            throw new BasicException("errors finding pivot model");
        }

        $pivot_model->delete();

        return $this;
    }
}
