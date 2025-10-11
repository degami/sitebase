<?php

/**
 * SiteBase
 * PHP Version 8.3
 *
 * @category CMS / Framework
 * @package  Degami\Sitebase
 * @author   Mirko De Grandis <degami@github.com>
 * @license  MIT https://opensource.org/licenses/mit-license.php
 * @link     https://github.com/degami/sitebase
 */

namespace App\Base\Models;

use App\App;
use App\Base\Abstracts\Models\BaseModel;
use App\Base\Abstracts\Models\BaseCollection;
use App\Base\Commands\Generate\Model;
use DateTime;
use Exception;
use RuntimeException;

/**
 * Model Version Model
 *
 * @method int getId()
 * @method string getClassName()
 * @method string getPrimaryKey()
 * @method string getVersionData()
 * @method int getUserId()
 * @method DateTime getCreatedAt()
 * @method DateTime getUpdatedAt()
 * @method self setId(int $id)
 * @method self setClassName(string $class_name)
 * @method self setPrimaryKey(string $primary_key)
 * @method self setVersionData(string $version_data)
 * @method self setUserId(int $user_id)
 * @method self setCreatedAt(DateTime $created_at)
 * @method self setUpdatedAt(DateTime $updated_at)
 */
class ModelVersion extends BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function canBeDuplicated() : bool
    {
        return false;
    }

    public function getObjectFromVersionData() : ?BaseModel
    {
        try {
            $object = App::getInstance()->containerMake($this->getClassName());

            $version_data = json_decode($this->getVersionData(), true);
            $object->setData($version_data);

            return $object;
        } catch (Exception $e) {}

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function saveVersion() : ModelVersion
    {
        throw new RuntimeException("Cannot save a version of a version");
    }

    /**
     * {@inheritdoc}
     */
    public function getVersions() : BaseCollection
    {
        throw new RuntimeException("Cannot load versions for a version");
    }

    public function compareWith(ModelVersion $other_version) : array
    {
        $current_data = json_decode($this->getVersionData(), true);
        $other_data = json_decode($other_version->getVersionData(), true);

        return $this->arrayDiffRecursive($current_data, $other_data);
    }

    private function arrayDiffRecursive(array $array1, array $array2): array
    {
        $diff = [];

        $ignoredKeys = ['__objectId', '__reference', '__maxDepthReached', 'updated_at', 'created_at'];

        foreach ($array1 as $key => $value) {
            // Salta chiavi da ignorare
            if (in_array($key, $ignoredKeys, true)) {
                continue;
            }

            if (array_key_exists($key, $array2)) {
                if (is_array($value) && is_array($array2[$key])) {
                    $nestedDiff = $this->arrayDiffRecursive($value, $array2[$key]);
                    if (!empty($nestedDiff)) {
                        $diff[$key] = $nestedDiff;
                    }
                } elseif ($value != $array2[$key]) {
                    $diff[$key] = ['current' => $value, 'other' => $array2[$key]];
                }
            } else {
                $diff[$key] = ['current' => $value, 'other' => null];
            }
        }

        foreach ($array2 as $key => $value) {
            if (!array_key_exists($key, $array1)) {
                $diff[$key] = ['current' => null, 'other' => $value];
            }
        }

        return $diff;
    }
}
