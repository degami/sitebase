<?php

/**
 * SiteBase
 * PHP Version 8.0
 *
 * @category CMS / Framework
 * @package  Degami\Sitebase
 * @author   Mirko De Grandis <degami@github.com>
 * @license  MIT https://opensource.org/licenses/mit-license.php
 * @link     https://github.com/degami/sitebase
 */

namespace App\Base\Traits;

use App\App;
use Fisharebest\Localization\Locale;
use Fisharebest\Localization\Translation;
use Fisharebest\Localization\Translator;

/**
 * Translators Trait
 */
trait TranslatorsTrait
{
    /**
     * @var array translators
     */
    public static array $translators = [];

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
}
