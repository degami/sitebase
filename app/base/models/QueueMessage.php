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

namespace App\Base\Models;

use DateTime;
use Exception;
use App\Base\Abstracts\Models\BaseModel;
use App\Base\Traits\WithWebsiteTrait;
use App\App;
use App\Base\Interfaces\Queue\QueueMessageInterface;
use PDO;

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
 * @method self setId(int $id)
 * @method self setWebsiteId(int $website_id)
 * @method self setQueueName(string $queue_name)
 * @method self setMessage(string $message)
 * @method self setStatus(string $status)
 * @method self setResult(string $result)
 * @method self setCreatedAt(DateTime $created_at)
 * @method self setUpdatedAt(DateTime $updated_at)
 */
class QueueMessage extends BaseModel implements QueueMessageInterface
{
    use WithWebsiteTrait;

    /**
     * {@inheritdoc}
     */
    public static function canBeDuplicated() : bool
    {
        return false;
    }

    /**
     * gets decoded message data
     *
     * @return array
     */
    public function getMessageData(): array
    {
        return json_decode($this->getMessage(), true);
    }

    /**
     * gets message worker class
     *
     * @return string
     */
    public function getWorkerClass(): string
    {
        if (class_exists("App\\Site\\Queues\\" . $this->snakeCaseToPascalCase($this->getQueueName()) . "\\Worker")) {
            return "App\\Site\\Queues\\" . $this->snakeCaseToPascalCase($this->getQueueName()) . "\\Worker";
        }

        return "App\\Base\\Queues\\" . $this->snakeCaseToPascalCase($this->getQueueName()) . "\\Worker";
    }

    /**
     * gets next message on queue
     *
     * @param string|null $queue_name
     * @return self|null
     */
    public static function nextMessage(?string $queue_name = null): ?QueueMessage
    {
        try {

            $message = static::getCollection()->where(
                ['status' => self::STATUS_PENDING] + ($queue_name != null ? ['queue_name' => $queue_name] : []),
                ['created_at' => 'ASC']
            )->getFirst();
            if ($message) {
                $message->setStatus(self::STATUS_PROCESSED)->setExecutedAt(date('Y-m-d H:i:s'))->persist();
                return $message;
            }
        } catch (Exception $e) {
            // ignore
        }
        return null;
    }

    /**
     * gets all queue names
     *
     * @return string[]
     */
    public static function getQueueNames() : array
    {
        try {
            $query = "SELECT DISTINCT(queue_name) FROM ".static::defaultTableName();
            $stmt = App::getInstance()->getDb()->prepare($query);
            $stmt->execute();
            return array_values($stmt->fetchAll(PDO::FETCH_COLUMN, 0));
        } catch (Exception $e) {
            // ignore
        }
        return [];
    }
}
