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

use \App\Base\Abstracts\Models\AccountModel;
use Degami\Basics\Exceptions\BasicException;
use \Psr\Container\ContainerInterface;
use \DateTime;
use \Exception;

/**
 * Guest User Model
 */
class GuestUser extends AccountModel
{
    const ROLE_ID = 1;

    /**
     * @var Role user role
     */
    protected $roleObj = null;

    /**
     * @var integer user id
     */
    public $id = 0;

    /**
     * @var null username
     */
    public $username = null;

    /**
     * @var null nickname
     */
    public $nickname = null;

    /**
     * @var null email
     */
    public $email = null;

    /**
     * @var array permissions
     */
    public $permissions = [];

    /**
     * class constructor
     *
     * @param ContainerInterface $container
     * @throws Exception
     */
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);

        /* @todo chek if it is possible to store data to a jwt token */
        $this->permissions = array_map(
            function ($el) {
                return $el->name;
            },
            $this->getRole()->getPermissionsArray()
        );
    }

    /**
     * checks if user has permission
     *
     * @param string $permission_name
     * @return boolean
     * @throws Exception
     */
    public function checkPermission($permission_name)
    {
        return $this->getRole()->checkPermission($permission_name);
    }

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

        return $this->roleObj = $this->getContainer()->make(Role::class)->fill($this->getRoleId());
    }

    /**
     * gets user id
     *
     * @return integer
     */
    public function getId()
    {
        return 0;
    }

    /**
     * gets username
     *
     * @return null
     */
    public function getUsername()
    {
        return null;
    }

    /**
     * gets user password
     *
     * @return null
     */
    public function getPassword()
    {
        return null;
    }

    /**
     * gets user role id
     *
     * @return integer
     */
    public function getRoleId()
    {
        return self::ROLE_ID;
    }

    /**
     * gets user email
     *
     * @return null
     */
    public function getEmail()
    {
        return null;
    }

    /**
     * gets user nickname
     *
     * @return null
     */
    public function getNickname()
    {
        return null;
    }

    /**
     * gets user creation date
     *
     * @return DateTime
     */
    public function getCreatedAt()
    {
        return new DateTime();
    }

    /**
     * gets user last update time
     *
     * @return DateTime
     */
    public function getUpdatedAt()
    {
        return new DateTime();
    }

    /**
     * gets user locale
     *
     * @return string
     * @throws BasicException
     */
    public function getLocale()
    {
        return $this->getSiteData()->getDefaultLocale();
    }

    /**
     * no save for this model
     */
    public function persist()
    {
        return $this;
    }

    /**
     * no delete for this model
     */
    public function remove()
    {
        return $this;
    }
}
