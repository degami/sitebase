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

use Degami\Basics\Exceptions\BasicException;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use \Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\HttpFoundation\Response;
use \Psr\Container\ContainerInterface;
use \App\Base\Traits\FrontendTrait;
use \App\Base\Exceptions\PermissionDeniedException;

/**
 * Base for admin pages
 */
abstract class LoggedUserPage extends FrontendPage
{
    use FrontendTrait;

    /**
     * @var string page title
     */
    protected $page_title;

    /**
     * @var string locale
     */
    protected $locale = null;

    /**
     * @var array template data
     */
    protected $templateData = [];

    /**
     * {@inheritdocs}
     *
     * @param ContainerInterface $container
     * @param Request|null $request
     * @throws BasicException
     * @throws PhpfastcacheSimpleCacheException
     */
    public function __construct(ContainerInterface $container, Request $request)
    {
        $this->page_title = ucwords(str_replace("_", " ", implode("", array_slice(explode("\\", get_class($this)), -1, 1))));

        parent::__construct($container, $request);
    }

    /**
     * returns valid route HTTP verbs
     *
     * @return array
     */
    public static function getRouteVerbs()
    {
        return ['GET', 'POST'];
    }

    /**
     * before render hook
     *
     * @return Response|self
     * @throws PermissionDeniedException
     * @throws BasicException
     */
    protected function beforeRender()
    {
        if (!$this->getEnv('ENABLE_LOGGEDPAGES')) {
            throw new PermissionDeniedException();
        }

        if (!$this->checkCredentials() || !$this->checkPermission($this->getAccessPermission())) {
            throw new PermissionDeniedException();
        }

        return parent::beforeRender();
    }

    /**
     * gets page title
     *
     * @return string
     */
    public function getPageTitle()
    {
        return $this->page_title;
    }

    /**
     * {@inheritdocs}
     *
     * @return array
     */
    protected function getTemplateData()
    {
        return $this->templateData;
    }

    /**
     * gets route group
     *
     * @return string
     */
    public static function getRouteGroup()
    {
        return (trim(getenv('LOGGEDPAGES_GROUP')) != null) ? '/' . getenv('LOGGEDPAGES_GROUP') : null;
    }

    /**
     * gets access permission name
     *
     * @return string
     */
    abstract protected function getAccessPermission();
}
