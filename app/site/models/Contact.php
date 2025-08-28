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

namespace App\Site\Models;

use App\App;
use App\Base\Abstracts\Models\BaseModel;
use App\Base\Abstracts\Models\FrontendModel;
use App\Base\GraphQl\GraphQLExport;
use DateTime;
use Degami\Basics\Exceptions\BasicException;
use Degami\PHPFormsApi as FAPI;
use Exception;

/**
 * Contact Form Model
 *
 * @method int getId()
 * @method int getWebsiteId()
 * @method string getTitle()
 * @method string getContent()
 * @method string getTemplateName()
 * @method string getMetaTitle()
 * @method string getMetaDescription()
 * @method string getMetaKeywords()
 * @method string getHtmlTitle()
 * @method string getLocale()
 * @method string getUrl()
 * @method string getSubmitTo()
 * @method int getUserId()
 * @method DateTime getCreatedAt()
 * @method DateTime getUpdatedAt()
 * @method self setId(int $id)
 * @method self setWebsiteId(int $website_id)
 * @method self setTitle(string $title)
 * @method self setContent(string $content)
 * @method self setTemplateName(string $template_name)
 * @method self setMetaTitle(string $meta_title)
 * @method self setMetaDescription(string $meta_description)
 * @method self setMetaKeywords(string $meta_keywords)
 * @method self setHtmlTitle(string $html_title)
 * @method self setLocale(string $locale)
 * @method self setUrl(string $url)
 * @method self setSubmitTo(string $submit_to)
 * @method self setUserId(int $user_id)
 * @method self setCreatedAt(DateTime $created_at)
 * @method self setUpdatedAt(DateTime $updated_at)
 */
#[GraphQLExport]
class Contact extends FrontendModel
{
    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getRewritePrefix(): string
    {
        return 'contact';
    }

    /**
     * gets contact form definition
     *
     * @return \App\Site\Models\ContactDefinition[]
     * @throws Exception
     */
    #[GraphQLExport]
    public function getContactDefinition(): array
    {
        $this->checkLoaded();

        return ContactDefinition::getCollection()->where(['contact_id' => $this->getId()])->getItems();
    }

    /**
     * gets contact form submissions
     *
     * @return int[]
     * @throws Exception
     */
    public function getContactSubmissions(): array
    {
        $this->checkLoaded();

        return array_map(
            fn ($el) => $el->getId(),
            ContactSubmission::getCollection()->where(['contact_id' => $this->getId()])->getItems()
        );
    }

    /**
     * gets contact form specific submission data
     *
     * @param int $submission_id
     * @return array|null
     * @throws Exception
     */
    public function getContactSubmission(int $submission_id): ?array
    {
        $this->checkLoaded();

        return ContactSubmission::getCollection()->where(['contact_id' => $this->getId(), 'id' => $submission_id])->getFirst()?->getFullData();
    }

    /**
     * fills form with elements
     *
     * @param FAPI\Abstracts\Base\Element $container
     * @param array                       &$form_state
     * @return FAPI\Abstracts\Base\Element
     * @throws Exception
     * @throws BasicException
     */
    public function getFormDefinition(FAPI\Abstracts\Base\Element $elemetContainer, &$form_state): FAPI\Abstracts\Base\Element
    {
        foreach ($this->getContactDefinition() as $field) {
            $field_data = (array)json_decode($field->getFieldData(), true);
            $field_data['title'] = App::getInstance()->getUtils()->translate($field_data['title'], locale: $this->getLocale());
            $elemetContainer->addField(
                $this->slugify($field->getFieldLabel()),
                ['type' => $field->getFieldType()] +
                ($field->getFieldRequired() == true ? ['validate' => ['required']] : []) +
                $field_data
            );
        }

        return $elemetContainer;
    }

    /**
     * {@inheritdoc}
     *
     * @return FrontendModel
     * @throws Exception
     */
    public function preRemove(): BaseModel
    {
        foreach ($this->contact_definitionList() as $contact_definition) {
            $contact_definition->delete();
        }

        return parent::preRemove();
    }
}
