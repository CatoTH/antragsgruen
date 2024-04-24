<?php

use app\components\{diff\Diff, diff\DiffRenderer, HTMLTools, UrlHelper};
use app\models\db\Amendment;
use app\models\sectionTypes\ISectionType;
use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var Amendment $amendment
 * @var \app\models\forms\ProposedChangeForm $form
 * @var null|string $msgSuccess
 * @var null|string $msgAlert
 */

/** @var \app\controllers\Base $controller */
$controller   = $this->context;
$layout       = $controller->layoutParams;
$consultation = $controller->consultation;

$this->title        = Yii::t('amend', 'proposal_edit_title');
$layout->fullWidth  = true;
$layout->fullScreen = true;
$layout->loadCKEditor();
$layout->loadSelectize();

$motionUrl = UrlHelper::createMotionUrl($amendment->getMyMotion());
$layout->addBreadcrumb($amendment->getMyMotion()->getBreadcrumbTitle(), $motionUrl);
if (!$consultation->getSettings()->hideTitlePrefix && $amendment->getFormattedTitlePrefix() != '') {
    $layout->addBreadcrumb($amendment->getFormattedTitlePrefix(), UrlHelper::createAmendmentUrl($amendment));
} else {
    $layout->addBreadcrumb(Yii::t('amend', 'amendment'), UrlHelper::createAmendmentUrl($amendment));
}
$layout->addBreadcrumb(Yii::t('amend', 'proposal_edit_bread'));

echo '<h1>' . Yii::t('amend', 'proposal_edit_title') . '</h1>';

$collidingAmendments = $amendment->collidesWithOtherProposedAmendments(true);

?>
    <div class="content">
        <a href="<?= UrlHelper::createAmendmentUrl($amendment) ?>" class="goBackLink">
            <span class="glyphicon glyphicon-chevron-left" aria-hidden="true"></span>
            <?= Yii::t('amend', 'proposal_edit_back') ?>
        </a>
        <?php
        if ($msgSuccess) {
            echo '<div class="alert alert-success">' . $msgSuccess . '</div>';
        }
        if ($msgAlert) {
            echo '<div class="alert alert-info">' . $msgAlert . '</div>';
        }

        echo $this->render('_set_proposed_procedure', ['amendment' => $amendment, 'context' => 'edit', 'msgAlert' => null]);

        echo Html::beginForm(UrlHelper::createAmendmentUrl($amendment, 'edit-proposed-change'), 'post', [
            'id'                        => 'proposedChangeTextForm',
            'data-antragsgruen-widget'  => 'backend/ProposedChangeEdit',
            'data-collision-check-url' => UrlHelper::createAmendmentUrl($amendment, 'edit-proposed-change-check'),
        ]);
        ?>

        <div class="stdEqualCols stdPadding">
            <section>
                <h2><?= Yii::t('amend', 'proposal_edit_title_prop') ?></h2>
            </section>
            <section>
                <h2><?= Yii::t('amend', 'proposal_edit_title_orig') ?></h2>
            </section>
        </div>
        <?php

        foreach ($form->getProposalSections() as $section) {
            $amendSection = null;
            foreach ($amendment->getActiveSections() as $amSec) {
                if ($amSec->sectionId == $section->sectionId) {
                    $amendSection = $amSec;
                }
            }

            $type     = $section->getSettings();
            $nameBase = 'sections[' . $type->id . ']';
            $htmlId   = 'sections_' . $type->id;

            ?>
            <div class="stdEqualCols stdPadding">
                <section class="motionTextHolder proposedVersion" data-section-id="<?= $type->id ?>">
                    <?php
                    $type = $section->getSectionType();
                    if ($amendSection->getSettings()->type === ISectionType::TYPE_TEXT_SIMPLE) {
                        /** @var \app\models\sectionTypes\TextSimple $type */
                        $type->forceMultipleParagraphMode(true);
                    }
                    echo $type->getAmendmentFormField();
                    ?>
                </section>
                <section class="motionTextHolder originalVersion">
                    <div class="title"><?= Html::encode($amendSection->getSettings()->title) ?></div>
                    <?php
                    switch ($amendSection->getSettings()->type) {
                        case ISectionType::TYPE_TITLE:
                            echo '<div class="paragraph"><div class="text textOrig">';
                            echo Html::encode($amendSection->data);
                            echo '</div></div>';
                            break;
                        case ISectionType::TYPE_TEXT_HTML:
                            echo '<div class="paragraph"><div class="text textOrig">';
                            echo $amendSection->data;
                            echo '</div></div>';
                            break;
                        case ISectionType::TYPE_TEXT_SIMPLE:
                            $diff = new Diff();
                            echo '<div class="paragraph">';
                            echo '<div class="text motionTextFormattings textOrig fixedWidthFont">';
                            echo implode("\n", $diff->compareHtmlParagraphs(
                                HTMLTools::sectionSimpleHTML($amendSection->getOriginalMotionSection()->getData()),
                                HTMLTools::sectionSimpleHTML($amendSection->data),
                                DiffRenderer::FORMATTING_ICE
                            ));
                            echo '</div></div>';
                            break;
                    }
                    ?>
                </section>
            </div>
            <?php
        }

        ?>
        <div class="save-row">
            <button class="btn btn-default pull-right" type="submit" name="reset">
                <?= Yii::t('amend', 'proposal_reset') ?>
            </button>
            <button class="btn btn-primary" type="submit" name="save">
                <?= Yii::t('base', 'save') ?>
            </button>
        </div>
        <aside id="collisionIndicator" class="<?= (count($collidingAmendments) === 0 ? 'hidden' : '') ?>">
            <h2><?= Yii::t('amend', 'proposal_conflict_title') ?>:</h2>
            <ul class="collisionList">
                <?php
                foreach ($collidingAmendments as $collidingAmendment) {
                    // Keep in sync with AmendmentController::actionEditProposedChangeCheck
                    $title = $collidingAmendment->getShortTitle();
                    $url   = UrlHelper::createAmendmentUrl($collidingAmendment);
                    if ($collidingAmendment->proposalStatus == Amendment::STATUS_VOTE) {
                        $title .= ' (' . Yii::t('amend', 'proposal_voting') . ')';
                    }

                    echo '<li>' . Html::a($title, $url, ['target' => '_blank']);
                    echo HTMLTools::amendmentDiffTooltip($collidingAmendment, 'top', 'fixedBottom');
                    echo '</li>';
                }
                ?>
            </ul>
        </aside>
    </div>
<?php

echo Html::endForm();
