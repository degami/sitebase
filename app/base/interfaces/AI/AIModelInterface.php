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

namespace App\Base\Interfaces\AI;


interface AIModelInterface 
{
    public static function getCode() : string;
    public static function getName() : string;
    public static function isEnabled() : bool;
    public function ask(string $prompt, ?string $model = null, ?array $previousMessages = null) : string;
    public function getAvailableModels(bool $reset = false) : array;
    public function getVersion() : string;
    public function getModel(?string $model = null) : string;
    public function getDefaultModel() : string;
}