<?php
/**
 * @var string $paginator
 * @var \App\Base\Interfaces\Model\ProductInterface[] $products
 * @var string $page_title
 */
$this->layout('frontend::layout', ['title' => $this->sitebase()->translate('Downloadables')] + get_defined_vars()) ?>

<h1 class="page-title"><?php echo $page_title;?></h1>
<div class="row">
    <div class="col-md-12">
        <ul class="downloadable-list">
            <?php foreach ($products as $key => $product) :?>
                <li>
                    <div>
                        <a href="<?= $product->getFrontendUrl(); ?>" class="downloadable-detail">
                            <span class="downloadable-title"><?= $product->getName(); ?></span>
                        </a>
                    </div>
                    <div class="downloadable-description"><?= $this->sitebase()->summarize($product->getDescription(), 20); ?></div>
                </li>
            <?php endforeach;?>
        </ul>
        <?= $paginator; ?>        
    </div>
</div>
