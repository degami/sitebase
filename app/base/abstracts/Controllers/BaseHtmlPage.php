<?php

/**
 * SiteBase
 * PHP Version 8.3
 *
 * @category CMS / Framework
 * @package  Degami\Sitebase
 * @author   Mirko De Grandis <degami@github.com>
 * @license  MIT https://opensource.org/licenses/mit-license.php
 * @link     https://github.com/degami/sitebase
 */

namespace App\Base\Abstracts\Controllers;

use App\Base\Routing\RouteInfo;
use App\Base\Traits\PageTrait;
use App\Base\Exceptions\PermissionDeniedException;
use App\Base\Interfaces\Controller\HtmlPageInterface;
use App\Base\Tools\DataCollector\PageDataCollector;
use App\Base\Tools\DataCollector\RedisDataCollector;
use App\Base\Tools\DataCollector\UserDataCollector;
use App\Base\Tools\DataCollector\BlocksDataCollector;
use App\Base\Models\Rewrite;
use App\Base\Tools\DataCollector\EnvironmentDataCollector;
use DebugBar\DebugBar;
use DebugBar\DebugBarException;
use Degami\Basics\Exceptions\BasicException;
use DI\DependencyException;
use DI\NotFoundException;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Cookie;
use League\Plates\Template\Template;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Throwable;

/**
 * Base for pages rendering an html response
 */
abstract class BaseHtmlPage extends BasePage implements HtmlPageInterface
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
     * before render hook
     *
     * @return Response|self
     * @throws PermissionDeniedException
     */
    protected function beforeRender(): BasePage|Response
    {
        if ($this->getEnvironment()->canDebug()) {
            $this->getAssets()->addHeadJs($this->getAssets()->assetUrl('/js/debugbar-EnvironmentWidget.js'));            
        }

        return parent::beforeRender();
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
    public function renderPage(?RouteInfo $route_info = null, array $route_data = []): BasePage|Response
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

        if ($this->getEnvironment()->canDebug()) {
            /** @var DebugBar $debugbar */
            $debugbar = $this->getDebugbar();
            if (!$debugbar->hasCollector(PageDataCollector::NAME)) {
                $debugbar->addCollector(new PageDataCollector($this));
            }
            if (!$debugbar->hasCollector(BlocksDataCollector::NAME)) {
                $debugbar->addCollector(new BlocksDataCollector());
            }
            if (!$debugbar->hasCollector(UserDataCollector::NAME)) {
                $debugbar->addCollector(new UserDataCollector($this->getCurrentUser()));
            }
            if ($this->getRedis()->isEnabled()) {
                if (!$debugbar->hasCollector(RedisDataCollector::NAME)) {
                    $debugbar->addCollector(new RedisDataCollector($this->getRedis()));
                }
            }
            if (!$debugbar->hasCollector(EnvironmentDataCollector::NAME)) {
                $debugbar->addCollector(new EnvironmentDataCollector($this->getEnvironment()));
            }
        }

        return $this->process($route_info, $route_data);
    }

    /**
     * {@inheritdoc}
     *
     * @param RouteInfo|null $route_info
     * @param array $route_data
     * @return Response
     * @throws BasicException
     * @throws Throwable
     * @throws PhpfastcacheSimpleCacheException
     */
    public function process(?RouteInfo $route_info = null, array $route_data = []): Response
    {
        try {
            $template_html = '';
            //$page_cache_key = 'site.fpc.' . trim(str_replace("/", ".", $this->getRouteInfo()->getRoute()), '.');
            $page_cache_key = $this->getFpcCacheKey();
            if ($this->getRequest()->getMethod() == 'GET' && !$this->getEnvironment()->isDebugActive() && $this->getEnvironment()->getVariable('ENABLE_FPC')) {
                if ($this->canBeFPC() && $this->getCache()->has($page_cache_key)) {
                    $template_html = $this->getCache()->get($page_cache_key);
                }
            }

            if (empty($template_html)) {
                $template_html = $this->template->render();
                if ($this->getRequest()->getMethod() == 'GET' && !$this->getEnvironment()->isDebugActive() && $this->getEnvironment()->getVariable('ENABLE_FPC')) {
                    if ($this->canBeFPC()) {
                        $this->getCache()->set($page_cache_key, $template_html);
                    }
                }
            }

            return $this->getUtils()->createHtmlResponse($template_html, 200, $this);
        } catch (Throwable $e) {
            return $this->getUtils()->exceptionPage($e);
        }
    }

    protected function normalizeCacheKey($key) : string
    {
        return strtolower(preg_replace("/\.+/", '.', str_replace([':','/'], '.', preg_replace("/\s+/i", "+", $key))));
    }

    /**
     * gets cache key
     */
    public function getCacheKey() : string
    {
        if ($this->getRewriteObject() == null) {
            return $this->normalizeCacheKey('site.' . $this->getSiteData()->getCurrentWebsiteId() . '.' . trim($this->getRouteInfo()->getRoute(), '.'));            
        }

        return $this->normalizeCacheKey(
            'site.'.$this->getRewriteObject()?->getWebsiteId().
            '.' . $this->getRewriteObject()?->getLocale() . 
            '.' . trim($this->getRouteInfo()->getRouteName() ?? "", '.')
        );
    }

    /**
     * gets fpc cache key
     */
    public function getFpcCacheKey() : string
    {
        return 'fpc.'.$this->getCacheKey();
    }

    /**
     * gets rewrite object
     */
    public function getRewriteObject() : ?Rewrite
    {
        if ($this->getRouteInfo()->getRewrite()) {
            return $this->containerCall([Rewrite::class, 'load'], ['id' => $this->getRouteInfo()->getRewrite()]);
        }

        return null;
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
     * @param bool $direct store message in session
     * @return self
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function addFlashMessage(string $type, string $message, bool $direct = false): BaseHtmlPage
    {
        $flash_messages = $this->getFlashMessages($direct);
        $flash_messages[$type][] = $message;

        // store flash messages in cookie - direct stores in session
        if (!$direct) {
            $cookie = $this->containerMake(Cookie::class, [
                'name' => 'flash_messages',
                'value' => json_encode($flash_messages),
                'expire' => time() + 3600,
                'path' => '/',
                'sameSite' => Cookie::SAMESITE_LAX,
            ]);
            $this->getResponse()->headers->setCookie($cookie);    
        } else {
            $_SESSION['flash_messages'] = json_encode($flash_messages);
        }

        return $this;
    }

    /**
     * adds a success flash message
     * 
     * @param string $message
     * @param bool $direct store message in session
     * @return self
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function addSuccessFlashMessage(string $message, bool $direct = false) : BaseHtmlPage
    {
        return $this->addFlashMessage(self::FLASHMESSAGE_SUCCESS, $message, $direct);
    }

    /**
     * adds a warning flash message
     * 
     * @param string $message
     * @param bool $direct store message in session
     * @return self
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function addWarningFlashMessage(string $message, bool $direct = false) : BaseHtmlPage
    {
        return $this->addFlashMessage(self::FLASHMESSAGE_WARNING, $message, $direct);
    }

    /**
     * adds a error flash message
     * 
     * @param string $message
     * @param bool $direct store message in session
     * @return self
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function addErrorFlashMessage(string $message, bool $direct = false) : BaseHtmlPage
    {
        return $this->addFlashMessage(self::FLASHMESSAGE_ERROR, $message, $direct);
    }

    /**
     * adds an info flash message
     * 
     * @param string $message
     * @param bool $direct store message in session
     * @return self
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function addInfoFlashMessage(string $message, bool $direct = false) : BaseHtmlPage
    {
        return $this->addFlashMessage(self::FLASHMESSAGE_INFO, $message, $direct);
    }

    /**
     * removes all currently stored flash messages
     *
     * @return self
     */
    public function dropFlashMessages(): BaseHtmlPage
    {
        unset($_SESSION['flash_messages']);
        $this->getResponse()->headers->clearCookie('flash_messages');

        return $this;
    }

    /**
     * gets currently stored flash messages
     *
     * @param bool $direct session stored flash messages
     * @return array|null
     */
    public function getFlashMessages(bool $direct = false): ?array
    {
        if ($direct) {
            return json_decode((string) ($_SESSION['flash_messages'] ?? null), true);
        }

        return json_decode((string) $this->getRequest()->cookies->get('flash_messages'), true);
    }

    /**
     * gets controller route name
     */
    static public function getPageRouteName() : string
    {
        $controllerClass = static::class;
        $path = str_replace("app/site/controllers/", "", str_replace("\\", "/", 
            str_replace("app/base/controllers/", "", str_replace("\\", "/", strtolower($controllerClass)))
        ));
        $route_name = str_replace("/", ".", trim($path, "/"));

        return $route_name;
    }

    /**
     * returns a redirect object to same page
     *
     * @return RedirectResponse
     */
    protected function refreshPage() : RedirectResponse 
    {
        return $this->doRedirect($this->getControllerUrl());
    }
}
