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

namespace App\Site\Controllers\Frontend;

use Degami\Basics\Exceptions\BasicException;
use DI\DependencyException;
use DI\NotFoundException;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use Degami\PHPFormsApi as FAPI;
use App\Base\Abstracts\Controllers\FormPage;
use App\Site\Models\LinkExchange;
use Throwable;

/**
 * Link Exchange Page
 */
class Links extends FormPage
{
    /**
     * gets route group
     *
     * @return string
     */
    public static function getRouteGroup(): string
    {
        return '';
    }

    /**
     * returns valid route HTTP verbs
     *
     * @return array
     */
    public static function getRouteVerbs(): array
    {
        return ['GET', 'POST'];
    }

    /**
     * return route path
     *
     * @return string
     */
    public static function getRoutePath(): string
    {
        return 'links';
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getTemplateName(): string
    {
        return 'links';
    }

    /**
     * {@inheritdocs}
     *
     * @return array
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     */
    protected function getTemplateData(): array
    {
        $data = $this->getContainer()->call([LinkExchange::class, 'paginate'], ['condition' => ['active' => 1, 'locale' => $this->getCurrentLocale()]]);
        return $this->template_data += [
            'page_title' => $this->getUtils()->translate('Links exchange', $this->getCurrentLocale()),
            'links' => $data['items'],
            'total' => $data['total'],
            'current_page' => $data['page'],
            'paginator' => $this->getHtmlRenderer()->renderPaginator($data['page'], $data['total'], $this),
        ];
    }

    /**
     * {@inheritdocs}
     *
     * @param FAPI\Form $form
     * @param array     &$form_state
     * @return FAPI\Form
     * @throws FAPI\Exceptions\FormException
     */
    public function getFormDefinition(FAPI\Form $form, &$form_state): FAPI\Form
    {
        $form->addField('url', [
            'type' => 'textfield',
            'title' => 'Insert your URL',
            'validate' => ['required'],
        ])->addField('email', [
            'type' => 'email',
            'title' => 'Your Email',
            'validate' => ['required'],
        ])->addField('title', [
            'type' => 'textfield',
            'title' => 'Your Site Name',
            'validate' => ['required'],
        ])->addField('description', [
            'type' => 'textarea',
            'title' => 'Your Site Description',
            'validate' => ['required'],
            'rows' => 5,
        ])->addField('button', [
            'type' => 'button',
            'value' => 'Send',
            'container_class' => 'form-item mt-3',
            'attributes' => ['class' => 'btn btn-primary btn-lg btn-block'],
        ]);

        return $form;
    }

    /**
     * {@inheritdocs}
     *
     * @param FAPI\Form $form
     * @param array     &$form_state
     * @return bool|string
     */
    public function formValidate(FAPI\Form $form, &$form_state): bool|string
    {
        return true;
    }

    /**
     * {@inheritdocs}
     *
     * @param FAPI\Form $form
     * @param array     &$form_state
     * @return mixed
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     * @throws PhpfastcacheSimpleCacheException
     * @throws Throwable
     */
    public function formSubmitted(FAPI\Form $form, &$form_state): mixed
    {
        $values = $form->getValues();

        /** @var LinkExchange $link */
        $link = $this->getContainer()->call([LinkExchange::class, 'new'], ['initial_data' => [
            'url' => $values->url,
            'email' => $values->email,
            'title' => $values->title,
            'description' => $values->description,
            'locale' => $this->getCurrentLocale(),
            'website_id' => $this->getSiteData()->getCurrentWebsiteId(),
        ]]);

        $link->persist();

        $form->addHighlight('Thanks for your submission!');

        $this->getUtils()->queueLinksFormMail(
            $values->email,
            $this->getSiteData()->getSiteEmail(),
            'New Link exchange',
            var_export($values->getData(), true)
        );

        $form->reset();
        return null;
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getCurrentLocale(): string
    {
        if (!$this->locale) {
            $this->locale = parent::getCurrentLocale();
            if ($this->locale == null) {
                $this->locale = 'en';
            }
        }
        $this->getApp()->setCurrentLocale($this->locale);
        return $this->locale;
    }
}
