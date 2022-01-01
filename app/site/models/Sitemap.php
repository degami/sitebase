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

namespace App\Site\Models;

use App\Base\Abstracts\Models\FrontendModel;
use Degami\Basics\Exceptions\BasicException;
use Exception;
use Spatie\ArrayToXml\ArrayToXml;
use DateTime;

/**
 * Sitemap Model
 *
 * @method int getId()
 * @method int getWebsiteId()
 * @method string getLocale()
 * @method string getTitle()
 * @method int getUserId()
 * @method DateTime getPublishedOn()
 * @method string getContent()
 * @method DateTime getCreatedAt()
 * @method DateTime getUpdatedAt()
 * @method self setId(int $id)
 * @method self setWebsiteId(int $website_id)
 * @method self setLocale(string $locale)
 * @method self setTitle(string $content)
 * @method self setUserId(int $user_id)
 * @method self setPublishedOn(DateTime $published_on)
 * @method self setContent(string $content)
 * @method self setCreatedAt(DateTime $created_at)
 * @method self setUpdatedAt(DateTime $updated_at)
 */
class Sitemap extends FrontendModel
{
    /**
     * @var array urlset
     */
    protected array $urlset = [];

    /**
     * gets sitemap urlset
     *
     * @param false $reset
     * @return array
     * @throws BasicException
     * @throws Exception
     */
    public function getUrlset($reset = false): array
    {
        $this->checkLoaded();

        if (!(is_array($this->urlset) && !empty($this->urlset)) || $reset == true) {
            $this->urlset = [
                'url' => array_map(
                    function ($el) {
                        $rewrite = $this->getContainer()->make(Rewrite::class, ['db_row' => $el->rewrite()->fetch()]);
                        return [
                            'id' => $el->id,
                            'rewrite' => $rewrite->id,
                            'changefreq' => $el->change_freq,
                            'priority' => $el->priority,
                            'loc' => $this->getWebRouter()->getUrl('frontend.root') . ltrim($rewrite->getUrl(), '/'),
                            'lastmod' => (new DateTime($rewrite->getUpdatedAt()))->format('Y-m-d'),
                        ];
                    },
                    $this->sitemap_rewriteList()->fetchAll()
                )
            ];
        }
        return $this->urlset;
    }

    /**
     * generate sitemap
     *
     * @return $this
     * @throws BasicException
     */
    public function generate(): Sitemap
    {
        $urlset = $this->getUrlset();
        foreach ($urlset['url'] as &$url) {
            unset($url['id']);
            unset($url['rewrite']);
        }
        $xml = ArrayToXml::convert(
            $urlset,
            [
                'rootElementName' => 'urlset',
                '_attributes' => [
                    'xmlns' => 'http://www.sitemaps.org/schemas/sitemap/0.9',
                ]
            ],
            true,
            'UTF-8'
        );

        $this->setContent($xml);
        if ($xml != null) {
            $this->setPublishedOn(new DateTime());
        }

        $this->persist();
        return $this;
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    public function getRewritePrefix(): string
    {
        return 'sitemap';
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     * @throws BasicException
     * @throws Exception
     */
    public function getFrontendUrl(): string
    {
        $this->checkLoaded();

        return '/' . $this->getLocale() . '/sitemap-' . $this->getUtils()->slugify($this->getTitle()) . '-' . $this->getWebsite()->getDomain() . '.xml';
    }
}
