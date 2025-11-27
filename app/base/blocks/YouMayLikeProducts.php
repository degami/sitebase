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

namespace App\Base\Blocks;

use App\Base\Abstracts\Blocks\BaseCodeBlock;
use App\Base\Abstracts\Controllers\AdminPage;
use App\Base\Abstracts\Controllers\BasePage;
use Degami\Basics\Exceptions\BasicException;
use DI\DependencyException;
use DI\NotFoundException;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use Degami\Basics\Html\TagElement;
use App\Base\Models\Cart;
use App\App;
use App\Base\Abstracts\Controllers\BaseHtmlPage;
use App\Base\Models\CartItem;
use App\Base\Interfaces\Model\ProductInterface;
use App\Base\Abstracts\Models\FrontendModel;
use App\Base\Controllers\Frontend\Commerce\Cart as CommerceCart;
use App\Base\Tools\Search\AIManager as AISearchManager;

/**
 * "You May Like" Block
 */
class YouMayLikeProducts extends BaseCodeBlock
{
    protected ?Cart $cart = null;
    protected ?AISearchManager $aiSearchManager = null;

    public function getCart(?BaseHtmlPage $current_page = null) : ?Cart
    {
        if ($this->cart instanceof Cart) {
            return $this->cart;
        }

        $cart = Cart::getCollection()->where([
            'user_id' => $current_page?->getCurrentUser()->getId(),
            'website_id' => $current_page?->getCurrentWebsite()->getId(),
            'is_active' => true,
        ])->getFirst();

        if ($cart instanceof Cart) {
            $this->cart = $cart;
            return $this->cart;
        }

        return null;
    }

    /**
     * {@inheritdoc}
     *
     * @param BasePage|null $current_page
     * @param array $data
     * @return string
     * @throws BasicException
     * @throws PhpfastcacheSimpleCacheException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function renderHTML(?BasePage $current_page = null, array $data = []): string
    {
        if (!App::getInstance()->getEnvironment()->getVariable('ENABLE_COMMERCE', false)) {
            return '';
        }

        if (!($current_page instanceof CommerceCart)) {
            return '';
        }

        $config = array_filter(json_decode($data['config'] ?? '{}', true));

        $route_info = $current_page?->getRouteInfo();

        // $current_page_handler = $route_info->getHandler();
        if ($current_page?->getRouteGroup() == AdminPage::getRouteGroup() || $route_info?->isAdminRoute() || !$current_page?->getCurrentUser()) {
            return '';
        }


        $youMayLike = [];
        foreach ($this->getCart($current_page)->getItems() as $item) {
            $youMayLike = array_unique_by(
                array_merge($youMayLike, $this->getProductSuggestions($item)), 
                fn ($el) => $el['modelClass'] . '::' . $el['id']
            );
        }

        $cartIdentifiers = array_map(function(CartItem $item) {
            return static::getClassBasename($item->getProduct()).'::'.$item->getProduct()->getId();
        }, $this->getCart($current_page)->getItems());

        $suggestions = [];
        foreach ($youMayLike as $elem) {
            ['modelClass' => $modelClass, 'type' => $type, 'id' => $id] = $elem;
            if (is_subclass_of($modelClass, ProductInterface::class)) {
                $suggestion = $this->containerCall([$modelClass, 'load'], ['id' => $id]);

                if (in_array(static::getClassBasename($suggestion).'::'.$suggestion->getId(), $cartIdentifiers)) {
                    continue;
                }

                $suggestions[] = $suggestion;
            }
        }

        $list = [];
        foreach ($suggestions as $suggestion) {
            /** @var FrontendModel $suggestion */
            $link = $this->containerMake(TagElement::class, ['options' => [
                'tag' => 'a',
                'attributes' => [
                    'href' => $suggestion->getFrontendUrl(),
                ],
                'text' => $suggestion->getTitle(),
            ]]);

            $sku = $this->containerMake(TagElement::class, ['options' => [
                'tag' => 'span',
                'attributes' => [
                    'class' => 'sku',
                ],
                'text' => $this->getUtils()->translate('SKU', locale: $current_page?->getCurrentLocale()) . ': ' . $suggestion->getSku(),
            ]]);

            $price = $this->containerMake(TagElement::class, ['options' => [
                'tag' => 'span',
                'attributes' => [
                    'class' => 'price',
                ],
                'text' => $this->getUtils()->translate('Price', locale: $current_page?->getCurrentLocale()) . ': ' . $this->getUtils()->formatPrice($suggestion->getPrice(), $this->getCart($current_page)->getCurrencyCode()),
            ]]);

            $list[] = '<div class="row"><div class="col-12">'.$link . '</div><div class="col d-flex justify-content-between">' . $sku . $price . '</div></div>';
        }

        if (empty($list)) {
            return '';
        }

        return "".$this->containerMake(TagElement::class, ['options' => [
            'tag' => 'div',
            'attributes' => [
                'class' => 'pt-5',
            ],
            'text' => '<h2>'.$this->getUtils()->translate('You may also like', locale: $current_page?->getCurrentLocale()).'</h2><ul class="list-group"><li class="list-group-item">'.implode('</li><li class="list-group-item">', $list).'</li></ul>',
        ]]);
    }

    public function isCachable() : bool
    {
        return false;
    }

    protected function getProductSuggestions(CartItem $item, ?BasePage $current_page = null) : array
    {
        /** @var ProductInterface $product */
        $product = $item->getProduct();

        $locale     = $current_page?->getCurrentLocale() ?? App::getInstance()->getCurrentLocale();
        $website_id = App::getInstance()->getSiteData()->getCurrentWebsiteId();

        $cacheKey = strtolower('youmaylike.'.static::getClassBasename($product).'.'.$product->getId().'.'.$locale.'.'.$website_id);

        if (App::getInstance()->getCache()->has($cacheKey)) {
            $result = (array) App::getInstance()->getCache()->get($cacheKey);
            return array_map(fn ($el) => ['modelClass' => $el['modelClass'], 'type' => $el['type'], 'id' => $el['id']], $result['docs']);
        }

        /** @var FrontendModel $product */
        $embeddable = $this->getSearchManager()->getEmbeddableDataForFrontendModel($product);
        $result = $this->getSearchManager()->searchNearby(implode(' ', array_filter($embeddable)));

        App::getInstance()->getCache()->set($cacheKey, $result, 1800);

        return array_map(fn ($el) => ['modelClass' => $el['modelClass'], 'type' => $el['type'], 'id' => $el['id']], $result['docs']);
    }

    protected function getSearchManager(string $llmCode = 'googlegemini') : AISearchManager
    {
        if (!is_null($this->aiSearchManager)) {
            return $this->aiSearchManager;
        }

        /** @var AISearchManager $embeddingManager */
        return $this->aiSearchManager = $this->containerMake(AISearchManager::class, [
            'llm' => $this->getAI()->getAIModel($llmCode),
            'model' => match ($llmCode) {
                'googlegemini' => 'text-embedding-004',
                'chatgpt' => 'text-embedding-3-small',
                'claude' => 'claude-2.0-embedding',
                'groq' => 'groq-vector-1',
                'mistral' => 'mistral-embedding-001',
                'perplexity' => 'perplexity-embedding-001',
                default => null,
            }
        ]);
    }
}
