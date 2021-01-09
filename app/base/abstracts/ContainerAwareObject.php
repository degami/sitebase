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

namespace App\Base\Abstracts;

use App\Base\Exceptions\InvalidValueException;
use DI\DependencyException;
use DI\NotFoundException;
use Psr\Container\ContainerInterface;
use Fisharebest\Localization\Locale;
use Fisharebest\Localization\Translation;
use Fisharebest\Localization\Translator;
use App\Base\Traits\ToolsTrait;
use App\Base\Traits\ContainerAwareTrait;
use App\App;

/**
 * Base for objects that are aware of Container
 */
abstract class ContainerAwareObject
{
    use ContainerAwareTrait;
    use ToolsTrait;

    /**
     * @var array translators
     */
    public static $translators = [];

    /**
     * constructor
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * gets translator
     *
     * @param string|null $locale_code
     * @return Translator
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getTranslator($locale_code = null): Translator
    {
        if ($locale_code == null) {
            $locale_code = 'en';
        }

        if (isset(static::$translators[$locale_code])) {
            return static::$translators[$locale_code];
        }

        // We need to translate into $locale_code
        $locale = Locale::create($locale_code);

        if (!file_exists(App::getDir(App::TRANSLATIONS) . DS . $locale->language()->code() . '.php')) {
            $locale_code = 'en';
            $locale = Locale::create($locale_code);
        }

        // Create the translation
        $translation = $this->getContainer()->make(
            Translation::class,
            [
                'filename' => App::getDir(App::TRANSLATIONS) . DS . $locale->language()->code() . '.php',
            ]
        );  // Can use .CSV, .PHP, .PO and .MO files
        // Create the translator
        static::$translators[$locale_code] = $this->getContainer()->make(
            Translator::class,
            [
                'translations' => $translation->asArray(),
                'plural_rule' => $locale->pluralRule(),
            ]
        );
        // Use the translator
        return static::$translators[$locale_code];
    }

    /**
     * {@inheritdocs}
     *
     * @param string $name
     * @param mixed $arguments
     * @return mixed
     * @throws InvalidValueException
     */
    public function __call(string $name, $arguments)
    {
        $method = strtolower(substr(trim($name), 0, 3));
        $prop = self::pascalCaseToSnakeCase(substr(trim($name), 3));
        if ($method == 'get' && $this->getContainer()->has($prop)) {
            return $this->getContainer()->get($prop);
        }

        throw new InvalidValueException("Method \"{$name}\" not found in class\"" . get_class($this) . "\"!", 1);
    }
}
