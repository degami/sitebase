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

namespace App\Base\Abstracts\Controllers;

use Degami\Basics\Exceptions\BasicException;
use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use League\Plates\Template\Template;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use \Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\HttpFoundation\Response;
use \Psr\Container\ContainerInterface;
use \App\Base\Traits\AdminTrait;
use \App\Base\Exceptions\PermissionDeniedException;
use \App\Site\Routing\RouteInfo;
use \App\Site\Models\AdminActionLog;
use \App\App;
use Throwable;

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
    protected $template_data = [];

    /**
     * {@inheritdocs}
     *
     * @param ContainerInterface $container
     * @param Request|null $request
     * @param RouteInfo $route_info
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function __construct(ContainerInterface $container, Request $request, RouteInfo $route_info)
    {
        $this->page_title = ucwords(str_replace("_", " ", implode("", array_slice(explode("\\", get_class($this)), -1, 1))));

        parent::__construct($container, $request, $route_info);
        if (!$this->getTemplates()->getFolders()->exists('admin')) {
            $this->getTemplates()->addFolder('admin', App::getDir(App::TEMPLATES) . DS . 'admin');
        }
    }

    /**
     * returns valid route HTTP verbs
     *
     * @return array
     */
    public static function getRouteVerbs(): array
    {
        return ['GET', 'POST'];
    }

    /**
     * before render hook
     *
     * @return Response|self
     * @throws BasicException
     * @throws PermissionDeniedException
     */
    protected function beforeRender()
    {
        if (!$this->checkCredentials() || !$this->checkPermission($this->getAccessPermission())) {
            throw new PermissionDeniedException();
        }

        return parent::beforeRender();
    }

    /**
     * {@inheritdocs}
     *
     * @param RouteInfo|null $route_info
     * @param array $route_data
     * @return Response
     * @throws PermissionDeniedException
     * @throws BasicException
     * @throws PhpfastcacheSimpleCacheException
     * @throws Throwable
     */
    public function renderPage(RouteInfo $route_info = null, $route_data = []): Response
    {
        $return = parent::renderPage($route_info, $route_data);

        if ($this->getSiteData()->getConfigValue('app/backend/log_requests') == true) {
            try {
                $log = $this->getContainer()->make(AdminActionLog::class);
                $log->fillWithRequest($this->getRequest(), $this);
                $log->persist();
            } catch (Exception $e) {
                $this->getUtils()->logException($e, "Can't write AdminActionLog", $this->getRequest());
                if ($this->getEnv('DEBUG')) {
                    return $this->getUtils()->exceptionPage($e);
                }
            }
        }

        return $return;
    }

    /**
     * {@inheritfocs}
     *
     * @return Template
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     */
    protected function prepareTemplate(): Template
    {
        $template = $this->getTemplates()->make('admin::' . $this->getTemplateName());
        $template->data($this->getTemplateData() + $this->getBaseTemplateData());

        $this->getAssets()->addJs(
            "\$('#admin').appAdmin(" . json_encode(
                [
                    'checkLoggedUrl' => $this->getUrl('admin.json.checksession'),
                    'logoutUrl' => $this->getUrl('admin.logout'),
                ]
            ) . ");"
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
     * @throws DependencyException
     * @throws NotFoundException
     */
    protected function getBaseTemplateData(): array
    {
        $out = parent::getBaseTemplateData();
        $out['current_user'] = $this->getCurrentUser();
        $out['body_class'] = 'admin-page ' . str_replace('.', '-', $this->getRouteName());
        return $out;
    }

    /**
     * gets page title
     *
     * @return string
     */
    public function getPageTitle(): string
    {
        return $this->page_title;
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getCurrentLocale(): ?string
    {
        if ($this->locale == null) {
            $this->locale = $this->getCurrentUser()->getLocale() ?? 'en';
        }

        $this->getApp()->setCurrentLocale($this->locale);
        return $this->locale;
    }

    /**
     * adds a back button to page
     *
     * @param array|null $query_params
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function addBackButton($query_params = null)
    {
        if (is_array($query_params)) {
            $query_params = http_build_query($query_params);
        }

        if ($query_params != null) {
            $query_params = trim($query_params);
            if (strlen($query_params) > 0) {
                $query_params = ($query_params[0] != '?' ? '?' : '') . $query_params;
            }
        }

        $this->addActionLink('back-btn', 'back-btn', $this->getUtils()->getIcon('rewind') . ' ' . $this->getUtils()->translate('Back', $this->getCurrentLocale()), $this->getControllerUrl() . $query_params);
    }

    /**
     * {@inheritdocs}
     *
     * @return array
     */
    protected function getTemplateData(): array
    {
        return $this->template_data;
    }

    /**
     * gets access permission name
     *
     * @return string
     */
    abstract protected function getAccessPermission(): string;
}
