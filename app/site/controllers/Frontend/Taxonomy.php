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

use App\Base\Exceptions\PermissionDeniedException;
use Degami\Basics\Exceptions\BasicException;
use \App\Base\Abstracts\Controllers\FrontendPageWithObject;
use \App\Site\Models\Taxonomy as TaxonomyModel;
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
     * returns valid route HTTP verbs
     *
     * @return array
     */
    public static function getRouteVerbs()
    {
        return ['GET'];
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
     * @throws PermissionDeniedException
     */
    protected function beforeRender()
    {
        $route_data = $this->getRouteData();

        if (isset($route_data['id'])) {
            $this->setObject($this->getContainer()->call([TaxonomyModel::class, 'load'], ['id' => $route_data['id']]));
        }

        return parent::beforeRender();
    }

    /**
     * {@inheritdocs}
     *
     * @return array
     * @throws BasicException
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
     * @return string
     */
    public static function getObjectClass()
    {
        return TaxonomyModel::class;
    }
}
