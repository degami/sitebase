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

use \App\Base\Abstracts\ContainerAwareObject;
use \App\Site\Models\User;
use \App\Site\Models\GuestUser;
use \Degami\PHPFormsApi\Accessories\TagElement;

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
        return (trim(getenv('ADMINPAGES_GROUP')) != null) ? '/'.getenv('ADMINPAGES_GROUP') : null;
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
        } catch (\Exception $e) {
            $this->getUtils()->logException($e);
        }

        return false;
    }

    /**
     * checks if current user has permission
     *
     * @param  string $permission_name
     * @return boolean
     */
    public function checkPermission($permission_name)
    {
        try {
            $this->current_user_model = $this->getCurrentUser();
            return $this->current_user_model->checkPermission($permission_name);
        } catch (\Exception $e) {
            $this->getUtils()->logException($e);
        }

        return false;
    }

    /**
     * gets current user
     *
     * @return User|GuestUser
     */
    public function getCurrentUser()
    {
        if ($this->current_user_model instanceof User) {
            return $this->current_user_model;
        }

        if (!$this->current_user && !$this->getTokenData()) {
            return $this->getContainer()->make(GuestUser::class);
        }

        if (!$this->current_user) {
            $this->getTokenData();
        }

        if (is_object($this->current_user) && property_exists($this->current_user, 'id') && !$this->current_user_model instanceof User) {
            $this->current_user_model = $this->getContainer()->call([User::class, 'load'], ['id' => $this->current_user->id]);
        }

        return $this->current_user_model;
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

        return '<ul class="navbar-nav mr-auto"><li class="nav-item active">' .
                implode('</li><li class="nav-item ml-1">', $this->action_buttons) .
                '</li></ul>';
    }

    /**
     * adds an action button
     *
     * @param string $key
     * @param string $button_id
     * @param string $button_text
     * @param string $button_class
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
     * @param string $key
     * @param string $link_id
     * @param string $link_text
     * @param string $link_href
     * @param string $link_class
     */
    protected function addActionLink($key, $link_id, $link_text, $link_href = '#', $link_class = 'btn btn-sm btn-light')
    {
        $button = (string)(new TagElement(
            [
            'tag' => 'a',
            'id' => $link_id,
            'attributes' => [
                'class' => $link_class,
                'href' => $link_href,
                'title' => $link_text,
            ],
            'text' => $link_text,
            ]
        ));

        $this->action_buttons[$key] = $button;
        return $this;
    }
}
