<?php

use app\components\UrlHelper;
use app\models\proposedProcedure\Agenda;
use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var Agenda[] $proposedAgenda
 * @var bool $expandAll
 * @var null|string $expandId
 * @var null|int $tagId
 */

/** @var \app\controllers\ConsultationController $controller */
$controller         = $this->context;
$layout             = $controller->layoutParams;
$layout->fullWidth  = true;
$layout->fullScreen = true;
$consultation = $controller->consultation;

$this->title = Yii::t('con', 'proposal_title_internal');
$layout->addBreadcrumb(Yii::t('admin', 'bread_list'), UrlHelper::createUrl('/admin/motion-list/index'));
$layout->addBreadcrumb(Yii::t('con', 'proposal_bc'));
$layout->loadBootstrapToggle();
$layout->loadSelectize();
$layout->addCSS('css/backend.css');

echo '<h1>' . Html::encode($this->title) . '</h1>';

$reloadOptions = ['admin/proposed-procedure/index-ajax'];
if ($expandId) {
    $reloadOptions['expandId'] = $expandId;
}
if ($tagId) {
    $reloadOptions['tagId'] = $tagId;
}
$reloadUrl = UrlHelper::createUrl($reloadOptions);
echo Html::beginForm('', 'post', [
    'class'                    => 'proposedProcedureReloadHolder',
    'data-antragsgruen-widget' => 'backend/ProposedProcedureOverview',
    'data-reload-url'          => $reloadUrl,
]);
?>
    <section class="proposedProcedureToolbar toolbarBelowTitle">
        <div class="left">
            <div class="currentDate">
                <?= Yii::t('con', 'proposal_updated') ?>:
                <span class="date"><?= date('H:i:s') ?></span>
            </div>
        </div>
        <div class="right">
            <?= $this->render('_switch_dropdown') ?>
            <?= $this->render('_functions_dropdown') ?>
            <div class="autoUpdateWidget">
                <label class="sr-only" for="autoUpdateToggle"></label>
                <input type="checkbox" id="autoUpdateToggle"
                       data-onstyle="success" data-size="normal" data-toggle="toggle"
                       data-on="<?= Html::encode(Yii::t('con', 'proposal_autoupdate')) ?>"
                       data-off="<?= Html::encode(Yii::t('con', 'proposal_autoupdate')) ?>">
            </div>
            <div class="fullscreenToggle">
                <button class="btn btn-default" type="button" data-antragsgruen-widget="frontend/FullscreenToggle">
                    <span class="glyphicon glyphicon-fullscreen" title="Fullscreen" aria-label="Fullscreen"></span>
                </button>
            </div>
        </div>
    </section>

    <section class="motionListFilter content" id="motionListSorter" aria-labelledby="motionListSorterTitle">
        <?php
        $tags = $consultation->getSortedTags(\app\models\db\ConsultationSettingsTag::TYPE_PROPOSED_PROCEDURE);
        ?>
        <div>
            <div class="tagList">
                <?php
                $url = UrlHelper::createUrl(['admin/proposed-procedure/index']);
                $btn = ($tagId === null ? 'btn-info' : 'btn-defult');
                echo '<a href="' . Html::encode($url) . '" class="btn ' . $btn . ' btn-xs tagAll" data-filter="*">' . Yii::t('con', 'discuss_filter_all') . '</a>';

                foreach ($tags as $tag) {
                    $url = UrlHelper::createUrl(['admin/proposed-procedure/index', 'tagId' => $tag->id]);
                    $btn = ($tagId === $tag->id ? 'btn-info' : 'btn-defult');
                    echo '<a href="' . Html::encode($url) . '" class="btn ' . $btn . ' btn-xs tag' . $tag->id . '">';
                    echo Html::encode($tag->title) . '</span></a>';
                }
                ?>
            </div>
        </div>
    </section>

    <div class="reloadContent">
        <?= $controller->showErrors() ?>
        <?= $this->render('_index_content', [
            'proposedAgenda' => $proposedAgenda,
            'expandAll'      => $expandAll,
            'expandId'       => $expandId,
            'tagId'          => $tagId,
        ]) ?>
    </div>
<?php
echo Html::endForm();
