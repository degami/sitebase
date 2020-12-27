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
use \DateTime;
use Degami\Basics\Exceptions\BasicException;
use DI\DependencyException;
use DI\NotFoundException;
use Exception;

/**
 * User Model
 *
 * @method int getId()
 * @method int getWebsiteId()
 * @method int getUserId()
 * @method mixed getSessionData()
 * @method DateTime getCreatedAt()
 * @method DateTime getUpdatedAt()
 * @method self setId(int $id)
 * @method self setWebsiteId(int $website_id)
 * @method self setUserId(int $user_id)
 * @method self setSessionData(mixed $session_data)
 * @method self setCreatedAt(DateTime $created_at)
 * @method self setUpdatedAt(DateTime $updated_at)
 */
class UserSession extends BaseModel
{
    public function postLoad(): BaseModel
    {
        $session_data = json_decode($this->getSessionData(), true);
        if (!is_array($session_data)) {
            $session_data = [$session_data];
        }
        $this->setSessionData($session_data);
        return parent::postLoad();
    }

    public function prePersist(): BaseModel
    {
        $this->setSessionData(json_encode($this->getSessionData()));
        return parent::prePersist();
    }

    public function addSessionData($key, $value): UserSession
    {
        $session_data = $this->getSessionData();
        $session_data[$key] = $value;
        $this->setSessionData($session_data);

        return $this;
    }

    public function arrayToSessionData(array $data): UserSession
    {
        $this->clearSessionData();

        foreach ($data as $key => $value) {
            $this->addSessionData($key, $value);
        }

        return $this;
    }

    public function clearSessionData(): UserSession
    {
        $this->setSessionData([]);

        return $this;
    }
}
