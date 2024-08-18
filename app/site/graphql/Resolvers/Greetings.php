<?php

namespace App\Site\GraphQL\Resolvers;

use App\Base\GraphQl\ResolverInterface;

class Greetings implements ResolverInterface
{
    public static function resolve(array $args): mixed
    {
        return trim(__('Hello').' '.($args['firstName'] ?? '') . ' '.($args['lastName'] ?? ''));
    }
}