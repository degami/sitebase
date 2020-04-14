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
use \App\Site\Models\MailLog;
use \App\Site\Models\RequestLog;
use \App\Site\Models\CronLog;
use \App\Site\Models\AdminActionLog;

/**
 * "Logs" Admin Page
 */
class Logs extends AdminPage
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
        return 'logs';
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getAccessPermission()
    {
        return 'administer_logs';
    }

    /**
     * {@inheritdocs}
     *
     * @return array
     */
    protected function getTemplateData()
    {
        $this->templateData = [
            'logtype' => $this->getRequest()->get('logtype') ?? null,
            'action' => $this->getRequest()->get('logtype') ? 'logs' : 'buttons',
        ];

        $header = $data = [];
        $log = null;

        if ($this->getRequest()->get('logtype')) {
            $this->addBackButton();

            switch ($this->getRequest()->get('logtype')) {
                case 'request':
                    $this->page_title = 'Requests Logs';

                    if (is_numeric($this->getRequest()->query->get('id'))) {
                        $log = $this->getContainer()->call([RequestLog::class, 'load'], ['id' => $this->getRequest()->query->get('id')]);
                    } else {
                        $data = $this->getContainer()->call([RequestLog::class, 'paginate'], ['order' => ['created_at' => 'DESC']]);
                        $header = ['id', 'url', 'method', 'response_code', 'user_id', 'ip_address', 'created_at', 'updated_at'];
                    }

                    break;
                case 'mail':
                    $this->page_title = 'Mail Logs';

                    if (is_numeric($this->getRequest()->query->get('id'))) {
                        $log = $this->getContainer()->call([MailLog::class, 'load'], ['id' => $this->getRequest()->query->get('id')]);
                    } else {
                        $data = $this->getContainer()->call([MailLog::class, 'paginate'], ['order' => ['created_at' => 'DESC']]);
                        $header = ['id', 'from', 'to', 'subject', 'template_name', 'result', 'created_at', 'updated_at'];
                    }

                    break;
                case 'cron':
                    $this->page_title = 'Cron Logs';

                    if (is_numeric($this->getRequest()->query->get('id'))) {
                        $log = $this->getContainer()->call([CronLog::class, 'load'], ['id' => $this->getRequest()->query->get('id')]);
                    } else {
                        $data = $this->getContainer()->call([CronLog::class, 'paginate'], ['order' => ['created_at' => 'DESC']]);
                        $header = ['id', 'run_time', 'duration', 'tasks', 'created_at', 'updated_at'];
                    }

                    break;
                case 'adminactions':
                    $this->page_title = 'Admin Actions Logs';

                    if (is_numeric($this->getRequest()->query->get('id'))) {
                        $log = $this->getContainer()->call([AdminActionLog::class, 'load'], ['id' => $this->getRequest()->query->get('id')]);
                    } else {
                        $data = $this->getContainer()->call([AdminActionLog::class, 'paginate'], ['order' => ['created_at' => 'DESC']]);
                        $header = ['id', 'action', 'method', 'url', 'created_at', 'updated_at'];
                    }

                    break;
            }

            if (is_numeric($this->getRequest()->query->get('id'))) {
                $this->templateData += [
                    'log' => $log,
                    'logHtml' => $this->getHtmlRenderer()->renderLog($log),
                ];
            } else {
                $this->templateData += [
                'header' => $header,
                'logs' => $data['items'],
                'total' => $data['total'],
                'current_page' => $data['page'],
                'paginator' => $this->getHtmlRenderer()->renderPaginator($data['page'], $data['total'], $this),
                ];
            }
        }

        return $this->templateData;
    }
}
