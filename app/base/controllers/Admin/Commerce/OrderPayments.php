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

namespace App\Base\Controllers\Admin\Commerce;

use App\App;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Base\Routing\RouteInfo;
use Degami\Basics\Exceptions\BasicException;
use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use App\Base\Abstracts\Controllers\AdminManageFrontendModelsPage;
use Degami\PHPFormsApi as FAPI;
use App\Base\Models\OrderPayment as OrderPaymentModel;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use Symfony\Component\HttpFoundation\Response;
use App\Base\Abstracts\Controllers\BasePage;

/**
 * "Order Payments" Admin Page
 */
class OrderPayments extends AdminManageFrontendModelsPage
{
    /**
     * {@inherithdocs}
     *
     * @param ContainerInterface $container
     * @param Request|null $request
     * @param RouteInfo $route_info
     * @throws BasicException
     * @throws FAPI\Exceptions\FormException
     * @throws PermissionDeniedException
     * @throws DependencyException
     * @throws NotFoundException
     * @throws OutOfRangeException
     */
    public function __construct(
        protected ContainerInterface $container, 
        protected ?Request $request = null, 
        protected ?RouteInfo $route_info = null,
        bool $asGrid = false,
    ) {
        parent::__construct($container, $request, $route_info, $asGrid);
        $this->page_title = 'Order Payments';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getTemplateName(): string
    {
        return 'base_admin_page';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public static function getAccessPermission(): string
    {
        return 'administer_orders';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public static function getObjectClass(): string
    {
        return OrderPaymentModel::class;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    protected function getObjectIdQueryParam(): string
    {
        return 'payment_id';
    }

    /**
     * {@inheritdoc}
     *
     * @return array|null
     */
    public static function getAdminPageLink() : array|null
    {
        return [
            'permission_name' => static::getAccessPermission(),
            'route_name' => static::getPageRouteName(),
            'icon' => 'dollar-sign',
            'text' => 'Order Payments',
            'section' => 'commerce',
            'order' => 30,
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @param FAPI\Form $form
     * @param array     &$form_state
     * @return FAPI\Form
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     * @throws PhpfastcacheSimpleCacheException
     */
    public function getFormDefinition(FAPI\Form $form, array &$form_state): FAPI\Form
    {
        $type = $this->getRequest()->query->get('action') ?? 'list';

        /**
         * @var OrderPaymentModel $orderPayment
         */
        $orderPayment = $this->getObject();

        $form->addField('action', [
            'type' => 'value',
            'value' => $type,
        ]);

        $websites = $this->getUtils()->getWebsitesSelectOptions();

        switch ($type) {
            case 'view' :
                
                $form->addMarkup($this->renderPaymentInfo($orderPayment));

                break;
            case 'edit':
            case 'new':

                $form->addField('website_id', [
                    'type' => 'select',
                    'title' => 'Website',
                    'options' => $websites,
                    'required' => true,
                    'default_value' => $orderPayment->getWebsiteId(),
                ]);

                $this->addSubmitButton($form);

                break;

            case 'delete':
                $this->fillConfirmationForm('Do you confirm the deletion of the selected element?', $form);
                break;
        }

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
        //$values = $form->values();
        // @todo : check if page language is in page website languages?
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
     * @throws NotFoundException
     */
    public function formSubmitted(FAPI\Form $form, &$form_state): mixed
    {
        /**
         * @var OrderPaymentModel $orderPayment
         */
        $orderPayment = $this->getObject();

        $values = $form->values();

        switch ($values['action']) {
            case 'new':

            // intentional fall trough
            // no break
            case 'edit':

                $this->setAdminActionLogData($orderPayment->getChangedData());

                $orderPayment
                    ->setWebsiteId($values['website_id']);

                $orderPayment->persist();

                $this->addSuccessFlashMessage($this->getUtils()->translate("Order payment Saved."));
                break;
            case 'delete':
                $orderPayment->delete();

                $this->setAdminActionLogData('Deleted order payment ' . $orderPayment->getId());

                $this->addInfoFlashMessage($this->getUtils()->translate("Order payment Deleted."));

                break;
        }
        return $this->refreshPage();
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    protected function getTableHeader(): ?array
    {
        return [
            'ID' => 'id',
            'Website' => ['order' => 'website_id', 'foreign' => 'website_id', 'table' => $this->getModelTableName(), 'view' => 'site_name'],
            'Order' => ['order' => 'order_id', 'foreign' => 'order_id', 'table' => $this->getModelTableName(), 'view' => 'order_number'],
            'Payment Method' => ['order' => 'payment_method'],
            'Transaction Id' => ['order' => 'transaction_id'],
            'Transaction Amount' => ['order' => 'transaction_amount'],
            'actions' => null,
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @param array $data
     * @param array $options
     * @return array
     * @throws BasicException
     * @throws Exception
     */
    protected function getTableElements(array $data, array $options = []): array
    {
        return array_map(
            function ($orderPayment) {
                return [
                    'ID' => $orderPayment->id,
                    'Website' => $orderPayment->getWebsiteId() == null ? 'All websites' : $orderPayment->getWebsite()->domain,
                    'Order' => $orderPayment->getOrder()->getOrderNumber(),
                    'Payment Method' => $orderPayment->getPaymentMethod(),
                    'Transaction Id' => $orderPayment->getTransactionId(),
                    'Transaction Amount' => $this->getUtils()->formatPrice($orderPayment->getTransactionAmount(), $orderPayment->getCurrencyCode()),
                    'actions' => [
                        static::VIEW_BTN => $this->getViewButton($orderPayment->id),                            
                        static::EDIT_BTN => $this->getEditButton($orderPayment->id),
                        static::DELETE_BTN => $this->getDeleteButton($orderPayment->id),
                    ],
                ];
            },
            $data
        );
    }

    /**
     * gets edit button html
     *
     * @param int $object_id
     * @return string
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getViewButton(int $object_id): string
    {
        return $this->getActionButton('view', $object_id, 'secondary', 'zoom-in', 'View');
    }

    protected function renderPaymentInfo(OrderPaymentModel $orderPayment) : string
    {
        $data = $orderPayment->getData();
        $data['additional_data'] = json_decode($orderPayment->getAdditionalData(), true);
        return '<h2>'.$this->getUtils()->translate('Payment for order %s', [$orderPayment->getOrder()?->getOrderNumber()]).'</h2><hr/>'.$this->getHtmlRenderer()->renderArrayOnTable($data);
    }


    protected function beforeRender(): BasePage|Response 
    {
        if (App::getInstance()->getEnvironment()->getVariable('ENABLE_COMMERCE', false) == false) {
            $this->addWarningFlashMessage($this->getUtils()->translate("Commerce functionallity is currently disabled"), true);
        }
        return parent::beforeRender();
    }

    public static function exposeDataToDashboard() : mixed
    {
        return null;
    }
}
