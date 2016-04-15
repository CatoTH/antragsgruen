<?php

/** @var \app\controllers\Base $controller */
use app\components\UrlHelper;
use yii\helpers\Html;

$controller   = $this->context;
$consultation = $controller->consultation;

$layout = $controller->layoutParams;
$layout->addOnLoadJS('jQuery.AntragsgruenAdmin.exportRowInit();');


$getExportLinkLi = function ($title, $route, $motionTypeId, $cssClass) {
    $link    = UrlHelper::createUrl([$route, 'motionTypeId' => $motionTypeId, 'withdrawn' => '0']);
    $linkTpl = UrlHelper::createUrl([$route, 'motionTypeId' => $motionTypeId, 'withdrawn' => 'WITHDRAWN']);
    if ($motionTypeId) {
        $cssClass .= $motionTypeId;
    }
    $attrs   = ['class' => $cssClass, 'data-href-tpl' => $linkTpl];
    return '<li class="exportLink">' . Html::a($title, $link, $attrs) . '</li>';
}

?>
<div class="motionListExportRow">
    <span class="title pull-left">Export:</span>

    <?php
    $amendLink = UrlHelper::createUrl('admin/amendment/openslides');
    $usersLink = UrlHelper::createUrl('admin/index/openslidesusers');
    ?>
    <div class="dropdown dropdown-menu-left exportOpenslidesDd pull-right">
        <button class="btn btn-default dropdown-toggle" type="button" id="exportOpenslidesBtn"
                data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
            <?= \Yii::t('export', 'btn_openslides') ?>
            <span class="caret"></span>
        </button>
        <ul class="dropdown-menu" aria-labelledby="exportOpenslidesBtn">
            <li>
                <?php
                $add = '<br><small>' . \Yii::t('admin', 'index_export_oslides_usersh') . '</small>';
                echo Html::a(\Yii::t('admin', 'index_export_oslides_users') . $add, $usersLink, ['class' => 'users']);
                ?></li>
            <?php
            foreach ($consultation->motionTypes as $motionType) {
                $motionTypeUrl = UrlHelper::createUrl(['admin/motion/openslides', 'motionTypeId' => $motionType->id]);
                $title         = $motionType->titlePlural;
                echo '<li>' .
                    Html::a($title, $motionTypeUrl, ['class' => 'slidesMotionType' . $motionType->id]) .
                    '</li>';
            } ?>
            <li>
                <?= Html::a(\Yii::t('admin', 'index_export_oslides_amend'), $amendLink, ['class' => 'amendments']) ?>
            </li>
        </ul>
    </div>

    <div class="dropdown dropdown-menu-left exportAmendmentDd pull-right">
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
    <?php
    for ($i = count($consultation->motionTypes) - 1; $i >= 0; $i--) {
        $motionType = $consultation->motionTypes[$i];
        ?>
        <div class="dropdown dropdown-menu-left exportMotionDd pull-right">
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
                $title  = \Yii::t('admin', 'index_export_ods');
                echo $getExportLinkLi($title, 'admin/motion/odslist', $motionType->id, 'motionODS');

                if ($controller->getParams()->xelatexPath) {
                    $title  = \Yii::t('admin', 'index_pdf_collection');
                    echo $getExportLinkLi($title, 'motion/pdfcollection', $motionType->id, 'motionPDF');
                }

                if ($controller->getParams()->xelatexPath) {
                    $title  = \Yii::t('admin', 'index_pdf_zip_list');
                    echo $getExportLinkLi($title, 'admin/motion/pdfziplist', $motionType->id, 'motionZIP');
                }

                $title  = \Yii::t('admin', 'index_odt_zip_list');
                echo $getExportLinkLi($title, 'admin/motion/odtziplist', $motionType->id, 'motionOdtZIP');

                $title    = \Yii::t('admin', 'index_export_excel') .
                    ' <span class="errorProne">(' . \Yii::t('admin', 'index_error_prone') . ')</span>';
                echo $getExportLinkLi($title, 'admin/motion/excellist', $motionType->id, 'motionExcel');
                ?>
            </ul>
        </div>
        <?php
    }
    ?>
</div>

