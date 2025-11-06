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

namespace App\Base\Controllers\Frontend\Users;

use App\App;
use App\Base\Abstracts\Controllers\LoggedUserFormPage;
use App\Base\Exceptions\NotAllowedException;
use App\Base\Models\Address;
use App\Base\Models\GiftcardRedeemCode;
use App\Base\Models\StoreCredit;
use Degami\PHPFormsApi as FAPI;

class GiftCards extends LoggedUserFormPage
{
    /**
     * @var string page title
     */
    protected ?string $page_title = 'My Giftcards';

    /**
     * @inheritDoc
     */
    public static function isEnabled(): bool
    {
        return boolval(App::getInstance()->getEnvironment()->getVariable('ENABLE_COMMERCE', false)) && boolval(App::getInstance()->getEnvironment()->getVariable('ENABLE_LOGGEDPAGES', false));
    }

    /**
     * @inheritDoc
     */
    public function getTemplateName(): string
    {
        return 'users/giftcards';
    }

    /**
     * return route path
     *
     * @return string
     */
    public static function getRoutePath(): string
    {
        return 'giftcards';
    }

    /**
     * @inheritdoc
     *
     * @return string
     */
    public static function getAccessPermission(): string
    {
        return 'view_logged_site';
    }

    /**
     * @inheritDoc
     */
    public function getTemplateData(): array
    {
        $creditSummary = '';

        $storeCredits = StoreCredit::getCollection()->where(['user_id' => $this->getCurrentUser()->getId()])->getItems();
        $creditSummary = "";

        foreach ($storeCredits as $credit) {
            /** @var StoreCredit $credit */
            $creditSummary .= "<h2 class=\"store-credit-summary\">" .
                $this->getUtils()->translate('Website', locale: $this->getCurrentLocale()) . ': ' . $credit->getWebsite()->getSiteName() . " | " .
                $this->getUtils()->translate("Total Credit", locale: $this->getCurrentLocale()) . ': <strong>' . $this->getUtils()->formatPrice($credit->getCredit()) . '</strong>' .
                "</h2>";
        }

        if (empty($creditSummary)) {
            $creditSummary = "<h4>" . $this->getUtils()->translate("No store credit available", locale: $this->getCurrentLocale()) . "</h4>";
        }

        $this->template_data += [
            'credit_summary' => $creditSummary,
            'current_user' => $this->getCurrentUser(),
            'giftcard_codes' => $this->getGiftCardCodes(),
        ];
        return $this->template_data;
    }

    protected function getGiftCardCodes() : array
    {
        return GiftcardRedeemCode::getCollection()->where([
            'user_id' => $this->getCurrentUser()->getId(),
            'redeemed' => 1,
        ])->getItems();
    }

    /**
     * gets form definition object
     *
     * @param FAPI\Form $form
     * @param array     &$form_state
     * @return FAPI\Form
     */
    public function getFormDefinition(FAPI\Form $form, array &$form_state): FAPI\Form
    {
        $form
        ->addField('redeem_code', [
            'type' => 'textfield',
            'title' => 'Redeem Code',
            'validate' => ['required'],
            'default_value' => '',
        ]);

        $this->addSubmitButton($form);

        return $form;
    }

    /**
     * validates form submission
     *
     * @param FAPI\Form $form
     * @param array     &$form_state
     * @return bool|string
     */
    public function formValidate(FAPI\Form $form, array &$form_state): bool|string
    {
        $values = $form->values();

        $redeem_code = GiftcardRedeemCode::getCollection()->where([
            'code' => $values['redeem_code'],
            'redeemed' => 0,
        ])->getFirst();

        if (!$redeem_code) {
            return $this->getUtils()->translate("Invalid redeem code");
        }

        return true;
    }

    /**
     * handles form submission
     *
     * @param FAPI\Form $form
     * @param array     &$form_state
     * @return mixed
     */
    public function formSubmitted(FAPI\Form $form, array &$form_state): mixed
    {
        $values = $form->values();

        /** @var GiftcardRedeemCode $redeem_code */
        $redeem_code = GiftcardRedeemCode::getCollection()->where([
            'code' => $values['redeem_code'],
            'redeemed' => 0,
        ])->getFirst();

        $redeem_code->setUserId($this->getCurrentUser()->getId());
        $redeem_code->setRedeemed(1);

        /** @var StoreCredit $storeCredit */
        $storeCredit = StoreCredit::getCollection()->where([
            'user_id' => $this->getCurrentUser()->getId(),
            'website_id' => $this->getCurrentWebsiteId(),
        ])->getFirst();

        if (!$storeCredit) {
            $storeCredit = new StoreCredit();
            $storeCredit->setUserId($this->getCurrentUser()->getId());
            $storeCredit->setWebsiteId($this->getCurrentWebsiteId());
            $storeCredit->setAmount(0);
            $storeCredit->persist();
        }

        $storeCredit->makeTransaction(
            $redeem_code->getCredit(),
            $this->getCurrentUser(),
            $this->getCurrentWebsite()
        );

        $redeem_code->persist();

        $this->addInfoFlashMessage($this->getUtils()->translate("Code redeemed"));

        return $this->doRedirect($this->getUrl('frontend.users.giftcards'));
    }
}