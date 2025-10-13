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
use App\Base\Models\Discount as DiscountModel;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use Symfony\Component\HttpFoundation\Response;
use App\Base\Abstracts\Controllers\BasePage;

/**
 * "Discounts" Admin Page
 */
class Discounts extends AdminManageFrontendModelsPage
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
        $this->page_title = 'Cart Discounts';
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
        return DiscountModel::class;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    protected function getObjectIdQueryParam(): string
    {
        return 'discount_id';
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
            'icon' => 'star',
            'text' => 'Discounts',
            'section' => 'commerce',
            'order' => 16,
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
         * @var DiscountModel $discount
         */
        $discount = $this->getObject();

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
                    'validate' => ['required'],
                    'default_value' => $discount->getWebsiteId(),
                ])->addField('title', [
                    'type' => 'textfield',
                    'title' => 'Title',
                    'validate' => ['required'],
                    'default_value' => $discount->getTitle(),
                ])->addField('code', [
                    'type' => 'textfield',
                    'title' => 'Code',
                    'validate' => ['required'],
                    'default_value' => $discount->getCode(),
                ])->addField('active', [
                    'type' => 'switchbox',
                    'title' => 'Active',
                    'default_value' => $discount->getActive(),
                ])->addField('discount_amount', [
                    'type' => 'textfield',
                    'title' => 'Discount Amount',
                    'validate' => ['required', 'numeric'],                    
                    'default_value' => $discount->getDiscountAmount(),
                ])->addField('discount_type', [
                    'type' => 'select',
                    'title' => 'Discount Type',
                    'options' => [
                        '' => '-- Select --',
                        'fixed' => 'Fixed Amount',
                        'percentage' => '% of Total',
                    ],
                    'default_value' => $discount->getDiscountType(),
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
         * @var DiscountModel $discount
         */
        $discount = $this->getObject();

        $values = $form->values();

        switch ($values['action']) {
            case 'new':
            $discount->setUserId($this->getCurrentUser()->getId());
            // intentional fall trough
            // no break
            case 'edit':

                $discount
                    ->setWebsiteId($values['website_id'])
                    ->setTitle($values['title'])
                    ->setCode($values['code'])
                    ->setActive($values['active'])
                    ->setDiscountAmount($values['discount_amount'])
                    ->setDiscountType($values['discount_type']);
                    
                $this->setAdminActionLogData($discount->getChangedData());

                $discount->persist();

                $this->addSuccessFlashMessage($this->getUtils()->translate("Discount Saved."));
                break;
            case 'delete':
                $discount->delete();

                $this->setAdminActionLogData('Deleted discount ' . $discount->getId());

                $this->addInfoFlashMessage($this->getUtils()->translate("Discount Deleted."));

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
            'Title' => ['order' => 'title', 'search' => 'title'],
            'Code' => ['order' => 'code', 'search' => 'code'],
            'Active' => ['order' => 'active', 'search' => 'active'],
            'Discount Amount' => ['order' => 'discount_amount', 'search' => 'discount_amount'],
            'Discount Type' => ['order' => 'discount_type', 'search' => 'discount_type'],
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
            function ($discount) {
                return [
                    'ID' => $discount->id,
                    'Website' => $discount->getWebsiteId() == null ? 'All websites' : $discount->getWebsite()->domain,
                    'Title' => $discount->getTitle(),
                    'Code' => $discount->getCode(),
                    'Active' => $discount->getActive() ? 'Yes' : 'No',
                    'Discount Amount' => $discount->getDiscountAmount(),
                    'Discount Type' => $discount->getDiscountType(),
                    'actions' => [
                        static::EDIT_BTN => $this->getEditButton($discount->id),
                        static::DELETE_BTN => $this->getDeleteButton($discount->id),
                    ],
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
