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
use \App\Base\Traits\WithWebsiteTrait;
use \App\Base\Traits\WithOwnerTrait;

/**
 * Link Exchange Model
 *
 * @method int getId()
 * @method integer getWebsiteId()
 * @method string getLocale()
 * @method string getUrl()
 * @method string getEmail()
 * @method string getTitle()
 * @method string getDescription()
 * @method int getUserId()
 * @method boolean getActive()
 */
class LinkExchange extends BaseModel
{
    use WithWebsiteTrait, WithOwnerTrait;

    /**
     * @var array link taxonomy terms
     */
    protected $terms = [];

    /**
     * gets Link Taxonomy Terms
     *
     * @return array
     */
    public function getTerms()
    {
        $this->checkLoaded();

        if (!(is_array($this->terms) && !empty($this->terms))) {
            $this->terms = array_map(
                function ($el) {
                    return $this->getContainer()->make(Taxonomy::class, ['dbrow' => $el]);
                },
                $this->link_echange_taxonomyList()->taxonomy()->fetchAll()
            );
        }
        return $this->terms;
    }

    /**
     * adds a Taxonomy Term to Link
     *
     * @param  Taxonomy $term
     * @return self
     */
    public function addTerm($term)
    {
        $new_link_echange_taxonomy_row = $this->getDb()->table('link_echange_taxonomy')->createRow();
        $new_link_echange_taxonomy_row->update(
            [
            'link_exchange_id' => $this->id,
            'taxonomy_id' => $term->id,
            ]
        );
        return $this;
    }

    /**
     * removes a Taxonomy term from link
     *
     * @param  Taxonomy $term
     * @return self
     */
    public function removeTerm($term)
    {
        $this->getDb()->table('link_echange_taxonomy')->where(
            [
            'link_exchange_id' => $this->id,
            'taxonomy_id' => $term->id,
            ]
        )->delete();
        return $this;
    }
}
