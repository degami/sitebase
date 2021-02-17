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

use App\Base\Abstracts\Models\BaseModel;
use App\Base\Traits\WithWebsiteTrait;
use App\Base\Traits\WithOwnerTrait;
use Degami\Basics\Exceptions\BasicException;
use Exception;

/**
 * Link Exchange Model
 *
 * @method int getId()
 * @method int getWebsiteId()
 * @method string getLocale()
 * @method string getUrl()
 * @method string getEmail()
 * @method string getTitle()
 * @method string getDescription()
 * @method int getUserId()
 * @method bool getActive()
 * @method self setId(int $id)
 * @method self setWebsiteId(int $website_id)
 * @method self setLocale(string $locale)
 * @method self setUrl(string $url)
 * @method self setEmail(string $email)
 * @method self setTitle(string $title)
 * @method self setDescription(string $description)
 * @method self setUserId(int $user_id)
 * @method self setActive(bool $active)
 */
class LinkExchange extends BaseModel
{
    use WithOwnerTrait;
    use WithWebsiteTrait;

    /**
     * @var array link taxonomy terms
     */
    protected array $terms = [];

    /**
     * gets Link Taxonomy Terms
     *
     * @param bool $reset
     * @return array
     * @throws Exception
     */
    public function getTerms(bool $reset = false): array
    {
        $this->checkLoaded();

        if (!(is_array($this->terms) && !empty($this->terms)) || $reset == true) {
            $this->terms = array_map(
                function ($el) {
                    return $this->getContainer()->make(Taxonomy::class, ['db_row' => $el]);
                },
                $this->link_exchange_taxonomyList()->taxonomy()->fetchAll()
            );
        }
        return $this->terms;
    }

    /**
     * adds a Taxonomy Term to Link
     *
     * @param Taxonomy $term
     * @return self
     * @throws BasicException
     */
    public function addTerm(Taxonomy $term): LinkExchange
    {
        $new_link_exchange_taxonomy_row = $this->getDb()->table('link_exchange_taxonomy')->createRow();
        $new_link_exchange_taxonomy_row->update(
            [
                'link_exchange_id' => $this->getId(),
                'taxonomy_id' => $term->getId(),
            ]
        );
        return $this;
    }

    /**
     * removes a Taxonomy term from link
     *
     * @param Taxonomy $term
     * @return self
     * @throws BasicException
     */
    public function removeTerm(Taxonomy $term): LinkExchange
    {
        $this->getDb()->table('link_exchange_taxonomy')->where(
            [
                'link_exchange_id' => $this->getId(),
                'taxonomy_id' => $term->getId(),
            ]
        )->delete();
        return $this;
    }
}
