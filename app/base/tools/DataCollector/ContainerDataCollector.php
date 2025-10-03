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

namespace App\Base\Tools\DataCollector;

use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;
use Psr\Container\ContainerInterface;

class ContainerDataCollector extends DataCollector implements Renderable
{
    public const NAME = "Container";

    protected ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function collect()
    {
        $services = [];

        // Se il container implementa un metodo per elencare i servizi, usalo:
        if (method_exists($this->container, 'getServiceIds')) {
            foreach ($this->container->getServiceIds() as $id) {
                $services[] = $id;
            }
        } elseif (method_exists($this->container, 'getRegisteredServices')) {
            // Adatta se Sitebase espone metodi personalizzati
            $services = array_keys($this->container->getRegisteredServices());
        } elseif (method_exists($this->container, 'getKnownEntryNames')) {
            $services = $this->container->getKnownEntryNames();
        }

        return [
            'service_count' => count($services),
            'services' => '<ul><li>'.implode('</li><li>', array_map(fn($el) => $el . ' - ' . $this->container->debugEntry($el), $services)).'</li></ul>',
        ];
    }

    public function getName()
    {
        return self::NAME;
    }

    public function getWidgets()
    {
        return [
            self::NAME => [
                'icon' => 'cog',
                'widget' => 'PhpDebugBar.Widgets.HtmlVariableListWidget',
                'map' => self::NAME,
                'default' => '{}'
            ],
            self::NAME.':badge' => [
                'map' => self::NAME.'.service_count',
                'default' => 0,
            ],
        ];
    }
}
