<?php
/**
 * @var $controller \App\Base\Abstracts\Controllers\BaseHtmlPage
 * @var $action string
 * @var $table string
 * @var $paginator string
 * @var $form \Degami\PHPFormsApi\Form
 * @var $submission \App\Site\Models\ContactSubmission
 */
$this->layout('admin::layout', ['title' => $controller->getPageTitle()] + get_defined_vars()) ?>

<?php if ($action == 'list') : ?>
    <div class="table-responsive">
        <?= $table; ?>
    </div>
    <?= $paginator; ?>
<?php elseif ($action == 'submissions') : ?>
    <div class="table-responsive">
        <?= $table; ?>
    </div>
    <?= $paginator; ?>
<?php elseif ($action == 'view_submission') : ?>
    <?php
    $submission_data = $submission->getFullData();
    ?>
    <div class="card card-inverse m-2">
        <div class="card-header">
            <div class="card-title">
                <h4 class="card-title"><?= $this->sitebase()->translate('Submission ID');?>: <?= $submission_data['id'];?></h4>
                <?= $this->sitebase()->translate('Submitted on');?>: <?= $submission_data['created_at'];?> <?= $this->sitebase()->translate('by');?>
                <?= $submission_data['user_id'] > 0 ? $submission_data['user']['nickname'] . ' ( ID:'.$submission_data['user_id'].')' : 'guest';?>
            </div>
        </div>
        <div class="card-block p-2">
            <?php foreach ($submission_data['values'] as $key => $value) :?>
            <div class="row">
                <div class="col-md-3 pt-1 pb-1"><strong><?= $key;?>:</strong></div>
                <div class="col-md-9 pt-1 pb-1"><?= $value;?></div>
            </div>
            <?php endforeach; ?>   
        </div>
    </div> 
<?php else : ?>
    <?= $form; ?>
<?php endif; ?>
