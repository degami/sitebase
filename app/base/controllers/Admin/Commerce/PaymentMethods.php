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
use App\Base\Abstracts\Models\BaseCollection;
use App\Base\Abstracts\Models\BaseModel;
use App\Base\Exceptions\PermissionDeniedException;
use App\Base\Routing\RouteInfo;
use App\Base\Models\User;
use App\Base\Abstracts\Models\FrontendModel;
use Degami\Basics\Exceptions\BasicException;
use Degami\PHPFormsApi\Exceptions\FormException;
use Degami\SqlSchema\Exceptions\OutOfRangeException;
use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Degami\Basics\Html\TagElement;
use ReflectionClass;
use App\Base\Interfaces\Commerce\PaymentMethodInterface;
use HaydenPierce\ClassFinder\ClassFinder;
use App\Base\Abstracts\Controllers\AdminFormPage;
use App\Base\Models\Configuration;
use Degami\PHPFormsApi as FAPI;

/**
 * Order Payments Methods manage
 */
class PaymentMethods extends AdminFormPage
{
    /**
     * @var array|null admin_log data
     */
    protected ?array $admin_action_log_data = null;

    /**
     * {@inheriydocs}
     *
     * @param ContainerInterface $container
     * @param Request $request
     * @param RouteInfo $route_info
     * @throws BasicException
     * @throws DependencyException
     * @throws FormException
     * @throws NotFoundException
     * @throws OutOfRangeException
     * @throws PermissionDeniedException
     */
    public function __construct(
        protected ContainerInterface $container, 
        protected ?Request $request = null, 
        protected ?RouteInfo $route_info = null
    ) {
        parent::__construct($container, $request, $route_info);
        if (($this->template_data['action'] ?? 'list') == 'list') {            
            $this->template_data += [
                'table' => $this->getHtmlRenderer()->renderAdminTable($this->getTableItems(), $this->getTableHeader(), $this),
            ];
        }
        $this->page_title = 'Payment Methods';
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
     * Returns Listing Table Items
     * 
     * @return array
     */
    protected function getTableItems() : array
    {
        return array_map(function($el) {
            return [
                'Code' => $el->getCode(),
                'Name' => $el->getName(),
                'Class' => get_class($el),
                'Is Active' => $this->getSiteData()->getConfigValue('payments/'.$el->getCode().'/active') ? 'yes' : 'no',
                'actions' => $this->getConfigButton($el->getCode())
            ];
        }, $this->getPaymentMethods());
    }

    /**
     * gets model table html
     *
     * @return string|null
     */
    public function getTable(): ?string
    {
        return $this->getTemplate()?->data()['table'] ?? ($this->getTemplateData()['table'] ?? null);
    }

    /**
     * defines table header
     *
     * @return array|null
     */
    protected function getTableHeader(): ?array
    {
        return [
            'Code' => [],
            'Name' => [],
            'Class' => [],
            'Is Active' => [],
            'actions' => null,
        ];
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
            'icon' => 'credit-card',
            'text' => 'Payments Methods',
            'section' => 'commerce',
            'order' => 15,
        ];
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
     * gets action button html
     *
     * @param string $action
     * @param int $object_id
     * @param string $class
     * @param string $icon
     * @param string $title
     * @return string
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getActionButton(string $action, string $object_id, string $class, string $icon, string $title = ''): string
    {
        try {
            $button = $this->containerMake(TagElement::class, ['options' => [
                'tag' => 'a',
                'attributes' => [
                    'class' => 'btn btn-sm btn-' . $class,
                    'href' => $this->getControllerUrl() . '?action=' . $action . '&code=' . $object_id,
                    'title' => (trim($title) != '') ? $this->getUtils()->translate($title, locale: $this->getCurrentLocale()) : '',
                ],
                'text' => $this->getHtmlRenderer()->getIcon($icon),
            ]]);

            return (string)$button;
        } catch (BasicException $e) {
        }

        return '';
    }

    /**
     * gets edit button html
     *
     * @param int $object_id
     * @return string
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getConfigButton(string $object_id): string
    {
        return $this->getActionButton('config', $object_id, 'primary', 'edit', 'Config');
    }

    /**
     * sets admin log data
     *
     * @param $admin_action_log_data
     * @return $this
     */
    public function setAdminActionLogData($admin_action_log_data): self
    {
        if (!is_array($admin_action_log_data)) {
            $admin_action_log_data = [$admin_action_log_data];
        }
        $this->admin_action_log_data = $admin_action_log_data;

        return $this;
    }

    /**
     * gets admin log data
     *
     * @return array|null
     */
    public function getAdminActionLogData(): ?array
    {
        return $this->admin_action_log_data;
    }

    protected function getPaymentMethods() : array
    {
        $out = array_map(function($paymentClassName) {
            return $this->containerMake($paymentClassName);
        }, array_filter(array_merge(
            ClassFinder::getClassesInNamespace(App::BASE_COMMERCE_NAMESPACE, ClassFinder::RECURSIVE_MODE),
            ClassFinder::getClassesInNamespace(App::COMMERCE_NAMESPACE, ClassFinder::RECURSIVE_MODE)
        ), function($className) {
            return is_subclass_of($className, PaymentMethodInterface::class);
        }));

        return array_combine(array_map(fn ($el) => $el->getCode(), $out), $out);
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

        if ($type == 'config') {
            $this->addBackButton();

            $code = $this->getRequest()->get('code');

            $form->addField('action', [
                'type' => 'value',
                'value' => $type,
            ]);

            $form->addField('active', [
                'type' => 'switchbox',
                'title' => 'Is Active',
                'default_value' => App::getInstance()->getSiteData()->getConfigValue('payments/' . $code . '/active') == 1,
            ]);

            /** @var PaymentMethodInterface $paymentMethod */
            $paymentMethod = $this->getPaymentMethods()[$code];

            $form = $paymentMethod->getConfigurationForm($form, $form_state);

            $this->addSubmitButton($form);
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
        $code = $this->getRequest()->get('code');
        $values = $form->values()->toArray();

        unset($values['action']);
        unset($values['button']);

        foreach ($values as $key => $value) {
            $this->getSiteData()->setConfigValue('payments/'.$code.'/'.$key, $value);
        }

        return $this->refreshPage();
    }
}
