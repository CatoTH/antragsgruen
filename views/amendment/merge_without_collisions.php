<?php

use app\components\{Diff\SingleAmendmentMergeViewParagraphData, MotionNumbering, UrlHelper};
use app\models\db\Amendment;
use Yii\helpers\Html;

/**
 * @var \Yii\web\View $this
 * @var Amendment $amendment
 * @var \app\models\db\Consultation $consultation
 * @var SingleAmendmentMergeViewParagraphData[][] $paragraphSections
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;
$layout->loadCKEditor();
$layout->addAMDModule('frontend/MergeSingleAmendment');
$layout->addCSS('css/formwizard.css');

$motion    = $amendment->getMyMotion();
$motionUrl = UrlHelper::createMotionUrl($motion);
$layout->addBreadcrumb($motion->getBreadcrumbTitle(), $motionUrl);
if (!$consultation->getSettings()->hideTitlePrefix && $amendment->getFormattedTitlePrefix() != '') {
    $layout->addBreadcrumb($amendment->getFormattedTitlePrefix(), UrlHelper::createAmendmentUrl($amendment));
} else {
    $layout->addBreadcrumb(Yii::t('amend', 'amendment'), UrlHelper::createAmendmentUrl($amendment));
}
$layout->addBreadcrumb(Yii::t('amend', 'merge1_title'));

$this->title = $amendment->getTitle() . ': ' . Yii::t('amend', 'merge1_title');


$fixedWidthSections = [];
foreach ($amendment->getActiveSections() as $section) {
    if ($section->getSettings()->fixedWidth) {
        $fixedWidthSections[] = $section->sectionId;
    }
}

echo '<h1>' . Html::encode($this->title) . '</h1>';

echo Html::beginForm('', 'post', ['id' => 'amendmentMergeForm']);

?>
    <div class="content">
        <div class="alert alert-info"><?= Yii::t('amend', 'merge1_intro_user') ?></div>

        <div class="form-group">
            <label for="motionTitlePrefix"><?= Yii::t('amend', 'merge1_motion_prefix') ?></label>
            <input type="text" class="form-control" id="motionTitlePrefix" name="motionTitlePrefix"
                   value="<?= Html::encode($amendment->getMyMotion()->titlePrefix) ?>">
        </div>

        <div class="form-group">
            <label for="motionVersion"><?= Yii::t('amend', 'merge1_motion_version') ?></label>
            <input type="text" class="form-control" id="motionVersion" name="motionVersion"
                   value="<?= Html::encode(MotionNumbering::getNewVersion($amendment->getMyMotion()->version)) ?>">
        </div>

        <fieldset class="affectedParagraphs">
            <?php
            foreach ($paragraphSections as $sectionId => $paragraphs) {
                $fixedClass = (in_array($sectionId, $fixedWidthSections) ? 'fixedWidthFont' : '');

                foreach ($paragraphs as $paragraphNo => $parDat) {
                    $nameBase = 'newParas[' . $sectionId . '][' . $paragraphNo . ']';
                    ?>
                    <section class="paragraph paragraph_<?= $sectionId ?>_<?= $paragraphNo ?> unmodified"
                             data-section-id="<?= $sectionId ?>" data-paragraph-no="<?= $paragraphNo ?>">
                        <h2 class="green">
                            <?php
                            if ($parDat->lineFrom == $parDat->lineTo) {
                                $tpl = Yii::t('amend', 'merge1_changein_1');
                            } else {
                                $tpl = Yii::t('amend', 'merge1_changein_x');
                            }
                            echo str_replace(
                                ['%LINEFROM%', '%LINETO%'],
                                [$parDat->lineFrom, $parDat->lineTo],
                                $tpl
                            );
                            ?>:
                        </h2>
                        <div class="content">
                            <div class="unmodifiedVersion motionTextHolder">
                                <div class="paragraph">
                                    <div class="text motionTextFormattings <?= $fixedClass ?>"><?= $parDat->diff ?></div>
                                </div>
                            </div>
                        </div>
                    </section>
                    <?php
                }
            }
            ?>
        </fieldset>

        <div class="saveholder content">
            <button type="submit" name="save" class="btn btn-primary save"><?= Yii::t('admin', 'save') ?></button>
        </div>
    </div>
<?php

echo Html::endForm();
