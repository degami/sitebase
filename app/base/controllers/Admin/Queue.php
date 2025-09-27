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

use App\App;
use Degami\Basics\Exceptions\BasicException;
use App\Base\Abstracts\Controllers\AdminManageModelsPage;
use App\Base\Abstracts\Controllers\BasePage;
use App\Base\Commands\Queue\Process;
use App\Base\Models\QueueMessage;
use Degami\PHPFormsApi as FAPI;
use DI\DependencyException;
use DI\NotFoundException;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Base\Routing\RouteInfo;
use Symfony\Component\HttpFoundation\Response;

/**
 * "Queue" Admin Page
 */
class Queue extends AdminManageModelsPage
{
    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getTemplateName(): string
    {
        return 'queue';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public static function getAccessPermission(): string
    {
        return 'administer_queue';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public static function getObjectClass(): string
    {
        return QueueMessage::class;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    protected function getObjectIdQueryParam(): string
    {
        return 'message_id';
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
            'icon' => 'truck',
            'text' => 'Queue',
            'section' => 'system',
            'order' => 7,
        ];
    }

    public function beforeRender() : BasePage|Response
    {
        $this->removeAction('new-btn');
        if (($this->getRequest()->get('action') ?? 'list') == 'list') {
            if ($this->checkQueueIsRunning()) {
                $this->addInfoFlashMessage($this->getUtils()->translate('Queue is running.'), true);
            } else {
                $this->addWarningFlashMessage($this->getUtils()->translate('Queue is NOT running.'), true);
            }
        }

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

        if ($this->getRequest()->get('action') == 'details' && $this->getRequest()->get('message_id')) {
            $this->addBackButton();
            $message = $this->containerCall([QueueMessage::class, 'load'], ['id' => $this->getRequest()->get('message_id')]);
            $out += [
                'message' => $message,
                'messageHtml' => $this->getHtmlRenderer()->renderQueueMessage($message),
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
        $type = $this->getRequest()->get('action') ?? 'list';
        //$message = $this->getObject();

        $form->addField('action', [
            'type' => 'value',
            'value' => $type,
        ]);

        switch ($type) {
            case 'requeue':
                $this->fillConfirmationForm('Do you confirm the requeue of the selected element?', $form);
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
        /** @var QueueMessage $queue */
        $queue = $this->getObject();

        $values = $form->values();
        switch ($values['action']) {
            case 'requeue':
                $queue->setStatus(QueueMessage::STATUS_PENDING);
                $queue->setResult(null);
                $queue->persist();

                $this->setAdminActionLogData('Requeued queue ' . $queue->getId());

                $this->addSuccessFlashMessage($this->getUtils()->translate('Message has been set for re-queue', locale: $this->getCurrentLocale()));
                break;
            case 'delete':
                $queue->delete();

                $this->setAdminActionLogData('Deleted queue ' . $queue->getId());

                $this->addInfoFlashMessage($this->getUtils()->translate("Message Deleted."));
                
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
            'Queue' => 'queue_name',
            'Status' => 'status',
            'Result' => null,
            'Created At' => null,
            'Executed At' => null,
            'actions' => null,
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @param array $data
     * @return array
     * @throws DependencyException
     * @throws NotFoundException
     */
    protected function getTableElements(array $data): array
    {
        return array_map(
            function ($message) {
                return [
                    'ID' => $message->id,
                    'Queue' => $message->queue_name,
                    'Status' => $message->status,
                    'Result' => $message->result,
                    'Created At' => $message->created_at,
                    'Executed At' => $message->executed_at,
                    'actions' => implode(
                        " ",
                        [
                            $this->getActionButton('details', $message->id, 'primary', 'zoom-in', 'Details'),
                            $this->getActionButton('requeue', $message->id, 'primary', 'rotate-cw', 'ReQueue'),
                            $this->getDeleteButton($message->id),
                        ]
                    ),
                ];
            },
            $data
        );
    }

    protected function checkQueueIsRunning() : bool
    {
        $lock_path = App::getDir(App::TMP) . DS . Process::LOCKFILE_NAME;
        if (!file_exists($lock_path)) {
            return false;
        }

        if ($fp = fopen($lock_path, "r+")) {
            if (flock($fp, LOCK_EX | LOCK_NB)) {
                return false;
            }
        }

        return true;
    }
}
