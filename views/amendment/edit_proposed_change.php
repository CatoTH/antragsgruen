<?php

use app\components\diff\Diff;
use app\components\diff\DiffRenderer;
use app\components\HTMLTools;
use app\components\UrlHelper;
use app\models\db\Amendment;
use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var Amendment $amendment
 * @var \app\models\forms\AmendmentProposedChangeForm $form
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;

$this->title        = 'Verfahrensvorschlag';
$layout->fullWidth  = true;
$layout->fullScreen = true;
$layout->addAMDModule('backend/AmendmentEditProposedChange');

$motionUrl = UrlHelper::createMotionUrl($amendment->getMyMotion());
$layout->addBreadcrumb($amendment->getMyMotion()->getBreadcrumbTitle(), $motionUrl);
if (!$consultation->getSettings()->hideTitlePrefix && $amendment->titlePrefix != '') {
    $layout->addBreadcrumb($amendment->titlePrefix, UrlHelper::createAmendmentUrl($amendment));
} else {
    $layout->addBreadcrumb(\Yii::t('amend', 'amendment'), UrlHelper::createAmendmentUrl($amendment));
}
$layout->addBreadcrumb('Verfahrensvorschlag');

echo '<h1>' . 'Verfahrensvorschlag bearbeiten' . '</h1>';

?>
    <div class="content">
        Text
    </div>

<?php
foreach ($form->getProposalSections() as $section) {
    $amendmentSection = null;
    foreach ($amendment->getActiveSections() as $amSec) {
        if ($amSec->sectionId == $section->sectionId) {
            $amendmentSection = $amSec;
        }
    }

    $diff          = new Diff();
    $originalParas = HTMLTools::sectionSimpleHTML($amendmentSection->getOriginalMotionSection()->data);

    $amendmentParas = HTMLTools::sectionSimpleHTML($amendmentSection->data);
    $amDiffSections = $diff->compareHtmlParagraphs($originalParas, $amendmentParas, DiffRenderer::FORMATTING_ICE);

    $proposalParas    = HTMLTools::sectionSimpleHTML($section->data);
    $propDiffSections = $diff->compareHtmlParagraphs($originalParas, $proposalParas, DiffRenderer::FORMATTING_ICE);

    ?>
    <h2 class="green"><?= Html::encode($section->getSettings()->title) ?></h2>
    <div class="row">
        <div class="col-md-6 motionTextHolder">
            <h3>Verfahrensvorschlag</h3>
            <div class="paragraph">
                <div class="text textOrig fixedWidthFont"><?php echo implode("\n", $propDiffSections); ?></div>
            </div>
        </div>
        <div class="col-md-6 motionTextHolder">
            <h3>Original-Änderungsantrag</h3>
            <div class="paragraph">
                <div class="text textOrig fixedWidthFont"><?php echo implode("\n", $amDiffSections); ?></div>
            </div>
        </div>
    </div>
    <?php
} ?>
<?php
