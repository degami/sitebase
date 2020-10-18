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

use Degami\Basics\Exceptions\BasicException;
use \App\Base\Abstracts\Controllers\AdminManageModelsPage;
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
        return 'base_admin_page';
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
     * @return string
     */
    protected function getObjectIdQueryParam()
    {
        return 'message_id';
    }

    /**
     * {@inheritdocs}
     *
     * @param FAPI\Form $form
     * @param array     &$form_state
     * @return FAPI\Form
     */
    public function getFormDefinition(FAPI\Form $form, &$form_state)
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
     * {@inheritdocs}
     *
     * @param FAPI\Form $form
     * @param array     &$form_state
     * @return boolean|string
     */
    public function formValidate(FAPI\Form $form, &$form_state)
    {
        //$values = $form->values();
        return true;
    }

    /**
     * {@inheritdocs}
     *
     * @param FAPI\Form $form
     * @param array     &$form_state
     * @return mixed
     * @throws BasicException
     */
    public function formSubmitted(FAPI\Form $form, &$form_state)
    {
        $queue = $this->getObject();

        $values = $form->values();
        switch ($values['action']) {
            case 'requeue':
                $queue->status = QueueMessage::STATUS_PENDING;
                $queue->persist();

                $this->setAdminActionLogData('Requeued queue ' . $queue->getId());

                $this->addFlashMessage('success', $this->getUtils()->translate('Message has been set for re-queue'));
                break;
            case 'delete':
                $queue->delete();

                $this->setAdminActionLogData('Deleted queue ' . $queue->getId());

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
     * @param array $data
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
                    'actions' => implode(
                        " ",
                        [
                            $this->getActionButton('requeue', $message->id, 'primary', 'rotate-cw', 'ReQueue'),
                            $this->getDeleteButton($message->id),
                        ]
                    ),
                ];
            },
            $data
        );
    }
}
