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

namespace App\Base\Traits;

use \App\Site\Models\User;
use \Degami\Basics\Html\TagElement;
use Exception;

/**
 * Administration pages Trait
 */
trait AdminTrait
{
    use PageTrait;

    /**
     * @var User current user model
     */
    protected $current_user_model;

    /**
     * @var array action_buttons
     */
    protected $action_buttons = [];

    /**
     * gets route group
     *
     * @return string
     */
    public static function getRouteGroup()
    {
        return (trim(getenv('ADMINPAGES_GROUP')) != null) ? '/' . getenv('ADMINPAGES_GROUP') : null;
    }

    /**
     * checks user credentials
     *
     * @return boolean
     */
    protected function checkCredentials()
    {
        try {
            if ($this->getTokenData()) {
                $this->current_user_model = $this->getCurrentUser();
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
     * @return boolean
     */
    public function checkPermission($permission_name)
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
     */
    protected function renderActionButtons()
    {
        if (empty($this->action_buttons)) {
            return '';
        }


        $ul = $this->getContainer()->make(
            TagElement::class,
            ['options' => [
                'tag' => 'ul',
                'attributes' => ['class' => 'navbar-nav mr-auto'],
            ]]
        );


        foreach ($this->action_buttons as $key => $button_html) {
            $ul->addChild(
                $this->getContainer()->make(
                    TagElement::class,
                    ['options' => [
                        'tag' => 'li',
                        'attributes' => ['class' => 'nav-item ml-1'],
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
     * @return AdminTrait
     */
    protected function addActionButton($key, $button_id, $button_text, $button_class = 'btn btn-sm btn-light')
    {
        $button = (string)(new TagElement(
            [
                'tag' => 'button',
                'id' => $button_id,
                'attributes' => [
                    'class' => $button_class,
                    'title' => $button_text,
                ],
                'text' => $button_text,
            ]
        ));
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
     * @return $this
     */
    public function addActionLink($key, $link_id, $link_text, $link_href = '#', $link_class = 'btn btn-sm btn-light', $attributes = [])
    {
        if (!is_array($attributes)) {
            $attributes = [];
        }
        $button = (string)(new TagElement(
            [
                'tag' => 'a',
                'id' => $link_id,
                'attributes' => [
                        'class' => $link_class,
                        'href' => $link_href,
                        'title' => strip_tags($link_text),
                    ] + $attributes,
                'text' => $link_text,
            ]
        ));

        $this->action_buttons[$key] = $button;
        return $this;
    }
}
