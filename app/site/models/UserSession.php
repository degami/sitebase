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
use DateTime;

/**
 * User Session Model
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
    /** @var array|null  */
    protected ?array $session_data_array = null;

    /**
     * {@inheritdoc}
     *
     * @return BaseModel
     */
    public function postLoad(): BaseModel
    {
        $this->session_data_array = $this->getNormalizedSessionData();

        return parent::postLoad();
    }

    /**
     * {@inheritdoc}
     *
     * @return BaseModel
     */
    public function prePersist(): BaseModel
    {
        $this->setSessionData(json_encode($this->getNormalizedSessionData()));
        return parent::prePersist();
    }

    /**
     * gets session data as array
     * @return array
     */
    protected function getNormalizedSessionData(): array
    {
        if (is_array($this->session_data_array)) {
            return $this->session_data_array;
        }

        $session_data = $this->getSessionData();
        if (is_string($session_data)) {
            $session_data = json_decode($session_data, true);
        }
        if (!is_array($session_data)) {
            if (!is_null($session_data)) {
                $session_data = [$session_data];
            } else {
                $session_data = [];
            }
        }

        return $session_data;
    }

    /**
     * adds data to User Session Object
     *
     * @param string $key
     * @param mixed $value
     * @return self
     */
    public function addSessionData(string $key, $value): UserSession
    {
        $session_data = $this->getNormalizedSessionData();
        if (static::isEncodable($value)) {
            $session_data[$key] = $value;
            $this->session_data_array = $session_data;
        }

        return $this;
    }

    /**
     * sets input array values as session values
     *
     * @param array $data
     * @return self
     */
    public function arrayToSessionData(array $data): UserSession
    {
        $this->clearSessionData();

        foreach ($data as $key => $value) {
            if (!static::isEncodable($value)) {
                continue;
            }
            $this->addSessionData($key, $value);
        }

        return $this;
    }

    /**
     * removes session data value by key
     *
     * @param string $key
     * @return self
     */
    public function removeSessionData(string $key): UserSession
    {
        $session_data = $this->getNormalizedSessionData();
        unset($session_data[$key]);
        $this->session_data_array = $session_data;

        return $this;
    }

    /**
     * removes session data
     *
     * @return self
     */
    public function clearSessionData(): UserSession
    {
        $this->session_data_array = [];

        return $this;
    }

    /**
     * checks if parameter is json representable
     * @param mixed $var
     * @return bool
     */
    protected static function isEncodable($var): bool
    {
        return json_encode($var) !== false;
    }
}
