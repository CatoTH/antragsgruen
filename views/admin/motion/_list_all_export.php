<?php

/** @var \app\controllers\Base $controller */
use app\components\UrlHelper;
use app\models\settings\AntragsgruenApp;
use yii\helpers\Html;

$controller   = $this->context;
$consultation = $controller->consultation;

$layout = $controller->layoutParams;


$getExportLinkLi = function ($title, $route, $motionTypeId, $cssClass) {
    $link    = UrlHelper::createUrl([$route, 'motionTypeId' => $motionTypeId, 'withdrawn' => '0']);
    $linkTpl = UrlHelper::createUrl([$route, 'motionTypeId' => $motionTypeId, 'withdrawn' => 'WITHDRAWN']);
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

?>
<div class="motionListExportRow">
    <?php if (count($creatableMotions) > 0) { ?>
        <div class="new">
            <div class="dropdown dropdown-menu-left exportAmendmentDd">
                <button class="btn btn-success dropdown-toggle" type="button" id="newMotionBtn"
                        data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
                    <span class="glyphicon glyphicon-plus-sign"></span>
                    <?= \Yii::t('admin', 'list_new') ?>
                    <span class="caret"></span>
                </button>
                <ul class="dropdown-menu" aria-labelledby="newMotionBtn">
                    <?php
                    foreach ($creatableMotions as $motionType) {
                        $createUrl = UrlHelper::createUrl(['motion/create', 'motionTypeId' => $motionType->id]);
                        $cssClass  = 'createMotion' . $motionType->id;
                        echo '<li>' . Html::a($motionType->titleSingular, $createUrl, ['class' => $cssClass]) . '</li>';
                    }
                    ?>
                </ul>
            </div>
        </div>
    <?php } ?>

    <div class="export">
        <span class="title">Export:</span>

        <?php foreach ($consultation->motionTypes as $motionType) { ?>
            <div class="dropdown dropdown-menu-left exportMotionDd">
                <button class="btn btn-default dropdown-toggle" type="button" id="exportMotionBtn<?= $motionType->id ?>"
                        data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
                    <?= Html::encode($motionType->titlePlural) ?>
                    <span class="caret"></span>
                </button>
                <ul class="dropdown-menu" aria-labelledby="exportMotionBtn<?= $motionType->id ?>">
                    <li class="checkbox"><label>
                            <input type="checkbox" class="withdrawn" name="withdrawn">
                            <?= \Yii::t('export', 'incl_withdrawn') ?>
                        </label></li>
                    <li role="separator" class="divider"></li>
                    <?php
                    $title = \Yii::t('admin', 'index_export_ods');
                    echo $getExportLinkLi($title, 'admin/motion/odslist', $motionType->id, 'motionODS');

                    if ($controller->getParams()->xelatexPath) {
                        $title = \Yii::t('admin', 'index_pdf_collection');
                        echo $getExportLinkLi($title, 'motion/pdfcollection', $motionType->id, 'motionPDF');
                    }

                    if ($controller->getParams()->xelatexPath) {
                        $title = \Yii::t('admin', 'index_pdf_zip_list');
                        echo $getExportLinkLi($title, 'admin/motion/pdfziplist', $motionType->id, 'motionZIP');
                    }

                    $title = \Yii::t('admin', 'index_odt_zip_list');
                    echo $getExportLinkLi($title, 'admin/motion/odtziplist', $motionType->id, 'motionOdtZIP');

                    $title = \Yii::t('admin', 'index_export_ods_listall');
                    echo $getExportLinkLi($title, 'admin/motion/odslistall', $motionType->id, 'motionODSlist');

                    if (AntragsgruenApp::hasPhpExcel()) {
                        $title = \Yii::t('admin', 'index_export_excel') .
                            ' <span class="errorProne">(' . \Yii::t('admin', 'index_error_prone') . ')</span>';
                        echo $getExportLinkLi($title, 'admin/motion/excellist', $motionType->id, 'motionExcel');
                    }
                    ?>
                </ul>
            </div>
        <?php } ?>

        <div class="dropdown dropdown-menu-left exportAmendmentDd">
            <button class="btn btn-default dropdown-toggle" type="button" id="exportAmendmentsBtn"
                    data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
                <?= \Yii::t('export', 'btn_amendments') ?>
                <span class="caret"></span>
            </button>
            <ul class="dropdown-menu" aria-labelledby="exportAmendmentsBtn">
                <li class="checkbox"><label>
                        <input type="checkbox" class="withdrawn" name="withdrawn">
                        <?= \Yii::t('export', 'incl_withdrawn') ?>
                    </label></li>
                <li role="separator" class="divider"></li>
                <?php

                $title = Yii::t('admin', 'index_export_ods');
                echo $getExportLinkLi($title, 'admin/amendment/odslist', null, 'amendmentOds');

                $title = \Yii::t('admin', 'index_pdf_collection');
                echo $getExportLinkLi($title, 'amendment/pdfcollection', null, 'amendmentPDF');

                $title = \Yii::t('admin', 'index_pdf_list');
                echo $getExportLinkLi($title, 'admin/amendment/pdflist', null, 'amendmentPdfList');

                if ($controller->getParams()->xelatexPath) {
                    $title = \Yii::t('admin', 'index_pdf_zip_list');
                    echo $getExportLinkLi($title, 'admin/amendment/pdfziplist', null, 'amendmentPdfZipList');
                }

                $title = \Yii::t('admin', 'index_odt_zip_list');
                echo $getExportLinkLi($title, 'admin/amendment/odtziplist', null, 'amendmentOdtZipList');
                ?>
            </ul>
        </div>

        <div class="dropdown dropdown-menu-left exportOpenslidesDd">
            <button class="btn btn-default dropdown-toggle" type="button" id="exportOpenslidesBtn"
                    data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
                <?= \Yii::t('export', 'btn_openslides') ?>
                <span class="caret"></span>
            </button>
            <ul class="dropdown-menu dropdown-menu-right" aria-labelledby="exportOpenslidesBtn">
                <li><?php
                    $add       = '<br><small>' . \Yii::t('admin', 'index_export_oslides_usersh') . '</small>';
                    $title     = 'V1: ' . \Yii::t('admin', 'index_export_oslides_users') . $add;
                    $usersLink = UrlHelper::createUrl(['admin/index/openslidesusers', 'version' => '1']);
                    echo Html::a($title, $usersLink, ['class' => 'users']);
                    ?></li>
                <?php
                foreach ($consultation->motionTypes as $motionType) {
                    $motionTypeUrl = UrlHelper::createUrl(
                        ['admin/motion/openslides', 'motionTypeId' => $motionType->id]
                    );
                    $title         = 'V1: ' . $motionType->titlePlural;
                    echo '<li>' .
                        Html::a($title, $motionTypeUrl, ['class' => 'slidesMotionType' . $motionType->id]) .
                        '</li>';
                } ?>
                <li><?php
                    $title     = 'V1: ' . \Yii::t('admin', 'index_export_oslides_amend');
                    $amendLink = UrlHelper::createUrl(['admin/amendment/openslides', 'version' => '1']);
                    echo Html::a($title, $amendLink, ['class' => 'amendments']);
                    ?></li>

                <li><?php
                    $add       = '<br><small>' . \Yii::t('admin', 'index_export_oslides_usersh') . '</small>';
                    $title     = 'V2: ' . \Yii::t('admin', 'index_export_oslides_users') . $add;
                    $usersLink = UrlHelper::createUrl(['admin/index/openslidesusers', 'version' => '2']);
                    echo Html::a($title, $usersLink, ['class' => 'users']);
                    ?></li>
                <?php
                foreach ($consultation->motionTypes as $motionType) {
                    $motionTypeUrl = UrlHelper::createUrl(
                        ['admin/motion/openslides', 'motionTypeId' => $motionType->id, 'version' => '2']
                    );
                    $title         = 'V2: ' . $motionType->titlePlural;
                    echo '<li>' .
                        Html::a($title, $motionTypeUrl, ['class' => 'slidesMotionType' . $motionType->id]) .
                        '</li>';
                } ?>
                <li><?php
                    $title = 'V2: ' . \Yii::t('admin', 'index_export_oslides_amend');
                    $amendLink = UrlHelper::createUrl(['admin/amendment/openslides', 'version' => '2']);
                    echo Html::a($title, $amendLink, ['class' => 'amendments']);
                    ?></li>
            </ul>
        </div>
    </div>
</div>

