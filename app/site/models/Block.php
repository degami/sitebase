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

use App\Base\Abstracts\Blocks\BaseCodeBlock;
use \App\Base\Abstracts\Models\BaseModel;
use \App\Base\Traits\BlockTrait;
use \App\Base\Traits\WithWebsiteTrait;
use \App\Base\Traits\WithOwnerTrait;
use \App\Base\Abstracts\Controllers\BasePage;
use DateTime;
use \Degami\Basics\Html\TagElement;
use Exception;

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
 * @method DateTime getCreatedAt()
 * @method DateTime getUpdatedAt()
 */
class Block extends BaseModel
{
    use BlockTrait, WithWebsiteTrait, WithOwnerTrait;

    /**
     * @var array rewrites
     */
    protected $rewrites = [];

    /**
     * @var BaseCodeBlock code block instance
     */
    protected $codeBlockInstance = null;

    /**
     * {@inheritdocs}
     *
     * @param BasePage $current_page
     * @return string
     * @throws Exception
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
     * @return BaseCodeBlock
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
     * @return self|BaseCodeBlock
     */
    public function getRealInstance()
    {
        return ($this->getInstance() == Block::class) ? $this : $this->loadInstance();
    }

    /**
     * renders block
     *
     * @param BasePage|null $current_page
     * @return string
     * @throws Exception
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
     * @param false $reset
     * @return array
     * @throws Exception
     */
    public function getRewrites($reset = false)
    {
        $this->checkLoaded();

        if (!(is_array($this->rewrites) && !empty($this->rewrites)) || $reset == true) {
/*
            $this->rewrites = array_map(
                function ($el) {
                    return $this->getContainer()->make(Rewrite::class, ['dbrow' => $el]);
                },
                $this->block_rewriteList()->rewrite()->fetchAll()
            );
*/
            $query = $this->getDb()->prepare("SELECT rewrite_id FROM block_rewrite WHERE block_id = :id");
            $query->execute(['id' => $this->id]);
            $ids = $query->fetchAll(\PDO::FETCH_COLUMN, 0);

            $this->rewrites = $this->getContainer()->call([Rewrite::class, 'loadMultiple'], ['ids' => $ids]);
        }
        return $this->rewrites;
    }

    /**
     * checks if block can be shown on specific rewrite
     *
     * @param Rewrite $rewrite
     * @return boolean
     * @throws Exception
     */
    public function checkValidRewrite($rewrite)
    {
        $this->checkLoaded();

        return (count($this->getRewrites()) == 0 || count(
            array_filter(
                $this->getRewrites(),
                function ($el) use ($rewrite) {
                    return $el->getId() == $rewrite->getId();
                }
            )
        ) > 0);
    }
}
