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

namespace App\Base\Models;

use App\App;
use App\Base\Abstracts\Models\BaseModel;
use App\Base\GraphQl\GraphQLExport;

/**
 * Website Model
 *
 * @method int getId()
 * @method string getSiteName()
 * @method string getDomain()
 * @method string getAliases()
 * @method string getDefaultLocale()
 * @method string getDefaultCurrencyCode()
 * @method self setId(int $id)
 * @method self setSiteName(string $site_name)
 * @method self setDomain(string $domain)
 * @method self setAliases(string $aliases)
 * @method self setDefaultLocale(string $default_locale)
 * @method self setDefaultCurrencyCode(string $default_currency_code)
*/
#[GraphQLExport]
class Website extends BaseModel
{
    /**
     * @var mixed
     */
    protected $aliases;

    /**
     * {@inheritdoc}
     *
     * @return self
     */
    public function prePersist(array $persistOptions = []): BaseModel
    {
        $this->aliases = implode(",", array_filter(array_map('trim', explode(",", (string)$this->aliases))));

        return parent::prePersist($persistOptions);
    }

    /**
     * {@inheritdoc}
     *
     * @return self
     */
    public function postPersist(array $persistOptions = []): BaseModel
    {
        if ($this->isFirstSave()) {
            $first_website = App::getInstance()->containerCall([Website::class, 'select'], ['options' => ['expr' => 'id', 'limitCount' => 1]])->fetch();
            $copy_from = $first_website['id'];
            $configurations = [];
            foreach (Configuration::getCollection()->where(['is_system' => 1, 'website_id' => $copy_from]) as $to_copy) {
                $data = [
                    'path' => $to_copy->getPath(),
                    'value' => $to_copy->getValue(),
                    'locale' => $to_copy->getLocale() != null ? $this->getDefaultLocale() : null,
                    'is_system' => 1,
                    'website_id' => $this->getId(),
                ];
                $configuration = App::getInstance()->containerCall([Configuration::class, 'new'], ['initial_data' => $data]);
                $configurations[$data['path']] = $configuration;
            }

            foreach ($configurations as $key => $config) {
                $config->persist();
            }
        }

        return parent::postPersist($persistOptions);
    }
}
