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

use \League\Plates\Engine;
use \League\Plates\Extension\ExtensionInterface;
use \App\Base\Abstracts\BasePage;
use \App\App;
use \Psr\Container\ContainerInterface;

/**
 * Plates template helper
 */
class SiteBase implements ExtensionInterface
{
    /** @var Engine templates engine */
    protected $engine;

    /** @var ContainerInterface container */
    protected $container;

    /**
     * constructor
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdocs}
     * @param  Engine $engine [description]
     * @return void
     */
    public function register(Engine $engine)
    {
        $this->engine = $engine;

//        foreach (get_class_methods(static::class) as $methodName) {
//            $engine->registerFunction($methodName, [$this, $methodName]);
//        }

        $engine->registerFunction('sitebase', [$this, 'getObject']);
    }

    /**
     * gets helper object
     * @return self
     */
    public function getObject()
    {
        return $this;
    }

    /**
     * gets current locale
     * @return string
     */
    public function getCurrentLocale()
    {
        return $this->container->get('app')->getCurrentLocale();
    }

    /**
     * render block for region
     * @param  string   $region
     * @param  BasePage $controller
     * @return string
     */
    public function renderBlocks($region, BasePage $controller)
    {
        return $this->getHtmlRenderer()->renderBlocks($region, $this->getCurrentLocale(), $controller);
    }

    /**
     * gets string translations
     * @param  string $string
     * @return string
     */
    public function translate($string)
    {
        return $this->container->get('utils')->translate($string, $this->getCurrentLocale());
    }

    /**
     * return debugger object
     * @return \DebugBar\StandardDebugBar
     */
    public function getDebugbar()
    {
        return $this->container->get('debugbar');
    }

    /**
     * gets asset url
     * @param  string $asset_path
     * @return string
     */
    public function assetUrl($asset_path)
    {
        return $this->getAssets()->assetUrl($asset_path);
    }

    /**
     * gets route url by name and params
     * @param  string $route_name
     * @param  array  $route_params
     * @return string
     */
    public function getUrl($route_name, $route_params = [])
    {
        return $this->container->get('routing')->getUrl($route_name, $route_params);
    }

    /**
     * gets assets manager object
     * @return \App\Base\Tools\Assets\Manager
     */
    protected function getAssets()
    {
        return $this->container->get('assets');
    }

    /**
     * gets html parts renderer
     * @return \App\Base\Tools\Utils\HtmlPartsRenderer
     */
    protected function getHtmlRenderer()
    {
        return $this->container->get('html_renderer');
    }

    /**
     * gets app object
     * @return \App\App
     */
    protected function getApp()
    {
        return $this->container->get('app');
    }

    /**
     * gets gravatar html
     * @param  string  $email
     * @param  integer $s
     * @param  string  $d
     * @param  string  $r
     * @param  string  $class
     * @return string
     */
    public function getGravatar($email, $s = 80, $d = 'mp', $r = 'g', $class = 'rounded-circle')
    {
        return $this->getHtmlRenderer()->getGravatar($email, $s, $d, $r, $class);
    }

    /**
     * draws icon
     * @param  string $icon_name
     * @return void
     */
    public function drawIcon($icon_name)
    {
        echo $this->container->get('utils')->getIcon($icon_name);
    }

    /**
     * gets env variable
     * @param  string $variable
     * @return mixed
     */
    public function env($variable)
    {
        echo $this->container->get('utils')->getEnv($variable);
    }

    /**
     * gets app version
     * @return string
     */
    public function version()
    {
        $arr = file(App::getDir(App::ROOT).DS.'VERSION');
        return trim(array_pop($arr));
    }

    /**
     * draws flash messages
     * @param  BasePage $controller
     * @return void
     */
    public function renderFlashMessages(BasePage $controller)
    {
        echo $this->container->get('html_renderer')->renderFlashMessages($controller);
    }

    /**
     * summarize text
     * @param  string  $text
     * @param  integer $max_words
     * @return string
     */
    public function summarize($text, $max_words = 10)
    {
        $max_words = abs(intval($max_words));
        $words = preg_split("/\s+/", $text);
        if (count($words) < $max_words) {
            return $text;
        }
        return implode(" ", array_slice($words, 0, $max_words)).' ...';
    }
}
