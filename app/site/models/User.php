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
use \DateTime;
use Degami\Basics\Exceptions\BasicException;
use Exception;

/**
 * User Model
 *
 * @method int getId()
 * @method string getUsername()
 * @method string getPassword()
 * @method int getRoleId()
 * @method string getEmail()
 * @method string getNickname()
 * @method string getLocale()
 * @method string getConfirmationCode()
 * @method string getAdditionalData()
 * @method DateTime getCreatedAt()
 * @method DateTime getUpdatedAt()
 * @method self setId(int $id)
 * @method self setUsername(string $username)
 * @method self setPassword(string $password)
 * @method self setRoleId(int $role_id)
 * @method self setEmail(string $email)
 * @method self setNickname(string $nickname)
 * @method self setLocale(string $locale)
 * @method self setConfirmationCode(string $confirmation_code)
 * @method self setAdditionalData(string $additional_data)
 * @method self setCreatedAt(DateTime $created_at)
 * @method self setUpdatedAt(DateTime $updated_at)
 */
class User extends AccountModel
{
    /**
     * @var Role role object
     */
    protected $roleObj;

    /**
     * gets user role
     *
     * @return Role
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     */
    public function getRole(): Role
    {
        if ($this->roleObj instanceof Role) {
            return $this->roleObj;
        }

        return $this->roleObj = $this->getContainer()->make(Role::class, ['dbrow' => $this->role()->fetch()]);
    }

    /**
     * sets user role
     *
     * @param Role|integer|string $role
     * @throws BasicException
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
     * @param string $permission_name
     * @return boolean
     * @throws Exception
     */
    public function checkPermission($permission_name): bool
    {
        $this->checkLoaded();

        return $this->getRole()->checkPermission($permission_name);
    }

    /**
     * gets registered since
     *
     * @return string
     * @throws Exception
     */
    public function getRegisteredSince(): string
    {
        if ($this->isLoaded()) {
            $date = new DateTime($this->getCreatedAt());
            $now = new DateTime();

            $interval = date_diff($date, $now);
            $differenceFormat = $this->getUtils()->translate('%y years %m months %d days');
            $date_format = $this->getSiteData()->getDateFormat();
            return $date->format($date_format) . ' (' . $interval->format($differenceFormat) . ')';
        }

        return "";
    }

    /**
     * calculates JWT token id
     *
     * @return string
     */
    protected function calcTokenId(): string
    {
        $string = $this->getId() . $this->getUsername();
        $string = $this->getContainer()->get('jwt_id') . $string;
        return substr(sha1($string), 0, 10);
    }

    /**
     * get JWT token
     *
     * @return string
     * @throws Exception
     */
    public function getJWT(): string
    {
        $this->checkLoaded();

        $token = $this->getContainer()->get('jwt:builder')
            ->setIssuer($this->getContainer()->get('jwt_issuer'))
            ->setAudience($this->getContainer()->get('jwt_audience'))
            ->setId($this->calcTokenId(), true)
            // Configures the id (jti claim), replicating as a header item
            ->setIssuedAt(time())
            // Configures the time that the token was issue (iat claim)
            ->setNotBefore(time())
            // Configures the time that the token can be used (nbf claim)
            ->setExpiration(time() + 3600)
            // Configures the expiration time of the token (exp claim)
            ->set('uid', $this->getId())
            // Configures a new claim, called "uid"
            ->set('username', $this->getUsername())
            ->set('userdata', (object)[
                'id' => $this->getId(),
                'username' => $this->getUsername(),
                'email' => $this->getEmail(),
                'nickname' => $this->getNickname(),
                'permissions' => array_map(
                    function ($el) {
                        return $el->name;
                    },
                    $this->getRole()->getPermissionsArray()
                )
            ])
            ->getToken(); // Retrieves the generated token

        return $token;
    }
}
