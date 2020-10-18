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

namespace App\Site\Models;

use DateTime;
use \Exception;
use \App\Base\Abstracts\Models\BaseModel;
use \App\Base\Traits\WithWebsiteTrait;
use \Psr\Container\ContainerInterface;

/**
 * Queue Message Model
 *
 * @method int getId()
 * @method int getWebsiteId()
 * @method string getQueueName()
 * @method string getMessage()
 * @method string getStatus()
 * @method string getResult()
 * @method DateTime getCreatedAt()
 * @method DateTime getUpdatedAt()
 */
class QueueMessage extends BaseModel
{
    use WithWebsiteTrait;

    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSED = 'processed';
    const ENDED_OK = 1;
    const ENDED_KO = 0;

    /**
     * gets decoded message data
     *
     * @return array
     */
    public function getMessageData()
    {
        return json_decode($this->getMessage(), true);
    }

    /**
     * gets message worker class
     *
     * @return string
     */
    public function getWorkerClass()
    {
        return "App\\Site\\Queues\\" . $this->snakeCaseToPascalCase($this->getQueueName()) . "\\Worker";
    }

    /**
     * gets next message on queue
     *
     * @param ContainerInterface $container
     * @param string|null $queue_name
     * @return self|null
     */
    public static function nextMessage(ContainerInterface $container, $queue_name = null)
    {
        try {
            $messageDBRow = static::getModelBasicWhere(
                $container,
                ['status' => self::STATUS_PENDING] + ($queue_name != null ? ['queue_name' => $queue_name] : []),
                ['created_at' => 'ASC']
            )
                ->limit(1)
                ->fetch();
            if ($messageDBRow && $messageDBRow->id) {
                $message = $container->make(static::class, ['dbrow' => $messageDBRow]);
                $message->setStatus(self::STATUS_PROCESSED)->persist();
                return $message;
            }
        } catch (Exception $e) {
            // ignore
        }
        return null;
    }
}
