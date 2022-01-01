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

use App\Base\Abstracts\Blocks\BaseCodeBlock;
use App\Base\Abstracts\Models\BaseModel;
use App\Base\Traits\BlockTrait;
use App\Base\Traits\WithWebsiteTrait;
use App\Base\Traits\WithOwnerTrait;
use App\Base\Abstracts\Controllers\BasePage;
use DateTime;
use Degami\Basics\Html\TagElement;
use DI\DependencyException;
use DI\NotFoundException;
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
 * @method int getOrder()
 * @method DateTime getCreatedAt()
 * @method DateTime getUpdatedAt()
 * @method self setId(int $id)
 * @method self setWebsiteId(int $website_id)
 * @method self setRegion(string $region)
 * @method self setLocale(string $locale)
 * @method self setInstanceClass(string $instance_class)
 * @method self setTitle(string $title)
 * @method self setContent(string $content)
 * @method self setConfig(string $config)
 * @method self setUserId(int $user_id)
 * @method self setOrder(int $order)
 * @method self setCreatedAt(DateTime $created_at)
 * @method self setUpdatedAt(DateTime $updated_at)
 */
class Block extends BaseModel
{
    use BlockTrait;
    use WithOwnerTrait;
    use WithWebsiteTrait;

    /**
     * @var array rewrites
     */
    protected array $rewrites = [];

    /**
     * @var BaseCodeBlock|null code block instance
     */
    protected ?BaseCodeBlock $codeBlockInstance = null;

    /**
     * {@inheritdocs}
     *
     * @param BasePage $current_page
     * @return string
     * @throws Exception
     */
    public function renderHTML(BasePage $current_page): string
    {
        $this->checkLoaded();

        $class = 'block block-' . $this->getId();

        return (string)(new TagElement(
            [
                'tag' => 'div',
                'attributes' => [
                    'class' => $class,
                ],
                'text' => $this->getContent(),
            ]
        ));
    }

    /**
     * gets block instance
     *
     * @return string
     */
    public function getInstance(): string
    {
        return $this->getInstanceClass() ?? Block::class;
    }

    /**
     * loads code block instance
     *
     * @return BaseCodeBlock|null
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function loadInstance(): ?BaseCodeBlock
    {
        if ($this->isCodeBlock() && is_null($this->codeBlockInstance)) {
            $this->codeBlockInstance = $this->getContainer()->make($this->getInstance());
        }

        return $this->codeBlockInstance;
    }

    /**
     * gets real block instance
     *
     * @return Block|BaseCodeBlock|null
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getRealInstance(): Block|BaseCodeBlock|null
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
    public function render(BasePage $current_page = null): string
    {
        return $this->getRealInstance()->renderHTML($current_page, $this->getData());
    }

    /**
     * checks if is code block
     *
     * @return bool
     */
    public function isCodeBlock(): bool
    {
        return $this->getInstance() != Block::class;
    }

    /**
     * gets block rewrite objects
     *
     * @param bool $reset
     * @return array
     * @throws Exception
     */
    public function getRewrites(bool $reset = false): array
    {
        $this->checkLoaded();

        if (!(is_array($this->rewrites) && !empty($this->rewrites)) || $reset == true) {
            /*
                        $this->rewrites = array_map(
                            function ($el) {
                                return $this->getContainer()->make(Rewrite::class, ['db_row' => $el]);
                            },
                            $this->block_rewriteList()->rewrite()->fetchAll()
                        );
            */
            $query = $this->getDb()->prepare("SELECT rewrite_id FROM block_rewrite WHERE block_id = :id");
            $query->execute(['id' => $this->getId()]);
            $ids = $query->fetchAll(\PDO::FETCH_COLUMN, 0);

            $this->rewrites = $this->getContainer()->call([Rewrite::class, 'loadMultiple'], ['ids' => $ids]);
        }
        return $this->rewrites;
    }

    /**
     * checks if block can be shown on specific rewrite
     *
     * @param Rewrite $rewrite
     * @return bool
     * @throws Exception
     */
    public function checkValidRewrite(Rewrite $rewrite): bool
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
