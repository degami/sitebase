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
use App\Site\Models\LinkExchange;

class LinkExchangeEventListener implements EventListenerInterface
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

        if (!isset($mutationFields['submitLinkExchange'])) {
            $mutationFields['submitLinkExchange'] = [
                'type' => $typesByName['SubmitActionResponse'],
                'args' => [
                    'url' => ['type' => Type::nonNull(Type::string())],
                    'email' => ['type' => Type::nonNull(Type::string())],
                    'title' => ['type' => Type::nonNull(Type::string())],
                    'description' => ['type' => Type::nonNull(Type::string())],
                ],
                'resolve' => function ($rootValue, $args, $context, ResolveInfo $info) use ($app) {
                    $success = false; $message = 'error';

                    $locale = $args['locale'] ?? $app->getCurrentLocale();

                    /** @var LinkExchange $link */
                    $link = $app->containerCall([LinkExchange::class, 'new'], ['initial_data' => [
                        'url' => $args['url'],
                        'email' => $args['email'],
                        'title' => $args['title'],
                        'description' => $args['description'],
                        'locale' => $locale,
                        'website_id' => $app->getSiteData()->getCurrentWebsiteId(),
                    ]]);

                    $link->persist();

                    $success = true;
                    $message = App::getInstance()->getUtils()->translate('Thanks for your submission!');

                    $app->getUtils()->queueLinksFormMail(
                        $args['email'],
                        $app->getSiteData()->getSiteEmail(),
                        'New Link exchange',
                        var_export($args, true)
                    );

                    return ['success' => $success, 'message' => $message];
                },
            ];
        }
    }
}