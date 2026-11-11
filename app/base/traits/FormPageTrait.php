<?php

/**
 * SiteBase
 * PHP Version 8.5
 *
 * @category CMS / Framework
 * @package  Degami\Sitebase
 * @author   Mirko De Grandis <degami@github.com>
 * @license  MIT https://opensource.org/licenses/mit-license.php
 * @link     https://github.com/degami/sitebase
 */

namespace App\Base\Traits;

use App\Base\Abstracts\Controllers\BasePage;
use App\Base\Exceptions\PermissionDeniedException;
use Degami\Basics\Exceptions\BasicException;
use Degami\PHPFormsApi as FAPI;
use DI\DependencyException;
use DI\NotFoundException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Form Page Trait
 */
trait FormPageTrait
{
    use TemplatePageTrait;
    use FormTrait;

    /**
     * get form object
     *
     * @return FAPI\Form|null
     */
    public function getForm(): ?FAPI\Form
    {
        return $this->getTemplate()?->data()['form'] ?? ($this->template_data['form'] ?? null);
    }

}
