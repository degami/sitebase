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

namespace App\Site\Models;

use DateTime;
use Exception;
use App\Base\Abstracts\Models\BaseModel;
use App\Base\Traits\WithWebsiteTrait;
use Psr\Container\ContainerInterface;
use App\App;
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
class QueueMessage extends BaseModel
{
    use WithWebsiteTrait;

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSED = 'processed';
    public const ENDED_OK = 1;
    public const ENDED_KO = 0;

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
        return "App\\Site\\Queues\\" . $this->snakeCaseToPascalCase($this->getQueueName()) . "\\Worker";
    }

    /**
     * gets next message on queue
     *
     * @param string|null $queue_name
     * @return self|null
     */
    public static function nextMessage($queue_name = null): ?QueueMessage
    {
        try {

            $message = static::getCollection()->where(
                ['status' => self::STATUS_PENDING] + ($queue_name != null ? ['queue_name' => $queue_name] : []),
                ['created_at' => 'ASC']
            )->getFirst();
            if ($message) {
                $message->setStatus(self::STATUS_PROCESSED)->persist();
                return $message;
            }
        } catch (Exception $e) {
            // ignore
        }
        return null;
    }

    public static function getQueueNames(?ContainerInterface $container = null) : array
    {
        try {
            if (is_null($container)) {
                $container = App::getInstance()->getContainer();
            }

            $query = "SELECT DISTINCT(queue_name) FROM ".static::defaultTableName();
            $stmt = $container->get('db')->prepare($query);
            $stmt->execute();
            return array_values($stmt->fetchAll(PDO::FETCH_COLUMN, 0));
        } catch (Exception $e) {
            // ignore
        }
        return [];
    }
}
