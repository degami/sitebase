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
namespace App\Base\Tools\DataCollector;

use \DebugBar\DataCollector\DataCollector;
use \DebugBar\DataCollector\Renderable;
use \DebugBar\DataCollector\AssetProvider;
use \App\Base\Abstracts\BasePage;

/**
 * Page data collector for debugging
 */
class PageDataCollector extends DataCollector implements Renderable, AssetProvider
{
    /**
     * @var BasePage subject object
     */
    protected $subject;

    /**
     * constructor
     *
     * @param BasePage $page
     */
    public function __construct(BasePage $page = null)
    {
        $this->subject = $page;
    }

    /**
     * collects data
     *
     * @return array
     */
    public function collect()
    {
        return $this->subject->getInfo();
    }

    /**
     * gets tab name
     *
     * @return string
     */
    public function getName()
    {
        return 'Page Data';
    }

    /**
     * gets tab widget
     *
     * @return array
     */
    public function getWidgets()
    {
        return [
            "Page Data" => [
                "icon" => "file-alt",
                "tooltip" => "Page Variables",
                "widget" => "PhpDebugBar.Widgets.VariableListWidget",
                "map" => "Page Data",
                "default" => "''"
            ]
        ];
    }

    /**
     * gets assets
     *
     * @return array
     */
    public function getAssets()
    {
        return [
        //            'css' => '',
        //            'js' => ''
        ];
    }
}
