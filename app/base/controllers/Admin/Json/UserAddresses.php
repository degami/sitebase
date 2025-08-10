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

namespace App\Base\Controllers\Admin\Json;

use Degami\Basics\Exceptions\BasicException;
use App\Base\Abstracts\Controllers\AdminJsonPage;
use App\Base\Controllers\Admin\Users;
use App\Base\Models\Address;
use App\Base\Models\User;
use DI\DependencyException;
use DI\NotFoundException;

/**
 * addresses for user JSON
 */
class UserAddresses extends AdminJsonPage
{
    /**
     * return route path
     *
     * @return string
     */
    public static function getRoutePath(): string
    {
        return 'json/user/{id:\d+}/addresses';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public static function getAccessPermission(): string
    {
        return 'administer_users';
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     */
    protected function getJsonData(): array
    {
        $route_data = $this->getRouteData();
        $user = $this->containerCall([User::class, 'load'], ['id' => $route_data['id']]);

        $addresses = array_map(function (Address $address) use ($user) {
            return '<div class="col-sm-4 pb-3">
                <div class="card">
                    <h5 class="card-header">' . $address->getFullName() . '</h5>
                    <div class="card-body">
                        <div class="full-address">' . $address->getFullAddress() . '</div>
                        <div class="full-contact">' . $address->getFullContact() . '</div>

                        <div class="d-grid mt-3 gap-2 d-md-flex justify-content-md-end">
                            <a href="?action=editaddress&asJson=1&user_id=' . $user->getid() . '&address_id=' . $address->getId() . '" class="btn btn-primary mr-1">'. $this->getHtmlRenderer()->getIcon('edit') . '</a>
                            <a href="?action=deleteaddress&asJson=1&&user_id=' . $user->getid() . '&address_id=' . $address->getId() . '" class="btn btn-danger">'. $this->getHtmlRenderer()->getIcon('trash') . '</a>
                        </div>

                    </div>
                </div>
            </div>';
        }, Address::getCollection()->where(['user_id' => $user->getId()])->getItems());

        $addressesData = array_map(
            function ($el) {
                return $el->getData();
            },
            Address::getCollection()->where(['user_id' => $user->getId()])->getItems()
        );

        $userController = $this->containerMake(Users::class);
        $form = $userController->getForm();

        $form->setAction($this->getUrl('admin.users') . '?user_id=' . $user->getId() . ($this->getRequest()->get('address_id') ? '&address_id' . $this->getRequest()->get('address_id') : '') . '&action=' . $this->getRequest()->get('action'));
        $form->addField(
            'user_id',
            [
                'type' => 'hidden',
                'default_value' => $user->getId(),
            ]
        );

        return [
            'success' => true,
            'params' => $this->getRequest()->query->all(),
            'addresses' => $addressesData,
            'html' => ($this->getRequest()->get('action') == 'newaddress' ? "<div class=\"user-addresses\">" . implode("", $addresses) . "</div><hr /><h4>" . $this->getUtils()->translate("Add a new one") . "</h4>" : '') . $form->render(),
            'js' => "",
        ];
    }
}
