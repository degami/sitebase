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

namespace App\Base\Cron\Tasks;

use App\Base\Abstracts\ContainerAwareObject;

/**
 * Cron Version Related
 */
class VersionManager extends ContainerAwareObject
{
    public const DEFAULT_SCHEDULE = '0 0 * * *'; // ogni giorno a mezzanotte

    public const MAX_VERSIONS_PATH = 'app/versions/keep_num';
    public const MAX_VERSIONS = 20;

    /**
     * Esegue il task
     *
     * @return string
     */
    public function cleanOldVersions(): string
    {
        $deletedCount = 0;

        $sql = "
            DELETE mv FROM model_version mv
            INNER JOIN (
                SELECT id FROM (
                    SELECT id,
                           ROW_NUMBER() OVER (
                               PARTITION BY class_name, primary_key 
                               ORDER BY created_at DESC
                           ) AS rn
                    FROM model_version
                ) t
                WHERE t.rn > :maxVersions
            ) old_versions ON mv.id = old_versions.id
        ";

        // Esegui la query
        $stmt = $this->getPdo()->prepare($sql);
        $stmt->bindValue(':maxVersions', $this->maxVersions(), \PDO::PARAM_INT);
        $stmt->execute();

        $deletedCount = $stmt->rowCount();

        return sprintf("CleanOldVersions: eliminated %d old versions", $deletedCount);
    }

    protected function maxVersions(): int
    {
        return $this->getSiteData()->getConfigValue(self::MAX_VERSIONS_PATH) ?? self::MAX_VERSIONS;
    }
}
