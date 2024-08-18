<?php

namespace App\Base\GraphQl;

interface ResolverInterface
{
    public static function resolve(array $args) : mixed;
}