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

namespace App\Site\Controllers\Frontend\Commerce;

use App\App;
use Degami\Basics\Exceptions\BasicException;
use Exception;
use App\Base\Abstracts\Controllers\FrontendPageWithObject;
use App\Base\Routing\RouteInfo;
use Symfony\Component\HttpFoundation\Response;
use App\Base\Exceptions\NotFoundException;
use App\Site\Models\Book;
use Throwable;
use Degami\PHPFormsApi as FAPI;
use App\Base\Abstracts\Controllers\BasePage;
use App\Base\Traits\FormPageTrait;

/**
 * Book Detail Page
 */
class BookDetail extends FrontendPageWithObject
{
    use FormPageTrait;

    /**
     * return route path
     *
     * @return string
     */
    public static function getRoutePath(): string
    {
        return 'book/{id:\d+}';
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
     */
    public function getTemplateName(): string
    {
        return 'commerce/book_detail';
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public static function isEnabled(): bool
    {
        return App::installDone() && boolval(\App\App::getInstance()->getEnvironment()->getVariable('ENABLE_COMMERCE'));
    }

    /**
     * {@inheritdoc}
     *
     * @param RouteInfo|null $route_info
     * @param array $route_data
     * @return Response
     * @throws Exception
     * @throws NotFoundException
     * @throws Throwable
     */
    public function process(?RouteInfo $route_info = null, array $route_data = []): Response
    {
        if (!($this->getObject() instanceof Book && $this->getObject()->isLoaded())) {
            throw new NotFoundException();
        }

        return parent::process($route_info);
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    protected function getHtmlBodyClasses() : string
    {
        return parent::getHtmlBodyClasses() . ' book-' . $this->getObject()->id;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public static function getObjectClass(): string
    {
        return Book::class;
    }

    /**
     * {@inheritdoc}
     *
     * @return ContactForm|Response
     * @throws BasicException
     * @throws FAPI\Exceptions\FormException
     * @throws PermissionDeniedException
     */
    protected function beforeRender() : BasePage|Response
    {
        $route_data = $this->getRouteData();

        if (isset($route_data['id'])) {
            $this->setObject($this->containerCall([Book::class, 'load'], ['id' => $route_data['id']]));

            $this->template_data += [
                'form' => FAPI\FormBuilder::getForm([$this, 'getFormDefinition'])
                    ->setValidate([[$this, 'formValidate']])
                    ->setSubmit([[$this, 'formSubmitted']]),
            ];
        }

        $this->processFormSubmit();

        if ($this->getForm() && $this->getForm()->isSubmitted()) {
            $this->getApp()->event('form_submitted', ['form' => $this->getForm()]);
            return $this->getForm()->getSubmitResults(get_class($this) . '::formSubmitted');
        }

        return parent::beforeRender();
    }

    /**
     * {@inheritdoc}
     *
     * @param FAPI\Form $form
     * @param array     &$form_state
     * @return FAPI\Form
     * @throws BasicException
     * @throws DependencyException
     * @throws \DI\NotFoundException
     */
    public function getFormDefinition(FAPI\Form $form, array &$form_state): FAPI\Form
    {
        $form->addField('quantity', [
            'type' => 'number',
            'label' => 'Quantity',
            'required' => true,
            'default_value' => 1,
            'size' => 3,
            'min' => 1,
            'max' => 10000,
            'container_class' => 'd-inline-block',
        ]);

        $stockQty = $this->getObject()->getProductStock()?->getCurrentQuantity() ?? 0;

        $this->addSubmitButton(
            $form, 
            true, 
            true, 
            $this->getUtils()->translate('Add to Cart', locale: $this->getCurrentLocale()),
            'shopping-cart',
            $stockQty < 1
        );

        $form->setSuffix($this->getUtils()->translate(' items in stock: %d', [$stockQty], locale: $this->getCurrentLocale()));

        return $form;
    }

    /**
     * {@inheritdoc}
     *
     * @param FAPI\Form $form
     * @param array     &$form_state
     * @return bool|string
     */
    public function formValidate(FAPI\Form $form, &$form_state): bool|string
    {
        $values = $form->getValues()->getData();
        $quentity = (int) $values['quantity'];

        if ($quentity < 1 || $quentity > 10000) {
            return 'Quantity must be between 1 and 10000.';
        }

        return true;
    }

        /**
     * {@inheritdoc}
     *
     * @param FAPI\Form $form
     * @param array     &$form_state
     * @return mixed
     * @throws BasicException
     * @throws DependencyException
     * @throws PhpfastcacheSimpleCacheExceptionAlias
     * @throws Throwable
     * @throws \DI\NotFoundException
     */
    public function formSubmitted(FAPI\Form $form, &$form_state): mixed
    {
        $values = $form->getValues()->getData();

        $product = $this->getObject();
        $quentity = (int) $values['quantity'];

        // head to "add to cart page"

        return $this->doRedirect(
            $this->getUrl('frontend.commerce.cart.add',
                [
                    'product_details' => base64_encode(json_encode([
                        'class' => Book::class,
                        'id' => $product->id,
                        'quantity' => $quentity,
                    ])),
                ])
        );

        $form->reset();
        return null;
    }

    /**
     * process form submission
     *
     * @return void
     * @throws BasicException
     */
    protected function processFormSubmit() : void
    {
        $this->getApp()->event('before_form_process', ['form' => $this->getForm()]);
        $this->getForm()?->process();
    }
}
