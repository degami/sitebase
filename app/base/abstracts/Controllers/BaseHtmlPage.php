<?php

/**
 * SiteBase
 * PHP Version 8.0
 *
 * @category CMS / Framework
 * @package  Degami\Sitebase
 * @author   Mirko De Grandis <degami@github.com>
 * @license  MIT https://opensource.org/licenses/mit-license.php
 * @link     https://github.com/degami/sitebase
 */

namespace App\Base\Abstracts\Controllers;

use App\Base\Exceptions\PermissionDeniedException;
use App\Base\Tools\DataCollector\PageDataCollector;
use App\Base\Tools\DataCollector\UserDataCollector;
use DebugBar\DebugBar;
use DebugBar\DebugBarException;
use Degami\Basics\Exceptions\BasicException;
use DI\DependencyException;
use DI\NotFoundException;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Cookie;
use League\Plates\Template\Template;
use App\Site\Routing\RouteInfo;
use App\Base\Traits\PageTrait;
use Throwable;

/**
 * Base for pages rendering an html response
 */
abstract class BaseHtmlPage extends BasePage
{
    use PageTrait;

    /**
     * @var Template|null template object
     */
    protected ?Template $template = null;

    /**
     * prepare template object
     *
     * @return Template
     * @throws BasicException
     */
    protected function prepareTemplate(): Template
    {
        $template = $this->getTemplates()->make($this->getTemplateName());
        $template->data($this->getTemplateData() + $this->getBaseTemplateData());

        return $template;
    }

    /**
     * controller entrypoint
     *
     * @param RouteInfo|null $route_info
     * @param array $route_data
     * @return BasePage|BaseHtmlPage|Response
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     * @throws PermissionDeniedException
     * @throws Throwable
     * @throws DebugBarException
     */
    public function renderPage(RouteInfo $route_info = null, $route_data = []): BasePage|Response
    {
        $this->route_info = $route_info;

        $before_result = $this->beforeRender();
        if ($before_result instanceof Response) {
            return $before_result;
        }

        $this->template = $this->prepareTemplate();
        if (method_exists($this, 'getCurrentLocale')) {
            $this->getApp()->setCurrentLocale($this->getCurrentLocale());
        }

        if ($this->getEnv('DEBUG')) {
            /** @var DebugBar $debugbar */
            $debugbar = $this->getContainer()->get('debugbar');
            $debugbar->addCollector(new PageDataCollector($this));
            $debugbar->addCollector(new UserDataCollector($this->getCurrentUser()));
        }

        return $this->process($route_info, $route_data);
    }

    /**
     * {@inheritdocs}
     *
     * @param RouteInfo|null $route_info
     * @param array $route_data
     * @return Response
     * @throws BasicException
     * @throws Throwable
     * @throws PhpfastcacheSimpleCacheException
     */
    public function process(RouteInfo $route_info = null, $route_data = []): Response
    {
        try {
            $template_html = '';
            $page_cache_key = 'site.fpc.' . trim(str_replace("/", ".", $this->getRouteInfo()->getRoute()), '.');

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
        } catch (Throwable $e) {
            return $this->getUtils()->exceptionPage($e, $this->getRequest());
        }
    }

    /**
     * get current template
     *
     * @return Template|null
     */
    public function getTemplate(): ?Template
    {
        return $this->template;
    }

    /**
     * prepares basic template data
     *
     * @return array
     */
    public function getBaseTemplateData(): array
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
    public function getInfo(): array
    {
        $templateName = $this->getTemplateName();
        $variablesInfo = [];
        if ($this->getTemplate() instanceof Template) {
            $template_data = $this->getTemplate()->data();
        } else {
            $template_data = $this->getTemplateData();
        }

        foreach ($template_data as $index => $elem) {
            $variablesInfo[] = "{$index}[" . ((is_object($elem)) ? get_class($elem) : gettype($elem)) . "]";
        }

        $out = [
            'route_info' => ($this->getRouteInfo() instanceof RouteInfo) ?
                $this->getRouteInfo()->toString() : $this->getRouteInfo(),
            'locale' => is_callable([$this, 'getCurrentLocale']) ?
                $this->getCurrentLocale() :
                ($template_data['locale'] ?? null),
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
     * @return self
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function addFlashMessage(string $type, string $message): BaseHtmlPage
    {
        $flash_messages = $this->getFlashMessages();
        $flash_messages[$type][] = $message;

        $cookie = $this->getContainer()->make(Cookie::class, [
            'name' => 'flash_messages',
            'value' => json_encode($flash_messages),
            'expire' => time() + 3600,
            'path' => '/',
            'sameSite' => 'Lax',
        ]);
        $this->getResponse()->headers->setCookie($cookie);

        return $this;
    }

    /**
     * removes all currently stored flash messages
     *
     * @return self
     */
    public function dropFlashMessages(): BaseHtmlPage
    {
        $this->getResponse()->headers->clearCookie('flash_messages');

        return $this;
    }

    /**
     * gets currently stored flash messages
     *
     * @return array|null
     */
    public function getFlashMessages(): ?array
    {
        return json_decode($this->getRequest()->cookies->get('flash_messages'), true);
    }

    /**
     * gets current page template name
     *
     * @return string
     */
    abstract protected function getTemplateName(): string;

    /**
     * gets current page template data
     *
     * @return array
     */
    abstract protected function getTemplateData(): array;
}
