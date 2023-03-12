<?php

use app\models\settings\Privileges;
use app\models\db\User;
use app\models\proposedProcedure\Agenda;
use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var Agenda[] $proposedAgenda
 */

/** @var \app\controllers\ConsultationController $controller */
$controller        = $this->context;
$layout            = $controller->layoutParams;
$layout->fullWidth = true;
$layout->loadBootstrapToggle();

$this->title = Yii::t('con', 'proposal_title');
$layout->addBreadcrumb(Yii::t('con', 'proposal_bc'));

$iAmAdmin  = User::havePrivilege($controller->consultation, Privileges::PRIVILEGE_CHANGE_PROPOSALS, null);
$reloadUrl = \app\components\UrlHelper::createUrl('consultation/proposed-procedure-ajax');

echo '<h1>' . Html::encode($this->title) . '</h1>';

?>
<div class="proposedProcedureReloadHolder"
     data-antragsgruen-widget="frontend/ProposedProcedureOverview"
     data-reload-url="<?= Html::encode($reloadUrl) ?>">
    <?php
    if ($iAmAdmin) {
        ?>
        <section class="proposedProcedureToolbar toolbarBelowTitle">
            <div class="left">
                <div class="currentDate">
                    <?= Yii::t('con', 'proposal_updated') ?>:
                    <span class="date"><?= date('H:i:s') ?></span>
                </div>
            </div>
            <div class="right">
                <?= $this->render('../admin/proposed-procedure/_switch_dropdown') ?>
                <div class="autoUpdateWidget">
                    <label class="sr-only" for="autoUpdateToggle"></label>
                    <input type="checkbox" id="autoUpdateToggle"
                           data-onstyle="success" data-size="normal" data-toggle="toggle"
                           data-on="<?= Html::encode(Yii::t('con', 'proposal_autoupdate')) ?>"
                           data-off="<?= Html::encode(Yii::t('con', 'proposal_autoupdate')) ?>">
                </div>
                <div class="fullscreenToggle">
                    <button class="btn btn-default" type="button" data-antragsgruen-widget="frontend/FullscreenToggle">
                        <span class="glyphicon glyphicon-fullscreen"></span>
                    </button>
                </div>
            </div>
        </section>
        <?php
    }
    ?>
    <div class="reloadContent">
        <?= $this->render('_proposed_procedure_content', ['proposedAgenda' => $proposedAgenda]) ?>
    </div>
</div>
