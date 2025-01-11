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

 namespace App\Site\Webhooks;

use App\Base\Abstracts\Controllers\BaseWebhookPage;
use App\Base\Abstracts\Models\Webhook;

 class Test extends BaseWebhookPage
 {
    /**
     * gets Weebook's event_type(s)
     * 
     * @return array
     */
    protected function getWebhookEventTypes(): array
    {
        return ['test'];
    }

    /**
     * process Webhook data
     *
     * @param Webhook $webhook
     * @return array
     */
    protected function processWebhook(Webhook $webhook): array
    {
        return [
            "result" => "Webhook test was succesful", 
            "webhook_data" => $webhook->getWebhookData(),
            "event_type" => $webhook->getEventType(),
            "timestamp" => $webhook->getTimestamp(),
            "source" => $webhook->getSource(),
        ];
    }
}