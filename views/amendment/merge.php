<?php
use app\components\HTMLTools;
use app\components\UrlHelper;
use app\models\db\Amendment;
use yii\helpers\Html;

/**
 * @var \yii\web\View $this
 * @var Amendment $amendment
 * @var string[][] $paragraphSections
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;
$layout->loadFuelux();
$layout->loadCKEditor();
$layout->setMainAMDModule('frontend/MergeSingleAmendment');

$motionUrl = UrlHelper::createMotionUrl($amendment->getMyMotion());
$layout->addBreadcrumb($amendment->getMyMotion()->motionType->titleSingular, $motionUrl);
if (!$consultation->getSettings()->hideTitlePrefix && $amendment->titlePrefix != '') {
    $layout->addBreadcrumb(UrlHelper::createAmendmentUrl($amendment), $amendment->titlePrefix);
} else {
    $layout->addBreadcrumb(UrlHelper::createAmendmentUrl($amendment), \Yii::t('amend', 'amendment'));
}
$layout->addBreadcrumb('Änderungen übernehmen');

$this->title = $amendment->getTitle() . ': ' . 'Änderungen übernehmen';

$fixedWidthSections = [];
foreach ($amendment->getActiveSections() as $section) {
    if ($section->getSettings()->fixedWidth) {
        $fixedWidthSections[] = $section->sectionId;
    }
}

/** @var Amendment[] $otherAmendments */
$otherAmendments = [];
foreach ($amendment->getMyMotion()->getAmendmentsRelevantForCollissionDetection() as $otherAmend) {
    if ($otherAmend->id != $amendment->id) {
        $otherAmendments[] = $otherAmend;
    }
}
$needsCollissionCheck = (count($otherAmendments) > 0);

?>

    <h1><?= Html::encode($this->title) ?></h1>

<?= Html::beginForm('', 'post', ['id' => 'amendmentMergeForm']) ?>

    <div class="content">
        <div class="alert alert-info">
            Wenn der Änderungsantrag in den Antrag übernommen wird, wird eine neue Version des Antrags mit diesen
            Änderungen erstellt. Die bisherige Version des Antrags sowie dieser Änderungsantrag werden archiviert,
            bleiben aber abrufbar.<br><br>
            Falls sich duch diese Übernahme andere Änderungsanträge erübrigen, kannst du dies hier markieren.
            Ansonsten lass sie einfach unverändert.<br><br>
            <strong>Hinweis:</strong> Falls von dieser Übernahme Stellen betroffen sind, auf die sich auch andere
            Änderungsanträge beziehen (die nicht als erledigt markiert werden), kommt es zu Kollissionen;
            in diesem Fall müssen die anderen kollidierenden Änderungsanträge händisch angepasst werden.
        </div>
    </div>

    <fieldset class="amendmentStatus">
        <h2 class="green"><?= 'Neuer Status des Änderungsantrags' ?></h2>
        <div class="content fuelux">
            <div class="fueluxSelectHolder">
                <?php
                echo HTMLTools::fueluxSelectbox('amendmentStatus', Amendment::getStati(), Amendment::STATUS_ACCEPTED);
                ?>
            </div>
        </div>
    </fieldset>

    <fieldset class="affectedParagraphs">
        <?php
        foreach ($paragraphSections as $sectionId => $paragraphs) {
            $fixedClass = (in_array($sectionId, $fixedWidthSections) ? 'fixedWidthFont' : '');

            foreach ($paragraphs as $paragraphNo => $paraData) {
                $nameBase = 'newParas[' . $sectionId . '][' . $paragraphNo . ']';
                ?>
                <section class="paragraph paragraph_<?= $sectionId ?>_<?= $paragraphNo ?> unmodified"
                         data-unchanged-amendment="<?= Html::encode($paraData['plain']) ?>"
                         data-section-id="<?= $sectionId ?>" data-paragraph-no="<?= $paragraphNo ?>">
                    <h2 class="green"><?php
                        echo str_replace(
                            ['%LINEFROM%', '%LINETO%'],
                            [$paraData['lineFrom'], $paraData['lineTo']],
                            'Änderung von Zeile %LINEFROM% bis %LINETO%'
                        )
                        ?>:
                    </h2>
                    <div class="content">
                        <div class="modifySelector">
                            <label>
                                <input type="radio" name="<?= $nameBase ?>[modified]" value="0" checked>
                                Unverändert übernehmen
                            </label>
                            <label>
                                <input type="radio" name="<?= $nameBase ?>[modified]" value="1">
                                Modifizierte Übernahme
                            </label>
                        </div>
                        <div class="unmodifiedVersion motionTextHolder">
                            <div class="paragraph">
                                <div class="text <?= $fixedClass ?>"><?= $paraData['diff'] ?></div>
                            </div>
                        </div>
                        <div class="affectedBlock">
                            <textarea name="<?= $nameBase ?>[modified]" class="modifiedText" title=""></textarea>
                            <div id="new_paragraphs_<?= $sectionId ?>_<?= $paragraphNo ?>"
                                 class="<?= $fixedClass ?> texteditor texteditorBox"
                                 title="<?= 'Änderungsantrag anpassen' ?>" data-track-changed="1">
                                <?= $paraData['plain'] ?>
                            </div>
                        </div>
                    </div>
                </section>
                <?php
            }
        }
        $url = UrlHelper::createAmendmentUrl($amendment, 'get-merge-collissions');
        if ($needsCollissionCheck) {
            echo '<div class="checkButtonRow">';
            echo '<button class="checkAmendmentCollissions btn btn-default" data-url="' . Html::encode($url) . '">' .
                'Finished editing / Check for collissions' . '</button>';
            echo '</div>';
        }
        ?>
    </fieldset>

    <fieldset class="amendmentCollissionsHolder"></fieldset>

    <fieldset class="otherAmendmentStatus">
        <h2 class="green">Stati der Änderungsanträge</h2>
        <div class="content">
            <?php
            foreach ($otherAmendments as $otherAmend) {
                echo '<div class="row"><div class="col-md-3">';
                echo Html::a($otherAmend->getTitle(), UrlHelper::createAmendmentUrl($otherAmend));
                echo ' (' . 'Von' . ': ' . $otherAmend->getInitiatorsStr() . ')';
                echo '</div><div class="col-md-9"><div class="fueluxSelectHolder">';
                $statiAll                   = $amendment->getStati();
                $stati                      = [
                    Amendment::STATUS_ACCEPTED          => $statiAll[Amendment::STATUS_ACCEPTED],
                    Amendment::STATUS_REJECTED          => $statiAll[Amendment::STATUS_REJECTED],
                    Amendment::STATUS_MODIFIED_ACCEPTED => $statiAll[Amendment::STATUS_MODIFIED_ACCEPTED],
                ];
                $stati[$otherAmend->status] = 'unverändert: ' . $statiAll[$amendment->status];
                echo HTMLTools::fueluxSelectbox(
                    'otherAmendmentsStatus[' . $otherAmend->id . ']',
                    $stati,
                    $otherAmend->status
                );
                echo '</div></div></div>';
            }
            ?>
        </div>
    </fieldset>

<?php
echo '<div class="saveholder content">';
if ($needsCollissionCheck) {
    $url = UrlHelper::createUrl(['admin/motion/get-amendment-rewrite-collissions', 'motionId' => $motion->id]);
    echo '<button class="checkAmendmentCollissions btn btn-default" data-url="' . Html::encode($url) . '">' .
        'Check for collissions' . '</button>';
}
echo '<button type="submit" name="save" class="btn btn-primary save">' . \Yii::t('admin', 'save') . '</button>
</div>';

echo Html::endForm();
