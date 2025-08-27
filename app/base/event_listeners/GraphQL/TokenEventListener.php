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

namespace App\Base\EventListeners\GraphQL;

use App\App;
use App\Base\Interfaces\EventListenerInterface;
use Gplanchat\EventManager\Event;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ObjectType;
use RuntimeException;
use App\Base\Models\User;
use GraphQL\Type\Definition\ResolveInfo;
use App\Base\Exceptions\NotFoundException;
use Exception;

class TokenEventListener implements EventListenerInterface
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

        if (!isset($typesByName['User'])) {
            $entrypoint->registerModelClass(User::class, false, true);
            $typesByName = $entrypoint->getTypesByName();
        }

        if (!isset($typesByName['LoginResponse'])) {
            $typesByName['LoginResponse'] = new ObjectType([
                'name' => 'LoginResponse',
                'fields' => function() use ($typesByName) {
                    return [
                        'jwt' => Type::nonNull(Type::string()),
                        'user' => $typesByName['User'],
                    ];
                },
            ]);
        }

        if (!isset($mutationFields['login'])) {
            $mutationFields['login'] = [
                'type' => $typesByName['LoginResponse'],
                'args' => [
                    'username' => Type::nonNull(Type::string()),
                    'password' => Type::nonNull(Type::string()),
                ],
                'resolve' => function ($rootValue, $args, $context, ResolveInfo $info) use ($app) {

                    try {
                        /** @var User|null $user */
                        $user = $app->getUtils()->getUserByCredentials($args['username'], $args['password']);

                        if (!$user) {
                            throw new NotFoundException('User not found');
                        }

                        $user->unlock()->persist();

                        // dispatch "user_logged_in" event
                        $app->event('user_logged_in', [
                            'logged_user' => $user
                        ]);
                    } catch (Exception $e) {
                        try {
                            /** @var User $user */
                            $user = $app->containerCall([User::class, 'loadByCondition'], ['condition' => [
                                'username' => $args['username'],
                            ]]);                            

                            $user->incrementLoginTries()->persist();

                            if ($user->getLocked() == true) {
                                throw new RuntimeException("Account locked. try again lated.");
                            }
                        } catch (\Exception $e) {}
                    }

                    if (!$user) {
                        throw new RuntimeException("Invalid username / password");
                    }

                    return ['jwt' => $user->getJWT(), 'user' => $user];
                },
            ];
        }
    }
}