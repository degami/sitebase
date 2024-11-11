<?php

/**
 * SiteBase
 * PHP Version 8.0
 *
 * @category CMS / Framework
 * @package  Degami\Sitebase
 * @author   Mirko De Grandis <degami@github.com>
 * @license  MIT https://opensource.org/licenses/mit-license.php
 * @link     https://github.com/degami/sitebase
 */

namespace App\Site\Controllers\Admin;

use Degami\Basics\Exceptions\BasicException;
use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use App\Base\Abstracts\Controllers\AdminManageFrontendModelsPage;
use Degami\PHPFormsApi as FAPI;
use App\Site\Models\UserNotification;
use App\App;
use App\Base\Abstracts\Models\BaseCollection;
use App\Site\Models\User;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Site\Routing\RouteInfo;
use DateTime;

/**
 * "User Notifications" Admin Page
 */
class UserNotifications extends AdminManageFrontendModelsPage
{
    /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getTemplateName(): string
    {
        return 'base_admin_page';
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getAccessPermission(): string
    {
        return 'administer_site';
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    public function getObjectClass(): string
    {
        return UserNotification::class;
    }


    /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getObjectIdQueryParam(): string
    {
        return 'notification_id';
    }

    /**
     * {@inheritdocs}
     *
     * @return array|null
     */
    public Function getAdminPageLink() : array|null
    {
        return [
            'permission_name' => $this->getAccessPermission(),
            'route_name' => static::getPageRouteName(),
            'icon' => 'bell',
            'text' => 'Notifications',
            'section' => 'system',
            'order' => 10,
        ];
    }

    function __construct(
        protected ContainerInterface $container, 
        protected ?Request $request = null, 
        protected ?RouteInfo $route_info = null

    ) {
        parent::__construct($container, $request, $route_info);
        $this->page_title = 'Notifications';
        if ($this->getCollection()->count() > 0) {
            if ($this->getRequest()->query->get('action') == null || $this->getRequest()->query->get('action') == 'list') {
                $this->addActionLink('readall-btn', 'readall-btn', $this->getHtmlRenderer()->getIcon('user-check') . $this->getUtils()->translate('Mark all as read', locale: $this->getCurrentLocale()), $this->getControllerUrl().'?action=markallasread', 'btn btn-sm btn-warning');
            }    
        }
    }
    
    protected function getCollection() : BaseCollection
    {
        $collection = parent::getCollection();

        $collection->where(['user_id' => $this->getCurrentUser()->getId()]);

        return $collection;
    }

    /**
     * {@inheritdocs}
     *
     * @param FAPI\Form $form
     * @param array     &$form_state
     * @return FAPI\Form
     * @throws BasicException
     * @throws PhpfastcacheSimpleCacheException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getFormDefinition(FAPI\Form $form, &$form_state): FAPI\Form
    {
        $type = $this->getRequest()->get('action') ?? 'list';
        $notification = $this->getObject();

        $form->addField('action', [
            'type' => 'value',
            'value' => $type,
        ]);

        switch ($type) {
            case 'new':
                $this->addBackButton();

                $message = $user_id = '';
                if ($notification->isLoaded()) {
                    $message = $notification->message;
                    $user_id = $notification->user_id;
                }
                $usersOptions = [];

                foreach(User::getCollection()->where(['id:not' => $this->getCurrentUser()->getId()]) as $userTo) {
                    $usersOptions[$userTo->getId()] = $userTo->getNickname();
                }

                $form->addField('user_id', [
                    'type' => 'select',
                    'title' => 'User To',
                    'default_value' => $user_id,
                    'options' => $usersOptions,
                    'validate' => ['required'],
                ])->addField('message', [
                    'type' => 'textarea',
                    'title' => 'Message',
                    'rows' => 3,
                    'default_value' => $message,
                    'validate' => ['required'],
                ]);

                $this->addFrontendFormElements($form, $form_state, ['website_id']);
                $this->addSubmitButton($form);

                break;
            case 'markallasread':
                $this->fillConfirmationForm('Do you really want to mark all notification as read?', $form);
                break;
                
            case 'delete':
                $this->fillConfirmationForm('Do you confirm the deletion of the selected element?', $form);
                break;
        }

        return $form;
    }

    /**
     * {@inheritdocs}
     *
     * @param FAPI\Form $form
     * @param array     &$form_state
     * @return bool|string
     */
    public function formValidate(FAPI\Form $form, &$form_state): bool|string
    {
        //$values = $form->values();
        // @todo : check if page language is in page website languages?
        return true;
    }

    /**
     * {@inheritdocs}
     *
     * @param FAPI\Form $form
     * @param array     &$form_state
     * @return mixed
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function formSubmitted(FAPI\Form $form, &$form_state): mixed
    {
        if ($form->values()['action'] == 'markallasread') {
            $total = 0;
            foreach (UserNotification::getCollection()->where(['user_id' => $this->getCurrentUser()->getId(), 'read' => false]) as $notification) {
                try {
                    /**
                     * @var UserNotification $notification
                     */
                    $notification
                        ->setRead(true)
                        ->setReadAt(new DateTime())
                        ->persist();

                    $total++;
                } catch (Exception $e) {}
            }

            $this->addFlashMessage('success', $this->getUtils()->translate('%d Notifications has been set as read.', params: [$total], locale: $this->getCurrentLocale()));
            return $this->doRedirect($this->getControllerUrl());
        }

        /**
         * @var UserNotification $notification
         */
        $notification = $this->getObject();

        $values = $form->values();

        switch ($values['action']) {
            case 'new':
                $notification->setSenderId($this->getCurrentUser()->getId());
                $notification->setUserId($values['user_id']);
                $notification->setMessage($values['message']);
                $notification->setWebsiteId($values['frontend']['website_id']);

                $this->setAdminActionLogData($notification->getChangedData());

                $notification->persist();
                break;
            case 'delete':
                $notification->delete();

                $this->setAdminActionLogData('Deleted notification ' . $notification->getId());

                break;
        }
        return $this->doRedirect($this->getControllerUrl());
    }

    /**
     * {@inheritdocs}
     *
     * @return array
     */
    protected function getTableHeader(): ?array
    {
        return [
            'ID' => 'id',
            'Website' => ['order' => 'website_id', 'foreign' => 'website_id', 'table' => $this->getModelTableName(), 'view' => 'site_name'],
            'User To' => ['order' => 'user_id', 'search' => 'user_id', 'foreign' => 'user_id', 'table' => $this->getModelTableName(), 'view' => 'nickname'],
            'User From' => ['order' => 'sender_id', 'search' => 'sender_id', 'foreign' => 'sender', 'table' => $this->getModelTableName(), 'view' => 'nickname'],
            'Message' => ['order' => 'message', 'search' => 'message'],
            'Read' => ['order' => 'read', 'search' => 'read'],
            'actions' => null,
        ];
    }

    /**
     * {@inheritdocs}
     *
     * @param array $data
     * @return array
     * @throws BasicException
     * @throws Exception
     */
    protected function getTableElements(array $data): array
    {
        return array_map(
            function ($notification) {
                /** @var UserNotification $notification */
                return [
                    'ID' => $notification->id,
                    'Website' => $notification->getWebsiteId() == null ? 'All websites' : $notification->getWebsite()->domain,
                    'User To' => $notification->getOwner()->getNickname(),
                    'User From' => $notification->getSender()?->getNickname() ?? __('System'),
                    'Message' => $notification->message,
                    'Read' => $notification->getRead() ? __('Yes') : __('No'),
                    'actions' => implode(
                        " ",
                        [
                            $this->getDeleteButton($notification->id),
                        ]
                    ),
                ];
            },
            $data
        );
    }
}