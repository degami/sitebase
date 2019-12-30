<?php $this->layout('frontend::layout', ['title' => $this->sitebase()->translate('News')] + get_defined_vars()) ?>

<h1 class="page-title"><?php echo $page_title;?></h1>
<div class="row">
    <div class="col-md-12">
        <ul class="news-list">
            <?php foreach ($news as $key => $news_elem) :?>
                <li>
                    <a href="<?= $news_elem->getFrontendUrl(); ?>" class="news-detail">
                        <span class="news-title"><?= $news_elem->getTitle(); ?></span>
                    </a>
                    <span class="news-date"><?= $news_elem->getDate(); ?></span>
                    <span class="news-description"><?= $this->sitebase()->summarize($news_elem->getContent(), 20); ?></span>
                </li>
            <?php endforeach;?>
        </ul>
        <?= $paginator; ?>        
    </div>
</div>
