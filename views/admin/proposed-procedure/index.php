<?php

use app\components\ProposedProcedureAgenda;
use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var ProposedProcedureAgenda[] $proposedAgenda
 */

/** @var \app\controllers\ConsultationController $controller */
$controller         = $this->context;
$layout             = $controller->layoutParams;
$layout->fullWidth  = true;
$layout->fullScreen = true;

$this->title = \Yii::t('con', 'proposal_title');
$layout->addBreadcrumb(\Yii::t('admin', 'bread_list'), \app\components\UrlHelper::createUrl('admin/motion-list'));
$layout->addBreadcrumb(\Yii::t('con', 'proposal_bc'));

echo '<h1>' . Html::encode($this->title) . '</h1>';

echo Html::beginForm('', 'post', [
    'class'                    => 'proposedProcedureReloadHolder',
    'data-antragsgruen-widget' => 'backend/ProposedProcedureOverview',
]);
?>
    <section class="proposedProcedureToolbar toolbarBelowTitle fuelux">
        <div class="right">
            <?= $this->render('_switch_dropdown') ?>
        </div>
    </section>
    <div class="reloadContent">
        <?= $this->render('_index_content', ['proposedAgenda' => $proposedAgenda]) ?>
    </div>
<?php
echo Html::endForm();
