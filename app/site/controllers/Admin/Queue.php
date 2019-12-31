<?php
/**
 * SiteBase
 * PHP Version 7.0
 *
 * @category CMS / Framework
 * @package  Degami\Sitebase
 * @author   Mirko De Grandis <degami@github.com>
 * @license  MIT https://opensource.org/licenses/mit-license.php
 * @link     https://github.com/degami/sitebase
 */
namespace App\Site\Controllers\Admin;

use \Psr\Container\ContainerInterface;
use \App\Base\Abstracts\AdminManageModelsPage;
use \App\Site\Models\QueueMessage;
use \Degami\PHPFormsApi as FAPI;

/**
 * "Queue" Admin Page
 */
class Queue extends AdminManageModelsPage
{
    /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getTemplateName()
    {
        return 'queue';
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getAccessPermission()
    {
        return 'administer_queue';
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    public function getObjectClass()
    {
        return QueueMessage::class;
    }

    /**
     * {@inheritdocs}
     *
     * @param  FAPI\Form $form
     * @param  array     &$form_state
     * @return FAPI\Form
     */
    public function getFormDefinition(FAPI\Form $form, &$form_state)
    {
        $type = $this->getRequest()->get('action') ?? 'list';
        $message = null;
        if ($this->getRequest()->get('message_id')) {
            $message = $this->loadObject($this->getRequest()->get('message_id'));
        }

        $form->addField(
            'action',
            [
            'type' => 'value',
            'value' => $type,
            ]
        );

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
     * {@inheritdocs}
     *
     * @param  FAPI\Form $form
     * @param  array     &$form_state
     * @return boolean|string
     */
    public function formValidate(FAPI\Form $form, &$form_state)
    {
        $values = $form->values();

        return true;
    }

    /**
     * {@inheritdocs}
     *
     * @param  FAPI\Form $form
     * @param  array     &$form_state
     * @return mixed
     */
    public function formSubmitted(FAPI\Form $form, &$form_state)
    {
        $message = $this->newEmptyObject();
        if ($this->getRequest()->get('message_id')) {
            $message = $this->loadObject($this->getRequest()->get('message_id'));
        }


        $values = $form->values();
        switch ($values['action']) {
            case 'requeue':
                $message->status = QueueMessage::STATUS_PENDING;
                $message->persist();

                $this->addFlashMessage('success', $this->getUtils()->translate('Message has been set for re-queue'));
                break;
            case 'delete':
                $queue->delete();
                break;
        }

        return $this->doRedirect($this->getControllerUrl());
    }

    /**
     * {@inheritdocs}
     *
     * @return array
     */
    protected function getTableHeader()
    {
        return [
            'ID' => 'id',
            'Queue' => 'queue_name',
            'Status' => 'status',
            'Result' => null,
            'Created At' => null,
            'actions' => null,
        ];
    }

    /**
     * {@inheritdocs}
     *
     * @param  array $data
     * @return array
     */
    protected function getTableElements($data)
    {
        return array_map(
            function ($message) {
                return [
                'ID' => $message->id,
                'Queue' => $message->queue_name,
                'Status' => $message->status,
                'Result' => $message->result,
                'Created At' => $message->created_at,
                'actions' => '<a class="btn btn-primary btn-sm" href="'. $this->getControllerUrl() .'?action=requeue&message_id='. $message->id.'">'.$this->getUtils()->getIcon('rotate-cw') .'</a>
                    <a class="btn btn-danger btn-sm" href="'. $this->getControllerUrl() .'?action=delete&message_id='. $message->id.'">'.$this->getUtils()->getIcon('trash') .'</a>'
                ];
            },
            $data
        );
    }
}
