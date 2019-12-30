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
use \App\Base\Abstracts\FormPage;
use \App\App;
use \App\Base\Abstracts\Model;
use \App\Site\Models\Contact;
use \App\Site\Models\ContactSubmission;
use \App\Site\Routing\RouteInfo;
use \Symfony\Component\HttpFoundation\Response;
use \DateTime;
use \Exception;

/**
 * Contact Form Page
 */
class ContactForm extends FormPage // and and is similar to FrontendPageWithObject
{
    /** @var array template data */
    protected $templateData = [];

    /** @var RouteInfo route info object */
    protected $route_info = null;

    /**
     * {@inheritdocs}
     * @return string
     */
    protected function getTemplateName()
    {
        return 'contact_form';
    }

    /**
     * gets route group
     * @return string
     */
    public static function getRouteGroup()
    {
        return '';
    }

    /**
     * return route path
     * @return string
     */
    public static function getRoutePath()
    {
        return 'contact/{id:\d+}';
    }

    /**
     * {@inheritdocs}
     * @param  RouteInfo|null $route_info
     * @param  array          $route_data
     * @return Response
     */
    public function process(RouteInfo $route_info = null, $route_data = [])
    {
        $this->route_info = $route_info;
        
        $this->templateData['object'] = $this->getContainer()->call([Contact::class, 'load'], ['id' => $route_data['id']]);
        if (!($this->templateData['object'] instanceof Model && $this->templateData['object']->isLoaded())) {
            return $this->getUtils()->errorPage(404);
        }

        return parent::process($route_info);
    }

    /**
     * {@inheritdocs}
     * @return string
     */
    public function getCurrentLocale()
    {
        if ($this->templateData['object'] instanceof Model && $this->templateData['object']->isLoaded()) {
            if ($this->templateData['object']->getLocale()) {
                return $this->getApp()->setCurrentLocale($this->templateData['object']->getLocale())->getCurrentLocale();
            }
        }

        return $this->getApp()->setCurrentLocale(parent::getCurrentLocale())->getCurrentLocale();
    }

    /**
     * {@inheritdocs}
     * @return array
     */
    protected function getBaseTemplateData()
    {
        $out = parent::getBaseTemplateData();
        $out ['body_class'] = str_replace('.', '-', $this->getRouteName()).' contact-'. $this->templateData['object']->id;
        return $out;
    }

    /**
     * {@inheritdocs}
     * @return array
     */
    protected function getTemplateData()
    {
        return $this->templateData;
    }

    /**
     * {@inheritdocs}
     * @param  FAPI\Form $form
     * @param  array    &$form_state
     * @return FAPI\Form
     */
    public function getFormDefinition(FAPI\Form $form, &$form_state)
    {
        $contact = $this->templateData['object'];
        if ($contact instanceof Contact && $contact->isLoaded()) {
            $form->setId($contact->getName());
            $form->addField('contact_id', [
                'type' => 'hidden',
                'default_value' => $contact->getId(),
            ]);
            $fieldset = $form->addField('form_definition', [
                'type' => 'tag_container',
                'tag' => 'div',
                'id' => 'fieldset-contactfields',
            ]);
            $fieldset = $contact->getFormDefinition($fieldset, $form_state);
            $form->addField('button', [
                'type' => 'button',
                'value' => $this->getUtils()->translate('Send', $this->getCurrentLocale()),
                'container_class' => 'form-item mt-3',
                'attributes' => ['class' => 'btn btn-primary btn-lg btn-block'],
            ]);
        }

        return $form;
    }

    /**
     * {@inheritdocs}
     * @param  FAPI\Form $form
     * @param  array    &$form_state
     * @return boolean|string
     */
    public function formValidate(FAPI\Form $form, &$form_state)
    {
        return true;
    }
    
    /**
     * search component by name
     * @param  Contact $contact
     * @param  string $name
     * @return array
     */
    protected function searchComponentByName($contact, $name)
    {
        $filtered_arr = array_filter(
            array_map(function ($el) use ($name) {
                if ($el['field_label'] == $name) {
                    return $el['id'];
                }
                return false;
            }, $contact->getContactDefinition())
        );
        return reset($filtered_arr);
    }

    /**
     * {@inheritdocs}
     * @param  FAPI\Form $form
     * @param  array    &$form_state
     * @return mixed
     */
    public function formSubmitted(FAPI\Form $form, &$form_state)
    {
        //['contact_id' => $form->getValues()->contact_id]
        //    id  contact_id  contact_submission_id   user_id     contact_definition_id   field_value     created_at  updated_at

        $values = $form->getValues()->form_definition->getData();
        $contact = $this->templateData['object'];
        $user_id = null;
        if ($this->getCurrentUser() && $this->getCurrentUser()->id) {
            $user_id = $this->getCurrentUser()->id;
        }

        $submission = [
            'contact_id' => $contact->getId(),
            'user_id' => $user_id,
            'data' => [],
        ];
        foreach ($values as $name => $value) {
            $contact_definition_id = $this->searchComponentByName($contact, $name);
            $submission['data'][] = [
                'contact_definition_id' => $contact_definition_id,
                'field_value' => $value,
            ];
        }

        $submission_obj = $this->getContainer()->call([ContactSubmission::class, 'submit'], ['submission_data' => $submission]);

        $form->addHighlight('Thanks for your submission!');
        //var_dump($form->get_triggering_element());
        // Reset the form if you want it to display again.

        $this->getUtils()->addQueueMessage('contact_form_mail', [
            'from' => $this->getSiteData()->getSiteEmail(),
            'to' => $contact->getSubmitTo(),
            'subject' => 'New Submission - '.$contact->getTitle(),
            'body' => var_export($values, true),
        ]);

        $form->reset();
    }

    /**
     * {@inheritdocs}
     * @return [type] [description]
     */
    public static function getObjectClass()
    {
        return Contact::class;
    }

    /**
     * {@inheritdocs}
     * @return array
     */
    public function getTranslations()
    {
        return array_map(function ($el) {
            return $this->getRouting()->getBaseUrl() . $el;
        }, $this->getContainer()->call([$this->templateData['object'], 'getTranslations']));
    }
}