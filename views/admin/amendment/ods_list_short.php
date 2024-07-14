<?php

use app\components\HTMLTools;
use app\models\db\{Amendment, Motion};
use CatoTH\HTML2OpenDocument\Spreadsheet;
use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var bool $textCombined
 * @var int $maxLen
 * @var array<array{motion: Motion, amendments: Amendment[]}> $amendments
 */

/** @var \app\controllers\Base $controller */
$controller   = $this->context;
$consultation = $controller->consultation;

$DEBUG = false;

/** @noinspection PhpUnhandledExceptionInspection */
$doc = new Spreadsheet([
    'tmpPath'   => \app\models\settings\AntragsgruenApp::getInstance()->getTmpDir(),
    'trustHtml' => true,
]);

$currCol = $firstCol = 1;

$COL_PREFIX    = $currCol++;
$COL_INITIATOR = $currCol++;
$COL_CHANGE    = $currCol++;
if (!$textCombined) {
    $COL_REASON = $currCol++;
}
$LAST_COL = $currCol - 1;

// Title

$doc->setCell(1, $firstCol, Spreadsheet::TYPE_TEXT, Yii::t('export', 'amendments'));
$doc->setCellStyle(1, $firstCol, [], [
    'fo:font-size'   => '16pt',
    'fo:font-weight' => 'bold',
]);
$doc->setMinRowHeight(1, 1.5);

$isNonRelevantReason = function ($reason) {
    $reason = trim($reason);
    if ($reason === '' || $reason === '<p>-</p>') {
        return true;
    }
    if (preg_match("/<p>-? ?\\(?(begründung )?(erfolgt )?(im zweifel )?mündn?l?ich ?-?\\)?\\.?<\/p>/i", $reason)) {
        return true;
    }
    return false;
};


// Heading

$doc->setCell(2, $COL_PREFIX, Spreadsheet::TYPE_TEXT, Yii::t('export', 'prefix_short'));
$doc->setCellStyle(2, $COL_PREFIX, [], ['fo:font-weight' => 'bold']);

$doc->setCell(2, $COL_INITIATOR, Spreadsheet::TYPE_TEXT, Yii::t('export', 'initiator'));
$doc->setColumnWidth($COL_INITIATOR, 6);

$doc->setCell(2, $COL_CHANGE, Spreadsheet::TYPE_TEXT, Yii::t('export', 'amend_change'));
$doc->setColumnWidth($COL_CHANGE, 15);

if (!$textCombined) {
    $doc->setCell(2, $COL_REASON, Spreadsheet::TYPE_TEXT, Yii::t('export', 'amend_reason'));
    $doc->setColumnWidth($COL_REASON, 10);
}

$doc->drawBorder(1, $firstCol, 2, $LAST_COL, 1.5);


// Amendments

$row = 3;

foreach ($amendments as $amendmentGroup) {
    $motion = $amendmentGroup['motion'];
    if ($motion->getMyMotionType()->amendmentsOnly) {
        continue;
    }
    $row++;
    $doc->setMinRowHeight($row, 2);

    $maxRows        = 1;
    $firstMotionRow = $row;

    $initiatorNames = [];
    foreach ($motion->getInitiators() as $supp) {
        $initiatorNames[] = $supp->getNameWithResolutionDate(false);
    }

    $title = '<strong>' . $motion->getTitleWithPrefix() . '</strong>';
    $title .= ' (' . Yii::t('export', 'motion_by') . ': ' . Html::encode(implode(', ', $initiatorNames)) . ')';
    $title = HTMLTools::correctHtmlErrors($title);
    $doc->setCell($row, $COL_PREFIX, Spreadsheet::TYPE_HTML, $title, null, ['fo:wrap-option' => 'no-wrap']);

    foreach ($amendmentGroup['amendments'] as $amendment) {
        $change = '';
        if ($amendment->changeEditorial !== '') {
            $change .= '<h4>' . Yii::t('amend', 'editorial_hint') . '</h4>';
            $change .= $amendment->changeEditorial;
        }
        foreach ($amendment->getSortedSections(false) as $section) {
            $change .= $section->getSectionType()->getAmendmentODS();
        }
        if ($textCombined && !$isNonRelevantReason($amendment->changeExplanation)) {
            $changeExplanation = HTMLTools::correctHtmlErrors($amendment->changeExplanation);
            $change            .= '<h4>' . Yii::t('amend', 'reason') . '</h4>';
            $change            .= $changeExplanation;
        }
        // If length exceeds $maxLen, don't show the text
        $changeLength = grapheme_strlen(strip_tags($change));
        if ($changeLength > $maxLen) {
            $change = '';
        }

        $row++;

        $initiatorNames   = [];
        $initiatorContacs = [];
        foreach ($amendment->getInitiators() as $supp) {
            $initiatorNames[] = $supp->getNameWithResolutionDate(false);
            if ($supp->contactEmail !== '') {
                $initiatorContacs[] = $supp->contactEmail;
            }
            if ($supp->contactPhone !== '') {
                $initiatorContacs[] = $supp->contactPhone;
            }
        }
        $firstLine = $amendment->getFirstDiffLine();

        $doc->setCell($row, $COL_PREFIX, Spreadsheet::TYPE_TEXT, $amendment->getFormattedTitlePrefix());
        $doc->setCell($row, $COL_INITIATOR, Spreadsheet::TYPE_TEXT, implode(', ', $initiatorNames));

        $change = HTMLTools::correctHtmlErrors($change);
        $doc->setCell($row, $COL_CHANGE, Spreadsheet::TYPE_HTML, $change);

        if (!$textCombined) {
            $changeExplanation = HTMLTools::correctHtmlErrors($amendment->changeExplanation);
            $doc->setCell($row, $COL_REASON, Spreadsheet::TYPE_HTML, $changeExplanation);
        }
    }

    $doc->drawBorder($firstMotionRow, $firstCol, $row, $LAST_COL, 1.5);
    $row++;
}

try {
    echo $doc->finishAndGetDocument();
} catch (Exception $e) {
    if (in_array(YII_ENV, ['dev', 'test'])) {
        var_dump($e);
    } else {
        echo Yii::t('base', 'err_unknown');
    }
    die();
}
