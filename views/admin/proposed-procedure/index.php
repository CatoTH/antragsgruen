<?php

use app\components\UrlHelper;
use app\models\proposedProcedure\Agenda;
use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var Agenda[] $proposedAgenda
 * @var bool $expandAll
 * @var null|string $expandId
 */

/** @var \app\controllers\ConsultationController $controller */
$controller         = $this->context;
$layout             = $controller->layoutParams;
$layout->fullWidth  = true;
$layout->fullScreen = true;

$this->title = \Yii::t('con', 'proposal_title_internal');
$layout->addBreadcrumb(\Yii::t('admin', 'bread_list'), \app\components\UrlHelper::createUrl('admin/motion-list'));
$layout->addBreadcrumb(\Yii::t('con', 'proposal_bc'));
$layout->loadBootstrapToggle();
$layout->addCSS('css/backend.css');

echo '<h1>' . Html::encode($this->title) . '</h1>';

$reloadOptions = ['admin/proposed-procedure/index-ajax'];
if ($expandId) {
    $reloadOptions['expandId'] = $expandId;
}
$reloadUrl = UrlHelper::createUrl($reloadOptions);
echo Html::beginForm('', 'post', [
    'class'                    => 'proposedProcedureReloadHolder',
    'data-antragsgruen-widget' => 'backend/ProposedProcedureOverview',
    'data-reload-url'          => $reloadUrl,
]);
?>
    <section class="proposedProcedureToolbar toolbarBelowTitle fuelux">
        <div class="left">
            <div class="currentDate">
                <?= \Yii::t('con', 'proposal_updated') ?>:
                <span class="date"><?= date('H:i:s') ?></span>
            </div>
        </div>
        <div class="right">
            <?= $this->render('_switch_dropdown') ?>
            <div class="autoUpdateWidget">
                <label class="sr-only" for="autoUpdateToggle"></label>
                <input type="checkbox" id="autoUpdateToggle"
                       data-onstyle="success" data-size="normal" data-toggle="toggle"
                       data-on="<?= Html::encode(\Yii::t('con', 'proposal_autoupdate')) ?>"
                       data-off="<?= Html::encode(\Yii::t('con', 'proposal_autoupdate')) ?>">
            </div>
            <div class="fullscreenToggle">
                <button class="btn btn-default" type="button" data-antragsgruen-widget="frontend/FullscreenToggle">
                    <span class="glyphicon glyphicon-fullscreen"></span>
                </button>
            </div>
        </div>
    </section>
    <div class="reloadContent">
        <?= $this->render('_index_content', [
            'proposedAgenda' => $proposedAgenda,
            'expandAll'      => $expandAll,
            'expandId'       => $expandId,
        ]) ?>
    </div>
<?php
echo Html::endForm();
