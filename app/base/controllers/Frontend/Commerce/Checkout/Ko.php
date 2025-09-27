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

namespace App\Base\Controllers\Frontend\Commerce\Checkout;

use App\App;
use App\Base\Traits\CommercePageTrait;
use Symfony\Component\HttpFoundation\Response;
use App\Base\Abstracts\Controllers\BasePage;
use App\Base\Abstracts\Controllers\FrontendPageWithLang;

class Ko extends FrontendPageWithLang
{
    use CommercePageTrait;

    /**
     * @inheritDoc
     */
    public static function isEnabled(): bool
    {
        return App::installDone() && App::getInstance()->getEnvironment()->getVariable('ENABLE_COMMERCE', false);
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
     * {@inheritdoc}
     *
     * @return string
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getRouteName(): string
    {
        return $this->getUtils()->translate('There was a little problem processing your Order', locale: $this->getCurrentLocale());
    }

    /**
     * @inheritDoc
     */
    public function getTemplateName(): string
    {
        return 'commerce/ko';
    }

    /**
     * @inheritDoc
     */
    public function getTemplateData(): array
    {
        $this->template_data += [
            'order' => $this->getOrder(),
            'user' => $this->getCurrentUser(),
            'locale' => $this->getCurrentLocale(),
        ];

        // Clear the order from the session after rendering the page
        $this->getCurrentUser()->getUserSession()?->removeSessionData('commerce.checkout.order')->persist();

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
            if ($this->hasLang()) {
                return $this->doRedirect($this->getUrl('frontend.commerce.cart.withlang', ['lang' => $this->getCurrentLocale()]));
            }
            return $this->doRedirect($this->getUrl('frontend.commerce.cart'));
        }

        return parent::beforeRender();
    }
}