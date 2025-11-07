<?php
/**
 * @var $controller \App\Base\Controllers\Admin\Commerce\Dashboard
 * @var $current_user \App\Base\Abstracts\Models\AccountModel
 */

$this->layout('admin::layout', ['title' => $controller->getPageTitle()] + get_defined_vars()) ?>

<div class="container-fluid my-4">
    <!-- Tabs navigation -->
    <?php $tabs = [
        'lifetime'   => $this->sitebase()->translate('Lifetime'),
        'last_year'  => $this->sitebase()->translate('Last year'),
        'last_month' => $this->sitebase()->translate('Last month'),
        'last_week'  => $this->sitebase()->translate('Last week'),
    ]; ?>

    <ul class="nav nav-tabs" id="dashboardTab" role="tablist">
        <?php $first = true; foreach ($tabs as $tab_id => $tab_label): ?>
            <li class="nav-item">
                <a 
                    class="nav-link <?= $first ? 'active' : '' ?>" 
                    id="<?= $tab_id ?>-tab" 
                    data-toggle="tab" 
                    href="#<?= $tab_id ?>" 
                    role="tab" 
                    aria-controls="<?= $tab_id ?>" 
                    aria-selected="<?= $first ? 'true' : 'false' ?>"
                >
                    <?= htmlspecialchars($tab_label) ?>
                </a>
            </li>
        <?php $first = false; endforeach; ?>
    </ul>

    <!-- Tabs content -->
    <div class="tab-content mt-3" id="dashboardTabContent">
        <?php $first = true; foreach ($tabs as $tab_id => $tab_label):
            $data = ${$tab_id};
        ?>
            <div 
                class="tab-pane fade <?= $first ? 'show active' : '' ?>" 
                id="<?= $tab_id ?>" 
                role="tabpanel" 
                aria-labelledby="<?= $tab_id ?>-tab"
            >
                <!-- Row 1: Main metrics -->
                <div class="row mt-3">
                    <div class="col-md-3">
                        <div class="card text-center h-100">
                            <div class="card-header">
                                <h6 class="text-muted mb-2"><?= $this->sitebase()->translate('Orders') ?></h6>
                            </div>
                            <div class="card-body">
                                <p class="h3 mb-0"><?= (int)$data['total_sales'] ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="card text-center h-100">
                            <div class="card-header">
                                <h6 class="text-muted mb-2"><?= $this->sitebase()->translate('Revenue (Gross)') ?></h6>
                            </div>
                            <div class="card-body">
                                <p class="h3 mb-0"><?= htmlspecialchars($data['total_income']) ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="card text-center h-100">
                            <div class="card-header">
                                <h6 class="text-muted mb-2"><?= $this->sitebase()->translate('Net Income') ?></h6>
                            </div>
                            <div class="card-body">
                                <p class="h3 mb-0"><?= htmlspecialchars($data['net_income']) ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="card text-center h-100">
                            <div class="card-header">
                                <h6 class="text-muted mb-2"><?= $this->sitebase()->translate('Average Order Value') ?></h6>
                            </div>
                            <div class="card-body">
                                <p class="h3 mb-0"><?= htmlspecialchars($data['average_order']) ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Row 2: Secondary metrics -->
                <div class="row mt-3">
                    <div class="col-md-3">
                        <div class="card text-center h-100">
                            <div class="card-header">
                                <h6 class="text-muted mb-2"><?= $this->sitebase()->translate('Products Sold') ?></h6>
                            </div>
                            <div class="card-body">
                                <p class="h3 mb-0"><?= (int)$data['total_products'] ?></p>
                                <small class="text-muted"><?= $this->sitebase()->translate('Avg per order: %s', [$data['avg_products']]) ?></small>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="card text-center h-100">
                            <div class="card-header">
                                <h6 class="text-muted mb-2"><?= $this->sitebase()->translate('Unique Customers') ?></h6>
                            </div>
                            <div class="card-body">
                                <p class="h3 mb-0"><?= (int)$data['unique_customers'] ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="card text-center h-100">
                            <div class="card-header">
                                <h6 class="text-muted mb-2"><?= $this->sitebase()->translate('Orders per day') ?></h6>
                            </div>
                            <div class="card-body">
                                <p class="h3 mb-0"><?= $data['orders_per_day'] ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="card text-center h-100">
                            <div class="card-header">
                                <h6 class="text-muted mb-2"><?= $this->sitebase()->translate('Shipping total') ?></h6>
                            </div>
                            <div class="card-body">
                                <p class="h3 mb-0"><?= htmlspecialchars($data['total_shipping']) ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Row 3: Tax, discount, payment -->
                <div class="row mt-3">
                    <div class="col-md-4">
                        <div class="card text-center h-100">
                            <div class="card-header">
                                <h6 class="text-muted mb-2"><?= $this->sitebase()->translate('Tax collected') ?></h6>
                            </div>
                            <div class="card-body">
                                <p class="h4 mb-0"><?= htmlspecialchars($data['total_tax']) ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card text-center h-100">
                            <div class="card-header">
                                <h6 class="text-muted mb-2"><?= $this->sitebase()->translate('Discounts given') ?></h6>
                            </div>
                            <div class="card-body">
                                <p class="h4 mb-0"><?= htmlspecialchars($data['total_discount']) ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card text-center h-100">
                            <div class="card-header">
                                <h6 class="text-muted mb-2"><?= $this->sitebase()->translate('Most used payment method') ?></h6>
                            </div>
                            <div class="card-body">
                                <p class="h4 mb-0"><?= htmlspecialchars($data['top_payment']) ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top sellers -->
                <h4 class="mt-4 mb-3">
                    <?= $this->sitebase()->translate('Top %d best sellers', [count($data['most_sold'])]); ?>
                </h4>

                <div class="table-responsive">
                    <table class="table table-striped table-bordered table-sm">
                        <thead class="thead-dark">
                            <tr>
                                <th><?= $this->sitebase()->translate('Product'); ?></th>
                                <th class="text-right"><?= $this->sitebase()->translate('Sold quantity'); ?></th>
                                <th class="text-right"><?= $this->sitebase()->translate('In Stock quantity'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data['most_sold'] as $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['product']) ?></td>
                                    <td class="text-right"><?= (int)$item['total_qty'] ?></td>
                                    <td class="text-right"><?= htmlspecialchars((string)$item['stock']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php $first = false; endforeach; ?>
    </div>

</div>
