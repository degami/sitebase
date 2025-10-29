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

namespace App\Base\Controllers\Frontend\Commerce;

use App\App;
use App\Base\Traits\CommercePageTrait;
use App\Base\Abstracts\Controllers\FormPageWithLang;
use Degami\PHPFormsApi as FAPI;
use App\Base\Routing\RouteInfo;
use Symfony\Component\HttpFoundation\Request;
use Psr\Container\ContainerInterface;
use Degami\PHPFormsApi\Containers\TableContainer;

class Cart extends FormPageWithLang
{
    use CommercePageTrait;
    
    public function __construct(
        protected ContainerInterface $container, 
        ?Request $request = null,
        ?RouteInfo $route_info = null
    ) {
        parent::__construct($container, $request, $route_info);
    }

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
     * @inheritDoc
     */
    public function getTemplateName(): string
    {
        return 'commerce/cart';
    }

    /**
     * returns valid route HTTP verbs
     *
     * @return array
     */
    public static function getRouteVerbs(): array
    {
        return ['GET', 'POST'];
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
        return $this->getUtils()->translate('Cart', locale: $this->getCurrentLocale());
    }

    /**
     * @inheritDoc
     */
    public function getTemplateData(): array
    {
        $checkoutURL = $this->getUrl('frontend.commerce.checkout.billing');
        if ($this->hasLang()) {
            $checkoutURL = $this->getUrl('frontend.commerce.checkout.billing.withlang', ['lang' => $this->getCurrentLocale()]);
        }
        $discounts = [];
        foreach ($this->getCart()->getDiscounts() ?? [] as $discountModel) {
            $removeUrl = $this->getUrl('frontend.commerce.cart.discount', [
                'action_details' => base64_encode(json_encode([
                    'action' => 'remove_discount', 
                    'discount_id' => $discountModel->getId()
                ]))
            ]);

            if ($this->hasLang()) {
                $removeUrl = $this->getUrl('frontend.commerce.cart.discount.withlang', [
                    'action_details' => base64_encode(json_encode([
                        'action' => 'remove_discount', 
                        'discount_id' => $discountModel->getId(),
                    ])),
                    'lang' => $this->getCurrentLocale(),
                ]);
            }

            $discounts[] = [
                'model' => $discountModel,
                'removeUrl' => $removeUrl,
            ];
        }

        $applyDiscountUrl = $this->getUrl('frontend.commerce.cart.discount', [
            'action_details' => base64_encode('{"from-request":true}')
        ]);
        if ($this->hasLang()) {
            $applyDiscountUrl = $this->getUrl('frontend.commerce.cart.discount.withlang', [
                'action_details' => base64_encode('{"from-request":true}'),
                'lang' => $this->getCurrentLocale(),
            ]);
        }
        return $this->template_data + [
            'cart' => $this->getCart()?->calculate(),
            'user' => $this->getCurrentUser(),
            'locale' => $this->getCurrentLocale(),
            'discounts' => $discounts,
            'checkoutURL' => $checkoutURL,
            'applyDiscountUrl' => $applyDiscountUrl,
        ];
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

        /** @var TableContainer */
        $table = $form->addField('cart_items', [
            'type' => 'table_container',
            'attributes' => [
                'class' => 'table table-striped',
            ],
        ]);

        $table->setTableHeader([
            $this->getUtils()->translate('Product'),
            $this->getUtils()->translate('Quantity'),
            $this->getUtils()->translate('Unit Price'),
            $this->getUtils()->translate('Subtotal'),
            $this->getUtils()->translate('Tax'),
            $this->getUtils()->translate('Row Total'),
            '',
        ]);

        foreach ($this->getCart()?->getItems() ?? [] as $item) {

            $itemTitle = $item->getProduct()->getName();
            if (is_callable([$item->getProduct(), 'getFrontendUrl']) && $item->getProduct()->getFrontendUrl()) {
                $itemTitle = '<a href="'.$item->getProduct()->getFrontendUrl().'">' . $item->getProduct()->getName() . '</a>';
            }

            $removeUrl = $this->getUrl('frontend.commerce.cart.remove', [
                'row_details' => base64_encode(json_encode(['id' => $item->getId()]))
            ]);
            if ($this->hasLang()) {
                $removeUrl = $this->getUrl('frontend.commerce.cart.remove.withlang', [
                    'lang' => $this->getCurrentLocale(),
                    'row_details' => base64_encode(json_encode(['id' => $item->getId()]))
                ]);
            }

            $table->addRow()
                ->addField('product_name_'.$table->numRows(), [
                    'type' => 'markup', 
                    'value' => $itemTitle,
                ])
                ->addField('quantity_'.$item->getId(), [
                    'type' => 'number',
                    'value' => $item->getQuantity(),
                    'attributes' => [
                        'min' => 1,
                        'class' => 'form-control',
                        'style' => 'max-width: 100px;',
                    ],
                ])
                ->addField('unit_price_'.$table->numRows(), [
                    'type' => 'markup',
                    'value' => $this->getUtils()->formatPrice($item->getUnitPrice(), $item->getCurrencyCode()),
                ])
                ->addField('subtotal_'.$table->numRows(), [
                    'type' => 'markup',
                    'value' => $this->getUtils()->formatPrice($item->getSubTotal(), $item->getCurrencyCode()),
                ])
                ->addField('tax_'.$table->numRows(), [
                    'type' => 'markup',
                    'value' => $this->getUtils()->formatPrice($item->getTaxAmount(), $item->getCurrencyCode()),
                ])
                ->addField('total_'.$table->numRows(), [
                    'type' => 'markup',
                    'value' => $this->getUtils()->formatPrice($item->getTotalInclTax(), $item->getCurrencyCode()),
                ])
                ->addField('remove_'.$table->numRows(), [
                    'type' => 'markup',
                    'value' => '<a href="' . $removeUrl . '" class="btn btn-danger">'
                    . $this->getHtmlRenderer()->getIcon('trash') . '</a>',
                    'container_class' => 'text-right'
                ]);
        }

        $table->addRow()
            ->addField('empty1', [
                'type' => 'markup',
                'value' => '&nbsp;',
            ])
            ->addField('update_cart', [
                'type' => 'submit',
                'value' => $this->getUtils()->translate('Update Cart'),
                'attributes' => ['class' => 'btn btn-outline-secondary btn-sm'],
                'container_class' => 'd-inline mt-3 mr-3',
            ])
            ->addField('empty2', [
                'type' => 'markup',
                'value' => '&nbsp;',
            ])
            ->addField('empty3', [
                'type' => 'markup',
                'value' => '&nbsp;',
            ])
            ->addField('empty4', [
                'type' => 'markup',
                'value' => '&nbsp;',
            ])
            ->addField('empty5', [
                'type' => 'markup',
                'value' => '&nbsp;',
            ])
            ->addField('empty6', [
                'type' => 'markup',
                'value' => '&nbsp;',
            ]);

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
        $values = $form->getValues();

        foreach($values->cart_items->getData() as $key => $value) {
            if (preg_match('/^quantity_/', $key)) {
                $item_id = str_replace('quantity_', '', $key);
                $quantity = (int) $value;
                if ($quantity > 0) {
                    $this->getCart()->getCartItem($item_id)?->setQuantity($quantity);
                } else {
                    $this->getCart()->removeItem($item_id);
                }
            }
        }

        $this->getCart()->calculate()->persist();

        $this->addSuccessFlashMessage(
            $this->getUtils()->translate('Cart updated successfully.')
        );

        return $this->refreshPage();
    }
}