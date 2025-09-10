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

namespace App\Site\EventListeners\GraphQL;

use App\App;
use App\Base\Interfaces\EventListenerInterface;
use Gplanchat\EventManager\Event;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ResolveInfo;
use App\Site\Models\Contact;
use GraphQL\Type\Definition\InputObjectType;
use App\Site\Models\ContactSubmission;

class ContactEventListener implements EventListenerInterface
{
	public function getEventHandlers() : array
	{
		// Return an array of event handlers as required by the interface
		return [
            'register_graphql_mutation_fields' => [$this, 'RegisterGraphQLMutationFields']
        ];
	}

    public function RegisterGraphQLMutationFields(Event $e) 
    {
        $app = App::getInstance();

        $object = $e->getData('object');
        $mutationFields = &$object->mutationFields;
        $typesByName = &$object->typesByName;
        $typesByClass = &$object->typesByClass;
        $entrypoint = $object->entrypoint;

        if (!isset($typesByName['SubmitContactFieldValue'])) {
            $typesByName['SubmitContactFieldValue'] = new InputObjectType([
                'name' => 'SubmitContactFieldValue',
                'fields' => [
                    'contact_definition_id' => ['type' => Type::nonNull(Type::int())],
                    'field_value' => ['type' => Type::nonNull(Type::string())],
                ]
            ]);
        }

        if (!isset($mutationFields['submitContact'])) {
            $mutationFields['submitContact'] = [
                'type' => $typesByName['SubmitActionResponse'],
                'args' => [
                    'contact_id' => ['type' => Type::nonNull(Type::int())],
                    'submission_data' => ['type' => Type::listOf($typesByName['SubmitContactFieldValue'])],
                ],
                'resolve' => function ($rootValue, $args, $context, ResolveInfo $info) use ($app) {
                    $success = false; $message = 'error';

                    $contactId = $args['contact_id'];
                    $submissionData = $args['submission_data'];

                    $user_id = null;
                    if ($app->getAuth()->getCurrentUser() && $app->getAuth()->getCurrentUser()->id) {
                        $user_id = $app->getAuth()->getCurrentUser()->id;
                    }

                    $contact = Contact::load($contactId);

                    $submission = [
                        'contact_id' => $contact->getId(),
                        'user_id' => $user_id,
                        'data' => $submissionData,
                    ];

                    /** @var ContactSubmission $contactSubmission */
                    $contactSubmission = $app->containerCall([ContactSubmission::class, 'submit'], ['submission_data' => $submission]);

                    $success = true;
                    $message = 'Thanks for your submission!';

                    $app->getUtils()->queueContactFormMail(
                        $app->getSiteData()->getSiteEmail(),
                        $contact->getSubmitTo(),
                        'New Submission - ' . $contact->getTitle(),
                        var_export($contactSubmission->getFullData()['values'], true)
                    );


                    return ['success' => $success, 'message' => App::getInstance()->getUtils()->translate($message)];
                },
            ];
        }
    }
}