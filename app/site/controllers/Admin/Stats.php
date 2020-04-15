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

use \Psr\Container\ContainerInterface;
use \App\Base\Abstracts\Controllers\AdminPage;

/**
 * "Stats" Admin Page
 */
class Stats extends AdminPage
{
    /**
     * @var array template data
     */
    protected $templateData = [];

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getTemplateName()
    {
        return 'stats';
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getAccessPermission()
    {
        return 'administer_site';
    }

    /**
     * {@inheritdocs}
     *
     * @return array
     */
    protected function getTemplateData()
    {
        $this->addActionLink('back-btn', 'back-btn', $this->getUtils()->getIcon('rewind').' '.$this->getUtils()->translate('Back', $this->getCurrentLocale()), $this->getRouting()->getUrl('admin.dashboard'));

        $queries = [
            'top_visitors' => 'SELECT ip_address, COUNT(id) AS cnt FROM request_log GROUP BY ip_address ORDER BY cnt DESC LIMIT 10',
            'most_viewed' => 'SELECT url, COUNT(id) AS cnt FROM request_log GROUP BY url ORDER BY cnt DESC LIMIT 10',
            'top_errors' => 'SELECT response_code, url, ip_address, COUNT(id) AS cnt FROM request_log WHERE response_code NOT IN (200, 301, 302) GROUP BY url, response_code, ip_address ORDER BY cnt DESC LIMIT 10',
        ];

        foreach ($queries as $key => $query) {
            $stmt = $this->getDb()->prepare($query);
            $stmt->execute();
            $this->templateData[$key] = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        }

        return $this->templateData;
    }
}
