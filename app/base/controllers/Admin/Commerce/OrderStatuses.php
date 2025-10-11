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
use App\Base\Models\OrderStatus as OrderStatusModel;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use Symfony\Component\HttpFoundation\Response;
use App\Base\Abstracts\Controllers\BasePage;

/**
 * "Order Statuses" Admin Page
 */
class OrderStatuses extends AdminManageFrontendModelsPage
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
        protected ?RouteInfo $route_info = null
    ) {
        parent::__construct($container, $request, $route_info);
        $this->page_title = 'Order Statuses';
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
        return OrderStatusModel::class;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    protected function getObjectIdQueryParam(): string
    {
        return 'status_id';
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
            'icon' => 'compass',
            'text' => 'Order Statuses',
            'section' => 'commerce',
            'order' => 20,
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
        $type = $this->getRequest()->get('action') ?? 'list';

        /**
         * @var OrderStatusModel $orderStatus
         */
        $orderStatus = $this->getObject();

        $form->addField('action', [
            'type' => 'value',
            'value' => $type,
        ]);

        $websites = $this->getUtils()->getWebsitesSelectOptions();

        switch ($type) {
            case 'edit':
            case 'new':

                $form->addField('website_id', [
                    'type' => 'select',
                    'title' => 'Website',
                    'options' => $websites,
                    'required' => true,
                    'default_value' => $orderStatus->getWebsiteId(),
                ])
                ->addField('status', [
                    'type' => 'textfield',
                    'title' => 'Status',
                    'required' => true,
                    'default_value' => $orderStatus->getStatus(),
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
         * @var OrderStatusModel $orderStatus
         */
        $orderStatus = $this->getObject();

        $values = $form->values();

        switch ($values['action']) {
            case 'new':

            // intentional fall trough
            // no break
            case 'edit':

                $this->setAdminActionLogData($orderStatus->getChangedData());

                $orderStatus
                    ->setStatus($values['status'])
                    ->setWebsiteId($values['website_id']);

                $orderStatus->persist();

                $this->addSuccessFlashMessage($this->getUtils()->translate("Order status Saved."));
                break;
            case 'delete':
                $orderStatus->delete();

                $this->setAdminActionLogData('Deleted order status ' . $orderStatus->getId());

                $this->addInfoFlashMessage($this->getUtils()->translate("Order status Deleted."));

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
            'Status' => ['order' => 'status', ],
            'actions' => null,
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @param array $data
     * @return array
     * @throws BasicException
     * @throws Exception
     */
    protected function getTableElements(array $data): array
    {
        return array_map(
            function ($orderStatus) {
                return [
                    'ID' => $orderStatus->id,
                    'Website' => $orderStatus->getWebsiteId() == null ? 'All websites' : $orderStatus->getWebsite()->domain,
                    'Status' => $orderStatus->getStatus(),
                    'actions' => implode(
                        " ",
                        [
                            $this->getEditButton($orderStatus->id),
                            $this->getDeleteButton($orderStatus->id),
                        ]
                    ),
                ];
            },
            $data
        );
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
