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

namespace App\Base\Controllers\Admin\Json;

use App\Base\Abstracts\Controllers\AdminJsonPage;
use App\Base\Models\Block;
use App\Site\Models\Page;
use DI\DependencyException;
use DI\NotFoundException;
use App\App;
use App\Base\Abstracts\Models\BaseCollection;
use App\Base\Models\ProgressManagerProcess;

/**
 * Progress Manager Processes Status Admin Callback
 */
class ProgressManagerProcessesStatus extends AdminJsonPage
{
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
     * @return array
     * @throws DependencyException
     * @throws NotFoundException
     */
    protected function getJsonData(): array
    {
        $data = [];
        $runningProcesses = $this->getActiveProcesses();
        $additionalIds = $this->getAdditionalIds();
        if (!empty($additionalIds)) {
            $runningProcesses = $runningProcesses->orWhere(['id' => $additionalIds]);
        }
        foreach ($runningProcesses as $runningProcess) {
            /** @var ProgressManagerProcess $runningProcess */
            $data[] = [
                'id' => $runningProcess->getId(),
                'callable' => $runningProcess->getCallable(),
                'progress' => $runningProcess->getProgress(),
                'total' => $runningProcess->getTotal(),
                'percentual' => $runningProcess->getProgressPercentual(),
                'message' => $runningProcess->getMessage(),
                'started_at' => $runningProcess->getStartedAt(),
                'ended_at' => $runningProcess->getEndedAt(),
            ];
        }
        return ['data' => $data];
    }

    protected function getAdditionalIds() : array
    {
        if (isJson($this->getRequest()->getContent())) {
            $data = json_decode($this->getRequest()->getContent(), true);
            return $data['ids'] ?? [];
        }

        return [];
    }

    protected function getActiveProcesses() : BaseCollection
    {
        // orWhere parameter needs to be an array of arrays, so every element will be joined with AND
        return ProgressManagerProcess::getCollection()->orWhere('started_at IS NOT NULL AND ended_at IS NULL')->addOrder(['started_at' => 'desc'])->addOrder(['started_at' => 'asc']);
    }

    protected function getProcesses() : BaseCollection
    {
        return ProgressManagerProcess::getCollection()->addOrder(['started_at' => 'desc'])->addOrder(['started_at' => 'asc']);
    }
}
