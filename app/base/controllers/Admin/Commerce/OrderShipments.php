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
use App\Base\Models\OrderShipment as OrderShipmentModel;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use Symfony\Component\HttpFoundation\Response;
use App\Base\Abstracts\Controllers\BasePage;
use App\Base\Models\Order;

/**
 * "Order Shipments" Admin Page
 */
class OrderShipments extends AdminManageFrontendModelsPage
{
    public function __construct(
        protected ContainerInterface $container, 
        protected ?Request $request = null, 
        protected ?RouteInfo $route_info = null,
        bool $asGrid = false,
    ) {
        parent::__construct($container, $request, $route_info, $asGrid);

        if ($this->getEnvironment()->getVariable('GOOGLE_API_KEY')) {
            $this->getAssets()->addHeadJs('https://maps.googleapis.com/maps/api/js?v=3.exp&amp&amp;libraries=geometry,places&amp;key='. $this->getEnvironment()->getVariable('GOOGLE_API_KEY'));
        } else if ($this->getEnvironment()->getVariable('MAPBOX_API_KEY')) {
            $this->getAssets()->addHeadJs('https://unpkg.com/leaflet@1.3.4/dist/leaflet.js', [
                'integrity' => "sha512-nMMmRyTVoLYqjP9hrbed9S+FzjZHW5gY1TWCHA5ckwXZBadntCNs8kEqAWdrb9O7rxbCaA4lKTIWjDXZxflOcA==",
                'crossorigin' => "1",
            ]);

            $this->getAssets()->addHeadCss('https://unpkg.com/leaflet@1.3.4/dist/leaflet.css', [
                'integrity' => "sha512-puBpdR0798OZvTTbP4A8Ix/l+A4dHDD0DGqYW6RQ+9jxkRFclaxxQb/SJAWZfWAkuyeQUytO7+7N4QKrDh+drA==",
                'crossorigin' => "1",
            ]);
        }
    }

    /**
     * @var string page title
     */
    protected ?string $page_title = 'Order Shipments';

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
        return OrderShipmentModel::class;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    protected function getObjectIdQueryParam(): string
    {
        return 'shipment_id';
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
            'icon' => 'navigation',
            'text' => 'Order Shipments',
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
         * @var OrderShipmentModel $orderShipment
         */
        $orderShipment = $this->getObject();

        $form->addField('action', [
            'type' => 'value',
            'value' => $type,
        ]);

        $websites = $this->getUtils()->getWebsitesSelectOptions();

        switch ($type) {
            case 'view' :


                break;
            case 'edit':
            case 'new':

                $locationFieldType = 'geolocation'; $mapOptions = [];
                if ($this->getEnvironment()->getVariable('GOOGLE_API_KEY')) {
                    $locationFieldType = 'gmaplocation';
                }
                else if ($this->getEnvironment()->getVariable('MAPBOX_API_KEY')) {
                    $locationFieldType = 'leafletlocation';
                    $mapOptions = [
                        'accessToken' => $this->getEnvironment()->getVariable('MAPBOX_API_KEY'),
                        'scrollwheel' => true,
                        'maptype' => 'mapbox/streets-v12',
                    ];
                }

                $form
                ->addField('shipping_method', [
                    'type' => 'textfield',
                    'title' => 'Shipping Method',
                    'required' => true,
                    'default_value' => $orderShipment->getShippingMethod(),
                ])
                ->addField('shipment_code', [
                    'type' => 'textfield',
                    'title' => 'Shipment Code',
                    'required' => true,
                    'default_value' => $orderShipment->getShipmentCode(),
                ])
                ->addField('status', [
                    'type' => 'textfield',
                    'title' => 'Status',
                    'required' => true,
                    'default_value' => $orderShipment->getStatus(),
                ])
                ->addField('location', [
                    'type' => $locationFieldType,
                    'title' => 'Location',
                    'default_value' => $orderShipment->getCurrentLocation(),
                ] + $mapOptions)
                ->addField('website_id', [
                    'type' => 'select',
                    'title' => 'Website',
                    'options' => $websites,
                    'required' => true,
                    'default_value' => $orderShipment->getWebsiteId(),
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
         * @var OrderShipmentModel $orderShipment
         */
        $orderShipment = $this->getObject();

        $values = $form->values();

        switch ($values['action']) {
            case 'new':

            // intentional fall trough
            // no break
            case 'edit':

                
                break;
            case 'delete':
                $orderShipment->delete();

                $this->setAdminActionLogData('Deleted order shipment ' . $orderShipment->getId());

                $this->addInfoFlashMessage($this->getUtils()->translate("Order shipment Deleted."));

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
            'Shipping Method' => ['order' => 'shipping_method'],
            'Shipment Code' => ['order' => 'shipment_code'],
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
            function ($orderShipment) {
                return [
                    'ID' => $orderShipment->id,
                    'Website' => $orderShipment->getWebsiteId() == null ? 'All websites' : $orderShipment->getWebsite()->domain,
                    'Order' => $orderShipment->getOrder()->getOrderNumber(),
                    'Shipping Method' => $orderShipment->getShippingMethod(),
                    'Shipment Code' => $orderShipment->getShipmentCode(),
                    'actions' => [
                        static::VIEW_BTN => $this->getViewButton($orderShipment->id),                            
                        static::EDIT_BTN => $this->getEditButton($orderShipment->id),
                        static::DELETE_BTN => $this->getDeleteButton($orderShipment->id),
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
