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

namespace App\Site\Blocks;

use App\Base\Abstracts\Blocks\BaseCodeBlock;
use App\Base\Abstracts\Controllers\BasePage;
use Degami\Basics\Exceptions\BasicException;
use DI\DependencyException;
use DI\NotFoundException;
use Psr\Container\ContainerInterface;
use Degami\Basics\Html\TagElement;

/**
 * GTM Block
 */
class GTM extends BaseCodeBlock
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
        $this->getAssets()->addJs(
            "(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','" . $this->getEnv('GTMID') . "');",
            true,
            'head',
            false
        );
    }

    /**
     * {@inheritdocs}
     *
     * @param BasePage|null $current_page
     * @return string
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function renderHTML(BasePage $current_page = null): string
    {
        /*
        <!-- Google Tag Manager -->
        <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
        new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
        j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
        'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
        })(window,document,'script','dataLayer','GTM-XXXX');</script>
        <!-- End Google Tag Manager -->
        <!-- Google Tag Manager (noscript) -->
        <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-XXXX"
        height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
        <!-- End Google Tag Manager (noscript) -->
        */

        $options = [
            'tag' => 'iframe',
            'attributes' => [
                'class' => '',
                'src' => 'https://www.googletagmanager.com/ns.html?id=' . $this->getEnv('GTMID'),
                'height' => 0,
                'width' => 0,
                'style' => "display:none;visibility:hidden",
            ],
        ];

        $iframe = $this->getContainer()->make(TagElement::class, ['options' => $options]);

        return $this->getContainer()->make(TagElement::class, ['options' => [
            'tag' => 'noscript',
            'attributes' => [
                'class' => '',
            ]
        ]])->addChild($iframe);
    }
}
