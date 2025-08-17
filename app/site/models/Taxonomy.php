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

namespace App\Site\Models;

use App\App;
use App\Base\Abstracts\Models\BaseModel;
use App\Base\Abstracts\Models\FrontendModelWithChildren;
use App\Base\Traits\WithParentTrait;
use DateTime;
use Exception;
use App\Base\GraphQl\GraphQLExport;

/**
 * Taxonomy Model
 *
 * @method int getId()
 * @method int getWebsiteId()
 * @method string getUrl()
 * @method int getParentId()
 * @method int getPosition()
 * @method string getLocale()
 * @method string getTitle()
 * @method string getMetaTitle()
 * @method string getMetaKeywords()
 * @method string getMetaDescription()
 * @method string getHtmlTitle()
 * @method string getContent()
 * @method string getTemplateName()
 * @method int getUserId()
 * @method DateTime getCreatedAt()
 * @method DateTime getUpdatedAt()
 * @method string getPath()
 * @method int getLevel()
 * @method self setId(int $id)
 * @method self setWebsiteId(int $website_id)
 * @method self setUrl(string $url)
 * @method self setParentId(int $parent)
 * @method self setPosition(int $position)
 * @method self setLocale(string $locale)
 * @method self setTitle(string $title)
 * @method self setMetaTitle(string $meta_title)
 * @method self setMetaKeywords(string $met_keywords)
 * @method self setMetaDescription(string $meta_description)
 * @method self setHtmlTitle(string $html_title)
 * @method self setContent(string $content)
 * @method self setTemplateName(string $template_name)
 * @method self setUserId(int $user_id)
 * @method self setCreatedAt(DateTime $created_at)
 * @method self setUpdatedAt(DateTime $updated_at)
 * @method self setPath(string $path)
 * @method self setLevel(int $level)
 */
#[GraphQLExport]
class Taxonomy extends FrontendModelWithChildren
{
    use WithParentTrait;

    /**
     * @var array taxonomy pages
     */
    protected array $pages = [];

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getRewritePrefix(): string
    {
        return 'taxonomy';
    }

    /**
     * gets term pages
     *
     * @param false $reset
     * @return \App\Site\Models\Page[]
     * @throws Exception
     */
    #[GraphQLExport]
    public function getPages($reset = false): array
    {
        $this->checkLoaded();

        if (!(is_array($this->pages) && !empty($this->pages)) || $reset == true) {
            $this->pages = array_map(
                function ($el) {
                    return App::getInstance()->containerMake(Page::class, ['db_row' => $el]);
                },
                $this->page_taxonomyList()->page()->fetchAll()
            );
        }
        return $this->pages;
    }

    public function prePersist(): BaseModel
    {
        $this->setPath($this->getParentIds());
        $this->setLevel(max(count(explode("/", (string) $this->path)) - 1, 0));
        return parent::prePersist();
    }

    public function persist(bool $recursive = true): BaseModel
    {
        $alsoChildren = array_key_exists('parent_id', $this->getChangedData()) || array_key_exists('level', $this->getChangedData());
        $out = parent::persist($recursive);

        if ($recursive && $alsoChildren) {
            foreach($this->getChildren() as $child) {
                $child->persist();
            }
        }

        return $out;
    }
}
