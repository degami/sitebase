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
use \App\Base\Abstracts\Models\BaseModel;
use \App\Base\Abstracts\Controllers\FrontendPage;
use \App\Site\Models\Contact;
use \App\Site\Models\ContactSubmission;
use \App\Site\Routing\RouteInfo;
use \Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\HttpFoundation\Response;
use \DateTime;
use \Exception;
use \App\Base\Traits\FrontendTrait;

/**
 * Contact Form Page
 */
class ContactForm extends FormPage // and and is similar to FrontendPageWithObject
{
    use FrontendTrait;

  /**
     * {@inheritdocs}
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container, Request $request = null)
    {
        // construct must be override in order to skip
        // form construction

        FrontendPage::__construct($container, $request);
    }


    /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getTemplateName()
    {
        if ($this->templateData['object']->getTemplateName()) {
            return $this->templateData['object']->getTemplateName();
        }

        return 'contact_form';
    }

    /**
     * return route path
     *
     * @return string
     */
    public static function getRoutePath()
    {
        return 'contact/{id:\d+}';
    }

    /**
     * returns valid route HTTP verbs
     *
     * @return array
     */
    public static function getRouteVerbs()
    {
        return ['GET', 'POST'];
    }

    /**
     * {@inheritdocs}
     *
     * @return Response|self
     */
    protected function beforeRender()
    {
        $route_data = $this->getRouteData();

        if (isset($route_data['id'])) {
            $this->setObject($this->getContainer()->call([Contact::class, 'load'], ['id' => $route_data['id']]));

            $this->templateData += [
                'form' => FAPI\FormBuilder::getForm([$this, 'getFormDefinition'])
                ->setValidate([ [$this, 'formValidate'] ])
                ->setSubmit([ [$this, 'formSubmitted'] ]),
            ];
        }

        return parent::beforeRender();
    }

    /**
     * {@inheritdocs}
     *
     * @param  RouteInfo|null $route_info
     * @param  array          $route_data
     * @return Response
     */
    public function process(RouteInfo $route_info = null, $route_data = [])
    {
        if (!($this->getObject() instanceof BaseModel &&
            is_a($this->getObject(), $this->getObjectClass()) &&
            $this->templateData['object']->isLoaded())
        ) {
            return $this->getUtils()->errorPage(404);
        }

        $this->processFormSubmit();

        return parent::process($route_info);
    }

    /**
     * {@inheritdocs}
     *
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
     *
     * @param  FAPI\Form $form
     * @param  array     &$form_state
     * @return FAPI\Form
     */
    public function getFormDefinition(FAPI\Form $form, &$form_state)
    {
        $contact = $this->templateData['object'] ?? null;
        if ($contact instanceof Contact && $contact->isLoaded()) {
            $form->setFormId($this->slugify('form_'.$contact->getTitle()));
            $form->setId($contact->getName());
            $form->addField(
                'contact_id',
                [
                'type' => 'hidden',
                'default_value' => $contact->getId(),
                ]
            );
            $fieldset = $form->addField(
                'form_definition',
                [
                'type' => 'tag_container',
                'tag' => 'div',
                'id' => 'fieldset-contactfields',
                ]
            );
            $fieldset = $contact->getFormDefinition($fieldset, $form_state);
            $form->addField(
                'button',
                [
                'type' => 'button',
                'value' => $this->getUtils()->translate('Send', $this->getCurrentLocale()),
                'container_class' => 'form-item mt-3',
                'attributes' => ['class' => 'btn btn-primary btn-lg btn-block'],
                ]
            );
        }

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
     * search component by name
     *
     * @param  Contact $contact
     * @param  string  $name
     * @return array
     */
    protected function searchComponentByName($contact, $name)
    {
        $filtered_arr = array_filter(
            array_map(
                function ($el) use ($name) {
                    if ($el['field_label'] == $name) {
                        return $el['id'];
                    }
                    return false;
                },
                $contact->getContactDefinition()
            )
        );
        return reset($filtered_arr);
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

        $this->getUtils()->addQueueMessage(
            'contact_form_mail',
            [
            'from' => $this->getSiteData()->getSiteEmail(),
            'to' => $contact->getSubmitTo(),
            'subject' => 'New Submission - '.$contact->getTitle(),
            'body' => var_export($values, true),
            ]
        );

        $form->reset();
    }

    /**
     * {@inheritdocs}
     *
     * @return [type] [description]
     */
    public static function getObjectClass()
    {
        return Contact::class;
    }
}
