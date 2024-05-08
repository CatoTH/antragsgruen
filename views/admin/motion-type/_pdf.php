<?php

use app\components\HTMLTools;
use app\models\db\ConsultationMotionType;
use app\views\pdfLayouts\IPDFLayout;
use yii\helpers\Html;

/**
 * @var ConsultationMotionType $motionType
 */

$supportSett = $motionType->getMotionSupportTypeClass()->getSettingsObj();

?>
<section id="typePdfForm" aria-labelledby="typePdfFormLabel">
    <h2 class="green" id="typePdfFormLabel"><?= Yii::t('admin', 'motion_type_pdf_layout') ?></h2>
    <div class="content">
    <div class="stdTwoCols">
        <label class="leftColumn" for="pdfIntroduction">
            <?= Yii::t('admin', 'con_pdf_intro') ?>:
        </label>
        <div class="rightColumn">
        <textarea name="type[pdfIntroduction]" class="form-control" id="pdfIntroduction"
                  placeholder="<?= Html::encode(Yii::t('admin', 'con_pdf_intro_place')) ?>"
        ><?= $motionType->getSettingsObj()->pdfIntroduction ?></textarea>
        </div>
    </div>

    <div class="stdTwoCols">
        <div class="leftColumn"></div>
        <div class="rightColumn">
            <input type="hidden" name="amendmentInitiatorSettingFields[]" value="hasOrganizations">
            <?php
            echo HTMLTools::labeledCheckbox(
                'type[showProposalsInExports]',
                Yii::t('admin', 'motion_type_export_pp'),
                $motionType->getSettingsObj()->showProposalsInExports,
                'typeShowProposalsInExports'
            );
            ?>
        </div>
    </div>

    <div class="stdTwoCols" id="typeMaxPdfSupportersRow">
        <label class="leftColumn" for="typeMaxPdfSupporters">
            <?= Yii::t('admin', 'motion_type_supp_max_pdf') ?>:
            <?= HTMLTools::getTooltipIcon(Yii::t('admin', 'motion_type_supp_max_pdfd')) ?>
        </label>
        <div class="rightColumn">
            <input type="number" name="maxPdfSupporters" class="form-control" id="typeMaxPdfSupporters"
                   value="<?= Html::encode($supportSett->maxPdfSupporters !== null ? $supportSett->maxPdfSupporters : '') ?>">
        </div>
    </div>

    <?php
    $params = \app\models\settings\AntragsgruenApp::getInstance();
    if (($params->xelatexPath || $params->lualatexPath) && !$params->weasyprintPath) {
        echo '<div class="alert alert-danger" role="alert"><p>';
        echo Yii::t('admin', 'motion_type_latex_warning');
        echo '</p></div>';
    }
    ?>

    <fieldset class="thumbnailedLayoutSelector">
        <legend class="sr-only"><?= Yii::t('admin', 'motion_type_pdf_layout') ?></legend>
        <?php
        $currValue = IPDFLayout::getPdfLayoutForMotionType($motionType);
        foreach (IPDFLayout::getSelectablePdfLayouts() as $layout) {
            echo '<label class="layout ' . $layout->getHtmlId() . '">';
            echo Html::radio('pdfTemplate', $layout->getHtmlId() === $currValue->getHtmlId(), ['value' => $layout->getHtmlId()]);
            echo '<span>';
            if ($layout->preview) {
                echo '<img src="' . Html::encode($layout->preview) . '" ' .
                    'alt="' . Html::encode($layout->title) . '" ' .
                    'title="' . Html::encode($layout->title) . '">';
                echo '<span class="sr-only">' . Html::encode($layout->title) . '</span>';
            } else {
                echo '<span class="placeholder">' . Html::encode($layout->title) . '</span>';
            }
            echo '</span>';
            echo '</label>';
        }
        ?>
    </fieldset>
    </div>
</section>
