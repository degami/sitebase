<?php


namespace App\Base\Traits;


trait TemplatePageTrait
{
    /**
     * @var array template data
     */
    protected array $template_data = [];

    /**
     * @var string|null locale
     */
    protected ?string $locale = null;
}