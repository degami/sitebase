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

use App\Base\Exceptions\PermissionDeniedException;
use App\Site\Controllers\Frontend\Page;
use \App\Site\Routing\RouteInfo;
use Degami\Basics\Exceptions\BasicException;
use \Exception;
use \App\Base\Traits\FrontendTrait;
use \App\Base\Abstracts\Models\BaseModel;
use \App\Base\Exceptions\NotFoundException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Base for a page displaying a model
 */
abstract class FrontendPageWithObject extends FrontendPage
{
    use FrontendTrait;

    /**
     * {@inheritdocs}
     *
     * @return Response|self
     * @throws PermissionDeniedException
     * @throws BasicException
     */
    protected function beforeRender()
    {
        $route_data = $this->getRouteData();

        if (isset($route_data['id'])) {
            $this->setObject($this->getContainer()->call([static::getObjectClass(), 'load'], ['id' => $route_data['id']]));
        }

        return parent::beforeRender();
    }

    /**
     * {@inheritdocs}
     *
     * @param RouteInfo|null $route_info
     * @param array $route_data
     * @return Response
     * @throws Exception
     * @throws Throwable
     */
    public function process(RouteInfo $route_info = null, $route_data = []): Response
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
            throw new NotFoundException();
        }

        return $return;
    }

    /**
     * gets object title
     *
     * @return string
     */
    public function getObjectTitle(): string
    {
        return $this->getObject()->getTitle() ?: '';
    }

    /**
     * {@inheritdocs}
     *
     * @return boolean
     */
    public function canBeFPC(): bool
    {
        return true;
    }

    /**
     * gets object class name for loading
     *
     * @return string
     */
    abstract public static function getObjectClass(): string;
}
