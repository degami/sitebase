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

namespace App\Base\Traits;

use App\Base\Routing\RouteInfo;
use App\Base\Routers\Web;
use App\Base\Routers\Crud;
use Degami\Basics\Exceptions\BasicException;
use Degami\Basics\Html\TagElement;
use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use App\Base\Exceptions\PermissionDeniedException;

/**
 * Administration pages Trait
 */
trait AdminTrait
{
    use PageTrait;
    use TemplatePageTrait;

    /**
     * @var array action_buttons
     */
    protected array $action_buttons = [];

    /**
     * gets route group
     *
     * @return string|null
     */
    public static function getRouteGroup(): ?string
    {
        return (trim(getenv('ADMINPAGES_GROUP')) != null) ? '/' . getenv('ADMINPAGES_GROUP') : null;
    }

    /**
     * checks user credentials
     *
     * @return bool
     * @throws BasicException
     */
    protected function checkCredentials(): bool
    {
        try {
            if ($this->getTokenData()) {
                $this->current_user_model = $this->getCurrentUser();

                if ($this->current_user_model->locked == true) {
                    throw new PermissionDeniedException('User Locked');
                }

                if ($this->getEnvironment()->getVariable('USE2FA_ADMIN') && !$this->isCrud($this->getRouteInfo()) && !in_array($this->getRouteInfo()->getRouteName(), ['admin.twofa', 'admin.login']) && $this->getAuth()->currentUserHasPassed2FA() != true) {
                    return false;
                }

                return $this->current_user_model->checkPermission('administer_site');
            }
        } catch (Exception $e) {
            $this->getUtils()->logException($e);
        }

        return false;
    }

    /**
     * checks if current user has permission
     *
     * @param string $permission_name
     * @return bool
     * @throws BasicException
     */
    public function checkPermission(string $permission_name): bool
    {
        try {
            $this->current_user_model = $this->getCurrentUser();
            return $this->current_user_model->checkPermission($permission_name);
        } catch (Exception $e) {
            $this->getUtils()->logException($e);
        }

        return false;
    }

    /**
     * renders action buttons
     *
     * @return string
     * @throws DependencyException
     * @throws NotFoundException
     */
    protected function renderActionButtons(): string
    {
        if (empty($this->action_buttons)) {
            return '';
        }


        $ul = $this->containerMake(
            TagElement::class,
            ['options' => [
                'tag' => 'ul',
                'attributes' => ['class' => 'navbar-nav mr-auto'],
            ]]
        );


        foreach ($this->action_buttons as $key => $button_html) {
            $ul->addChild(
                $this->containerMake(
                    TagElement::class,
                    ['options' => [
                        'tag' => 'li',
                        'attributes' => ['class' => 'nav-item ml-1 text-nowrap d-inline-flex align-items-center'],
                        'text' => $button_html
                    ]]
                )
            );
        }

        return (string)$ul;
    }

    /**
     * adds an action button
     *
     * @param string $key
     * @param string $button_id
     * @param string $button_text
     * @param string $button_class
     * @return self
     */
    public function addActionButton(string $key, string $button_id, string $button_text, $button_class = 'btn btn-sm btn-light') : static
    {
        $button = $this->containerMake(TagElement::class, ['options' => [
            'tag' => 'button',
            'id' => $button_id,
            'attributes' => [
                'class' => $button_class,
                'title' => $button_text,
            ],
            'text' => $button_text,
        ]]);
        $this->action_buttons[$key] = $button;

        return $this;
    }

    /**
     * adds an action link
     *
     * @param $key
     * @param $link_id
     * @param $link_text
     * @param string $link_href
     * @param string $link_class
     * @param array $attributes
     * @return self
     */
    public function addActionLink($key, $link_id, $link_text, $link_href = '#', $link_class = 'btn btn-sm btn-light', $attributes = []) : static
    {
        if (!is_array($attributes)) {
            $attributes = [];
        }
        $button = $this->containerMake(TagElement::class, ['options' => [
            'tag' => 'a',
            'id' => $link_id,
            'attributes' => [
                    'class' => $link_class,
                    'href' => $link_href,
                    'title' => strip_tags($link_text),
                ] + $attributes,
            'text' => $link_text,
        ]]);

        $this->action_buttons[$key] = $button;
        return $this;
    }

    /**
     * removes an action link / button
     *
     * @param $key
     * @return self
     */
    public function removeAction($key) : static
    {
        if (((string)$key) == "") {
            return $this;
        }
        unset($this->action_buttons[$key]);
        return $this;
    }

    public function isWeb(RouteInfo $routeInfo) : bool
    {
        if ($routeInfo->getType() == Web::ROUTER_TYPE ) {
            return true;
        }

        return false;
    }

    public function isCrud(RouteInfo $routeInfo) : bool
    {
        if ($routeInfo->getType() == Crud::ROUTER_TYPE) {
            return true;
        }

        return false;
    }
}
