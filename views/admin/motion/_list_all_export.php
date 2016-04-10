<?php

/** @var \app\controllers\Base $controller */
use app\components\UrlHelper;
use yii\helpers\Html;

$controller   = $this->context;
$consultation = $controller->consultation;

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
            Openslides
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
            Änderungsanträge
            <span class="caret"></span>
        </button>
        <ul class="dropdown-menu" aria-labelledby="exportAmendmentsBtn">
            <li><label>
                    <input type="checkbox" class="withdrawn"> Zurückgezogene
                </label></li>
            <li role="separator" class="divider"></li>
            <?php
            $amendmentOdsLink  = UrlHelper::createUrl('admin/amendment/odslist');
            $amendmentPDFLink  = UrlHelper::createUrl('admin/amendment/pdflist');
            $pdfCollectionLink = UrlHelper::createUrl('amendment/pdfcollection');

            if ($controller->getParams()->xelatexPath) {
                $amendmentPDFZIPLink = UrlHelper::createUrl('admin/amendment/pdfziplist');
                $ttle                = \Yii::t('admin', 'index_pdf_zip_list');
                echo '<li>' .
                    Html::a($title, $amendmentPDFZIPLink, ['class' => 'amendmentPdfZipList']) . '
    </li>';
            }
            $amendmentODTZIPLink = UrlHelper::createUrl('admin/amendment/odtziplist');
            $title               = \Yii::t('admin', 'index_odt_zip_list');
            echo '<li>' .
                Html::a($title, $amendmentODTZIPLink, ['class' => 'amendmentOdtZipList']) . '
    </li>';
            echo '<li>' .
                Html::a(\Yii::t('admin', 'index_export_ods'), $amendmentOdsLink, ['class' => 'amendmentOds']) .
                '</li>';

            $title = \Yii::t('admin', 'index_pdf_list');
            echo '<li>' . Html::a($title, $amendmentPDFLink, ['class' => 'amendmentPdfList']) . '</li>';
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
                <li><label>
                        <input type="checkbox" class="withdrawn"> Zurückgezogene
                    </label></li>
                <li role="separator" class="divider"></li>
                <?php
                if ($controller->getParams()->xelatexPath) {
                    $zipURL = UrlHelper::createUrl(['motion/pdfcollection', 'motionTypeId' => $motionType->id]);
                    $title  = \Yii::t('admin', 'index_pdf_collection');
                    echo '<li>';
                    echo Html::a($title, $zipURL, ['class' => 'motionPDF' . $motionType->id]);
                    echo '</li>';
                }

                if ($controller->getParams()->xelatexPath) {
                    $zipURL = UrlHelper::createUrl(['admin/motion/pdfziplist', 'motionTypeId' => $motionType->id]);
                    $title  = \Yii::t('admin', 'index_pdf_zip_list');
                    echo '<li>';
                    echo Html::a($title, $zipURL, ['class' => 'motionZIP' . $motionType->id]);
                    echo '</li>';
                }

                $zipURL = UrlHelper::createUrl(['admin/motion/odtziplist', 'motionTypeId' => $motionType->id]);
                $title  = \Yii::t('admin', 'index_odt_zip_list');
                echo '<li>';
                echo Html::a($title, $zipURL, ['class' => 'motionOdtZIP' . $motionType->id]);
                echo '</li>';

                $odsUrl = UrlHelper::createUrl(['admin/motion/odslist', 'motionTypeId' => $motionType->id]);
                $title  = \Yii::t('admin', 'index_export_ods');
                echo '<li>';
                echo Html::a($title, $odsUrl, ['class' => 'motionODS' . $motionType->id]) . '</li>';

                $excelUrl = UrlHelper::createUrl(['admin/motion/excellist', 'motionTypeId' => $motionType->id]);
                $title    = \Yii::t('admin', 'index_export_excel') .
                    ' <span class="errorProne">(' . \Yii::t('admin', 'index_error_prone') . ')</span>';
                echo '<li>' . Html::a($title, $excelUrl, ['class' => 'motionExcel' . $motionType->id]) . '</li>';
                ?>
            </ul>
        </div>
        <?php
    }
    ?>
</div>

