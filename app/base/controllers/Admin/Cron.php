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
use App\Base\Models\CronLog;
use Degami\Basics\Exceptions\BasicException;
use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use App\Base\Abstracts\Controllers\AdminManageModelsPage;
use Degami\PHPFormsApi as FAPI;
use HaydenPierce\ClassFinder\ClassFinder;
use App\Base\Models\CronTask;
use App\Base\Abstracts\ContainerAwareObject;
use App\Base\Abstracts\Controllers\BasePage;
use DateTime;
use Symfony\Component\HttpFoundation\Response;

/**
 * "Cron" Admin Page
 */
class Cron extends AdminManageModelsPage
{
    public const ATTENTION_SPAN = 1800;

    /**
     * @var string page title
     */
    protected ?string $page_title = 'Cron Tasks';

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getTemplateName(): string
    {
        return 'cron';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public static function getAccessPermission(): string
    {
        return 'administer_cron';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public static function getObjectClass(): string
    {
        return CronTask::class;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    protected function getObjectIdQueryParam(): string
    {
        return 'task_id';
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
            'icon' => 'watch',
            'text' => 'Cron Tasks',
            'section' => 'system',
            'order' => 8,
        ];
    }

    public function beforeRender() : BasePage|Response
    {
        if (($this->getRequest()->query->get('action') ?? 'list') == 'list') {
            $baseTaskClasses = ClassFinder::getClassesInNamespace(App::BASE_CRON_TASKS_NAMESPACE);
            $taskClasses = ClassFinder::getClassesInNamespace(App::CRON_TASKS_NAMESPACE);
            foreach (array_merge($baseTaskClasses, $taskClasses) as $taskClass) {
                foreach (get_class_methods($taskClass) as $key => $method_name) {
                    $cron_task_callable = json_encode([$taskClass, $method_name]);

                    $cron_task_name = ltrim(
                        strtolower(
                            str_replace("App\\Site\\Cron\\Tasks", "", str_replace("App\\Base\\Cron\\Tasks", "", $taskClass))
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

                    $existing_tasks = CronTask::getCollection()->where('title = "' . $cron_task_name . '"')->getItems();
                    if (count($existing_tasks) > 0) {
                        continue;
                    }

                    $cron_task = $this->containerCall([CronTask::class, 'new'], ['initial_data' => [
                        'title' => $cron_task_name,
                        'cron_task_callable' => $cron_task_callable,
                        'schedule' => defined($taskClass . '::DEFAULT_SCHEDULE') ? $taskClass::DEFAULT_SCHEDULE : null,
                        'active' => 0,
                    ]]);

                    $cron_task->persist();
                }
            }

            $lastBeat = $this->getLastHeartBeat();

            if (!$lastBeat) {
                $this->addErrorFlashMessage($this->getUtils()->translate('No heart beat run yet'), true);
            } else {
                $lasbeat_date = new DateTime($lastBeat['run_time']);
                $now = new DateTime();
    
                $interval = date_diff($lasbeat_date, $now);
                $differenceFormat = '%y Year %m Month %d Day, %h Hours %i Minutes %s Seconds';

                $differenceFormat = '%y ' . $this->getUtils()->translate('Year') . ' ' . 
                                    '%m ' . $this->getUtils()->translate('Month') . ' ' .
                                    '%d ' . $this->getUtils()->translate('Day') . ', ' .
                                    '%h ' . $this->getUtils()->translate('Hours') . ' ' .
                                    '%i ' . $this->getUtils()->translate('Minutes'). ' ' .
                                    '%s ' . $this->getUtils()->translate('Seconds');
    
                $beatMessage = $this->getUtils()->translate('Last Beat on %s (%s ago)', [$lastBeat['run_time'], $interval->format($differenceFormat)]);
                if (abs($lasbeat_date->getTimestamp() - $now->getTimestamp()) < self::ATTENTION_SPAN) {
                    $this->addSuccessFlashMessage($beatMessage, true);
                } else {
                    $this->addWarningFlashMessage($beatMessage, true);
                }
            }
        }
        
        return parent::beforeRender();
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
        $task = $this->getObject();

        $form->addField('action', [
            'type' => 'value',
            'value' => $type,
        ]);

        switch ($type) {
            case 'edit':
            case 'new':

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

                $this->addSuccessFlashMessage($this->getUtils()->translate("Task Saved."));
                $task->persist();
                break;
            case 'run':
                try {
                    $this->containerCall(json_decode($task->getCronTaskCallable()));
                    $cron_executed[] = $task->getTitle();

                    $this->addSuccessFlashMessage($this->getUtils()->translate("Task executed: %s", [$task->getTitle()]));
                } catch (Exception $e) {
                    $this->addErrorFlashMessage($e->getMessage());
                    $this->getLog()->critical($e->getMessage() . "\n" . $e->getTraceAsString());
                }
                break;
            case 'delete':
                $task->delete();

                $this->setAdminActionLogData('Deleted cron task ' . $task->getId());

                $this->addInfoFlashMessage($this->getUtils()->translate("Task Deleted."));

                break;
        }

        return $this->refreshPage();
    }

    /**
     * gets last heart beat
     *
     * @return array|null
     * @throws Exception
     */
    protected function getLastHeartBeat(): ?array
    {
        $lastBeat = $this->containerCall([CronLog::class, 'select'], ['options' => ['where' => ["1 AND FIND_IN_SET('heartbeat_pulse', tasks) > 0"], 'orderBy' => ['run_time DESC'], 'limitCount' => 1]])->fetch();
        if (!is_array($lastBeat)) {
            return null;
        }

        return $lastBeat;
    }

    /**
     * renders last heart beat
     *
     * @return string
     */
    public function renderLastBeat(?array $lastBeat) : string
    {
        $out = '<div class="alert alert-danger" role="alert">No heart beat run yet</div>';
        if ($lastBeat != null) {
            $lasbeat_date = new DateTime($lastBeat['run_time']);
            $now = new DateTime();

            $interval = date_diff($lasbeat_date, $now);
            $differenceFormat = '%y Year %m Month %d Day, %h Hours %i Minutes %s Seconds';
            $out = '<div class="alert alert-' . (abs($lasbeat_date->getTimestamp() - $now->getTimestamp()) < self::ATTENTION_SPAN ? 'success' : 'warning') . '" role="alert">Last Beat on ' . $lastBeat['run_time'] . ' (' . $interval->format($differenceFormat) . ' ago)</div>';
        }
        return $out;
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
            'Title' => ['order' => 'title', 'search' => 'title'],
            'Callable' => ['order' => 'cron_task_callable', 'search' => 'cron_task_callable'],
            'Schedule' => ['order' => 'schedule', 'search' => 'schedule'],
            'Active' => 'active',
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
     * @throws DependencyException
     * @throws NotFoundException
     */
    protected function getTableElements(array $data, array $options = []): array
    {
        return array_map(
            function ($task) {
                return [
                    'ID' => $task->id,
                    'Title' => $task->title,
                    'Callable' => $task->cron_task_callable,
                    'Schedule' => '<a href="' . $task->getInfoUrl() . '" target="_blank">' . $task->schedule . '</a>',
                    'Active' => $this->getUtils()->translate($task->active ? 'Yes' : 'No', locale: $this->getCurrentLocale()),
                    'actions' => $this->getModelRowButtons($task) + [
                        'run-btn' => $this->getRunButton($task->id),
                    ],
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

    protected function hasLayoutSelector(): bool
    {
        return false;
    }
}
