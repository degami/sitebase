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
use App\Base\Models\StoreCredit;
use App\Base\Models\StoreCreditTransaction;
use DI\DependencyException;
use DI\NotFoundException;

/**
 * store credit for user JSON
 */
class UserStoreCredit extends AdminJsonPage
{
    /**
     * return route path
     *
     * @return string
     */
    public static function getRoutePath(): string
    {
        return 'json/user/{id:\d+}/store_credit';
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

        $transactions = []; $transactionsData = [];
        $storeCredits = StoreCredit::getCollection()->where(['user_id' => $user->getId()])->getItems();
        $creditSummary = "";

        foreach ($storeCredits as $credit) {
            /** @var StoreCredit $credit */

            $creditSummary .= "<div class=\"store-credit-summary\">" .
                $this->getUtils()->translate('Website', locale: $this->getCurrentLocale()) . ': ' . $credit->getWebsite()->getSiteName() . " | " .
                $this->getUtils()->translate("Total Credit", locale: $this->getCurrentLocale()) . ': ' . $credit->getCredit() .
                "</div>";

            foreach ($credit->getTransactions()->getItems() as $transaction) {
                $transactionsData[] = $transaction->getData();
                $transactions[] = "<div class=\"store-credit-transaction\">" .
                    $this->getUtils()->translate('Website', locale: $this->getCurrentLocale()) . ': ' . $credit->getWebsite()->getSiteName() . " | " .
                    $this->getUtils()->translate("Transaction ID", locale: $this->getCurrentLocale()) . ': ' . $transaction->getTransactionId() . " | " .
                    $this->getUtils()->translate("Amount", locale: $this->getCurrentLocale()) . ': ' . $transaction->getAmount() . " | " .
                    $this->getUtils()->translate("Type", locale: $this->getCurrentLocale()) . ': ' . ($transaction->getMovementType() == StoreCreditTransaction::MOVEMENT_TYPE_INCREASE ? $this->getUtils()->translate("Increase", locale: $this->getCurrentLocale()) : $this->getUtils()->translate("Decrease", locale: $this->getCurrentLocale())) . " | " .
                    $this->getUtils()->translate("Date", locale: $this->getCurrentLocale()) . ': ' . $transaction->getCreatedAt() .
                    "</div>";
            }
        }

        $userController = $this->containerMake(Users::class);
        $form = $userController->getForm();

        $form->setAction($this->getUrl('admin.users') . '?user_id=' . $user->getId() . '&action=' . $this->getRequest()->query->get('action'));
        $form->addField(
            'user_id',
            [
                'type' => 'hidden',
                'default_value' => $user->getId(),
            ]
        );

        if (count($transactions) == 0) {
            $transactions[] = "<div class=\"no-transactions-message\">" . $this->getUtils()->translate("No store credit transactions found for this user.", locale: $this->getCurrentLocale()) . "</div>";
        }

        return [
            'success' => true,
            'params' => $this->getRequest()->query->all(),
            'addresses' => $transactionsData,
            'html' => ($this->getRequest()->query->get('action') == 'newstorecredittransaction' ? $creditSummary."<hr /><div class=\"user-transactions\">" . implode("", $transactions) . "</div><hr /><h4>" . $this->getUtils()->translate("Add a new one") . "</h4>" : '') . $form->render(),
            'js' => "",
        ];
    }
}
