<?php

/**
 * SiteBase
 * PHP Version 8.5
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
use DI\DependencyException;
use DI\NotFoundException;
use App\Base\Abstracts\Controllers\AdminPage;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Base\Routing\RouteInfo;
use App\Base\Abstracts\Controllers\BasePage;
use Degami\Basics\Html\TagElement;
use Symfony\Component\HttpFoundation\Response;
use App\Base\Tools\Cache\Manager as CacheManager;


/**
 * "Cache" Admin Page
 */
class Cache extends AdminPage
{
    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public static function isEnabled(): bool
    {
        return boolval(\App\App::getInstance()->getEnvironment()->getVariable('DISABLE_CACHE')) == false;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getTemplateName(): string
    {
        return 'cache';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public static function getAccessPermission(): string
    {
        return 'administer_site';
    }

    /**
     * {@inheritdoc}
     *
     * @return array|null
     */
    public static function getAdminPageLink() : array|null
    {
        if (boolval(\App\App::getInstance()->getEnvironment()->getVariable('DISABLE_CACHE'))) {
            return null;
        }

        return [
            'permission_name' => static::getAccessPermission(),
            'route_name' => static::getPageRouteName(),
            'icon' => 'tag',
            'text' => 'Cache',
            'section' => 'system',
            'order' => 100,
        ];
    }

    function __construct(
        protected ContainerInterface $container, 
        protected ?Request $request = null, 
        protected ?RouteInfo $route_info = null

    ) {
        parent::__construct($container, $request, $route_info);
        if (($this->getRequest()->query->get('action') ?? 'list') == 'list') {
            $this->addActionLink('clear-btn', 'clear-btn', $this->getHtmlRenderer()->getIcon('refresh-cw') . ' ' . $this->getUtils()->translate('Clear', locale: $this->getCurrentLocale()), $this->getControllerUrl().'?action=clear', 'btn btn-sm btn-warning');
        }    
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getTemplateData(): array
    {
        $tableContents = [];
        foreach ($this->getCache()->getAllItemsByTag(CacheManager::CACHE_TAG) as $key => $item) {
            if ($item->getLength() > 0) {
                $tableContents[] = ['Key' => $key, 'Size' => $item->getLength(), 'Is Expired' => $item->isExpired() ? 'true' : 'false'];
            }
        }

        $this->template_data += [
            'table_header' => ['Key' => null, 'Size' => null, 'Is Expired' => null],
            'table_contents' => $tableContents,
            'info' => implode("\n", [
                $this->getCache()->getStats()->getInfo(),
                "Cache size: " . $this->getCache()->getStats()->getSize(),
                "Cache Lifetime: " . $this->getCache()->getCacheLifetime()
            ]),
        ];

        return $this->template_data;
    }

    /**
     * before render hook
     *
     * @return Response|self
     * @throws BasicException
     * @throws PermissionDeniedException
     */
    protected function beforeRender(): BasePage|Response
    {
        $out = parent::beforeRender();

        if ($this->getRequest()->query->get('action') == 'clear') {


            $this->getCache()->clear();

            $this->addSuccessFlashMessage($this->getUtils()->translate("Cache cleared"));

            return $this->refreshPage();
        }

        return $out;
    }

    /**
     * gets action button html
     *
     * @param string $action
     * @param int $object_id
     * @param string $class
     * @param string $icon
     * @param string $title
     * @return string
     * @throws DependencyException
     * @throws NotFoundException
     */
    protected function getActionButton(string $action, string $type, string $class, string $icon, string $title = ''): string
    {
        try {
            $button = $this->containerMake(TagElement::class, ['options' => [
                'tag' => 'a',
                'attributes' => [
                    'class' => 'btn btn-sm btn-' . $class,
                    'href' => $this->getControllerUrl() . '?action=' . $action . '&type=' . $type,
                    'title' => (trim($title) != '') ? $this->getUtils()->translate($title, locale: $this->getCurrentLocale()) : '',
                ],
                'text' => $this->getHtmlRenderer()->getIcon($icon),
            ]]);

            return (string)$button;
        } catch (BasicException $e) {
        }

        return '';
    }
}
