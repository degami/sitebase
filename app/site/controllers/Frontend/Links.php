<?php
/**
 * SiteBase
 * PHP Version 7.0
 *
 * @category CMS / Framework
 * @package  Degami\Sitebase
 * @author   Mirko De Grandis <degami@github.com>
 * @license  MIT https://opensource.org/licenses/mit-license.php
 * @link     https://github.com/degami/sitebase
 */
namespace App\Site\Controllers\Frontend;

use \Psr\Container\ContainerInterface;
use \Degami\PHPFormsApi as FAPI;
use \App\Base\Abstracts\Controllers\FormPage;
use \App\App;
use \App\Site\Models\LinkExchange;
use \App\Site\Routing\RouteInfo;
use \Symfony\Component\HttpFoundation\Response;

/**
 * Link Exchange Page
 */
class Links extends FormPage
{
    /**
     * @var string locale
     */
    protected $locale = null;

    /**
     * gets route group
     *
     * @return string
     */
    public static function getRouteGroup()
    {
        return '';
    }

    /**
     * returns valid route HTTP verbs
     *
     * @return array
     */
    public static function getRouteVerbs()
    {
        return ['GET','POST'];
    }

    /**
     * return route path
     *
     * @return string
     */
    public static function getRoutePath()
    {
        return 'links';
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getTemplateName()
    {
        return 'links';
    }

    /**
     * {@inheritdocs}
     *
     * @return array
     */
    protected function getTemplateData()
    {
        $data = $this->getContainer()->call([LinkExchange::class, 'paginate'], ['condition' => ['active' => 1, 'locale' => $this->getCurrentLocale()]]);
        return $this->templateData += [
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
     * @param  FAPI\Form $form
     * @param  array     &$form_state
     * @return FAPI\Form
     */
    public function getFormDefinition(FAPI\Form $form, &$form_state)
    {
        $form->addField(
            'url',
            [
            'type' => 'textfield',
            'title' => 'Insert your URL',
            'validate' => ['required'],
            ]
        )
            ->addField(
                'email',
                [
                'type' => 'email',
                'title' => 'Your Email',
                'validate' => ['required'],
                ]
            )
            ->addField(
                'title',
                [
                'type' => 'textfield',
                'title' => 'Your Site Name',
                'validate' => ['required'],
                ]
            )
            ->addField(
                'description',
                [
                'type' => 'textarea',
                'title' => 'Your Site Description',
                'validate' => ['required'],
                'rows' => 5,
                ]
            )
            ->addField(
                'button',
                [
                'type' => 'button',
                'value' => 'Send',
                'container_class' => 'form-item mt-3',
                'attributes' => ['class' => 'btn btn-primary btn-lg btn-block'],
                ]
            );

        return $form;
    }

    /**
     * {@inheritdocs}
     *
     * @param  FAPI\Form $form
     * @param  array     &$form_state
     * @return boolean|string
     */
    public function formValidate(FAPI\Form $form, &$form_state)
    {
        return true;
    }

    /**
     * {@inheritdocs}
     *
     * @param  FAPI\Form $form
     * @param  array     &$form_state
     * @return mixed
     */
    public function formSubmitted(FAPI\Form $form, &$form_state)
    {
        $values = $form->getValues();

        $link = $this->getContainer()->call([LinkExchange::class, 'new']);
        $link->url = $values->url;
        $link->email = $values->email;
        $link->title = $values->title;
        $link->description = $values->description;
        $link->locale = $this->getCurrentLocale();
        $link->website_id = $this->getSiteData()->getCurrentWebsiteId();

        $link->persist();


        $form->addHighlight('Thanks for your submission!');

        $this->getUtils()->addQueueMessage(
            'link_form_mail',
            [
            'from' => $values->email,
            'to' => $this->getSiteData()->getSiteEmail(),
            'subject' => 'New Link exchange',
            'body' => var_export($values->getData(), true),
            ]
        );

        $form->reset();
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    public function getCurrentLocale()
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
