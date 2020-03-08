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

use \Psr\Container\ContainerInterface;
use \App\App;
use \App\Site\Routing\RouteInfo;
use \Exception;
use \App\Base\Traits\FrontendTrait;
use \App\Base\Abstracts\Models\BaseModel;

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

        if (is_null($this->getObject())) {
            throw new Exception('Missing "object" property');
        }

        if (!(
            $this->getObject() instanceof BaseModel &&
            is_a($this->getObject(), $this->getObjectClass()) &&
            $this->getObject()->isLoaded())
        ) {
            return $this->getUtils()->errorPage(404);
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
