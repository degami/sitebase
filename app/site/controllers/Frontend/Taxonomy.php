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
namespace App\Site\Controllers\Frontend;

use \Psr\Container\ContainerInterface;
use \Degami\PHPFormsApi as FAPI;
use \App\Base\Abstracts\FrontendPageWithObject;
use \App\App;
use \App\Site\Models\Taxonomy as TaxonomyModel;
use \App\Site\Routing\RouteInfo;
use \Symfony\Component\HttpFoundation\Response;

/**
 * Taxonomy Term Detail Page
 */
class Taxonomy extends FrontendPageWithObject
{
    /**
     * return route path
     *
     * @return string
     */
    public static function getRoutePath()
    {
        return 'taxonomy/{id:\d+}';
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getTemplateName()
    {
        if ($this->getObject() instanceof TaxonomyModel && $this->getObject()->isLoaded()) {
            if (!empty($this->getObject()->getTemplateName())) {
                return $this->getObject()->getTemplateName();
            }
        }

        return 'taxonomy';
    }

    /**
     * {@inheritdocs}
     *
     * @return Response|self
     */
    protected function beforeRender()
    {
        $route_data = $this->getRouteInfo()->getVars();

        if (isset($route_data['id'])) {
            $this->setObject($this->getContainer()->call([TaxonomyModel::class, 'load'], ['id' => $route_data['id']]));
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
    public function process(RouteInfo $route_info = null, $route_data = [])
    {
        if (!($this->getObject() instanceof TaxonomyModel && $this->getObject()->isLoaded())) {
            return $this->getUtils()->errorPage(404);
        }

        return parent::process($route_info);
    }

    /**
     * {@inheritdocs}
     *
     * @return array
     */
    protected function getBaseTemplateData()
    {
        $out = parent::getBaseTemplateData();
        $out ['body_class'] = str_replace('.', '-', $this->getRouteName()).' taxonomy-'. $this->getObject()->id;
        return $out;
    }

    /**
     * {@inheritdocs}
     *
     * @return [type] [description]
     */
    public static function getObjectClass()
    {
        return TaxonomyModel::class;
    }
}
