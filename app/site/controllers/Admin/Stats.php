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

namespace App\Site\Controllers\Admin;

use Degami\Basics\Exceptions\BasicException;
use PDO;
use \App\Base\Abstracts\Controllers\AdminPage;

/**
 * "Stats" Admin Page
 */
class Stats extends AdminPage
{
    /**
     * @var array template data
     */
    protected $template_data = [];

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getTemplateName(): string
    {
        return 'stats';
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getAccessPermission(): string
    {
        return 'administer_site';
    }

    /**
     * {@inheritdocs}
     *
     * @return array
     * @throws BasicException
     */
    protected function getTemplateData(): array
    {
        $this->addActionLink('back-btn', 'back-btn', $this->getUtils()->getIcon('rewind') . ' ' . $this->getUtils()->translate('Back', $this->getCurrentLocale()), $this->getWebRouter()->getUrl('admin.dashboard'));

        $queries = [
            'top_visitors' => 'SELECT ip_address, COUNT(id) AS cnt FROM request_log GROUP BY ip_address ORDER BY cnt DESC LIMIT 10',
            'most_viewed' => 'SELECT url, COUNT(id) AS cnt FROM request_log GROUP BY url ORDER BY cnt DESC LIMIT 10',
            'top_errors' => 'SELECT response_code, url, ip_address, COUNT(id) AS cnt FROM request_log WHERE response_code NOT IN (200, 301, 302) GROUP BY url, response_code, ip_address ORDER BY cnt DESC LIMIT 10',
            'top_scanners' => 'SELECT ip_address, GROUP_CONCAT(DISTINCT(response_code)) AS codes, COUNT(id) AS cnt FROM request_log WHERE response_code NOT IN (200, 301, 302) GROUP BY ip_address ORDER BY cnt DESC LIMIT 10',
        ];

        foreach ($queries as $key => $query) {
            $stmt = $this->getDb()->prepare($query);
            $stmt->execute();
            $this->template_data[$key] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        return $this->template_data;
    }
}
