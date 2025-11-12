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

namespace App\Site\Tools\FAPI\Fields;

use App\App;
use Degami\PHPFormsApi\Abstracts\Base\Field;
use Degami\PHPFormsApi\Form;
use App\Site\Models\MediaElement;
use App\Base\Abstracts\Models\BaseCollection;
use Degami\Basics\Html\TagElement;

class Gallery extends Field
{
    protected $multiple = false;

    public function isMultiple(?bool $multiple = null) : bool
    {
        if (is_null($multiple)) {
            return $this->multiple;
        }

        return $this->multiple = $multiple;
    }

    /**
     * this function tells to the form if this element is a value that needs to be
     * included into parent values() function call result
     *
     * @return boolean include_me
     */
    public function isAValue() : bool // tells if component value is passed on the parent values() function call
    {
        return true;
    }

    /**
     * The function that actually renders the html field
     *
     * @param Form $form form object
     *
     * @return string|BaseElement the field html
     */
    public function renderField(Form $form) // renders html
    {
        $id = $this->getHtmlId();

        /** @var TagElement $field */
        $field = App::getInstance()->containerMake(TagElement::class, ['options' => [
            'tag' => 'div',
            'id' => $id,
            'attributes' => [
                'class' => 'row row-cols-6 p-4',
            ],
        ]]);
        
        $parent_id = App::getInstance()->getEnvironment()->getRequest()->query->get('parent_id');
        if (empty($parent_id) || !is_numeric($parent_id)) {
            $parent_id = null;
        }
        $medias = $this->getCollection($parent_id);

        if (!is_null($parent_id)) {

            $parent = App::getInstance()->containerCall([MediaElement::class, 'load'], ['id' => $parent_id]);
            if ($parent->getParentId()) {
                $grandparent = App::getInstance()->containerCall([MediaElement::class, 'load'], ['id' => $parent->getParentId()]);
            } else {
                $grandparent = null;
            }

            $field->addChild(
                App::getInstance()->containerMake(TagElement::class, ['options' => [
                    'tag' => 'div',
                    'attributes' => ['class' => 'col text-center h-100 align-bottom d-flex flex-column justify-content-center', 'style' => 'min-height: 100px;'],
                    'text' => $this->renderMediaElement(
                        $grandparent, App::getInstance()->getHtmlRenderer()->getIcon('corner-left-up') . ' ' . __('Up')
                    ),
                ]])
            );
        }

        foreach ($medias as $media) {
            /** @var MediaElement $media */
            $field->addChild(
                App::getInstance()->containerMake(TagElement::class, ['options' => [
                    'tag' => 'div',
                    'attributes' => ['class' => 'col text-center h-100 align-bottom d-flex flex-column justify-content-center', 'style' => 'min-height: 100px;'],
                    'text' => $this->renderMediaElement($media),
                ]])
            );
        }

        $field->addChild(
            App::getInstance()->containerMake(TagElement::class, ['options' => [
                'tag' => 'input',
                'type' => 'hidden',
                'name' => $this->name,
                'value' => $this->getValues(),
            ]])
        );

        return $field;
    }

    /**
     * {@inheritdoc}
     *
     * @param Form $form form object
     */
    public function preRender(Form $form)
    {
        if ($this->pre_rendered == true) {
            return;
        }
        $id = $this->getHtmlId();

        if ($this->multiple) {
            $this->addJs("
                $('#{$id}','#{$form->getId()}').on('change', '.media-selector', function(){
                    var selected = [];
                    \$('.media-selector:checked','#{$form->getId()}').each(function(){
                        selected.push( \$(this).val() );
                    });
                    \$('#{$id} input[type=\"hidden\"]','#{$form->getId()}').val( selected.join(',') );
                });
            ");
        } else {
            $this->addJs("
                $('#{$id}','#{$form->getId()}').on('change', '.media-selector', function(){
                    \$('#{$id} input[type=\"hidden\"]','#{$form->getId()}').val( \$(this).val() );
                    \$('.media-selector','#{$form->getId()}').not(this).prop('checked', false);
                });
            ");
        }

        parent::preRender($form);
    }

    protected function renderMediaElement(?MediaElement $media, ?string $labelText = null, int $maxLength = 12): string
    {
        $text = ($labelText ?? '<abbr title="' . $media?->getFilename() . '">' . substr("" . $media?->getFilename(), 0, $maxLength) . (strlen("".$media?->getFilename()) > $maxLength ? '...' : '') . '</abbr>');
        
        $label = '<div class="pretty p-icon p-smooth p-primary p-curve">
            <input class="media-selector" type="checkbox" id="media-selector-'.$media?->getId().'" value="' . $media?->getId() . '"  />
            <div class="state">
                <i class="icon fa fa-check"></i>
                <label for="media-selector-'.$media?->getId().'">' . $text . '</label>
            </div>
        </div>';

        if (is_null($media) || $media->isDirectory()) {
            $uri = App::getInstance()->getEnvironment()->getRequest()->getUri();
            $parsed = parse_url($uri);
            parse_str($parsed['query'] ?? '', $queryParams);
            unset($queryParams['parent_id']);
            $uri = $parsed['scheme'] . '://' . $parsed['host'] . ($parsed['port'] ?? '') . $parsed['path'] . '?' . http_build_query($queryParams);

            $label = '<label class="text-nowrap"><a class="inToolSidePanel" href="'.$uri.'&parent_id='.$media?->getId().'">' . $text . '</a></label>';
        }

        return match (true) {
            is_null($media), $media?->isDirectory() => '<div class="media-element media-directory"><h2 style="height: 40px; margin: 0; zoom:1.5;">'.App::getInstance()->getHtmlRenderer()->getFAIcon('folder', 'solid').'</h2></div>',
            $media?->isImage() => '<div class="media-element media-image">' . $media->getThumb('50x50') . '</div>',
            default => '<div class="media-element media-file">' . $media->getMimeIcon() . '</div>',
        } . '<div>'.$label.'</div>';
    }

    protected function getCollection(?int $parent_id): BaseCollection
    {
        $collection = MediaElement::getCollection();

        if (is_numeric($parent_id)) {
            $collection->addCondition(['parent_id' => $parent_id]);
        } else {
            $collection->addCondition(['parent_id' => null]);
        }

        $collection->addSelect('*')->addSelect('IF(mimetype =\'inode/directory\', 1, 0) AS is_dir');
        $collection->addOrder(['is_dir' => 'DESC'], 'start');

        return $collection;
    }

}
