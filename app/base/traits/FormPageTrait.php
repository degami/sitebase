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

use Degami\PHPFormsApi as FAPI;

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
