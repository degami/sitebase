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

namespace App\Site\Models;

use App\Base\Abstracts\Models\BaseModel;
use App\Base\Exceptions\InvalidValueException;
use DateTime;
use Degami\Basics\Exceptions\BasicException;
use Exception;

/**
 * Role Model
 *
 * @method int getId()
 * @method string getName()
 * @method DateTime getCreatedAt()
 * @method DateTime getUpdatedAt()
 * @method self setId(int $id)
 * @method self setName(string $name)
 * @method self setCreatedAt(DateTime $created_at)
 * @method self setUpdatedAt(DateTime $updated_at)
 */
class Role extends BaseModel
{
    /**
     * @var array permissions
     */
    protected array $permissionsArray = [];

    /**
     * returns permissions array
     *
     * @param bool $reset
     * @return array
     * @throws Exception
     */
    public function getPermissionsArray(bool $reset = false): array
    {
        $this->checkLoaded();

        if (!(is_array($this->permissionsArray) && !empty($this->permissionsArray)) || $reset == true) {
            $this->permissionsArray = array_map(
                function ($el) {
                    return $this->getContainer()->make(Permission::class, ['db_row' => $el]);
                },
                $this->role_permissionList()->permission()->fetchAll()
            );
        }
        return $this->permissionsArray;
    }

    /**
     * checks if role has permission
     *
     * @param string $permission_name
     * @param bool $reset
     * @return bool
     * @throws Exception
     */
    public function checkPermission(string $permission_name, bool $reset = false): bool
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
     * @param string $permission_name
     * @return self
     * @throws InvalidValueException
     */
    public function grantPermission(string $permission_name): Role
    {
        $pivot_model = $this->getContainer()->call([RolePermission::class, 'new']);

        $permission_model = $this->getContainer()->call([Permission::class, 'loadBy'], ['field' => 'name', 'value' => $permission_name]);
        if (!$permission_model->isLoaded()) {
            throw new InvalidValueException("permission not found: " . $permission_name);
        }

        $pivot_model->permission_id = $permission_model->id;
        $pivot_model->role_id = $this->id;
        $pivot_model->persist();

        return $this;
    }

    /**
     * revokes permission from role
     *
     * @param string $permission_name
     * @return $this
     * @throws BasicException
     * @throws InvalidValueException
     * @throws Exception
     */
    public function revokePermission(string $permission_name): Role
    {
        if (!$this->checkPermission($permission_name)) {
            throw new InvalidValueException("permission not found in role: " . $permission_name);
        }

        $permission_model = $this->getContainer()->call([Permission::class, 'loadBy'], ['field' => 'name', 'value' => $permission_name]);
        if (!$permission_model->isLoaded()) {
            throw new InvalidValueException("permission not found: " . $permission_name);
        }

        $pivot_model = $this->getContainer()->call([RolePermission::class, 'where'], ['condition' => ['permission_id' => $permission_model->id, 'role_id' => $this->id]]);
        $pivot_model = reset($pivot_model);
        if (!$pivot_model->isLoaded()) {
            throw new BasicException("errors finding pivot model");
        }

        $pivot_model->delete();

        return $this;
    }
}
