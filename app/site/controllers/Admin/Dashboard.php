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
use \App\Base\Abstracts\AdminPage;

/**
 * "Dashboard" Admin Page
 */
class Dashboard extends AdminPage
{
    /** @var array template data */
    protected $templateData = [];

    /**
     * {@inheritdocs}
     * @return string
     */
    protected function getTemplateName()
    {
        return 'dashboard';
    }

    /**
     * {@inheritdocs}
     * @return string
     */
    protected function getAccessPermission()
    {
        return 'administer_site';
    }

    /**
     * {@inheritdocs}
     * @return array
     */
    protected function getTemplateData()
    {
        $this->templateData = [
            'websites' => count($this->getDb()->table('website')->fetchAll()),
            'users' => count($this->getDb()->table('user')->fetchAll()),
            'pages' => count($this->getDb()->table('page')->fetchAll()),
            'contact_forms' => count($this->container->get('db')->table('contact')->fetchAll()),
            'contact_submissions' => count($this->container->get('db')->table('contact_submission')->fetchAll()),
            'taxonomy_terms' => count($this->getDb()->table('taxonomy')->fetchAll()),
            'blocks' => count($this->getDb()->table('block')->fetchAll()),
            'media' => count($this->getDb()->table('media_element')->fetchAll()),
            'page_views' => count($this->getDb()->table('request_log')->fetchAll()),
            'mails_sent' => count($this->getDb()->table('mail_log')->fetchAll()),
            'links' => count($this->getDb()->table('link_exchange')->fetchAll()),
            'news' => count($this->getDb()->table('news')->fetchAll()),
        ];
        return $this->templateData;
    }
}
