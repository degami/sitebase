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

namespace App\Base\Controllers\Admin\Commerce;

use App\Base\Abstracts\Controllers\AdminPage;
use App\Base\Interfaces\Model\PhysicalProductInterface;
use DateInterval;
use DateTime;

/**
 * "Dashboard" Admin Page
 */
class Dashboard extends AdminPage
{
    /**
     * @var string page title
     */
    protected ?string $page_title = 'Commerce Dashboard';

    /**
     * {@inheritdoc}
     */
    public function getTemplateName(): string
    {
        return 'commerce/dashboard';
    }

    /**
     * {@inheritdoc}
     */
    public static function getAccessPermission(): string
    {
        return 'administer_orders';
    }

    /**
     * {@inheritdoc}
     */
    public static function getAdminPageLink(): ?array
    {
        return [
            'permission_name' => '',
            'route_name' => static::getPageRouteName(),
            'icon' => 'bar-chart',
            'text' => 'Dashboard',
            'section' => 'commerce',
            'order' => 0,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplateData(): array
    {
        $now = new DateTime();

        $this->template_data = [
            'lifetime'   => $this->collectData(clone $now, null),
            'last_year'  => $this->collectData(clone $now, (clone $now)->sub(DateInterval::createFromDateString('1 year'))),
            'last_month' => $this->collectData(clone $now, (clone $now)->sub(DateInterval::createFromDateString('1 month'))),
            'last_week'  => $this->collectData(clone $now, (clone $now)->sub(DateInterval::createFromDateString('1 week'))),
        ];

        return $this->template_data;
    }

    /**
     * Returns sales data for a given date range
     */
    protected function collectData(DateTime $to, ?DateTime $from = null): array
    {
        $args = [];
        $where = " WHERE 1";

        if ($from) {
            $where .= ' AND o.created_at >= :from';
            $args['from'] = $from->format('Y-m-d 00:00:00');
        }

        if ($to) {
            $where .= ' AND o.created_at <= :to';
            $args['to'] = $to->format('Y-m-d 23:59:59');
        }

        // === 1️⃣ Statistiche generali (solo tabella `order`) ===
        $qOrders = "
            SELECT
                COUNT(o.id) AS total_sales,
                SUM(o.admin_total_incl_tax) AS total_income,
                SUM(o.discount_amount) AS total_discounts,
                SUM(o.tax_amount) AS total_tax,
                MAX(o.admin_currency_code) AS admin_currency_code
            FROM `order` o
            $where
        ";

        $stmt1 = $this->getPdo()->prepare($qOrders);
        $stmt1->execute($args);
        $orders = $stmt1->fetch(\PDO::FETCH_ASSOC) ?: [];

        // === 2️⃣ Totale prodotti venduti ===
        $qItems = "
            SELECT SUM(oi.quantity) AS total_products
            FROM `order_item` oi
            INNER JOIN `order` o ON o.id = oi.order_id
            $where
        ";

        $stmt2 = $this->getPdo()->prepare($qItems);
        $stmt2->execute($args);
        $items = $stmt2->fetch(\PDO::FETCH_ASSOC) ?: [];

        // === 3️⃣ Prodotti più venduti (TOP 10) ===
        $qTop = "
            SELECT 
                CONCAT(oi.product_class, ':', oi.product_id) AS product_key,
                SUM(oi.quantity) AS total_qty
            FROM `order` o
            INNER JOIN `order_item` oi ON oi.order_id = o.id
            $where
            GROUP BY product_key
            ORDER BY total_qty DESC
            LIMIT 5
        ";

        $stmt3 = $this->getPdo()->prepare($qTop);
        $stmt3->execute($args);

        $most_sold = [];
        foreach ($stmt3->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            try {
                $p = explode(':', $row['product_key']);
                $product = $this->containerCall([$p[0], 'load'], ['id' => $p[1]]);
                $label = method_exists($product, 'getSku')
                    ? ($product->getSku() ?: $product->getName())
                    : $product->getName();
                $stock = ($product instanceof PhysicalProductInterface) ? 
                    $product->getProductStock()->getCurrentQuantity() : 
                    $this->getUtils()->translate('unlimited', locale: $this->getCurrentLocale());
            } catch (\Exception $e) {
                $label = 'n/a';
                $stock = 'n/a';
            }

            $most_sold[] = [
                'product' => $label,
                'total_qty' => (int) $row['total_qty'],
                'stock' => $stock,
            ];
        }

        // === 4️⃣ Metodo di pagamento più usato ===
        $qPay = "
            SELECT op.payment_method, COUNT(*) AS total
            FROM `order_payment` op
            INNER JOIN `order` o ON o.id = op.order_id
            $where
            GROUP BY op.payment_method
            ORDER BY total DESC
            LIMIT 1
        ";

        $stmt4 = $this->getPdo()->prepare($qPay);
        $stmt4->execute($args);
        $payment = $stmt4->fetch(\PDO::FETCH_ASSOC);

        // === 5️⃣ Calcoli e formattazione ===
        $utils = $this->getUtils();
        $currency = $orders['admin_currency_code'] ?? 'EUR';

        $total_sales = (int) ($orders['total_sales'] ?? 0);
        $total_income = (float) ($orders['total_income'] ?? 0);
        $total_tax = (float) ($orders['total_tax'] ?? 0);
        $total_discount = (float) ($orders['total_discounts'] ?? 0);
        $total_products = (int) ($items['total_products'] ?? 0);

        $aov = $total_sales > 0 ? $total_income / $total_sales : 0;

        return [
            'total_sales'     => $total_sales,
            'total_income'    => $utils->formatPrice($total_income, $currency),
            'total_tax'       => $utils->formatPrice($total_tax, $currency),
            'total_discount'  => $utils->formatPrice($total_discount, $currency),
            'total_products'  => $total_products,
            'average_order'   => $utils->formatPrice($aov, $currency),
            'most_sold'       => $most_sold,
            'top_payment'     => $payment['payment_method'] ?? 'n/a',
        ];
    }
}
