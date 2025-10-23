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
use Degami\PHPFormsApi as FAPI;
use App\Base\Exceptions\InvalidValueException;
use App\Base\Abstracts\Controllers\AdminFormPage;
use App\Base\Models\ProgressManagerProcess;

/**
 * "ProgressManager" Admin Page
 */
class ProgressManager extends AdminFormPage
{
    /**
     * @var string page title
     */
    protected ?string $page_title = 'Progress Manager';

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getTemplateName(): string
    {
        return 'form_admin_page';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public static function getAccessPermission(): string
    {
        return 'administer_site';
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
            'icon' => 'coffee',
            'text' => 'Progress Manager',
            'section' => 'system',
            'order' => 4,
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @param FAPI\Form $form
     * @param array     &$form_state
     * @return FAPI\Form
     * @throws \Exception
     */
    public function getFormDefinition(FAPI\Form $form, array &$form_state): FAPI\Form
    {
        $table = $form->addField('table', [
            'type' => 'table_container',
            'attributes' => [
                'class' => 'table table-striped',
                'style' => 'width: 100%; display: table;',
            ],
            'thead_attributes' => [
                'class' => 'thead-dark',
            ],
        ]);

        $table->setTableHeader(
            ['&nbsp;','id',['value' => 'percentual' , 'attributes' => ['width' => 200]],['value' => 'message', 'attributes' => ['width' => '450']],'callable','progress','total','started_at','ended_at']
        );

        $progress_num = 0;
        foreach ($this->getActiveProcesses() as $process) {
            /** @var ProgressManagerProcess $process */
            $progress_num++;
            $table
                ->addRow()
                ->addField(
                    $process->getId() . '_chk',
                    [
                        'type' => 'checkbox',
                        'title' => '',
                        'default_value' => $process->getId(),
                        'name' => 'processes['.$process->getId().']',
                    ],
                    $progress_num
                )
                ->addField($process->getId() . '_id', [
                    'type' => 'markup',
                    'value' => $process->getId(),
                ])
                ->addField($process->getId() . '_percentual', [
                    'type' => 'progressbar',
                    'value' => $process->getProgressPercentual(),
                ])
                ->addField($process->getId() . '_message', [
                    'type' => 'markup',
                    'value' => $process->getMessage(),
                    'container_class' => 'form-item ' . $process->getId() . '_message',
                ])
                ->addField($process->getId() . '_callable', [
                    'type' => 'markup',
                    'value' => $process->getCallable(),
                ])
                ->addField($process->getId() . '_progress', [
                    'type' => 'markup',
                    'value' => $process->getProgress(),
                    'container_class' => 'form-item ' . $process->getId() . '_progress',
                ])
                ->addField($process->getId() . '_total', [
                    'type' => 'markup',
                    'value' => $process->getTotal(),
                ])
                ->addField($process->getId() . '_started_at', [
                    'type' => 'markup',
                    'value' => $process->getStartedAt(),
                ])
                ->addField($process->getId() . '_ended_at', [
                    'type' => 'markup',
                    'value' => $process->getEndedAt(),
                    'container_class' => 'form-item ' . $process->getId() . '_ended_at',
                ]);
        }

        $refresURL = $this->getUrl('crud.app.base.controllers.admin.json.progressmanagerprocessesstatus'); 
        $form->addJs('
            function refreshData() {
                const visibleIds =  Array.from(document.getElementById("'.$form->getFormId().'").querySelectorAll(\'input[type="checkbox"]\')).map((el) => el.value);
                $.ajax({
                    type: "POST",
                    url: "'.$refresURL.'",
                    contentType: "application/json",
                    data: JSON.stringify({ids : visibleIds}),
                    success: function(response) {
                        $(response.data).each(function(index, process) {

                            let hasActive = false;
                            $(response.data).each(function(index, process) {
                                if (!process.ended_at || process.ended_at === "") {
                                    hasActive = true;
                                }
                            });

                            $(\'#abort\').prop(\'disabled\', !hasActive);

                            if ($("#processes_"+process.id+"").length == 0) {
                                let newRow = `
                                    <tr id="table-row-${process.id}" class="tr">
                                        <td>
                                            <div class="form-item checkbox-container">
                                                <label class="label-checkbox">
                                                    <input type="checkbox" name="processes[${process.id}]" id="processes_${process.id}" value="${process.id}" class="form-control checkbox">
                                                </label>
                                            </div>
                                        </td>
                                        <td><div class="form-item markup-container">${process.id}</div></td>
                                        <td>
                                            <div class="form-item progressbar-container">
                                                <div id="${process.id}_percentual" class="form-control progressbar"></div>
                                            </div>
                                        </td>
                                        <td><div class="form-item ${process.id}_message markup-container">${process.message ?? ""}</div></td>
                                        <td><div class="form-item markup-container">${process.callable ?? ""}</div></td>
                                        <td><div class="form-item ${process.id}_progress markup-container">${process.progress ?? 0}</div></td>
                                        <td><div class="form-item markup-container">${process.total ?? 0}</div></td>
                                        <td><div class="form-item markup-container">${process.started_at ?? ""}</div></td>
                                        <td><div class="form-item ${process.id}_ended_at markup-container">${process.ended_at ?? ""}</div></td>
                                    </tr>
                                `;

                                let tbody = $("table tbody", "#'.$form->getFormId().'");

                                // cerca l’ultima riga attiva (senza ended_at valorizzato)
                                let lastActive = tbody.find("tr").filter(function() {
                                    return $(this).find("td:last div").text().trim() === "";
                                }).last();

                                if (lastActive.length > 0) {
                                    lastActive.after(newRow);
                                } else {
                                    tbody.prepend(newRow);
                                }

                                $("#"+process.id+"_percentual").progressbar({
                                    value: parseInt(process.percentual) || 0
                                }); 
                            }

                            $("#"+process.id+"_percentual").progressbar("value", parseInt(process.percentual));
                            $("."+process.id+"_message").text(process.message);
                            $("."+process.id+"_progress").text(process.progress);
                            $("."+process.id+"_ended_at").text(process.ended_at);

                            if ($.trim(process.ended_at) != "") {
                                $("#processes_"+process.id).prop("disabled", true);
                            }

                        });

                        setTimeout(() => refreshData(), 1000);
                    },
                    dataType: "json"
                });
            }

            refreshData();
        ');

        //$this->addSubmitButton($form);

        $this->addButton(
            $form,
            'abort',
            'Abort selected process(es)',
            'ban',
            [
                'attributes' => ['class' => 'btn btn-danger'] + (count($this->getActiveProcesses()) == 0 ? ['disabled' => 'disabled'] : []),
                'weight' => 130,
            ]
        );

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
     * @throws InvalidValueException
     */
    public function formSubmitted(FAPI\Form $form, &$form_state): mixed
    {
        $values = array_filter($form->values()['table']->toArray());

        $numAborted = 0;
        foreach ($values as $key => $value) {
            if (str_ends_with($key, "_chk")) {
                /** @var ProgressManagerProcess $process */
                $process = ProgressManagerProcess::load($value);
                $process->abort();

                $numAborted++;
            }
        }

        $this->addInfoFlashMessage($this->getUtils()->translate("%d process(es) aborted.", [$numAborted]));

        return $this->refreshPage();
    }

    protected function getActiveProcesses() : array
    {
        return ProgressManagerProcess::getCollection()->where(['started_at:not' => null, 'ended_at' => null])->addOrder(['started_at' => 'desc'])->addOrder(['started_at' => 'asc'])->getItems();
    }

    protected function getProcesses() : array
    {
        return ProgressManagerProcess::getCollection()->addOrder(['started_at' => 'desc'])->addOrder(['started_at' => 'asc'])->getItems();
    }

    public static function exposeDataToDashboard() : mixed
    {
        return ProgressManagerProcess::getCollection()->where(['started_at:not' => null, 'ended_at' => null])->count();        
    }
}
