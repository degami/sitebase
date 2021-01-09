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

use App\Base\Abstracts\Models\BaseModel;

/**
 * Role Permission Pivot Model
 *
 * @method int getId()
 * @method int getRoleId()
 * @method int getPermissionId()
 * @method self setId(int $id)
 * @method self setRoleId(int $role_id)
 * @method self setPermissionId(int $permission_id)
 */
class RolePermission extends BaseModel
{
}
