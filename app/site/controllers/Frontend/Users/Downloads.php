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

namespace App\Site\Controllers\Frontend\Users;

use App\App;
use App\Base\Abstracts\Controllers\LoggedUserPage;
use App\Base\Abstracts\Models\BaseCollection;
use App\Site\Models\DownloadableProduct;
use App\Site\Models\UserDownload;
use App\Base\Abstracts\Controllers\BasePage;
use Symfony\Component\HttpFoundation\Response;
use App\Base\Exceptions\PermissionDeniedException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class Downloads extends LoggedUserPage
{
    const ITEMS_PER_PAGE = 20;

    /**
     * @var string page title
     */
    protected ?string $page_title = 'My Downloads';

    /**
     * @inheritDoc
     */
    public static function isEnabled(): bool
    {
        return boolval(App::getInstance()->getEnvironment()->getVariable('ENABLE_COMMERCE', false)) && boolval(App::getInstance()->getEnvironment()->getVariable('ENABLE_LOGGEDPAGES', false));
    }

    /**
     * @inheritDoc
     */
    public function getTemplateName(): string
    {
        return 'users/downloads';
    }

    /**
     * return route path
     *
     * @return string
     */
    public static function getRoutePath(): string
    {
        return 'downloads';
    }

    /**
     * @inheritdoc
     *
     * @return string
     */
    public static function getAccessPermission(): string
    {
        return 'view_logged_site';
    }

    /**
     * @inheritDoc
     */
    public function getTemplateData(): array
    {
        $page = $this->getRequest()->query->get('page') ?? 0;
        $total = $this->getDownloads()?->count() ?? 0;

        $downloads = $this->getDownloads()?->limit(self::ITEMS_PER_PAGE, $page * self::ITEMS_PER_PAGE)->getItems() ?? [];

        $this->template_data += [
            'current_user' => $this->getCurrentUser(),
            'downloads' => $downloads,
            'paginator' => $this->getHtmlRenderer()->renderPaginator($page, $total, $this, self::ITEMS_PER_PAGE, 5),
        ];
        return $this->template_data;
    }

    protected function getDownloads() : ?BaseCollection
    {

//        // find paid orders
//        $orders = Order::getCollection()->where([
//            'user_id' => $this->getCurrentUser()->getId(), 
//            'order_status_id' => OrderStatus::getByStatus(OrderStatus::PAID)->getId()
//        ]);
//
//        $productIds = [];
//
//        // get downloadble products ids
//        foreach ($orders as $order) {
//            foreach ($order->getItems() as $orderItem) {
//                /** @var OrderItem $oderItem */
//                if ($orderItem->getProduct() instanceof DownloadableProduct) {
//                    $productIds[] = $orderItem->getProduct()->getId();
//                }
//            }
//        }
//
//        // inject ids into collection condition
//        return DownloadableProduct::getCollection()->where(['id' => array_unique($productIds)]);

        return UserDownload::getCollection()->where(['user_id' => $this->getCurrentUser()->getId()]);
    }

    /**
     * before render hook
     *
     * @return Response|self
     * @throws PermissionDeniedException
     * @throws BasicException
     */
    protected function beforeRender() : BasePage|Response
    {
        $out = parent::beforeRender();

        if ($this->getRequest()->query->get('action') == 'download') {
            $id = $this->getRequest()->query->get('id');

            if (!in_array($id, array_map(fn ($download) => $download->getId(), $this->getDownloads()->getItems()))) {
                throw new PermissionDeniedException();
            }

            $downloadableProduct = DownloadableProduct::load($id);

            $response = new BinaryFileResponse($downloadableProduct->getMedia()->getPath());
            $response->setContentDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                basename($downloadableProduct->getMedia()->getPath())
            );

            $response->headers->set('Content-Type', 'application/octet-stream');
            $response->headers->set('Content-Transfer-Encoding', 'binary');
            $response->headers->set('Cache-Control', 'must-revalidate, no-cache, no-store, private');
            $response->headers->set('Pragma', 'no-cache');

            return $response;
        }

        return $out;
    }
}