<?php

use app\components\MotionNumbering;
use app\components\UrlHelper;
use app\models\db\{Amendment, MotionSection};
use app\models\mergeAmendments\Init;
use yii\helpers\Html;

/**
 * @var \yii\web\View $this
 * @var Init $form
 * @var bool $twoCols
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;
$motion     = $form->motion;

$layout->robotsNoindex = true;
$layout->addBreadcrumb($motion->getBreadcrumbTitle(), UrlHelper::createMotionUrl($motion));
$layout->addBreadcrumb(Yii::t('amend', 'merge_bread'));
$layout->loadVue();
$layout->loadCKEditor();

$layout->addVueTemplate('@app/views/merging/_merging_paragraph_status.vue.php');

if ($twoCols) {
    $layout->fullWidth = true;
    //$layout->fullScreen = true;
}

$title       = str_replace('%TITLE%', $motion->motionType->titleSingular, Yii::t('amend', 'merge_title'));
$this->title = $title . ': ' . $motion->getTitleWithPrefix();

$amendments = Init::getMotionAmendmentsForMerging($form->motion);
$pp = $form->motion->getAlternativeProposaltextReference();
if ($pp && $pp['motion']->id === $form->motion->id) {
    $amendments[] = $pp['modification'];
}

$amendmentStaticData = array_map(function (Amendment $amendment) {
    return Init::getJsAmendmentStaticData($amendment);
}, $amendments);

/** @var MotionSection[] $newSections */
$newSections = [];
foreach ($motion->getSortedSections(false) as $section) {
    $newSections[$section->sectionId] = $section;
}


echo '<h1 class="stickyHeader">' . $motion->getEncodedTitleWithPrefix() . '</h1>';

echo '<div class="motionData">';

include(__DIR__ . '/../motion/_view_motiondata.php');

if (count($amendments) > 0) {
    $explanation = Yii::t('amend', 'merge_explanation');
    $explanation = str_replace('###COLLIDINGHINT###', '', $explanation);
    $explanation = str_replace('###NEWPREFIX###', $motion->titlePrefix, $explanation);
    $explanation = str_replace('###NEWVERSION###', MotionNumbering::getNewVersion($motion->version), $explanation);
    echo '<div class="alert alert-info alert-dismissible">' . $explanation . '</div>';
}

echo $controller->showErrors();

echo '</div>';

echo Html::beginForm(UrlHelper::createMotionUrl($motion, 'merge-amendments'), 'post', [
    'class'                      => 'motionMergeForm motionMergeStyles',
    'enctype'                    => 'multipart/form-data',
    'data-draft-saving-url'      => UrlHelper::createMotionUrl($motion, 'save-merging-draft'),
    'data-check-status-url'      => UrlHelper::createMotionUrl($motion, 'merge-amendments-status-ajax', ['knownAmendments' => 'AMENDMENTS']),
    'data-antragsgruen-widget'   => 'frontend/MotionMergeAmendments',
    'data-amendment-static-data' => json_encode($amendmentStaticData),
]);

$publicDraftLink = UrlHelper::createMotionUrl($motion, 'merge-amendments-public');
$pdfLink         = UrlHelper::createMotionUrl($motion, 'merge-amendments-draft-pdf');
$resumedDate     = ($form->draftData->time ? $form->draftData->time->format('c') : '');
?>
    <section id="draftSavingPanel" data-resumed-date="<?= ($resumedDate) ?>" aria-labelledby="draftSavingPanelTitle">
        <header>
            <h2 id="draftSavingPanelTitle">
                <?= Yii::t('amend', 'merge_draft_title') ?>
            </h2>
            <a href="<?= Html::encode($pdfLink) ?>" class="pdfLink" target="_blank">
                <span class="glyphicon glyphicon-download-alt" aria-hidden="true"></span>
                PDF
            </a>
        </header>
        <label class="public">
            <a href="<?= Html::encode($publicDraftLink) ?>" target="_blank"
               class="publicLink <?= ($form->draftData->public ? '' : 'hidden') ?>">
                <span class="glyphicon glyphicon-share" aria-hidden="true"></span>
                <span class="sr-only"><?= Yii::t('amend', 'merge_draft_public_link') ?></span>
            </a>
            <input type="checkbox" name="public" <?= ($form->draftData->public ? 'checked' : '') ?>>
            <?= Yii::t('amend', 'merge_draft_public') ?>
        </label>
        <label class="autosave">
            <input type="checkbox" name="autosave" checked> <?= Yii::t('amend', 'merge_draft_auto_save') ?>
        </label>
        <div class="savingError hidden" aria-live="polite">
            <div class="errorNetwork"><?= Yii::t('amend', 'merge_draft_err_saving') ?></div>
            <div class="errorHolder"></div>
        </div>
        <div class="save">
            <div class="lastSaved">
                <?= Yii::t('amend', 'merge_draft_date') ?>:
                <span class="value"></span>
                <span class="none"><?= Yii::t('amend', 'merge_draft_not_saved') ?></span>
            </div>
            <button class="saveDraft btn btn-default btn-xs"
                    type="button"><?= Yii::t('amend', 'merge_draft_save') ?></button>
        </div>
    </section>

    <section id="newAmendmentAlert" aria-live="polite" aria-labelledby="newAmendmentAlertTitle" class="hidden">
        <div class="holder">
            <button class="btn btn-link btn-sm closeLink" type="button" title="<?= Yii::t('base', 'aria_close') ?>">
                <span aria-hidden="true">&times;</span>
                <span class="sr-only"><?= Yii::t('base', 'aria_close') ?></span>
            </button>
            <div class="message" id="newAmendmentAlertTitle">
                <span class="one"><?= Yii::t('amend', 'merge_new_amend_1') ?>:</span>
                <span class="many"><?= Yii::t('amend', 'merge_new_amend_x') ?>:</span>
            </div>
            <span class="buttons"></span>
        </div>
    </section>

    <section class="newMotion">
        <h2 class="green"><?= Yii::t('amend', 'merge_new_text') ?></h2>
        <?php
        foreach ($motion->getSortedSections(false) as $section) {
            $type = $section->getSettings();
            if ($type->type === \app\models\sectionTypes\ISectionType::TYPE_TITLE) {
                echo $this->render('_merging_section_title', ['form' => $form, 'section' => $section, 'twoCols' => $twoCols]);
            } elseif ($type->type === \app\models\sectionTypes\ISectionType::TYPE_TEXT_SIMPLE) {
                echo $this->render('_merging_section', ['form' => $form, 'section' => $section, 'twoCols' => $twoCols]);
            } else {
                echo $this->render('_merging_section_other', ['form' => $form, 'section' => $section, 'twoCols' => $twoCols]);
            }
        }
        ?>
    </section>
<?php


$editorials = [];
foreach ($motion->getVisibleAmendments(false) as $amendment) {
    if ($amendment->changeEditorial !== '') {
        $str          = '<div class="amendment content"><h3>';
        $str          .= str_replace(
            ['%TITLE%', '%INITIATOR%'],
            [Html::encode($amendment->getFormattedTitlePrefix()), Html::encode($amendment->getInitiatorsStr())],
            Yii::t('amend', 'merge_amend_by')
        );
        $str          .= '</h3>';
        $str          .= '<div class="text">';
        $str          .= $amendment->changeEditorial;
        $str          .= '</div></div>';
        $editorials[] = $str;
    }
}
if (count($editorials) > 0) {
    ?>
    <section class="editorialAmendments" aria-labelledby="editorialAmendmentsTitle">
        <h2 class="green" id="editorialAmendmentsTitle"><?= Yii::t('amend', 'merge_amend_editorials') ?></h2>
        <div><?= implode('', $editorials) ?></div>
    </section>
    <?php
}

?>

    <section class="mergingProtocol" aria-labelledby="mergingProtocolTitle">
        <h2 class="green" id="mergingProtocolTitle"><?= Yii::t('motion', 'protocol') ?></h2>

        <div class="content">
            <label>
                <input type="radio" name="protocol_public" value="1"<?= ($form->draftData->protocolPublic ? ' checked' : '') ?>>
                <?= Yii::t('motion', 'protocol_public') ?>
            </label>
            <label>
                <input type="radio" name="protocol_public" value="0"<?= ($form->draftData->protocolPublic ? '' : ' checked') ?>>
                <?= Yii::t('motion', 'protocol_private') ?>
            </label><br>
            <div class="form-group wysiwyg-textarea single-paragraph">
                <label for="protocol_text" class="hidden"><?= Yii::t('motion', 'protocol') ?>:</label>
                <textarea id="protocol_text" name="protocol"></textarea>
                <div class="texteditor boxed motionTextFormattings" id="protocol_text_wysiwyg"><?php
                    echo $form->draftData->protocol;
                    ?></div>
            </div>
        </div>

    </section>


<?php

echo Html::input('hidden', 'mergeDraft', json_encode($form->draftData), ['id' => 'mergeDraft']);
?>
    <div class="submitHolder content">
        <button type="submit" name="save" class="btn btn-primary">
            <span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span>
            <?= Yii::t('amend', 'go_on') ?>
        </button>
    </div>

<?php
echo Html::endForm();
