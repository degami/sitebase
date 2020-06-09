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
use \App\Base\Traits\BlockTrait;
use \App\Base\Traits\WithWebsiteTrait;
use \App\Base\Traits\WithOwnerTrait;
use \App\Base\Abstracts\Controllers\BasePage;
use \Degami\Basics\Html\TagElement;

/**
 * Block Model
 *
 * @method int getId()
 * @method int getWebsiteId()
 * @method string getRegion()
 * @method string getLocale()
 * @method string getInstanceClass()
 * @method string getTitle()
 * @method string getContent()
 * @method string getConfig()
 * @method int getUserId()
 * @method \DateTime getCreatedAt()
 * @method \DateTime getUpdatedAt()
 */
class Block extends BaseModel
{
    use BlockTrait, WithWebsiteTrait, WithOwnerTrait;

    /**
     * @var array rewrites
     */
    protected $rewrites = [];

    /**
     * @var \App\Base\Abstracts\Blocks\BaseCodeBlock code block instance
     */
    protected $codeBlockInstance = null;

    /**
     * {@inheritdocs}
     *
     * @param  BasePage $current_page
     * @return string
     */
    public function renderHTML(BasePage $current_page)
    {
        $this->checkLoaded();

        $class = 'block block-'.$this->getId();

        return (string)(new TagElement(
            [
            'tag' => 'div',
            'attributes' => [
                'class' => $class,
            ],
            'text' => $this->content,
            ]
        ));
    }

    /**
     * gets block instance
     *
     * @return string
     */
    public function getInstance()
    {
        return $this->getInstanceClass() ?? Block::class;
    }

    /**
     * loads code block instance
     *
     * @return \App\Base\Abstracts\Blocks\BaseCodeBlock
     */
    public function loadInstance()
    {
        if ($this->isCodeBlock() && is_null($this->codeBlockInstance)) {
            $this->codeBlockInstance = $this->getContainer()->make($this->getInstance());
        }

        return $this->codeBlockInstance;
    }

    /**
     * gets real block instance
     *
     * @return self|\App\Base\Abstracts\Blocks\BaseCodeBlock
     */
    public function getRealInstance()
    {
        return ($this->getInstance() == Block::class) ? $this : $this->loadInstance();
    }

    /**
     * renders block
     *
     * @param  BasePage|null $current_page
     * @return string
     */
    public function render(BasePage $current_page = null)
    {
        return $this->getRealInstance()->renderHTML($current_page, $this->getData());
    }

    /**
     * checks if is code block
     *
     * @return boolean
     */
    public function isCodeBlock()
    {
        return $this->getInstance() != Block::class;
    }

    /**
     * gets block rewrite objects
     *
     * @return array
     */
    public function getRewrites($reset = false)
    {
        $this->checkLoaded();

        if (!(is_array($this->rewrites) && !empty($this->rewrites)) || $reset == true) {
            $this->rewrites = array_map(
                function ($el) {
                    return $this->getContainer()->make(Rewrite::class, ['dbrow' => $el]);
                },
                $this->block_rewriteList()->rewrite()->fetchAll()
            );
        }
        return $this->rewrites;
    }

    /**
     * checks if block can be shown on specific rewrite
     *
     * @param  Rewrite $rewrite
     * @return boolean
     */
    public function checkValidRewrite($rewrite)
    {
        $this->checkLoaded();

        return (count($this->getRewrites()) == 0 || count(
            array_filter(
                $this->getRewrites(),
                function ($el) use ($rewrite) {
                    return ($el->getId() == $rewrite->getId()) ? true : false;
                }
            )
        ) > 0);
    }
}
