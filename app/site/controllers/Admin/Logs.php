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
use \App\Site\Models\MailLog;
use \App\Site\Models\RequestLog;
use \App\Site\Models\CronLog;

/**
 * "Logs" Admin Page
 */
class Logs extends AdminPage
{
    /** @var array template data */
    protected $templateData = [];

    /**
     * {@inheritdocs}
     * @return string
     */
    protected function getTemplateName()
    {
        return 'logs';
    }

    /**
     * {@inheritdocs}
     * @return string
     */
    protected function getAccessPermission()
    {
        return 'administer_logs';
    }

    /**
     * {@inheritdocs}
     * @return array
     */
    protected function getTemplateData()
    {
        $this->templateData = [
            'logtype' => $this->getRequest()->get('logtype') ?? null,
            'action' => $this->getRequest()->get('logtype') ? 'logs' : 'buttons',
        ];
        switch ($this->getRequest()->get('logtype')) {
            case 'request':
                $this->addBackButton();
                $this->page_title = 'Requests Logs';
                $data = $this->getContainer()->call([RequestLog::class, 'paginate']);
                $this->templateData += [
                    'header' => ['id', 'url', 'method', 'user_id', 'ip_address', 'created_at', 'updated_at'],
                    'logs' => $data['items'],
                    'total' => $data['total'],
                    'current_page' => $data['page'],
                    'paginator' => $this->getHtmlRenderer()->renderPaginator($data['page'], $data['total'], $this),
                ];

                break;
            case 'mail':
                $this->addBackButton();
                $this->page_title = 'Mail Logs';
                $data = $this->getContainer()->call([MailLog::class, 'paginate']);
                $this->templateData += [
                    'header' => ['id', 'from', 'to', 'subject', 'template_name', 'result', 'created_at', 'updated_at'],
                    'logs' => $data['items'],
                    'total' => $data['total'],
                    'current_page' => $data['page'],
                    'paginator' => $this->getHtmlRenderer()->renderPaginator($data['page'], $data['total'], $this),
                ];

                break;
            case 'cron':
                $this->addBackButton();
                $this->page_title = 'Cron Logs';
                $data = $this->getContainer()->call([CronLog::class, 'paginate']);
                $this->templateData += [
                    'header' => ['id', 'run_time', 'duration', 'tasks', 'created_at', 'updated_at'],
                    'logs' => $data['items'],
                    'total' => $data['total'],
                    'current_page' => $data['page'],
                    'paginator' => $this->getHtmlRenderer()->renderPaginator($data['page'], $data['total'], $this),
                ];

                break;
        }
        return $this->templateData;
    }
}
