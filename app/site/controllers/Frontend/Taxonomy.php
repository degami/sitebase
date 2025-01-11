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

namespace App\Site\Controllers\Frontend;

use Degami\Basics\Exceptions\BasicException;
use App\Base\Abstracts\Controllers\FrontendPageWithObject;
use App\Site\Models\Taxonomy as TaxonomyModel;
use DI\DependencyException;
use DI\NotFoundException;

/**
 * Taxonomy Term Detail Page
 */
class Taxonomy extends FrontendPageWithObject
{
    /**
     * return route path
     *
     * @return string
     */
    public static function getRoutePath(): string
    {
        return 'taxonomy/{id:\d+}';
    }

    /**
     * returns valid route HTTP verbs
     *
     * @return array
     */
    public static function getRouteVerbs(): array
    {
        return ['GET'];
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getTemplateName(): string
    {
        return 'taxonomy';
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getBaseTemplateData(): array
    {
        $out = parent::getBaseTemplateData();
        $out ['body_class'] = str_replace('.', '-', $this->getRouteName()) . ' taxonomy-' . $this->getObject()->id;
        return $out;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public static function getObjectClass(): string
    {
        return TaxonomyModel::class;
    }
}
