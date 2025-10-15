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

namespace App\Base\Controllers\Admin;

use Degami\Basics\Exceptions\BasicException;
use App\Base\Abstracts\Controllers\AdminManageModelsPage;
use App\Base\Abstracts\Controllers\BasePage;
use App\Base\Models\ApplicationLog;
use Degami\PHPFormsApi as FAPI;
use DI\DependencyException;
use DI\NotFoundException;
use Symfony\Component\HttpFoundation\Response;

/**
 * "Application Logs" Admin Page
 */
class ApplicationLogs extends AdminManageModelsPage
{
    /**
     * @var string page title
     */
    protected ?string $page_title = 'Application Logs';

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getTemplateName(): string
    {
        return 'application_logs';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public static function getAccessPermission(): string
    {
        return 'administer_applicationlogs';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public static function getObjectClass(): string
    {
        return ApplicationLog::class;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    protected function getObjectIdQueryParam(): string
    {
        return 'log_id';
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    protected function defaultOrder() : array
    {
        return ['created_at' => 'DESC'];
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
            'icon' => 'cast',
            'text' => 'Application Logs',
            'section' => 'system',
            'order' => 8,
        ];
    }

    public function beforeRender() : BasePage|Response
    {
        $this->removeAction('new-btn');

        return parent::beforeRender();
    }

    /**
     * @return array
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getTemplateData(): array
    {
        $out = parent::getTemplateData();

        if ($this->getRequest()->query->get('action') == 'details' && $this->getRequest()->query->get('log_id')) {
            $log = $this->containerCall([ApplicationLog::class, 'load'], ['id' => $this->getRequest()->query->get('log_id')]);
            $out += [
                'log' => $log,
                'logHtml' => $this->getHtmlRenderer()->renderApplicationLog($log),
            ];
        }

        return $out;
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
     */
    public function getFormDefinition(FAPI\Form $form, array &$form_state): FAPI\Form
    {
        $type = $this->getRequest()->query->get('action') ?? 'list';

        $form->addField('action', [
            'type' => 'value',
            'value' => $type,
        ]);

        switch ($type) {
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
        /** @var ApplicationLog $log */
        $log = $this->getObject();

        $values = $form->values();
        switch ($values['action']) {
            case 'delete':
                $log->delete();

                $this->setAdminActionLogData('Deleted log ' . $log->getId());

                $this->addInfoFlashMessage($this->getUtils()->translate("Application Log Deleted."));
                
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
            'File' => 'file',
            'Line' => 'line',
            'Level' => 'level',
            'Is Exception' => 'is_exception',
            'Created At' => null,
            'actions' => null,
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @param array $data
     * @param array $options
     * @return array
     * @throws DependencyException
     * @throws NotFoundException
     */
    protected function getTableElements(array $data, array $options = []): array
    {
        return array_map(
            function ($log) {
                return [
                    'ID' => $log->id,
                    'File' => $log->file,
                    'Line' => $log->line,
                    'Level' => $log->level,
                    'Is Exception' => $log->is_exception ? 'true' : 'false',
                    'Created At' => $log->created_at,
                    'actions' => [
                        'details-btn.dblclick' => $this->getActionButton('details', $log->id, 'primary', 'zoom-in', 'Details'),
                        static::DELETE_BTN => $this->getDeleteButton($log->id),
                    ],
                ];
            },
            $data
        );
    }

    public static function exposeDataToDashboard() : mixed
    {
        return null;
    }

    protected function hasLayoutSelector(): bool
    {
        return false;
    }
}
