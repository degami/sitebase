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

namespace App\Base\Cron\Tasks;

use App\App;
use App\Base\Abstracts\Models\FrontendModel;
use Degami\Basics\Exceptions\BasicException;
use DI\DependencyException;
use DI\NotFoundException;
use HaydenPierce\ClassFinder\ClassFinder;
use App\Base\Abstracts\ContainerAwareObject;

/**
 * Cron Search Related
 */
class SearchManager extends ContainerAwareObject
{
    public const DEFAULT_SCHEDULE = '20 3 * * *';

    /**
     * update search DB method
     *
     * @return string|null
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     * @throws \Exception
     */
    public function updateSearchDB(): ?string
    {
        if (!$this->getSearch()->isEnabled()) {
            return null;
        }

        $classes = array_filter(ClassFinder::getClassesInNamespace(App::MODELS_NAMESPACE, ClassFinder::RECURSIVE_MODE), fn($modelClass) => is_subclass_of($modelClass, FrontendModel::class));
        foreach ($classes as $className) {
            if (!$this->containerCall([$className, 'isIndexable'])) {
                continue;
            }
            foreach ($this->containerCall([$className, 'getCollection']) as $object) {
                /** @var FrontendModel $object */
                $this->getSearch()->indexFrontendModel($object);
            }
        }
        return null;
    }
}
