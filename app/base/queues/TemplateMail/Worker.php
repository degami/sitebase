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

namespace App\Base\Queues\TemplateMail;

use App\App;
use Degami\Basics\Exceptions\BasicException;
use DI\DependencyException;
use DI\NotFoundException;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use App\Base\Abstracts\Queues\BaseQueueWorker;
use Degami\Basics\Html\TagElement;
use Throwable;

/**
 * Template Mail Queue Worker
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
        $template_name = $message_data['template_name'] ?? 'default_mail';
        $subject = $message_data['subject'] ?? $this->getUtils()->translate("New message from %s", [$this->getSiteData()->getCurrentWebsite()?->getSiteName()]);

        $template_vars = (array)($message_data['template_vars'] ?? []);

        $from = $message_data['from'];
        $to = $message_data['to'];

        foreach ($template_vars as &$var) {
            if (isJson($var)) {
                $var = json_decode($var, true);
            }

            if (is_array($var) && count($var) == 2) {
                // could be an handler. check.
                if (is_callable(reset($var))) {
                    // a callable with arguments
                    $var = call_user_func_array(reset($var), (array) end($var));
                } else if (is_callable(($var))) {
                    // a single callable, without arguments
                    $var = call_user_func($var);
                }
            } else if (is_string($var) && is_callable($var)) {
                // a single callable, without arguments
                $var = call_user_func($var);
            }
        }

        $body = $this->getUtils()->getTemplateMailBody($subject, $template_vars, $template_name);

        return $this->getMailer()->sendMail(
            $from,
            $to,
            $subject,
            $body
        );
    }
}
