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

namespace App\Base\Abstracts\Models;

use Degami\Basics\DataElement;

/**
 * Webhook class
 * @package App\Base\Abstracts\Models
 * 
 * @method string getEventType()
 * @method string getTimestamp()
 * @method mixed getSource()
 */
class Webhook extends DataElement
{
    public function __construct(array $data = [])
    {
        $this->setData($data);
    }

    public function getWebhookData() : mixed
    {
        return $this->getData()['data'] ?? null;
    }
}