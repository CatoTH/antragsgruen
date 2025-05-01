<?php

/**
 * @var \app\controllers\Base $controller
 * @var Yii\web\View $this
 * @var AdminMotionFilterForm $search
 * @var bool $hasProposedProcedures
 * @var bool $hasResponsibilities
 */

use app\models\forms\AdminMotionFilterForm;
use app\models\settings\Privileges;
use app\components\{HTMLTools, UrlHelper};
use yii\helpers\Html;

$controller = $this->context;
$consultation = $controller->consultation;
$layout = $controller->layoutParams;

$hasOpenslides = $consultation->getSettings()->openslidesExportEnabled;
$hasInactiveFunctionality = (!$hasResponsibilities || !$hasProposedProcedures || !$hasOpenslides);

$getExportLinkLi = function ($title, $route, $motionTypeIds, $cssClass) use ($search) {
    $params     = array_merge($route, ['motionTypeId' => $motionTypeIds, 'inactive' => '0']);
    $paramsTmpl = array_merge($route, ['motionTypeId' => ($motionTypeIds ? 'MOTIONTYPES' : null), 'inactive' => 'INACTIVE']);
    if (!$search->isDefaultSettings()) {
        $params = array_merge($params, $search->getSearchUrlParams());
        $paramsTmpl = array_merge($paramsTmpl, $search->getSearchUrlParams());
    }
    if ($route[0] === 'amendment/pdfcollection') {
        $params['filename']     = Yii::t('con', 'feed_amendments') . '.pdf';
        $paramsTmpl['filename'] = Yii::t('con', 'feed_amendments') . '.pdf';
    } elseif ($route[0] === 'motion/pdfcollection') {
        $params['filename']     = Yii::t('admin', 'index_pdf_collection') . '.pdf';
        $paramsTmpl['filename'] = Yii::t('admin', 'index_pdf_collection') . '.pdf';
    }
    $link    = UrlHelper::createUrl($params);
    $linkTpl = UrlHelper::createUrl($paramsTmpl);
    if ($motionTypeIds) {
        $cssClass .= $motionTypeIds;
    }
    $attrs = ['class' => $cssClass, 'data-href-tpl' => $linkTpl];

    return '<li class="exportLink">' . HtmlTools::createExternalLink($title, $link, $attrs) . '</li>';
};

$creatableMotions = [];
foreach ($consultation->motionTypes as $motionType) {
    $motionp = $motionType->getMotionPolicy();
    if ($motionp->checkCurrUserMotion()) {
        $creatableMotions[] = $motionType;
    }
}

$btnNew       = count($creatableMotions) > 0;
$btnFunctions = $consultation->havePrivilege(Privileges::PRIVILEGE_CONSULTATION_SETTINGS, null) && $hasInactiveFunctionality;

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
                    $createUrl = $motionType->getCreateLink();
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
                <span class="caret" aria-hidden="true"></span>
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
                if (!$hasOpenslides) {
                    $url   = UrlHelper::createUrl(['/admin/motion-list/index', 'activate' => 'openslides']);
                    $title = Yii::t('admin', 'list_functions_openslides');
                    echo '<li>' . Html::a($title, $url, ['class' => 'activateOpenslides']) . '</li>';
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

        <div class="dropdown dropdown-menu-left exportMotionDd">
            <button class="btn btn-default dropdown-toggle" type="button" id="exportMotionBtn"
                    data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
                <?= Yii::t('export', 'motions') ?>
                <span class="caret" aria-hidden="true"></span>
            </button>
            <ul class="dropdown-menu" aria-labelledby="exportMotionBtn">
                <li class="checkbox"><label>
                        <input type="checkbox" class="inactive" name="inactive">
                        <?= Yii::t('export', 'incl_inactive') ?>
                    </label></li>
                <li role="separator" class="divider"></li>
                <?php
                foreach ($consultation->motionTypes as $motionType) {
                    ?>
                    <li class="checkbox motionTypeCheckbox"><label>
                            <input type="checkbox" class="motionType" name="motionType" value="<?= $motionType->id ?>" checked>
                            <?= Html::encode($motionType->titlePlural) ?>
                        </label></li>
                <?php
                }
                ?>
                <li role="separator" class="divider"></li>
                <?php

                $motionTypeIds = implode(",", array_map(fn($motionType) => $motionType->id, $consultation->motionTypes));

                $title = Yii::t('admin', 'index_export_ods');
                $title .= HTMLTools::getTooltipIcon(Yii::t('admin', 'index_export_ods_tt'));
                echo $getExportLinkLi($title, ['admin/motion-list/motion-odslist'], $motionTypeIds, 'motionODS');

                if ($controller->getParams()->weasyprintPath) {
                    $title = Yii::t('admin', 'index_pdf_collection');
                    echo $getExportLinkLi($title, ['motion/pdfcollection'], $motionTypeIds, 'motionPDF');
                }

                if ($controller->getParams()->weasyprintPath) {
                    $title = Yii::t('admin', 'index_pdf_zip_list');
                    $path  = ['admin/motion-list/motion-pdfziplist'];
                    echo $getExportLinkLi($title, $path, $motionTypeIds, 'motionZIP');
                }

                $title = Yii::t('admin', 'index_odt_zip_list');
                $path  = ['admin/motion-list/motion-odtziplist'];
                echo $getExportLinkLi($title, $path, $motionTypeIds, 'motionOdtZIP');

                $title = Yii::t('admin', 'index_odt_allmot');
                $path  = ['admin/motion-list/motion-odtall'];
                echo $getExportLinkLi($title, $path, $motionTypeIds, 'motionOdtAll');

                $title = Yii::t('admin', 'index_export_ods_listall');
                $path  = ['admin/motion-list/motion-odslistall'];
                echo $getExportLinkLi($title, $path, $motionTypeIds, 'motionODSlist');

                $title = Yii::t('admin', 'index_export_comments_xlsx');
                echo $getExportLinkLi($title, ['admin/motion-list/motion-comments-xlsx'], $motionTypeIds, 'motionCommentsXlsx');
                ?>
            </ul>
        </div>

        <div class="dropdown dropdown-menu-left exportAmendmentDd">
            <button class="btn btn-default dropdown-toggle" type="button" id="exportAmendmentsBtn"
                    data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
                <?= Yii::t('export', 'btn_amendments') ?>
                <span class="caret"></span>
            </button>
            <ul class="dropdown-menu" aria-labelledby="exportAmendmentsBtn">
                <li class="checkbox"><label>
                        <input type="checkbox" class="inactive" name="inactive">
                        <?= Yii::t('export', 'incl_inactive') ?>
                    </label></li>
                <li role="separator" class="divider"></li>
                <?php

                $title = Yii::t('admin', 'index_export_ods');
                $title .= HTMLTools::getTooltipIcon(Yii::t('admin', 'index_export_ods_tt'));
                echo $getExportLinkLi($title, ['admin/amendment/odslist'], null, 'amendmentOds');

                $title = Yii::t('admin', 'index_export_ods_short');
                $path  = ['admin/amendment/odslist-short', 'maxLen' => 2000, 'textCombined' => 1];
                echo $getExportLinkLi($title, $path, null, 'amendmentOdsShort');

                $title = Yii::t('admin', 'index_export_excel');
                $title .= HTMLTools::getTooltipIcon(Yii::t('admin', 'index_error_prone'));
                $path  = ['admin/amendment/xlsx-list'];
                echo $getExportLinkLi($title, $path, null, 'amendmentXlsx');

                $title = Yii::t('admin', 'index_pdf_collection');
                echo $getExportLinkLi($title, ['amendment/pdfcollection'], null, 'amendmentPDF');

                $title = Yii::t('admin', 'index_pdf_list');
                echo $getExportLinkLi($title, ['admin/amendment/pdflist'], null, 'amendmentPdfList');

                if ($controller->getParams()->weasyprintPath) {
                    $title = Yii::t('admin', 'index_pdf_zip_list');
                    echo $getExportLinkLi($title, ['admin/amendment/pdfziplist'], null, 'amendmentPdfZipList');
                }

                $title = Yii::t('admin', 'index_odt_zip_list');
                echo $getExportLinkLi($title, ['admin/amendment/odtziplist'], null, 'amendmentOdtZipList');
                ?>
            </ul>
        </div>

        <?php
        if ($consultation->getSettings()->openslidesExportEnabled) {
            ?>
            <div class="dropdown dropdown-menu-left exportOpenslidesDd">
                <button class="btn btn-default dropdown-toggle" type="button" id="exportOpenslidesBtn"
                        data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
                    <?= Yii::t('export', 'btn_openslides') ?>
                    <span class="caret"></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-right" aria-labelledby="exportOpenslidesBtn">
                    <!--
                <li><?php
                    $add = '<br><small>' . Yii::t('admin', 'index_export_oslides_usersh') . '</small>';
                    $title = 'V1: ' . Yii::t('admin', 'index_export_oslides_users') . $add;
                    $usersLink = UrlHelper::createUrl(['admin/index/openslidesusers', 'version' => '1']);
                    echo Html::a($title, $usersLink, ['class' => 'users']);
                    ?></li>
                    <?php
                    foreach ($consultation->motionTypes as $motionType) {
                        $motionTypeUrl = UrlHelper::createUrl(
                            ['admin/motion-list/motion-openslides', 'motionTypeId' => $motionType->id]
                        );
                        $title = 'V1: ' . Html::encode($motionType->titlePlural);
                        echo '<li>' .
                             Html::a($title, $motionTypeUrl, ['class' => 'slidesMotionType' . $motionType->id]) .
                             '</li>';
                    } ?>
                    <li><?php
                    $title = 'V1: ' . Yii::t('admin', 'index_export_oslides_amend');
                    $amendLink = UrlHelper::createUrl(['admin/amendment/openslides', 'version' => '1']);
                    echo Html::a($title, $amendLink, ['class' => 'amendments']);
                    ?></li>
                    -->
                    <li>
                        <?php
                        $add = '<br><small>' . Yii::t('admin', 'index_export_oslides_usersh') . '</small>';
                        $title = Yii::t('admin', 'index_export_oslides_users') . $add;
                        $usersLink = UrlHelper::createUrl(['admin/index/openslidesusers', 'version' => '2']);
                        echo Html::a($title, $usersLink, ['class' => 'users']);
                        ?>
                    </li>
                    <?php
                    foreach ($consultation->motionTypes as $motionType) {
                        $motionTypeUrl = UrlHelper::createUrl(
                            ['admin/motion-list/motion-openslides', 'motionTypeId' => $motionType->id, 'version' => '2']
                        );
                        $title = Html::encode($motionType->titlePlural);
                        echo '<li>' .
                             Html::a($title, $motionTypeUrl, ['class' => 'slidesMotionType' . $motionType->id]) .
                             '</li>';
                    } ?>
                    <li>
                        <?php
                        $title = Yii::t('admin', 'index_export_oslides_amend');
                        $amendLink = UrlHelper::createUrl(['admin/amendment/openslides', 'version' => '2']);
                        echo Html::a($title, $amendLink, ['class' => 'amendments']);
                        ?>
                    </li>
                </ul>
            </div>
            <?php
        }
        if ($hasProposedProcedures) {
            echo $this->render('../proposed-procedure/_switch_dropdown');
        }
        ?>
    </div>
</section>

