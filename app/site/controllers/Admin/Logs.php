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
     * @throws BasicException
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
            if ($this->getRequest()->get('id')) {
                $this->addBackButton(['logtype' => $this->getRequest()->get('logtype')]);
            } else {
                $this->addBackButton();
            }

            $paginate_params = [
                'order' => $this->getRequest()->query->get('order') ?? ['created_at' => 'DESC'],
                'condition' => $this->getSearchParameters(),
            ];

            if (is_array($paginate_params['condition'])) {
                $conditions = [];
                if (isset($paginate_params['condition']['like'])) {
                    foreach ($paginate_params['condition']['like'] as $col => $search) {
                        if (trim($search) == '') {
                            continue;
                        }
                        $conditions['`'.$col . '` LIKE ?'] = ['%'.$search.'%'];
                    }
                }
                if (isset($paginate_params['condition']['eq'])) {
                    foreach ($paginate_params['condition']['eq'] as $col => $search) {
                        if (trim($search) == '') {
                            continue;
                        }
                        $conditions['`'.$col . '` = ?'] = [$search];
                    }
                }

                $paginate_params['condition'] = array_filter($conditions);
            }

            switch ($this->getRequest()->get('logtype')) {
                case 'request':
                    $this->page_title = 'Requests Logs';

                    if (is_numeric($this->getRequest()->query->get('id'))) {
                        $log = $this->getContainer()->call([RequestLog::class, 'load'], ['id' => $this->getRequest()->query->get('id')]);
                    } else {
                        $data = $this->getContainer()->call([RequestLog::class, 'paginate'], $paginate_params);
                        $header = ['id', 'url', 'method', 'response_code', 'user_id', 'ip_address', 'created_at', 'updated_at'];
                    }

                    break;
                case 'mail':
                    $this->page_title = 'Mail Logs';

                    if (is_numeric($this->getRequest()->query->get('id'))) {
                        $log = $this->getContainer()->call([MailLog::class, 'load'], ['id' => $this->getRequest()->query->get('id')]);
                    } else {
                        $data = $this->getContainer()->call([MailLog::class, 'paginate'], $paginate_params);
                        $header = ['id', 'from', 'to', 'subject', 'template_name', 'result', 'created_at', 'updated_at'];
                    }

                    break;
                case 'cron':
                    $this->page_title = 'Cron Logs';

                    if (is_numeric($this->getRequest()->query->get('id'))) {
                        $log = $this->getContainer()->call([CronLog::class, 'load'], ['id' => $this->getRequest()->query->get('id')]);
                    } else {
                        $data = $this->getContainer()->call([CronLog::class, 'paginate'], $paginate_params);
                        $header = ['id', 'run_time', 'duration', 'tasks', 'created_at', 'updated_at'];
                    }

                    break;
                case 'adminactions':
                    $this->page_title = 'Admin Actions Logs';

                    if (is_numeric($this->getRequest()->query->get('id'))) {
                        $log = $this->getContainer()->call([AdminActionLog::class, 'load'], ['id' => $this->getRequest()->query->get('id')]);
                    } else {
                        $data = $this->getContainer()->call([AdminActionLog::class, 'paginate'], $paginate_params);
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
                'table' => $this->getHtmlRenderer()->renderAdminTable($this->getTableElements($data['items'], $header), $this->getTableHeader($header), $this),

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


    /**
     * gets search parameters
     */
    protected function getSearchParameters()
    {
        $out = array_filter([
            'like' => $this->getRequest()->query->get('search'),
            'eq' =>  $this->getRequest()->query->get('foreign'),
        ]);
        return !empty($out) ? $out : null;
    }

    /**
     * {@inheritdocs}
     *
     * @param array $data
     * @param array $header
     * @return array[]
     * @throws BasicException
     */
    protected function getTableElements($data, $header)
    {
        return array_map(function ($log) use ($header) {
            $data = $log->getData();
            $out = [];
            foreach ($header as $element) {
                $out[$element] = $data[$element] ?? null;
            }
            $out['actions'] = '<a href="'. $this->getControllerUrl().'?logtype='. $this->getRequest()->query->get('logtype').'&id='. $log->id.'">'. $this->getUtils()->getIcon('zoom-in', ['style' => 'vertical-align: middle']).' '. $this->getUtils()->translate('View').'</a>';
            return $out;
        }, $data);
    }

    /**
     * {@inheritdocs}
     *
     * @param array $header
     * @return array
     */
    protected function getTableHeader($header)
    {
        $out = [];
        foreach ($header as $element) {
            $out[$element] = ['order' => $element, 'search' => $element];
        }
        $out['actions'] = null;
        return $out;
    }
}
