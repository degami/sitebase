<?php

/**
 * SiteBase
 * PHP Version 8.0
 *
 * @category CMS / Framework
 * @package  Degami\Sitebase
 * @author   Mirko De Grandis <degami@github.com>
 * @license  MIT https://opensource.org/licenses/mit-license.php
 * @link     https://github.com/degami/sitebase
 */

namespace App\Site\Models;

use App\Base\Abstracts\Models\BaseModel;
use App\Base\Abstracts\Models\FrontendModel;
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
class Contact extends FrontendModel
{
    /**
     * {@inheritdocs}
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
     * @return array
     * @throws Exception
     */
    public function getContactDefinition(): array
    {
        $this->checkLoaded();

        return array_map(
            function ($el) {
                return $el->getData();
            },
            $this->getDb()->table('contact_definition')->where(['contact_id' => $this->id])->fetchAll()
        );
    }

    /**
     * gets contact form submissions
     *
     * @return array
     * @throws Exception
     */
    public function getContactSubmissions(): array
    {
        $this->checkLoaded();

        return array_map(
            function ($el) {
                /** @var ContactSubmission $el */
                return $el->getId();
            },
            $this->getContainer()->call([ContactSubmission::class, 'where'], ['condition' => ['contact_id' => $this->getId()]])
        );
    }

    /**
     * gets contact form specific submission
     *
     * @param int $submission_id
     * @return array
     * @throws Exception
     */
    public function getContactSubmission(int $submission_id): array
    {
        $this->checkLoaded();

        return array_map(
            function ($el) {
                return $el->getData();
            },
            $this->getDb()->table('contact_definition')->where(['contact_id' => $this->getId(), 'contact_submission_id' => $submission_id])->fetchAll()
        );
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
    public function getFormDefinition(FAPI\Abstracts\Base\Element $container, &$form_state): FAPI\Abstracts\Base\Element
    {
        foreach ($this->getContactDefinition() as $field) {
            $field_data = (array)json_decode($field['field_data']);
            $field_data['title'] = $this->getUtils()->translate($field_data['title'], $this->getLocale());
            $container->addField(
                $this->slugify($field['field_label']),
                ['type' => $field['field_type']] +
                ($field['field_required'] == true ? ['validate' => ['required']] : []) +
                $field_data
            );
        }

        return $container;
    }

    /**
     * {@inheritdocs}
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
