<?php

use app\components\IMotionStatusFilter;
use app\components\UrlHelper;
use app\models\db\ConsultationAgendaItem;
use yii\helpers\Html;

/**
 * @var \app\controllers\Base $controller
 * @var Yii\web\View $this
 */

$controller   = $this->context;
$consultation = $controller->consultation;

?>
<div class="dropdown dropdown-menu-left exportProcedureDd"
     data-antragsgruen-widget="backend/ProposedProcedureExport">
    <button class="btn btn-default dropdown-toggle" type="button" id="exportProcedureBtn"
            data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
        <?= Yii::t('admin', 'index_export_procedure') ?>
        <span class="caret"></span>
    </button>
    <ul class="dropdown-menu dropdown-menu-right" aria-labelledby="exportProcedureBtn">
        <li class="exportLink linkProcedureIntern">
            <?php
            $url = UrlHelper::createUrl('admin/proposed-procedure/index');
            echo Html::a(Yii::t('export', 'pp_admin_site'), $url);
            ?>
        </li>
        <li class="exportLink linkProcedurePublic">
            <?php
            $url = UrlHelper::createUrl('consultation/proposed-procedure');
            echo Html::a(Yii::t('export', 'pp_public_site'), $url);
            ?>
        </li>
        <li role="separator" class="divider"></li>
        <li class="checkbox">
            <label>
                <input type="checkbox" class="c" name="comments">
                <?= Yii::t('export', 'pp_ods_comments') ?>
            </label>
        </li>
        <li class="checkbox">
            <label>
                <input type="checkbox" class="c" name="onlypublic">
                <?= Yii::t('export', 'pp_ods_public') ?>
            </label>
        </li>
        <li class="exportLink">
            <?php
            $url = UrlHelper::createUrl([
                'admin/proposed-procedure/ods',
                'comments'   => 'COMMENTS',
                'onlypublic' => 'ONLYPUBLIC',
            ]);
            echo Html::a(Yii::t('export', 'pp_ods_all'), $url, [
                'class'         => 'odsLink',
                'data-href-tpl' => $url,
            ]);
            ?>
        </li>
        <?php
        if (count($consultation->agendaItems) > 0) {
            foreach (ConsultationAgendaItem::getSortedFromConsultation($consultation) as $item) {
                if (count($item->getMyIMotions(IMotionStatusFilter::onlyUserVisible($consultation, true))) === 0) {
                    continue;
                }
                ?>
                <li class="exportLink">
                    <?php
                    $route = 'admin/proposed-procedure/ods';
                    $url   = UrlHelper::createUrl([
                        $route,
                        'agendaItemId' => $item->id,
                        'comments'     => 'COMMENTS',
                        'onlypublic'   => 'ONLYPUBLIC',
                    ]);
                    echo Html::a('ODS: ' . $item->title, $url, [
                        'class'         => 'odsLink',
                        'data-href-tpl' => $url,
                    ]);
                    ?>
                </li>
                <?php
            }
        }
        ?>
    </ul>
</div>
