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

namespace App\Base\Tools\Plates;

use App\Base\Tools\Assets\Manager as AssetsManager;
use App\Base\Tools\Utils\Globals;
use App\Base\Tools\Utils\HtmlPartsRenderer;
use App\Base\Tools\Utils\SiteData;
use App\Base\Models\Website;
use DebugBar\StandardDebugBar;
use Degami\Basics\Exceptions\BasicException;
use DI\DependencyException;
use DI\NotFoundException;
use League\Plates\Engine;
use League\Plates\Extension\ExtensionInterface;
use App\Base\Abstracts\Controllers\BasePage;
use App\App;
use App\Base\Abstracts\Controllers\AdminPage;
use Degami\Basics\Html\TagElement;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use Psr\Container\ContainerInterface;

/**
 * Plates template helper
 */
class SiteBase implements ExtensionInterface
{
    /**
     * @var Engine templates engine
     */
    protected Engine $engine;

    /**
     * @var Website|null current website cached element
     */
    protected static ?Website $currentWebsite = null;

    public $template;

    /**
     * constructor
     *
     * @param ContainerInterface $container
     */
    public function __construct(
        protected ContainerInterface $container
    ) { }

    /**
     * {@inheritdoc}
     *
     * @param Engine $engine [description]
     * @return void
     */
    public function register(Engine $engine) : void
    {
        $this->engine = $engine;
        $engine->registerFunction('sitebase', [$this, 'getObject']);
    }

    /**
     * gets helper object
     *
     * @return self
     */
    public function getObject(): SiteBase
    {
        return $this;
    }

    /**
     * gets current website
     *
     * @return Website|null
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getCurrentWebsite(): ?Website
    {
        if (is_null(static::$currentWebsite)) {
            static::$currentWebsite = $this->getSiteData()->getCurrentWebsite();
        }
        return static::$currentWebsite;
    }

    /**
     * gets current website name
     *
     * @return string
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getCurrentWebsiteName(): string
    {
        if ($this->getCurrentWebsite() == null) {
            return "";
        }

        return $this->getCurrentWebsite()->getSiteName();
    }

    /**
     * gets current locale
     *
     * @return string|null
     */
    public function getCurrentLocale(): ?string
    {
        if ($this->getApp() == null) {
            return "";
        }

        return $this->getApp()->getCurrentLocale();
    }

    /**
     * render block for region
     *
     * @param $region
     * @param BasePage $controller
     * @return string
     * @throws BasicException
     * @throws PhpfastcacheSimpleCacheException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function renderBlocks($region, BasePage $controller): string
    {
        return $this->getHtmlRenderer()->renderBlocks($region, $this->getCurrentLocale(), $controller);
    }

    /**
     * gets string translations
     *
     * @param string $string
     * @return string
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function translate(string $string, array $params = []): string
    {
        return $this->getUtils()->translate($string, params: $params, locale: $this->getCurrentLocale());
    }

    /**
     * return debugger object
     *
     * @return StandardDebugBar
     */
    public function getDebugbar(): StandardDebugBar
    {
        return $this->container->get('debugbar');
    }

    /**
     * gets asset url
     *
     * @param string $asset_path
     * @return string
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     * @throws PhpfastcacheSimpleCacheException
     */
    public function assetUrl(string $asset_path): string
    {
        return $this->getAssets()->assetUrl($asset_path, $this->getCurrentWebsite()->getId());
    }

    /**
     * gets route url by name and params
     *
     * @param string $route_name
     * @param array $route_params
     * @return string
     */
    public function getUrl(string $route_name, $route_params = []): string
    {
        if (str_starts_with($route_name, 'admin')) {
            return $this->container->get('admin_router')->getUrl($route_name, $route_params);            
        }
        return $this->container->get('web_router')->getUrl($route_name, $route_params);
    }

    /**
     * gets assets manager object
     *
     * @return AssetsManager
     */
    protected function getAssets(): AssetsManager
    {
        return $this->container->get('assets');
    }

    /**
     * gets html parts renderer
     *
     * @return HtmlPartsRenderer
     */
    protected function getHtmlRenderer(): HtmlPartsRenderer
    {
        return $this->container->get('html_renderer');
    }

    /**
     * gets utils
     *
     * @return Globals
     */
    protected function getUtils(): Globals
    {
        return $this->container->get('utils');
    }

    /**
     * gets site_data
     *
     * @return SiteData
     */
    protected function getSiteData(): SiteData
    {
        return $this->container->get('site_data');
    }


    /**
     * gets app object
     *
     * @return App|null
     */
    protected function getApp(): ?App
    {
        try {
            return $this->container->get('app');
        } catch (\Exception $e) {
        }

        return null;
    }

    /**
     * gets gravatar html
     *
     * @param string $email
     * @param int $s
     * @param string $d
     * @param string $r
     * @param string $class
     * @return string
     */
    public function getGravatar(string $email, $s = 80, $d = 'mp', $r = 'g', $class = 'rounded-circle'): string
    {
        return $this->getHtmlRenderer()->getGravatar($email, $s, $d, $r, $class);
    }

    /**
     * draws icon
     *
     * @param string $icon_name
     * @return void
     * @throws BasicException
     */
    public function drawIcon(string $icon_name, $attributes = [], $translateSectionsName = false): void
    {
        if ($translateSectionsName == true) {
            $icon_name = match($icon_name) {
                'main' => 'award',
                'cms' => 'feather',
                'commerce' => 'shopping-bag',
                'site' => 'layout',
                'system' => 'settings',
                'tools' => 'tool',
                default => $icon_name
            };    
        }

        echo $this->getHtmlRenderer()->getIcon($icon_name, $attributes);
    }

    /**
     * gets env variable
     *
     * @param string $variable
     * @param null $default
     * @return mixed
     * @throws BasicException
     */
    public function env(string $variable, mixed $default = null): mixed
    {
        return $this->getUtils()->getEnv($variable, $default);
    }

    /**
     * gets app version
     *
     * @return string
     */
    public function version(): string
    {
        $arr = file(App::getDir(App::ROOT) . DS . 'VERSION');
        return trim(array_pop($arr));
    }

    /**
     * draws flash messages
     *
     * @param BasePage $controller
     * @return void
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function renderFlashMessages(BasePage $controller): void
    {
        echo $this->getHtmlRenderer()->renderFlashMessages($controller);
    }

    /**
     * summarize text
     *
     * @param string $text
     * @param int $max_words
     * @return string
     */
    public function summarize(string $text, int $max_words = 10): string
    {
        $max_words = abs(intval($max_words));
        $words = preg_split("/\s+/", strip_tags($text));
        if (count($words) < $max_words) {
            return $text;
        }
        return implode(" ", array_slice($words, 0, $max_words)) . ' ...';
    }

    /**
     * returns page regions
     *
     * @return array
     * @throws BasicException
     */
    public function getPageRegions(): array
    {
        return $this->getSiteData()->getPageRegions();
    }

    /**
     * returns admin links for sidebar
     *
     * @return array
     */
    public function getAdminSidebarVisibleLinks(AdminPage $controller) : array
    {
        return $this->getSiteData()->getAdminSidebarVisibleLinks($controller);
    }

    public function renderAdminTable(array $elements, ?array $header = null, ?BasePage $current_page = null) : string
    {
        return $this->getHtmlRenderer()->renderAdminTable($elements, $header, $current_page);    
    }

    public function getDarkModeSwitch(AdminPage $controller) : string
    {
        $user = $controller->getCurrentUser();
        $uiSettings = $user->getUserSession()->getSessionKey('uiSettings');
        $isDarkMode = $uiSettings['darkMode'] ?? $this->getUtils()->getEnv('ADMIN_DARK_MODE', false);

        $checkbox = $this->getApp()->containerMake(TagElement::class, ['options' => [
            'tag' => 'input',
            'id' => 'darkmode-selector',
            'type' => 'checkbox',
            'attributes' => [
                'class' => 'paginator-items-choice',
                'style' => 'width: 50px',
                'checked' => $isDarkMode ? 'checked' : '',
            ],
        ]]);
        $span = $this->getApp()->containerMake(TagElement::class, ['options' => [
            'tag' => 'span',
            'attributes' => [
                'class' => 'slider',
            ],
        ]]);

        $label = $this->getApp()->containerMake(TagElement::class, ['options' => [
            'tag' => 'label',
            'attributes' => [
                'class' => 'switch',
            ],
            'children' => [
                $checkbox,
                $span,
            ],
        ]]);

        return "<div class=\"darkmode-switch\">" . __('Dark Mode') . ' ' . $label."</div>";

        //return "<label for='darkmode-selector'>".__('Dark Mode').'</label>' . $checkbox;
    }

    public function getQrCode($url) : string
    {
        return $this->getHtmlRenderer()->getQrCode($url);    
    }

    public function getAvailableAIs(bool $withNames = false) : array
    {
        return $this->getSiteData()->getAvailableAIs($withNames);
    }

    public function isAiAvailable(string|array|null $ai = null) : bool
    {
        return $this->getSiteData()->isAiAvailable($ai);
    }

    public function getCurrentUrl() : string
    {
        return $this->getSiteData()->getRequest()->getUri();
    }

    public function isCommerceAvailable() : bool
    {
        return boolval($this->getSiteData()->getEnv('ENABLE_COMMERCE'));
    }

    public function formatPrice(float $price, string $currencyCode = '') : string
    {
        return $this->getUtils()->formatPrice($price, $currencyCode ?: $this->getCurrentWebsite()->getDefaultCurrencyCode());
    }

    public function formatBytes(int $size) : string
    {
        return $this->getUtils()->formatBytes($size);
    }
}