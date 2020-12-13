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

namespace App\Site\Models;

use \App\Base\Abstracts\Models\BaseModel;

/**
 * Website Model
 *
 * @method string getSiteName()
 * @method string getDomain()
 * @method string getAliases()
 * @method integer getDefaultLocale()
 * @method self setSiteName(string $site_name)
 * @method self setDomain(string $domain)
 * @method self setAliases(string $aliases)
 * @method self setDefaultLocale(integer $default_locale)
 */
class Website extends BaseModel
{
    /**
     * @var mixed|string
     */
    protected $aliases;

    /**
     * {@inheritdocs}
     *
     * @return self
     */
    public function prePersist(): Website
    {
        $this->aliases = implode(",", array_filter(array_map('trim', explode(",", $this->aliases))));

        return parent::prePersist();
    }

    /**
     * {@inheritdocs}
     *
     * @return self
     */
    public function postPersist(): Website
    {
        if ($this->isFirstSave()) {
            $first_website = $this->getContainer()->call([Website::class, 'select'], ['options' => ['expr' => 'id', 'limitCount' => 1]])->fetch();
            $copy_from = $first_website['id'];
            $copy_configurations = $this->getContainer()->call([Configuration::class, 'where'], ['condition' => ['is_system' => 1, 'website_id' => $copy_from]]);
            $configurations = [];
            foreach ($copy_configurations as $to_copy) {
                $data = [
                    'path' => $to_copy->getPath(),
                    'value' => $to_copy->getValue(),
                    'locale' => $to_copy->getLocale() != null ? $this->getDefaultLocale() : null,
                    'is_system' => 1,
                    'website_id' => $this->getId(),
                ];
                $configuration = $this->getContainer()->call([Configuration::class, 'new'], ['initialdata' => $data]);
                $configurations[$data['path']] = $configuration;
            }

            foreach ($configurations as $key => $config) {
                $config->persist();
            }
        }

        return parent::postPersist();
    }
}
