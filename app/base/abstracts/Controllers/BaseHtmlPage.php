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

use \Exception;
use \Psr\Container\ContainerInterface;
use \Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\HttpFoundation\Cookie;
use \League\Plates\Template\Template;
use \App\App;
use \App\Site\Routing\RouteInfo;
use \App\Base\Traits\PageTrait;

/**
 * Base for pages rendering an html response
 */
abstract class BaseHtmlPage extends BasePage
{
    use PageTrait;

    /**
     * @var Template template object
     */
    protected $template;

    /**
     * prepare template object
     *
     * @return Template
     */
    protected function prepareTemplate()
    {
        $template = $this->getTemplates()->make($this->getTemplateName());
        $template->data($this->getTemplateData()+$this->getBaseTemplateData());

        return $template;
    }

    /**
     * controller entrypoint
     *
     * @param  RouteInfo|null $route_info
     * @param  array          $route_data
     * @return Response|self
     */
    public function renderPage(RouteInfo $route_info = null, $route_data = [])
    {
        $this->route_info = $route_info;

        $before_result = $this->beforeRender();
        if ($before_result instanceof Response) {
            return $before_result;
        }

        $this->template = $this->prepareTemplate();
        $this->getApp()->setCurrentLocale($this->getCurrentLocale());
        if ($this->getEnv('DEBUG')) {
            $debugbar = $this->getContainer()->get('debugbar');
            $debugbar->addCollector(new \App\Base\Tools\DataCollector\PageDataCollector($this));
        }

        return $this->process($route_info, $route_data);
    }

    /**
     * {@inheritdocs}
     *
     * @param  RouteInfo|null $route_info
     * @param  array          $route_data
     * @return Response
     */
    public function process(RouteInfo $route_info = null, $route_data = [])
    {
        try {
            $template_html = '';
            $page_cache_key = 'site.fpc.'.trim(str_replace("/", ".", $this->getRouteInfo()->getRoute()), '.');

            if ($this->getRequest()->getMethod() == 'GET' && !$this->getEnv('DEBUG') && $this->getEnv('ENABLE_FPC')) {
                if ($this->canBeFPC() && $this->getCache()->has($page_cache_key)) {
                    $template_html = $this->getCache()->get($page_cache_key);
                }
            }

            if (empty($template_html)) {
                $template_html = $this->template->render();
                if ($this->getRequest()->getMethod() == 'GET' && !$this->getEnv('DEBUG') && $this->getEnv('ENABLE_FPC')) {
                    $this->getCache()->set($page_cache_key, $template_html);
                }
            }

            return $this
                ->getResponse()
                ->prepare($this->getRequest())
                ->setContent($template_html);
        } catch (Exception $e) {
            return $this->getUtils()->exceptionPage($e);
        }
    }

    /**
     * get current template
     *
     * @return Template
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * prepares basic template data
     *
     * @return array
     */
    protected function getBaseTemplateData()
    {
        return [
            'controller' => $this,
            'route_info' => $this->getRouteInfo(),
            'request' => $this->getRequest(),
        ];
    }

    /**
     * gets info about current page
     *
     * @return array
     */
    public function getInfo()
    {
        $templateName = $this->getTemplateName();
        $templateData = $this->getTemplateData();
        $variablesInfo = [];
        if ($this->getTemplate() instanceof Template) {
            $templateName = $this->getTemplateName();
            $templateData = $this->getTemplate()->data();
        }

        foreach ($templateData as $index => $elem) {
            $variablesInfo[] =  "{$index}[". ((is_object($elem)) ? get_class($elem) : gettype($elem))."]";
        }

        $out = [
            'route_info' => ($this->getRouteInfo() instanceof RouteInfo) ?
                                $this->getRouteInfo()->toString() : $this->getRouteInfo(),
            'locale' => is_callable([$this, 'getCurrentLocale']) ?
                                $this->getCurrentLocale() :
                                ($templateData['locale'] ?? null),
            'template_name' => $templateName,
        ];

        if ($this->getTemplate() instanceof Template) {
            $out['path'] = $this->getTemplate()->path();
        }

        $out['variables'] = implode(", ", $variablesInfo);
        return $out;
    }

    /**
     * adds a flash message for next response
     *
     * @param string $type
     * @param string $message
     */
    public function addFlashMessage($type, $message)
    {
        $flash_messages = $this->getFlashMessages();
        $flash_messages[$type][] = $message;

        $this->getResponse()->headers->setCookie(new Cookie('flash_messages', json_encode($flash_messages), time()+3600, "/"));

        return $this;
    }

    /**
     * removes all currently stored flash messages
     *
     * @return self
     */
    public function dropFlashMessages()
    {
        $this->getResponse()->headers->clearCookie('flash_messages');

        return $this;
    }

    /**
     * gets currently stored flash messages
     *
     * @return array
     */
    public function getFlashMessages()
    {
        return json_decode($this->getRequest()->cookies->get('flash_messages'));
    }

    /**
     * gets current page template name
     *
     * @return string
     */
    abstract protected function getTemplateName();

    /**
     * gets current page template data
     *
     * @return array
     */
    abstract protected function getTemplateData();
}
