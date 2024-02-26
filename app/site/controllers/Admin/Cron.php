<?php

/**
 * SiteBase
 * PHP Version 8.0
 *
 * @category CMS / Framework
 * @package  Degami\Sitebase
 * @author   Mirko De Grandis <degami@github.com>
 * @license  MIT https://opensource.org/licenses/mit-license.php
 * @link     https://github.com/degami/sitebase
 */

namespace App\Site\Controllers\Admin;

use App\Base\Exceptions\PermissionDeniedException;
use App\Site\Models\CronLog;
use App\Site\Routing\RouteInfo;
use Degami\Basics\Exceptions\BasicException;
use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Base\Abstracts\Controllers\AdminFormPage;
use App\Base\Abstracts\Controllers\AdminManageModelsPage;
use Degami\PHPFormsApi as FAPI;
use HaydenPierce\ClassFinder\ClassFinder;
use App\Site\Models\CronTask;
use App\Base\Abstracts\ContainerAwareObject;
use DateTime;

/**
 * "Cron" Admin Page
 */
class Cron extends AdminManageModelsPage
{
    public const ATTENTION_SPAN = 1800;

    /**
     * {@inheritdocs}
     *
     * @param ContainerInterface $container
     * @param Request|null $request
     * @param RouteInfo $route_info
     * @throws BasicException
     * @throws FAPI\Exceptions\FormException
     * @throws PermissionDeniedException
     * @throws DependencyException
     * @throws NotFoundException
     * @throws Exception
     */
    public function __construct(
        protected ContainerInterface $container, 
        protected ?Request $request = null, 
        protected ?RouteInfo $route_info = null
    ) {
        AdminFormPage::__construct($container, $request, $route_info);
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

                    $existing_tasks = $this->getContainer()->call([CronTask::class, 'where'], ['condition' => 'title = "' . $cron_task_name . '"']);
                    if (count($existing_tasks) > 0) {
                        continue;
                    }

                    $cron_task = $this->getContainer()->call([CronTask::class, 'new'], ['initial_data' => [
                        'title' => $cron_task_name,
                        'cron_task_callable' => $cron_task_callable,
                        'schedule' => defined($taskClass . '::DEFAULT_SCHEDULE') ? $taskClass::DEFAULT_SCHEDULE : null,
                        'active' => 0,
                    ]]);

                    $cron_task->persist();
                }
            }
        }
        parent::__construct($container, $request, $route_info);
        $this->template_data += [
            'last_beat' => $this->getLastHeartBeat(),
        ];
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getTemplateName(): string
    {
        return 'cron';
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getAccessPermission(): string
    {
        return 'administer_cron';
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    public function getObjectClass(): string
    {
        return CronTask::class;
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getObjectIdQueryParam(): string
    {
        return 'task_id';
    }

    /**
     * {@inheritdocs}
     *
     * @return array|null
     */
    public Function getAdminPageLink() : array|null
    {
        return [
            'permission_name' => $this->getAccessPermission(),
            'route_name' => 'admin.cron',
            'icon' => 'watch',
            'text' => 'Cron Tasks',
            'section' => 'system',
        ];
    }

    /**
     * {@inheritdocs}
     *
     * @param FAPI\Form $form
     * @param array     &$form_state
     * @return FAPI\Form
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getFormDefinition(FAPI\Form $form, &$form_state): FAPI\Form
    {
        $type = $this->getRequest()->get('action') ?? 'list';
        $task = $this->getObject();

        $form->addField('action', [
            'type' => 'value',
            'value' => $type,
        ]);

        switch ($type) {
            case 'edit':
            case 'new':
                $this->addBackButton();

                //$container = $this->getContainer();

                $task_title = $task_callable = $task_schedule = $task_active = '';
                if ($task->isLoaded()) {
                    $task_title = $task->title;
                    $task_callable = $task->cron_task_callable;
                    $task_schedule = $task->schedule;
                    $task_active = $task->active;
                }
                $form->addField('title', [
                    'type' => 'textfield',
                    'title' => 'Title',
                    'default_value' => $task_title,
                    'validate' => ['required'],
                ])->addField('cron_task_callable', [
                    'type' => 'textfield',
                    'title' => 'Callable',
                    'default_value' => $task_callable,
                    'validate' => ['required'],
                ])->addField('schedule', [
                    'type' => 'textfield',
                    'title' => 'Schedule',
                    'default_value' => $task_schedule,
                    'validate' => ['required'],
                ])->addField('active', [
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
                ]);

                $this->addSubmitButton($form);
                break;

            case 'run':
                $this->fillConfirmationForm('Do you confirm the execution of the selected element? Can take a while, do not close this page after submission', $form);
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
     * @return bool|string
     */
    public function formValidate(FAPI\Form $form, &$form_state): bool|string
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
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function formSubmitted(FAPI\Form $form, &$form_state): mixed
    {
        /**
         * @var CronTask $task
         */
        $task = $this->getObject();

        $values = $form->values();
        switch ($values['action']) {
            case 'new':
                $task->setUserId($this->getCurrentUser()->getId());
            // intentional fall trough
            // no break
            case 'edit':
                $task->setTitle($values['title']);
                $task->setCronTaskCallable($values['cron_task_callable']);
                $task->setSchedule($values['schedule']);
                $task->setActive(intval($values['active']));

                $this->setAdminActionLogData($task->getChangedData());

                $task->persist();
                break;
            case 'run':
                try {
                    $this->getContainer()->call(json_decode($task->getCronTaskCallable()));
                    $cron_executed[] = $task->getTitle();

                    $this->addFlashMessage('success', "Task executed: " . $task->getTitle());
                } catch (Exception $e) {
                    $this->addFlashMessage('error', $e->getMessage());
                    $this->getLog()->critical($e->getMessage() . "\n" . $e->getTraceAsString());
                }
                break;
            case 'delete':
                $task->delete();

                $this->setAdminActionLogData('Deleted cron task ' . $task->getId());

                break;
        }

        return $this->doRedirect($this->getControllerUrl());
    }

    /**
     * gets last heart beat
     *
     * @return string
     * @throws Exception
     */
    protected function getLastHeartBeat(): string
    {
        $out = '<div class="alert alert-danger" role="alert">No heart beat run yet</div>';
        // SELECT * FROM `cron_log` WHERE 1 AND FIND_IN_SET('heartbeat_pulse', tasks) > 0 ORDER BY run_time DESC LIMIT 1
        /** @var CronLog $last_beat */
        $last_beat = $this->getContainer()->call([CronLog::class, 'select'], ['options' => ['where' => ["1 AND FIND_IN_SET('heartbeat_pulse', tasks) > 0"], 'orderBy' => ['run_time DESC'], 'limitCount' => 1]])->fetch();

        if ($last_beat != null) {
            $lasbeat_date = new DateTime($last_beat['run_time']);
            $now = new DateTime();

            $interval = date_diff($lasbeat_date, $now);
            $differenceFormat = '%y Year %m Month %d Day, %h Hours %i Minutes %s Seconds';
            $out = '<div class="alert alert-' . (abs($lasbeat_date->getTimestamp() - $now->getTimestamp()) < self::ATTENTION_SPAN ? 'success' : 'warning') . '" role="alert">Last Beat on ' . $last_beat['run_time'] . ' (' . $interval->format($differenceFormat) . ' ago)</div>';
        }
        return $out;
    }

    /**
     * {@inheritdocs}
     *
     * @return array
     */
    protected function getTableHeader(): ?array
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
     * @param array $data
     * @return array
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     */
    protected function getTableElements(array $data): array
    {
        return array_map(
            function ($task) {
                return [
                    'ID' => $task->id,
                    'Title' => $task->title,
                    'Callable' => $task->cron_task_callable,
                    'Schedule' => '<a href="' . $task->getInfoUrl() . '" target="_blank">' . $task->schedule . '</a>',
                    'Active' => $this->getUtils()->translate($task->active ? 'Yes' : 'No', $this->getCurrentLocale()),
                    'actions' => implode(
                        " ",
                        [
                            $this->getEditButton($task->id),
                            $this->getDeleteButton($task->id),
                            $this->getRunButton($task->id),
                        ]
                    ),
                ];
            },
            $data
        );
    }

    /**
     * gets run button html
     *
     * @param int $object_id
     * @return string
     */
    public function getRunButton(int $object_id): string
    {
        return $this->getActionButton('run', $object_id, 'success', 'play', 'Run');
    }
}
