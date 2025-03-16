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

namespace App\Base\Migrations;

use App\Base\Abstracts\Migrations\DBMigration;
use Degami\SqlSchema\Exceptions\DuplicateException;
use Degami\SqlSchema\Exceptions\EmptyException;
use Degami\SqlSchema\Table;

/**
 * add "read at" and "sender" to user notification table migration
 */
class AddReadAtSenderToUserNotifications extends DBMigration
{
    /**
     * @var string table name
     */
    protected string $tableName = 'user_notification';

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getName(): string
    {
        return '05_' . parent::getName();
    }

    /**
     * {@inheritdoc}
     *
     * @param Table $table
     * @return Table
     * @throws EmptyException
     * @throws DuplicateException
     */
    public function addDBTableDefinition(Table $table): Table
    {
        $table
            ->addColumn('read_at', 'TIMESTAMP', null, [], true, null)
            ->addColumn('sender_id', 'INT', null, ['UNSIGNED'])
            ->addForeignKey('fk_usernotification_sender', ['sender_id'], 'user', ['id']);
        
        return $table;
    }
}
