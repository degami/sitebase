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
use \App\Base\Abstracts\AdminFormPage;
use \App\Base\Abstracts\AdminManageModelsPage;
use \Degami\PHPFormsApi as FAPI;
use \HaydenPierce\ClassFinder\ClassFinder;
use \App\Site\Models\CronTask;
use \App\Base\Abstracts\ContainerAwareObject;
use \DateTime;

/**
 * "Cron" Admin Page
 */
class Cron extends AdminManageModelsPage
{
    const ATTENTION_SPAN = 1800;

    /**
     * {@inheritdocs}
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        AdminFormPage::__construct($container);
        $this->page_title = 'Cron Tasks';
        if (($this->getRequest()->get('action') ?? 'list') == 'list') {
            $taskClasses = ClassFinder::getClassesInNamespace('App\Site\Cron\Tasks');
            foreach ($taskClasses as $taskClass) {
                foreach (get_class_methods($taskClass) as $key => $method_name) {
                    $cron_task_callable = json_encode([$taskClass, $method_name]);

                    $cron_task_name = ltrim(
                        strtolower(
                            str_replace("App\\Site\\Cron\\Tasks", "", $taskClass)
                        ) . "_" .
                        strtolower($method_name),
                        "\\"
                    );

                    if (preg_match("/^__/i", $method_name)) {
                        continue;
                    }
                    if (in_array($method_name, get_class_methods(ContainerAwareObject::class))) {
                        continue;
                    }

                    $existing_tasks = $this->getContainer()->call([CronTask::class, 'where'], ['condition' => 'title = "'.$cron_task_name.'"']);
                    if (count($existing_tasks) > 0) {
                        continue;
                    }

                    $cron_task = $this->getContainer()->make(CronTask::class);
                    $cron_task->title = $cron_task_name;
                    $cron_task->cron_task_callable = $cron_task_callable;
                    $cron_task->schedule = null;
                    if (defined($taskClass.'::DEFAULT_SCHEDULE')) {
                        $cron_task->schedule = $taskClass::DEFAULT_SCHEDULE;
                    }
                    $cron_task->active = 0;

                    $cron_task->persist();
                }
            }
        }
        parent::__construct($container);
        $this->templateData += [
            'last_beat' => $this->getLastHeartBeat(),
        ];
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getTemplateName()
    {
        return 'cron';
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getAccessPermission()
    {
        return 'administer_cron';
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    public function getObjectClass()
    {
        return CronTask::class;
    }

   /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getObjectIdQueryParam()
    {
        return 'task_id';
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
        $task = $this->getObject();

        $form->addField(
            'action',
            [
            'type' => 'value',
            'value' => $type,
            ]
        );

        switch ($type) {
            case 'edit':
            case 'new':
                $this->addBackButton();

                $container = $this->getContainer();

                $task_title = $task_callable = $task_schedule = $task_active = '';
                if ($task->isLoaded()) {
                    $task_title = $task->title;
                    $task_callable = $task->cron_task_callable;
                    $task_schedule = $task->schedule;
                    $task_active = $task->active;
                }
                $form
                ->addField(
                    'title',
                    [
                    'type' => 'textfield',
                    'title' => 'Title',
                    'default_value' => $task_title,
                    'validate' => ['required'],
                    ]
                )
                ->addField(
                    'cron_task_callable',
                    [
                    'type' => 'textfield',
                    'title' => 'Callable',
                    'default_value' => $task_callable,
                    'validate' => ['required'],
                    ]
                )
                ->addField(
                    'schedule',
                    [
                    'type' => 'textfield',
                    'title' => 'Schedule',
                    'default_value' => $task_schedule,
                    'validate' => ['required'],
                    ]
                )
                ->addField(
                    'active',
                    [
                    'type' => 'switchbox',
                    'title' => 'Active',
                    //                    'value' => boolval($task_active) ? 1 : 0,
                    //                    'default_value' => 1,
                    'default_value' => boolval($task_active) ? 1 : 0,
                    'yes_value' => 1,
                    'yes_label' => 'Yes',
                    'no_value' => 0,
                    'no_label' => 'No',
                    'field_class' => 'switchbox',
                    ]
                );

                $this->addSubmitButton($form);
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
        /**
         * @var CronTask $task
         */
        $task = $this->getObject();

        $values = $form->values();
        switch ($values['action']) {
            case 'new':
                $task->user_id = $this->getCurrentUser()->id;
                // intentional fall trough
                // no break
            case 'edit':
                $task->title = $values['title'];
                $task->cron_task_callable = $values['cron_task_callable'];
                $task->schedule = $values['schedule'];
                $task->active = intval($values['active']);

                $task->persist();
                break;
            case 'delete':
                $task->delete();
                break;
        }

        return $this->doRedirect($this->getControllerUrl());
    }

    /**
     * gets last heart beat
     *
     * @return string
     */
    protected function getLastHeartBeat()
    {
        $out = '<div class="alert alert-danger" role="alert">No heart beat run yet</div>';
        // SELECT * FROM `cron_log` WHERE 1 AND FIND_IN_SET('heartbeat_pulse', tasks) > 0 ORDER BY run_time DESC LIMIT 1
        $last_beat = $this->getDb()
            ->cron_log()
            ->where("1 AND FIND_IN_SET('heartbeat_pulse', tasks) > 0")
            ->orderBy('run_time', 'DESC')
            ->limit(1)
            ->fetch();

        if ($last_beat != null) {
            $lasbeat_date = new DateTime($last_beat['run_time']);
            $now = new DateTime();

            $interval = date_diff($lasbeat_date, $now);
            $differenceFormat = '%y Year %m Month %d Day, %h Hours %i Minutes %s Seconds';
            $out = '<div class="alert alert-'.(abs($lasbeat_date->getTimestamp() - $now->getTimestamp()) < self::ATTENTION_SPAN ? 'success':'warning').'" role="alert">Last Beat on '.$last_beat['run_time'].' ('.$interval->format($differenceFormat).' ago)</div>';
        }
        return $out;
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
            'Title' => ['order' => 'title', 'search' => 'title'],
            'Callable' => ['order' => 'cron_task_callable', 'search' => 'cron_task_callable'],
            'Schedule' => ['order' => 'schedule', 'search' => 'schedule'],
            'Active' => 'active',
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
            function ($task) {
                return [
                'ID' => $task->id,
                'Title' => $task->title,
                'Callable' => $task->cron_task_callable,
                'Schedule' => '<a href="'. $task->getInfoUrl() .'" target="_blank">'. $task->schedule .'</a>',
                'Active' => $this->getUtils()->translate($task->active ? 'Yes' : 'No', $this->getCurrentLocale()),
                'actions' => implode(
                    " ",
                    [
                    $this->getEditButton($task->id),
                    $this->getDeleteButton($task->id),
                    ]
                ),
                ];
            },
            $data
        );
    }
}
