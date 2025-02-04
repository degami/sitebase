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

namespace App\Site\Controllers\Admin;

use App\App;
use Degami\Basics\Exceptions\BasicException;
use DI\DependencyException;
use DI\NotFoundException;
use App\Base\Abstracts\Controllers\AdminPage;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Base\Routing\RouteInfo;
use App\Base\Abstracts\Controllers\BasePage;
use App\Base\Abstracts\Models\FrontendModel;
use Degami\Basics\Html\TagElement;
use HaydenPierce\ClassFinder\ClassFinder;
use Symfony\Component\HttpFoundation\Response;
use App\Base\Tools\Search\Manager as SearchManager;

/**
 * "Elasticsearch" Admin Page
 */
class Elasticsearch extends AdminPage
{
    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public static function isEnabled(): bool
    {
        return boolval(\App\App::getInstance()->getSearch()->isEnabled());
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getTemplateName(): string
    {
        return 'elasticsearch';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public static function getAccessPermission(): string
    {
        return 'administer_site';
    }

    /**
     * {@inheritdoc}
     *
     * @return array|null
     */
    public Function getAdminPageLink() : array|null
    {
        if (!$this->getSearch()->isEnabled()) {
            return null;
        }

        return [
            'permission_name' => static::getAccessPermission(),
            'route_name' => static::getPageRouteName(),
            'icon' => 'search',
            'text' => 'Elasticsearch',
            'section' => 'system',
            'order' => 100,
        ];
    }

    function __construct(
        protected ContainerInterface $container, 
        protected ?Request $request = null, 
        protected ?RouteInfo $route_info = null

    ) {
        parent::__construct($container, $request, $route_info);
        if (($this->getRequest()->query->get('action') ?? 'list') == 'list') {
            $this->addActionLink('reindex-btn', 'reindex-btn', $this->getHtmlRenderer()->getIcon('refresh-cw') . ' ' . $this->getUtils()->translate('Reindex', locale: $this->getCurrentLocale()), $this->getControllerUrl().'?action=reindex', 'btn btn-sm btn-warning');
        }    
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getTemplateData(): array
    {
        $count_result = $this->getSearch()->setQuery('*')->countAll();

        $types = [];

        for ($i=0; $i<(intval($count_result / SearchManager::MAX_ELEMENTS_PER_QUERY)+1); $i++) {    
            $search_result = $this->getSearch()->setQuery('*')->searchData($i, SearchManager::MAX_ELEMENTS_PER_QUERY);
            $docs = $search_result['docs'];

            foreach($docs as $doc) {
                $type = $doc['type'];
                if (!isset($types[$type])) {
                    $types[$type] = 0;
                } 
                $types[$type]++;
            }
        }


        $tableContents = [];

        $classes = array_filter(ClassFinder::getClassesInNamespace(App::MODELS_NAMESPACE, ClassFinder::RECURSIVE_MODE), fn($modelClass) => is_subclass_of($modelClass, FrontendModel::class));
        foreach ($classes as $className) {
            if (!$this->containerCall([$className, 'isIndexable'])) {
                continue;
            }
            $type = strtolower(basename(str_replace("\\", DS, $className)));
            $tableContents[] = [
                'Type' => $type, 
                'Count' => 0, 
                'actions' => implode("", [
                    $this->getActionButton('reindex', $type, 'secondary', 'refresh-cw', 'Reindex'),
                ])
            ];
        }

        foreach ($types as $type => $count) {
            array_walk($tableContents, function(&$tableRow) use ($type, $count) {
                if ($tableRow['Type'] == $type) {
                    $tableRow['Count'] = $count;
                }
            });
        }

        usort($tableContents, fn ($a, $b) => $a['Type'] <=> $b['Type']);

        $clientInfo = $this->getSearch()->clientInfo();

        $this->template_data += [
            'table_header' => ['Type' => '', 'Count' => '', 'actions' => null],
            'table_contents' => $tableContents,
            'total' => "Total documents: " . $count_result,
            'info' => implode("\n", [
                $clientInfo['tagline'],
                $clientInfo['cluster_name'] . ' ' . $clientInfo['version']['number'],
            ]),
        ];

        return $this->template_data;
    }

    /**
     * before render hook
     *
     * @return Response|self
     * @throws BasicException
     * @throws PermissionDeniedException
     */
    protected function beforeRender(): BasePage|Response
    {
        $out = parent::beforeRender();

        if ($this->getRequest()->get('action') == 'reindex') {
            $results = [];

            $classes = ClassFinder::getClassesInNamespace(App::MODELS_NAMESPACE, ClassFinder::RECURSIVE_MODE);
            foreach ($classes as $modelClass) {
                $type = basename(str_replace("\\", "/", strtolower($modelClass)));
                if ($this->getRequest()->get('type') && $type != $this->getRequest()->get('type')) {
                    continue;
                }
    
                $reindexed = $this->reindexModels($modelClass);
                foreach ($reindexed as $type => $count) {
                    if (isset($results[$type])) {
                        $results[$type] += $count;
                    } else {
                        $results[$type] = $count;
                    }
                }
            }

            $total = 0;
            foreach($results as $type => $count) {
                $total += $count;
            }
            $this->addSuccessFlashMessage($this->getUtils()->translate("Reindexed %d elements", [$total]));

            return $this->refreshPage();
        }

        return $out;
    }

    protected function reindexModels(string $modelClass)
    {
        $results = [];
        if (is_subclass_of($modelClass, FrontendModel::class)) {
            foreach ($this->containerCall([$modelClass, 'getCollection']) as $object) {
                /** @var FrontendModel $object */
                $response = $this->getSearch()->indexFrontendModel($object);

                if (!isset($results[$response['result']])) {
                    $results[$response['result']] = 0;
                }
                $results[$response['result']]++;
            }
        }

        return $results;
    }

    /**
     * gets action button html
     *
     * @param string $action
     * @param int $object_id
     * @param string $class
     * @param string $icon
     * @param string $title
     * @return string
     * @throws DependencyException
     * @throws NotFoundException
     */
    protected function getActionButton(string $action, string $type, string $class, string $icon, string $title = ''): string
    {
        try {
            $button = $this->containerMake(TagElement::class, ['options' => [
                'tag' => 'a',
                'attributes' => [
                    'class' => 'btn btn-sm btn-' . $class,
                    'href' => $this->getControllerUrl() . '?action=' . $action . '&type=' . $type,
                    'title' => (trim($title) != '') ? $this->getUtils()->translate($title, locale: $this->getCurrentLocale()) : '',
                ],
                'text' => $this->getHtmlRenderer()->getIcon($icon),
            ]]);

            return (string)$button;
        } catch (BasicException $e) {
        }

        return '';
    }
}
