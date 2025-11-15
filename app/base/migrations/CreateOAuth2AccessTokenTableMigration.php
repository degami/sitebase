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
 * "oauth2_access_token" table migration
 */
class CreateOAuth2AccessTokenTableMigration extends DBMigration
{
    protected string $tableName = 'oauth2_access_token';

    public function getName(): string
    {
        return '05_'.parent::getName();
    }

    public function addDBTableDefinition(Table $table): Table
    {
        $table->addColumn('id', 'INT', null, ['UNSIGNED'])
            ->addColumn('api_base_url', 'VARCHAR', [255], [], true, null)
             ->addColumn('access_token', 'VARCHAR', [255], [], true, null)
             ->addColumn('refresh_token', 'VARCHAR', [255], [], true, null)
             ->addColumn('type', 'VARCHAR', [255], [], true, null)
             ->addColumn('expites_at', 'DATETIME', null, [], true, null)
             ->addColumn('scope', 'VARCHAR', [255], [], true, null)
            ->addColumn('created_at', 'TIMESTAMP', null, [], false, 'CURRENT_TIMESTAMP()')
            ->addColumn('updated_at', 'TIMESTAMP', null, [], false, 'CURRENT_TIMESTAMP()')
            ->addIndex(null, 'id', Index::TYPE_PRIMARY)
            ->setAutoIncrementColumn('id');

        return $table;
    }
}
