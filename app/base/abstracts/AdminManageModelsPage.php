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
namespace App\Base\Abstracts;

use \Psr\Container\ContainerInterface;
use \Symfony\Component\HttpFoundation\Response;
use \App\Base\Abstracts\AdminPage;
use \Degami\PHPFormsApi as FAPI;
use \Degami\PHPFormsApi\Accessories\TagElement;
use \App\App;

/**
 * Base for admin page that manages a Model
 */
abstract class AdminManageModelsPage extends AdminFormPage
{
    /**
     * {@inheriydocs}
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        if ($this->templateData['action'] == 'list') {
            $this->addNewButton();
            $data = $this->getContainer()->call([$this->getObjectClass(), 'paginate'], ['order' => $this->getRequest()->query->get('order')]);
            $this->templateData += [
                'table' => $this->getHtmlRenderer()->renderAdminTable($this->getTableElements($data['items']), $this->getTableHeader(), $this),
                'total' => $data['total'],
                'current_page' => $data['page'],
                'paginator' => $this->getHtmlRenderer()->renderPaginator($data['page'], $data['total'], $this),
            ];
        }
    }

    /**
     * {@inheritdocs}
     *
     * @return array
     */
    protected function getTemplateData()
    {
        return $this->templateData;
    }

    /**
     * gets model table html
     *
     * @return string
     */
    public function getTable()
    {
        return $this->getTemplateData()['table'];
    }

    /**
     * gets paginator html
     *
     * @return string
     */
    public function getPaginator()
    {
        return $this->getTemplateData()['paginator'];
    }

    /**
     * defines table header
     *
     * @return array|null
     */
    protected function getTableHeader()
    {
        return null;
    }

    /**
     * defines table rows
     *
     * @param  array $data
     * @return array
     */
    abstract protected function getTableElements($data);
}
