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

namespace App\Site\Migrations;

use App\App;
use App\Base\Abstracts\Migrations\BaseMigration;
use App\Base\Exceptions\InvalidValueException;
use App\Base\Models\OrderStatus;
use App\Base\Models\TaxClass;
use App\Base\Models\Website;
use Degami\Basics\Exceptions\BasicException;
use DI\DependencyException;
use DI\NotFoundException;
use ReflectionClass;

/**
 * basic commerce migration
 */
class InitialCommerceMigration extends BaseMigration
{
    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getName(): string
    {
        return '100.1_' . parent::getName();
    }

    public static function enabled() : bool
    {
        return boolval(App::getInstance()->getEnvironment()->getVariable('ENABLE_COMMERCE'));
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     * @throws BasicException
     * @throws InvalidValueException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function up()
    {
        $ref = new ReflectionClass(OrderStatus::class);
        $costants = $ref->getConstants();

        foreach (Website::getCollection() as $website) {
            /** @var Website $website */
            foreach($costants as $costant) {
                /** @var OrderStatus $orderStatus */
                $orderStatus = App::getInstance()->containerMake(OrderStatus::class);
                $orderStatus->setStatus($costant)->setWebsiteId($website->getId());
                $orderStatus->persist();
            }

            /** @var TaxClass $taxClass */
            $taxClass = App::getInstance()->containerMake(TaxClass::class);
            $taxClass->setWebsiteId($website->getId());
            $taxClass->setClassName('Products');
            $taxClass->persist();

            /** @var TaxClass $taxClass */
            $taxClass = App::getInstance()->containerMake(TaxClass::class);
            $taxClass->setWebsiteId($website->getId());
            $taxClass->setClassName('No Tax');
            $taxClass->persist();
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    public function down()
    {
    }

}
