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
namespace App\Base\Abstracts;

use \Symfony\Component\HttpFoundation\Response;
use \Psr\Container\ContainerInterface;
use \App\Base\Traits\AdminTrait;
use \App\Site\Routing\RouteInfo;
use \App\Site\Models\AdminActionLog;
use \App\App;

/**
 * Base for admin pages
 */
abstract class AdminPage extends BaseHtmlPage
{
    use AdminTrait;

    /**
     * @var string page title
     */
    protected $page_title;

    /**
     * @var string locale
     */
    protected $locale = null;

    /**
     * @var array template data
     */
    protected $templateData = [];

    /**
     * {@inheritdocs}
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->page_title = ucwords(str_replace("_", " ", implode("", array_slice(explode("\\", get_class($this)), -1, 1))));

        parent::__construct($container);
        if (!$this->getTemplates()->getFolders()->exists('admin')) {
            $this->getTemplates()->addFolder('admin', App::getDir(App::TEMPLATES).DS.'admin');
        }
    }

    /**
     * returns valid route HTTP verbs
     *
     * @return array
     */
    public static function getRouteVerbs()
    {
        return ['GET','POST'];
    }

    /**
     * before render hook
     *
     * @return Response|self
     */
    protected function beforeRender()
    {
        if (!$this->checkCredentials() || !$this->checkPermission($this->getAccessPermission())) {
            return $this->getUtils()->errorPage(403);
        }

        return parent::beforeRender();
    }

    /**
     * {@inheritdocs}
     *
     * @param  RouteInfo|null $route_info
     * @param  array          $route_data
     * @return Response
     */
    public function renderPage(RouteInfo $route_info = null, $route_data = [])
    {
        $return = parent::renderPage($route_info, $route_data);

        if ($this->getSiteData()->getConfigValue('app/backend/log_requests') == true) {
            try {
                $log = $this->getContainer()->make(AdminActionLog::class);
                $log->fillWithRequest($this->getRequest(), $this);
                $log->persist();
            } catch (Exception $e) {
                $this->getUtils()->logException($e, "Can't write AdminActionLog");
                if ($this->getEnv('DEBUG')) {
                    return $this->getUtils()->errorException($e);
                }
            }
        }

        return $return;
    }

    /**
     * {@inheritfocs}
     *
     * @return \League\Plates\Template\Template
     */
    protected function prepareTemplate()
    {
        $template = $this->getTemplates()->make('admin::'.$this->getTemplateName());
        $template->data($this->getTemplateData()+$this->getBaseTemplateData());

        $this->getAssets()->addJs(
            "\$('#admin').appAdmin(".json_encode(
                [
                'checkLoggedUrl' => $this->getUrl('admin.json.checksession'),
                'logoutUrl' => $this->getUrl('admin.logout'),
                ]
            ).");"
        );

        $template->start('scripts');
        echo $this->getAssets()->renderPageInlineJS();
        $template->stop();

        $template->start('action_buttons');
        echo $this->renderActionButtons();
        $template->stop();

        return $template;
    }

    /**
     * {@inheritdocs}
     *
     * @return array
     */
    protected function getBaseTemplateData()
    {
        $out = parent::getBaseTemplateData();
        $out['current_user'] = $this->getCurrentUser();
        $out['body_class'] = 'admin-page '.str_replace('.', '-', $this->getRouteName());
        return $out;
    }

    /**
     * gets page title
     *
     * @return string
     */
    public function getPageTitle()
    {
        return $this->page_title;
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    public function getCurrentLocale()
    {
        if ($this->locale == null) {
            $this->locale = $this->getCurrentUser()->getLocale() ?? 'en';
        }

        $this->getApp()->setCurrentLocale($this->locale);
        return $this->locale;
    }

    /**
     * adds a back button to page
     */
    public function addBackButton()
    {
        $this->addActionLink('back-btn', 'back-btn', $this->getUtils()->getIcon('rewind').' '.$this->getUtils()->translate('Back', $this->getCurrentLocale()), $this->getControllerUrl());
    }

    /**
     * {@inheritdocs}
     *
     * @return array
     */
    protected function getTemplateData()
    {
        return $this->templateData;
    }

    /**
     * gets access permission name
     *
     * @return string
     */
    abstract protected function getAccessPermission();
}
