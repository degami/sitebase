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

use App\Base\Abstracts\Models\BaseModel;

/**
 * Website Model
 *
 * @method int getId()
 * @method string getSiteName()
 * @method string getDomain()
 * @method string getAliases()
 * @method int getDefaultLocale()
 * @method self setId(int $id)
 * @method self setSiteName(string $site_name)
 * @method self setDomain(string $domain)
 * @method self setAliases(string $aliases)
 * @method self setDefaultLocale(int $default_locale)
 */
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
    public function prePersist(): BaseModel
    {
        $this->aliases = implode(",", array_filter(array_map('trim', explode(",", $this->aliases))));

        return parent::prePersist();
    }

    /**
     * {@inheritdoc}
     *
     * @return self
     */
    public function postPersist(): BaseModel
    {
        if ($this->isFirstSave()) {
            $first_website = $this->containerCall([Website::class, 'select'], ['options' => ['expr' => 'id', 'limitCount' => 1]])->fetch();
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
                $configuration = $this->containerCall([Configuration::class, 'new'], ['initial_data' => $data]);
                $configurations[$data['path']] = $configuration;
            }

            foreach ($configurations as $key => $config) {
                $config->persist();
            }
        }

        return parent::postPersist();
    }
}
