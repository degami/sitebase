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
namespace App\Site\Blocks;

use \App\Base\Abstracts\BaseCodeBlock;
use \App\Base\Abstracts\BasePage;
use \Psr\Container\ContainerInterface;
use \Degami\PHPFormsApi\Accessories\TagElement;
use \App\Site\Models\Website;

/**
 * Year&Copy Block
 */
class YearCopy extends BaseCodeBlock
{
    /**
     * {@inheritdocs}
     * @param  BasePage|null $current_page
     * @return string
     */
    public function renderHTML(BasePage $current_page = null)
    {
        try {
            $website = $this->getContainer()->call([Website::class, 'load'], ['id' => $this->getSiteData()->getCurrentWebsite()]);

            $class = 'block copy';

            return (string)(new TagElement([
                'tag' => 'div',
                'attributes' => [
                    'class' => $class,
                ],
                'text' => $website->getSiteName()." &copy; - ".date('Y'),
            ]));
        } catch (Exception $e) {
        }
        return "";
    }
}