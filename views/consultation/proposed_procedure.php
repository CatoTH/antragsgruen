<?php

use app\components\ProposedProcedureAgenda;
use app\models\db\Amendment;
use app\models\db\User;
use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var ProposedProcedureAgenda[] $proposedAgenda
 */

/** @var \app\controllers\ConsultationController $controller */
$controller        = $this->context;
$layout            = $controller->layoutParams;
$layout->fullWidth = true;
$layout->loadBootstrapToggle();

$this->title = \Yii::t('con', 'proposal_title');
$layout->addBreadcrumb(\Yii::t('con', 'proposal_bc'));

$iAmAdmin = User::havePrivilege($controller->consultation, User::PRIVILEGE_CHANGE_PROPOSALS);
$reloadUrl = \app\components\UrlHelper::createUrl('consultation/proposed-procedure-ajax');

echo '<h1>' . Html::encode($this->title) . '</h1>';

?>
<div class="proposedProcedureReloadHolder"
     data-antragsgruen-widget="frontend/ProposedProcedureOverview"
     data-reload-url="<?= Html::encode($reloadUrl) ?>">
    <section class="proposedProcedureToolbar toolbarBelowTitle fuelux">
        <div class="left">
            <div class="currentDate">
                <?= \Yii::t('con', 'proposal_updated') ?>:
                <span class="date"><?= date('H:i:s') ?></span>
            </div>
        </div>
        <div class="right">
            <?php
            if ($iAmAdmin) {
                echo $this->render('../admin/proposed-procedure/_switch_dropdown');
            }
            ?>
            <div class="autoUpdateWidget">
                <label class="sr-only" for="autoUpdateToggle"></label>
                <input type="checkbox" id="autoUpdateToggle"
                       data-onstyle="success" data-size="normal" data-toggle="toggle"
                       data-on="<?= Html::encode(\Yii::t('con', 'proposal_autoupdate')) ?>"
                       data-off="<?= Html::encode(\Yii::t('con', 'proposal_autoupdate')) ?>">
            </div>
        </div>
    </section>
    <div class="reloadContent">
        <?= $this->render('_proposed_procedure_content', ['proposedAgenda' => $proposedAgenda]) ?>
    </div>
</div>
