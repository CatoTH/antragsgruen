<?php

use app\components\UrlHelper;
use app\models\settings\Privileges;
use yii\helpers\Html;

/** @var \app\controllers\Base $controller */
$controller   = $this->context;
$consultation = $controller->consultation;

$hasResponsibilities   = false;
$hasProposedProcedures = false;
foreach ($consultation->motionTypes as $motionType) {
    if ($motionType->getSettingsObj()->hasResponsibilities) {
        $hasResponsibilities = true;
    }
    if ($motionType->getSettingsObj()->hasProposedProcedure) {
        $hasProposedProcedures = true;
    }
}
$btnFunctions = $consultation->havePrivilege(Privileges::PRIVILEGE_CONSULTATION_SETTINGS, null) && (!$hasResponsibilities || !$hasProposedProcedures);

if ($btnFunctions) {
    ?>
    <div class="dropdown dropdown-menu-left">
        <button class="btn btn-default dropdown-toggle" type="button" id="activateFncBtn"
                data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
            <?= Yii::t('admin', 'list_functions') ?>
            <span class="caret"></span>
        </button>
        <ul class="dropdown-menu" aria-labelledby="activateFncBtn">
            <?php
            if (!$hasResponsibilities) {
                $url   = UrlHelper::createUrl(['/admin/proposed-procedure/index', 'activate' => 'responsibilities']);
                $title = Yii::t('admin', 'list_functions_responsib');
                echo '<li>' . Html::a($title, $url, ['class' => 'activateResponsibilities']) . '</li>';
            }
            if (!$hasProposedProcedures) {
                $url   = UrlHelper::createUrl(['/admin/proposed-procedure/index', 'activate' => 'procedure']);
                $title = Yii::t('admin', 'list_functions_procedure');
                echo '<li>' . Html::a($title, $url, ['class' => 'activateProcedure']) . '</li>';
            }
            ?>
        </ul>
    </div>
    <?php
}
