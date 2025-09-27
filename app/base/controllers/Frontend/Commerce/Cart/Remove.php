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

namespace App\Base\Controllers\Frontend\Commerce\Cart;

use App\App;
use App\Base\Traits\CommercePageTrait;
use App\Base\Abstracts\Controllers\BasePage;
use App\Base\Abstracts\Controllers\FrontendPageWithLang;
use Symfony\Component\HttpFoundation\Response;

class Remove extends FrontendPageWithLang
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
     * return route path
     *
     * @return array
     */
    public static function getRoutePath(): array
    {
        return [
            'frontend.commerce.cart.remove' => '/commerce/cart/remove/{row_details}',
            'frontend.commerce.cart.remove.withlang' => '/{lang:[a-z]{2}}/commerce/cart/remove/{row_details}',
        ];
    }

    /**
     * @inheritDoc
     */
    public function getTemplateName(): string
    {
        return '';
    }

    /**
     * @inheritDoc
     */
    public function getTemplateData(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     *
     * @return BasePage|Response
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function beforeRender() : BasePage|Response
    {
        // check if commerce is enabled
        if (!$this->hasLoggedUser()) {
            return $this->returnAfterLogin();
        }

        $rowDetails = base64_decode(
            $this->getRouteData('row_details') ?? ''
        );

        if (isJson($rowDetails)) {
            $rowDetails = json_decode($rowDetails, true);
            $rowId = $rowDetails['id'] ?? null;

            $this->getCart()->removeItem($rowId);

            $this->getCart()->calculate()->persist();

            $this->addSuccessFlashMessage(
                $this->getUtils()->translate('Cart Item removed successfully.')
            );

            if ($this->hasLang()) {
                return $this->doRedirect($this->getUrl('frontend.commerce.cart.withlang', ['lang' => $this->getCurrentLocale()]));            
            }

            return $this->doRedirect($this->getUrl('frontend.commerce.cart'));
        }


        $this->addErrorFlashMessage(
            $this->getUtils()->translate('No row details provided.')
        );

        if ($this->hasLang()) {
            return $this->doRedirect($this->getUrl('frontend.commerce.cart.withlang', ['lang' => $this->getCurrentLocale()]));            
        }

        return $this->doRedirect($this->getUrl('frontend.commerce.cart'));
    }
}