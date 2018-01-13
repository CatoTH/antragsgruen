<?php

use app\components\UrlHelper;
use app\models\db\ConsultationAgendaItem;
use yii\helpers\Html;

/**
 * @var \app\controllers\Base $controller
 * @var \yii\web\View $this
 */

$controller   = $this->context;
$consultation = $controller->consultation;

?>
<div class="dropdown dropdown-menu-left exportProcedureDd">
    <button class="btn btn-default dropdown-toggle" type="button" id="exportProcedureBtn"
            data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
        <?= \Yii::t('admin', 'index_export_procedure') ?>
        <span class="caret"></span>
    </button>
    <ul class="dropdown-menu" aria-labelledby="exportProcedureBtn">
        <li class="exportLink"><?php
            $url = UrlHelper::createUrl('admin/proposed-procedure/index');
            echo Html::a(\Yii::t('export', 'pp_admin_site'), $url);
            ?></li>
        <li class="exportLink"><?php
            $url = UrlHelper::createUrl('consultation/proposed-procedure');
            echo Html::a(\Yii::t('export', 'pp_public_site'), $url);
            ?></li>
        <li class="exportLink"><?php
            $url = UrlHelper::createUrl('admin/proposed-procedure/ods');
            echo Html::a(Yii::t('export', 'pp_ods_all'), $url);
            ?></li>
        <?php
        if (count($consultation->agendaItems) > 0) {
            foreach (ConsultationAgendaItem::getSortedFromConsultation($consultation) as $item) {
                if (count($item->getVisibleMotions(true)) === 0) {
                    continue;
                }
                ?>
                <li class="exportLink"><?php
                    $route = 'admin/proposed-procedure/ods';
                    $url   = UrlHelper::createUrl([$route, 'agendaItemId' => $item->id]);
                    echo Html::a('ODS: ' . $item->title, $url);
                    ?></li>
                <?php
            }
        }
        ?>
    </ul>
</div>
