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

namespace App\Site\Queues\LinkFormMail;

use Degami\Basics\Exceptions\BasicException;
use DI\DependencyException;
use DI\NotFoundException;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use App\Base\Abstracts\Queues\BaseQueueWorker;

/**
 * Links Form Queue Worker
 */
class Worker extends BaseQueueWorker
{
    /**
     * {@inheritdoc}
     *
     * @param array $message_data
     * @return bool
     * @throws BasicException
     * @throws PhpfastcacheSimpleCacheException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function processMessage(array $message_data): bool
    {
        return $this->getMailer()->sendMail(
            $message_data['from'],
            $message_data['to'],
            $message_data['subject'],
            $message_data['body']
        );
    }
}
