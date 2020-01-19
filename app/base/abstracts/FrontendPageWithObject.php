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

use \Psr\Container\ContainerInterface;
use \App\App;
use \App\Site\Routing\RouteInfo;
use \Exception;
use \App\Base\Traits\FrontendTrait;

/**
 * Base for a page diplaying a model
 */
abstract class FrontendPageWithObject extends FrontendPage
{
    use FrontendTrait;

    /**
     * {@inheritdocs}
     *
     * @param  RouteInfo|null $route_info
     * @param  array          $route_data
     * @return Response
     */
    public function process(RouteInfo $route_info = null, $route_data = [])
    {
        $return = parent::process($route_info, $route_data);

        if (!getenv('DEBUG')) {
            if (!($this->templateData['object'] instanceof Model && $this->templateData['object']->isLoaded())) {
                return $this->getUtils()->errorPage(404);
            }
        }

        if (!isset($this->templateData['object'])) {
            throw new Exception('Missing "object" property');
        }

        return $return;
    }

    /**
     * {@inheritdocs}
     *
     * @return boolean
     */
    public function canBeFPC()
    {
        return true;
    }

    /**
     * gets object class name for loading
     *
     * @return string
     */
    abstract public static function getObjectClass();
}
