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

namespace App\Base\Controllers\Frontend\Commerce\Checkout\Stripe;

use App\App;
use App\Base\Traits\CommercePageTrait;
use Symfony\Component\HttpFoundation\Response;
use App\Base\Abstracts\Controllers\BasePage;
use App\Base\Abstracts\Controllers\FrontendPageWithLang;

class StripeReturnCallback extends FrontendPageWithLang
{
    use CommercePageTrait;

    /**
     * @inheritDoc
     */
    public static function isEnabled(): bool
    {
        return App::installDone() && App::getInstance()->getEnv('ENABLE_COMMERCE', false) && App::getInstance()->getSiteData()->getConfigValue('payments/stripe/active') == true;
    }

    /**
     * specifies if this controller is eligible for full page cache
     *
     * @return bool
     */
    public function canBeFPC(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function getTemplateName(): string
    {
        return 'commerce/stripe_return_callback';
    }

    /**
     * @inheritDoc
     */
    public function getTemplateData(): array
    {

        $this->template_data += [
            'stripe_public_key' => App::getInstance()->getSiteData()->getConfigValue('payments/stripe/public_key'),
            'ok_url' =>$this->getUrl('frontend.commerce.checkout.typ'),
            'ko_url' => $this->getUrl('frontend.commerce.checkout.ko'),
            'order' => $this->getOrder(),
            'user' => $this->getCurrentUser(),
        ];

        return parent::getTemplateData();
    }

    /**
     * {@inheritdoc}
     *
     * @return Response|self
     * @throws BasicException
     * @throws PermissionDeniedException
     */
    protected function beforeRender() : BasePage|Response
    {
        if (!$this->getOrder()) {
            return $this->doRedirect($this->getUrl('frontend.commerce.cart'));            
        }

        return parent::beforeRender();
    }
}