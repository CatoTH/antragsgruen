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

<?= Html::beginForm('', 'post', ['class' => 'content amendmentMergeForm']) ?>

<div class="alert alert-info">
    Wenn der Änderungsantrag in den Antrag übernommen wird, wird eine neue Version des Antrags mit diesen Änderungen
    erstellt. Die bisherige Version des Antrags sowie dieser Änderungsantrag werden archiviert, bleiben aber
    abrufbar.<br><br>
    Falls sich duch diese Übernahme andere Änderungsanträge erübrigen, kannst du dies hier markieren.
    Ansonsten lass sie einfach unverändert.<br><br>
    <strong>Hinweis:</strong> Falls von dieser Übernahme Stellen betroffen sind, auf die sich auch andere
    Änderungsanträge beziehen (die nicht als erledigt markiert werden), kommt es zu Kollissionen;
    in diesem Fall müssen die anderen kollidierenden Änderungsanträge händisch angepasst werden.
</div>

<fieldset class="affectedParagraphs">
    <?php
    foreach ($paragraphSections as $sectionId => $paragraphs) {
        foreach ($paragraphs as $paragraphNo => $text) {
            echo '<section class="affectedBlock">';
            echo '<textarea name="newParas[' . $sectionId . '][' . $paragraphNo . ']" value="" class=""></textarea>';
            echo '<div id="new_paragraphs_' . $sectionId . '_' . $paragraphNo . '" class="';
            if (in_array($sectionId, $fixedWidthSections)) {
                echo 'fixedWidthFont ';
            }
            echo 'texteditor texteditorBox" title="' . 'Änderungsantrag anpassen' . '" data-track-changed="1">';
            echo $text;
            echo '</div></section>';
        }
    }
    $url = UrlHelper::createUrl(['admin/motion/get-amendment-rewrite-collissions', 'motionId' => $motion->id]);
    if ($needsCollissionCheck) {
        echo '<div class="check-button-row">';
        echo '<button class="checkAmendmentCollissions btn btn-default" data-url="' . Html::encode($url) . '">' .
            'Finished editing / Check for collissions' . '</button>';
        echo '</div>';
    }
    ?>
</fieldset>

<fieldset class="amendmentCollissionsHolder"></fieldset>

<fieldset class="otherAmendmentStatus">
    <?php
    foreach ($otherAmendments as $otherAmend) {
        echo '<div class="row"><div class="col-md-3">';
        echo Html::a($otherAmend->getTitle(), UrlHelper::createAmendmentUrl($otherAmend));
        echo ' (' . 'Von' . ': ' . $otherAmend->getInitiatorsStr() . ')';
        echo '</div><div class="col-md-9">';
        $statiAll                  = $amendment->getStati();
        $stati                     = [
            Amendment::STATUS_ACCEPTED          => $statiAll[Amendment::STATUS_ACCEPTED],
            Amendment::STATUS_REJECTED          => $statiAll[Amendment::STATUS_REJECTED],
            Amendment::STATUS_MODIFIED_ACCEPTED => $statiAll[Amendment::STATUS_MODIFIED_ACCEPTED],
        ];
        $stati[$otherAmend->status] = 'unverändert: ' . $statiAll[$amendment->status];
        echo HTMLTools::fueluxSelectbox('amendmentStatus[' . $otherAmend->id . ']', $stati, $otherAmend->status);
        echo '</div></div>';
    }
    ?>
</fieldset>

<?php
echo '<div class="saveholder">';
if ($needsCollissionCheck) {
    $url = UrlHelper::createUrl(['admin/motion/get-amendment-rewrite-collissions', 'motionId' => $motion->id]);
    echo '<button class="checkAmendmentCollissions btn btn-default" data-url="' . Html::encode($url) . '">' .
        'Check for collissions' . '</button>';
}
echo '<button type="submit" name="save" class="btn btn-primary save">' . \Yii::t('admin', 'save') . '</button>
</div>';

echo Html::endForm();

$layout->addOnLoadJS('jQuery.Antragsgruen.amendmentMergeForm();');
