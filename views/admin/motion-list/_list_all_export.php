<?php

/**
 * @var \app\controllers\Base $controller
 * @var Yii\web\View $this
 * @var bool $hasProposedProcedures
 * @var bool $hasResponsibilities
 */

use app\components\UrlHelper;
use app\models\settings\AntragsgruenApp;
use yii\helpers\Html;

$controller   = $this->context;
$consultation = $controller->consultation;

$layout = $controller->layoutParams;


$getExportLinkLi = function ($title, $route, $motionTypeId, $cssClass) {
    $params     = array_merge($route, ['motionTypeId' => $motionTypeId, 'withdrawn' => '0']);
    $paramsTmpl = array_merge($route, ['motionTypeId' => $motionTypeId, 'withdrawn' => 'WITHDRAWN']);
    if ($route[0] === 'amendment/pdfcollection') {
        $params['filename']     = Yii::t('con', 'feed_amendments') . '.pdf';
        $paramsTmpl['filename'] = Yii::t('con', 'feed_amendments') . '.pdf';
    } elseif ($route[0] === 'motion/pdfcollection') {
        $params['filename']     = Yii::t('admin', 'index_pdf_collection') . '.pdf';
        $paramsTmpl['filename'] = Yii::t('admin', 'index_pdf_collection') . '.pdf';
    }
    $link    = UrlHelper::createUrl($params);
    $linkTpl = UrlHelper::createUrl($paramsTmpl);
    if ($motionTypeId) {
        $cssClass .= $motionTypeId;
    }
    $attrs = ['class' => $cssClass, 'data-href-tpl' => $linkTpl];

    return '<li class="exportLink">' . Html::a($title, $link, $attrs) . '</li>';
};

$creatableMotions = [];
foreach ($consultation->motionTypes as $motionType) {
    $motionp = $motionType->getMotionPolicy();
    if ($motionp->checkCurrUserMotion()) {
        $creatableMotions[] = $motionType;
    }
}

$btnNew       = count($creatableMotions) > 0;
$btnFunctions = $consultation->havePrivilege(\app\models\db\User::PRIVILEGE_CONSULTATION_SETTINGS) && (!$hasResponsibilities || !$hasProposedProcedures);

?>
<section class="motionListExportRow toolbarBelowTitle">
    <?php
    if ($btnNew || $btnFunctions) {
        echo '<div class="new">';
    }
    if ($btnNew) {
        ?>
        <div class="dropdown dropdown-menu-left exportAmendmentDd">
            <button class="btn btn-success dropdown-toggle" type="button" id="newMotionBtn"
                    data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
                <span class="glyphicon glyphicon-plus-sign" aria-hidden="true"></span>
                <?= Yii::t('admin', 'list_new') ?>
                <span class="caret"></span>
            </button>
            <ul class="dropdown-menu" aria-labelledby="newMotionBtn">
                <?php
                foreach ($creatableMotions as $motionType) {
                    $createUrl = UrlHelper::createUrl(['motion/create', 'motionTypeId' => $motionType->id]);
                    $cssClass  = 'createMotion' . $motionType->id;
                    $title     = Html::encode($motionType->titleSingular);
                    echo '<li>' . Html::a($title, $createUrl, ['class' => $cssClass]) . '</li>';
                }
                ?>
            </ul>
        </div>
        <?php
    }
    if ($btnFunctions) {
        ?>
        <div class="dropdown dropdown-menu-left exportAmendmentDd">
            <button class="btn btn-default dropdown-toggle" type="button" id="activateFncBtn"
                    data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
                <?= Yii::t('admin', 'list_functions') ?>
                <span class="caret"></span>
            </button>
            <ul class="dropdown-menu" aria-labelledby="activateFncBtn">
                <?php
                if (!$hasResponsibilities) {
                    $url   = UrlHelper::createUrl(['/admin/motion-list/index', 'activate' => 'responsibilities']);
                    $title = Yii::t('admin', 'list_functions_responsib');
                    echo '<li>' . Html::a($title, $url, ['class' => 'activateResponsibilities']) . '</li>';
                }
                if (!$hasProposedProcedures) {
                    $url   = UrlHelper::createUrl(['/admin/motion-list/index', 'activate' => 'procedure']);
                    $title = Yii::t('admin', 'list_functions_procedure');
                    echo '<li>' . Html::a($title, $url, ['class' => 'activateProcedure']) . '</li>';
                }
                ?>
            </ul>
        </div>
        <?php
    }
    if ($btnNew || $btnFunctions) {
        echo '</div>';
    }
    ?>

    <div class="export">
        <span class="title">Export:</span>

        <?php
        foreach ($consultation->motionTypes as $motionType) { ?>
            <div class="dropdown dropdown-menu-left exportMotionDd">
                <button class="btn btn-default dropdown-toggle" type="button" id="exportMotionBtn<?= $motionType->id ?>"
                        data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
                    <?= Html::encode($motionType->titlePlural) ?>
                    <span class="caret"></span>
                </button>
                <ul class="dropdown-menu" aria-labelledby="exportMotionBtn<?= $motionType->id ?>">
                    <li class="checkbox"><label>
                            <input type="checkbox" class="withdrawn" name="withdrawn">
                            <?= Yii::t('export', 'incl_inactive') ?>
                        </label></li>
                    <li role="separator" class="divider"></li>
                    <?php
                    $title = Yii::t('admin', 'index_export_ods');
                    echo $getExportLinkLi($title, ['admin/motion-list/motion-odslist'], $motionType->id, 'motionODS');

                    if ($controller->getParams()->xelatexPath || $controller->getParams()->lualatexPath) {
                        $title = Yii::t('admin', 'index_pdf_collection');
                        echo $getExportLinkLi($title, ['motion/pdfcollection'], $motionType->id, 'motionPDF');
                    }

                    if ($controller->getParams()->xelatexPath || $controller->getParams()->lualatexPath) {
                        $title = Yii::t('admin', 'index_pdf_zip_list');
                        $path  = ['admin/motion-list/motion-pdfziplist'];
                        echo $getExportLinkLi($title, $path, $motionType->id, 'motionZIP');
                    }

                    $title = Yii::t('admin', 'index_odt_zip_list');
                    $path  = ['admin/motion-list/motion-odtziplist'];
                    echo $getExportLinkLi($title, $path, $motionType->id, 'motionOdtZIP');

                    $title = Yii::t('admin', 'index_export_ods_listall');
                    $path  = ['admin/motion-list/motion-odslistall'];
                    echo $getExportLinkLi($title, $path, $motionType->id, 'motionODSlist');

                    if (AntragsgruenApp::hasPhpExcel()) {
                        $title = Yii::t('admin', 'index_export_excel') .
                                 ' <span class="errorProne">(' . Yii::t('admin', 'index_error_prone') . ')</span>';
                        $path  = ['admin/motion-list/motion-excellist'];
                        echo $getExportLinkLi($title, $path, $motionType->id, 'motionExcel');
                    }
                    ?>
                </ul>
            </div>
            <?php
        } ?>

        <div class="dropdown dropdown-menu-left exportAmendmentDd">
            <button class="btn btn-default dropdown-toggle" type="button" id="exportAmendmentsBtn"
                    data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
                <?= Yii::t('export', 'btn_amendments') ?>
                <span class="caret"></span>
            </button>
            <ul class="dropdown-menu" aria-labelledby="exportAmendmentsBtn">
                <li class="checkbox"><label>
                        <input type="checkbox" class="withdrawn" name="withdrawn">
                        <?= Yii::t('export', 'incl_inactive') ?>
                    </label></li>
                <li role="separator" class="divider"></li>
                <?php

                $title = Yii::t('admin', 'index_export_ods');
                echo $getExportLinkLi($title, ['admin/amendment/odslist'], null, 'amendmentOds');

                $title = Yii::t('admin', 'index_export_ods_short');
                $path  = ['admin/amendment/odslist-short', 'maxLen' => 2000, 'textCombined' => 1];
                echo $getExportLinkLi($title, $path, null, 'amendmentOdsShort');

                $title = Yii::t('admin', 'index_pdf_collection');
                echo $getExportLinkLi($title, ['amendment/pdfcollection'], null, 'amendmentPDF');

                $title = Yii::t('admin', 'index_pdf_list');
                echo $getExportLinkLi($title, ['admin/amendment/pdflist'], null, 'amendmentPdfList');

                if ($controller->getParams()->xelatexPath || $controller->getParams()->lualatexPath) {
                    $title = Yii::t('admin', 'index_pdf_zip_list');
                    echo $getExportLinkLi($title, ['admin/amendment/pdfziplist'], null, 'amendmentPdfZipList');
                }

                $title = Yii::t('admin', 'index_odt_zip_list');
                echo $getExportLinkLi($title, ['admin/amendment/odtziplist'], null, 'amendmentOdtZipList');
                ?>
            </ul>
        </div>

        <div class="dropdown dropdown-menu-left exportOpenslidesDd">
            <button class="btn btn-default dropdown-toggle" type="button" id="exportOpenslidesBtn"
                    data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
                <?= Yii::t('export', 'btn_openslides') ?>
                <span class="caret"></span>
            </button>
            <ul class="dropdown-menu dropdown-menu-right" aria-labelledby="exportOpenslidesBtn">
                <!--
                <li><?php
                $add       = '<br><small>' . Yii::t('admin', 'index_export_oslides_usersh') . '</small>';
                $title     = 'V1: ' . Yii::t('admin', 'index_export_oslides_users') . $add;
                $usersLink = UrlHelper::createUrl(['admin/index/openslidesusers', 'version' => '1']);
                echo Html::a($title, $usersLink, ['class' => 'users']);
                ?></li>
                <?php
                foreach ($consultation->motionTypes as $motionType) {
                    $motionTypeUrl = UrlHelper::createUrl(
                        ['admin/motion-list/motion-openslides', 'motionTypeId' => $motionType->id]
                    );
                    $title         = 'V1: ' . Html::encode($motionType->titlePlural);
                    echo '<li>' .
                         Html::a($title, $motionTypeUrl, ['class' => 'slidesMotionType' . $motionType->id]) .
                         '</li>';
                } ?>
                <li><?php
                $title     = 'V1: ' . Yii::t('admin', 'index_export_oslides_amend');
                $amendLink = UrlHelper::createUrl(['admin/amendment/openslides', 'version' => '1']);
                echo Html::a($title, $amendLink, ['class' => 'amendments']);
                ?></li>
                    -->
                <li>
                    <?php
                    $add       = '<br><small>' . Yii::t('admin', 'index_export_oslides_usersh') . '</small>';
                    $title     = Yii::t('admin', 'index_export_oslides_users') . $add;
                    $usersLink = UrlHelper::createUrl(['admin/index/openslidesusers', 'version' => '2']);
                    echo Html::a($title, $usersLink, ['class' => 'users']);
                    ?>
                </li>
                <?php
                foreach ($consultation->motionTypes as $motionType) {
                    $motionTypeUrl = UrlHelper::createUrl(
                        ['admin/motion-list/motion-openslides', 'motionTypeId' => $motionType->id, 'version' => '2']
                    );
                    $title         = Html::encode($motionType->titlePlural);
                    echo '<li>' .
                         Html::a($title, $motionTypeUrl, ['class' => 'slidesMotionType' . $motionType->id]) .
                         '</li>';
                } ?>
                <li>
                    <?php
                    $title     = Yii::t('admin', 'index_export_oslides_amend');
                    $amendLink = UrlHelper::createUrl(['admin/amendment/openslides', 'version' => '2']);
                    echo Html::a($title, $amendLink, ['class' => 'amendments']);
                    ?>
                </li>
            </ul>
        </div>
        <?php
        if ($hasProposedProcedures) {
            echo $this->render('../proposed-procedure/_switch_dropdown');
        }
        ?>
    </div>
</section>

