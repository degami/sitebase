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
use Psr\Container\ContainerInterface;
use Degami\SqlSchema\Index;
use Degami\SqlSchema\Table;

/**
 * "giftcard_redeem_code" table migration
 */
class CreateGiftcardRedeemCodeTableMigration extends DBMigration
{
    protected string $tableName = 'giftcard_redeem_code';

    public function getName(): string
    {
        return '08.6_'.parent::getName();
    }

    public function addDBTableDefinition(Table $table): Table
    {
        $table->addColumn('id', 'INT', null, ['UNSIGNED'])
            ->addColumn('code', 'VARCHAR', [255], [], true, null)
            ->addColumn('credit', 'FLOAT', null, [], true, null)
            ->addColumn('order_item_id', 'INT', null, ['UNSIGNED'], true, null)
            ->addColumn('user_id', 'INT', null, ['UNSIGNED'], false, null)
            ->addColumn('website_id', 'INT', null, ['UNSIGNED'], false, null)
            ->addColumn('redeemed', 'BOOLEAN', null, [], false, null)
            ->addColumn('created_at', 'TIMESTAMP', null, [], false, 'CURRENT_TIMESTAMP()')
            ->addColumn('updated_at', 'TIMESTAMP', null, [], false, 'CURRENT_TIMESTAMP()')
            ->addIndex(null, 'id', Index::TYPE_PRIMARY)
            ->addForeignKey('fk_giftcardredeem_website_id', ['website_id'], 'website', ['id'])
            ->addForeignKey('fk_giftcardredeem_owner_id', ['user_id'], 'user', ['id'])
            ->addForeignKey('fk_giftcardredeem_orderitem_id', ['order_item_id'], 'order_item', ['id'])
            ->setAutoIncrementColumn('id');

        return $table;
    }
}
