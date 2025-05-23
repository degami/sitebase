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

namespace App\Base\Controllers\Admin;

use App\App;
use Degami\Basics\Exceptions\BasicException;
use App\Base\Abstracts\Controllers\AdminPage;
use Degami\SqlSchema\Exceptions\OutOfRangeException;
use DI\DependencyException;
use DI\NotFoundException;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Base\Routing\RouteInfo;
use Degami\Basics\Html\TagElement;

/**
 * "Routes" Admin Page
 */
class Routes extends AdminPage
{
    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getTemplateName(): string
    {
        return 'base_admin_page';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public static function getAccessPermission(): string
    {
        return 'system_info';
    }

    /**
     * {@inheritdoc}
     *
     * @return array|null
     */
    public Function getAdminPageLink() : array|null
    {
        return [
            'permission_name' => static::getAccessPermission(),
            'route_name' => static::getPageRouteName(),
            'icon' => 'target',
            'text' => 'App Routes',
            'section' => 'tools',
            'order' => 100,
        ];
    }

    function __construct(
        protected ContainerInterface $container, 
        protected ?Request $request = null, 
        protected ?RouteInfo $route_info = null

    ) {
        parent::__construct($container, $request, $route_info);
        $this->page_title = 'App Routes';
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     * @throws OutOfRangeException
     */
    public function getTemplateData(): array
    {
        $tableContents = [];

        foreach ($this->getRouters() as $routerName) {
            $router = $this->getService($routerName);

            if (!$this->containerCall([$router, 'isEnabled'])) {
                continue;
            }

            foreach ($router->getRoutes() as $group => $routes) {
                foreach ($routes as $route) {
                    $routeName = $route['name'];
                    $routePath = $route['path'];
                    $callable = $route['class'] . '::' . $route['method'];

                    if (is_file(App::getDir(App::WEBROOT) . DS . 'docs' . DS . 'classes' . DS . str_replace("\\", "-", $route['class']) . ".html")) {
                        $linkTo = $this->getUrl('crud.app.base.controllers.admin.json.readdocs') . "?docpage=".urlencode("/docs/classes/". str_replace("\\", "-", $route['class']) . ".html#method_".$route['method']);
                        $callable = $this->containerMake(TagElement::class, [
                            'options' => [
                                'tag' => 'a',
                                'attributes' => [
                                    'href' => $linkTo,
                                    'class' => 'inToolSidePanel',
                                ],
                                'text' => $callable
                            ]
                            ]);
                    }

                    $tableContents[] = [
                        'Router' => $routerName,
                        'Name' => $routeName, 
                        'Group' => $group, 
                        'Path' => $routePath, 
                        'Callable' => $callable,
                    ];
                }
            }    
        }

        $this->template_data = [
            'action' => 'list',
            'table' => $this->getHtmlRenderer()->renderAdminTable($tableContents, [
                'Router' => null,
                'Name' => null,
                'Group' => null,
                'Path' => null,
                'Callable' => null,
                'actions' => null,
            ], $this),
            'paginator' => '',
        ];

        return $this->template_data;
    }
}
