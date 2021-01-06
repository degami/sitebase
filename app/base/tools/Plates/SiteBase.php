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

namespace App\Base\Tools\Plates;

use App\Base\Tools\Assets\Manager as AssetsManager;
use App\Base\Tools\Utils\Globals;
use App\Base\Tools\Utils\HtmlPartsRenderer;
use App\Base\Tools\Utils\SiteData;
use App\Site\Models\Website;
use DebugBar\StandardDebugBar;
use Degami\Basics\Exceptions\BasicException;
use DI\DependencyException;
use DI\NotFoundException;
use \League\Plates\Engine;
use \League\Plates\Extension\ExtensionInterface;
use \App\Base\Abstracts\Controllers\BasePage;
use \App\App;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use \Psr\Container\ContainerInterface;

/**
 * Plates template helper
 */
class SiteBase implements ExtensionInterface
{
    /**
     * @var Engine templates engine
     */
    protected $engine;

    /**
     * @var ContainerInterface container
     */
    protected $container;

    /**
     * constructor
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdocs}
     *
     * @param Engine $engine [description]
     * @return void
     */
    public function register(Engine $engine)
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
     * @return Website
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getCurrentWebsite(): Website
    {
        return $this->getSiteData()->getCurrentWebsite();
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
        return $this->getCurrentWebsite()->getSiteName();
    }

    /**
     * gets current locale
     *
     * @return string
     */
    public function getCurrentLocale(): ?string
    {
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
    public function translate(string $string): string
    {
        return $this->getUtils()->translate($string, $this->getCurrentLocale());
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
        return $this->getAssets()->assetUrl($asset_path);
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
     * @return App
     */
    protected function getApp(): App
    {
        return $this->container->get('app');
    }

    /**
     * gets gravatar html
     *
     * @param string $email
     * @param integer $s
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
    public function drawIcon(string $icon_name)
    {
        echo $this->getHtmlRenderer()->getIcon($icon_name);
    }

    /**
     * gets env variable
     *
     * @param string $variable
     * @param null $default
     * @return mixed
     * @throws BasicException
     */
    public function env(string $variable, $default = null)
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
    public function renderFlashMessages(BasePage $controller)
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
}
