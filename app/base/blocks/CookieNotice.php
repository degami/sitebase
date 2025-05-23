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
use App\Base\Abstracts\Controllers\BasePage;
use App\Base\Models\Rewrite;
use Degami\Basics\Exceptions\BasicException;
use Degami\Basics\Html\TagElement;
use Degami\PHPFormsApi as FAPI;
use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use Psr\Container\ContainerInterface;

/**
 * CookieNotice Block
 */
class CookieNotice extends BaseCodeBlock
{
    /**
     * class constructor
     *
     * @param ContainerInterface $container
     * @throws BasicException
     */
    public function __construct(
        protected ContainerInterface $container
    ) {
        parent::__construct($container);
        $this->getAssets()->addCss("
            .cookie-notice {
                position: fixed;
                left: 0;
                width:100%;
                z-index: 100000;
                padding: 10px;
            }
            
            .cookie-notice a {
                color: inherit;
            }
        ");
        $this->getAssets()->addJs("
            \$('.cookie-notice .close-btn').click(function(evt) {
                evt.preventDefault();
                \$('.cookie-notice').fadeOut();
                \$.cookie('cookie-accepted',1, { expires: 365, path: '/' });
                \$('body').removeClass('cookie-notice-visible').addClass('cookie-notice-hidden');
            });
            if (\$.cookie('cookie-accepted') != 1) {
                \$('.cookie-notice').fadeIn();
                \$('body').addClass('cookie-notice-visible');
            }
        ");
    }

    /**
     * {@inheritdoc}
     *
     * @param BasePage|null $current_page
     * @param array $data
     * @return string
     */
    public function renderHTML(?BasePage $current_page = null, array $data = []): string
    {
        if (
            $current_page instanceof BasePage &&
            $current_page->getRouteInfo() &&
            $current_page->getRouteInfo()->isAdminRoute()
        ) {
            return '';
        }

        try {
            $class = 'cookie-notice';

            $config = array_filter(json_decode($data['config'] ?? '{}', true));
            $config += ['sticky' => 'bottom'];

            $locale = $current_page?->getCurrentLocale();
            /** @var Rewrite $rewrite */
            $rewrite = $this->containerCall([Rewrite::class, 'load'], ['id' => $config['rewrite_' . $locale]]);

            return $this->containerMake(TagElement::class, ['options' => [
                'tag' => 'div',
                'attributes' => [
                    'class' => $class,
                    'style' => "display:none;{$config['sticky']}: 0;background-color: {$config['background-color']};color: {$config['color']};border-" . (($config['sticky'] == 'top' ? 'bottom' : 'top')) . ": solid 1px {$config['color']};",
                ],
                'text' => $this->getUtils()->translate('We use cookies to ensure that we give you the best experience on our website. <a href="%s" target="_blank">Click here</a> for more information.', locale: $locale, params: [$rewrite->getUrl()]),
                'children' => [
                    $this->containerMake(TagElement::class, ['options' => [
                        'tag' => 'button',
                        'attributes' => [
                            'class' => 'btn btn-primary btn-sm ml-2 close-btn',
                        ],
                        'text' => $this->getUtils()->translate('Got it!', locale: $locale),
                    ]])
                ]
            ]]);
        } catch (Exception $e) {
        }
        return "";
    }

    /**
     * additional configuration fieldset
     *
     * @param FAPI\Form $form
     * @param $form_state
     * @param $default_values
     * @return array
     * @throws BasicException
     * @throws FAPI\Exceptions\FormException
     * @throws PhpfastcacheSimpleCacheException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function additionalConfigFieldset(FAPI\Form $form, &$form_state, $default_values): array
    {
        $config_fields = [];

        $rewrites = ['none' => ''];
        foreach (Rewrite::getCollection() as $rewrite) {
            /** @var Rewrite $rewrite */
            $rewrites[$rewrite->getId()] = $rewrite->getUrl() . " ({$rewrite->getRoute()})";
        }

        $current_website = $this->getSiteData()->getCurrentWebsiteId();
        foreach ($this->getSiteData()->getSiteLocales($current_website) as $locale) {
            $config_fields[] = $form->getFieldObj('rewrite_' . $locale, [
                'type' => 'select',
                'title' => 'Privacy page ' . $locale,
                'options' => $rewrites,
                'default_value' => $default_values['rewrite_' . $locale] ?? '',
            ]);
        }

        $config_fields[] = $form->getFieldObj('background-color', [
            'type' => 'colorpicker',
            'title' => 'Background color',
            'default_value' => $default_values['background-color'] ?? 'cecece',
        ]);

        $config_fields[] = $form->getFieldObj('color', [
            'type' => 'colorpicker',
            'title' => 'Color',
            'default_value' => $default_values['color'] ?? '000000',
        ]);

        $config_fields[] = $form->getFieldObj('sticky', [
            'type' => 'select',
            'title' => 'Position',
            'options' => [
                'top' => 'Top',
                'bottom' => 'Bottom',
            ],
            'default_value' => $default_values['sticky'] ?? 'bottom',
        ]);

        return $config_fields;
    }
}
