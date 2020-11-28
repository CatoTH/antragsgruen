<?php

use app\components\HTMLTools;
use app\models\db\AmendmentSection;
use app\models\db\Motion;
use app\models\sectionTypes\TextSimple;
use CatoTH\HTML2OpenDocument\Spreadsheet;
use yii\helpers\Html;

/**
 * @var $this yii\web\View
 * @var Motion $motion
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$consultation = $controller->consultation;

$motionType = $motion->getMyMotionType();

$DEBUG = false;

/** @var \app\models\settings\AntragsgruenApp $params */
$params = Yii::$app->params;

/** @noinspection PhpUnhandledExceptionInspection */
$doc = new Spreadsheet([
    'tmpPath' => $params->getTmpDir(),
    'trustHtml' => true,
    'templateFile' => __DIR__ . '/../assets/template.ods',
]);

$doc->setPreSaveHook(function (DOMDocument $doc) {
    $table = $doc->getElementsByTagNameNS(Spreadsheet::NS_TABLE, 'table');
    if (count($table) !== 1) {
        die("No table found");
    }
    $table = $table->item(0);

    /*
    <table:shapes>
        <draw:frame draw:z-index="0" draw:name="Image 1" draw:style-name="gr1" draw:text-style-name="P1" svg:width="7.451cm" svg:height="4cm"
                    svg:x="3.419cm" svg:y="0.645cm">
            <draw:image xlink:href="Pictures/10000201000008920000049A91CDC9EE8EC46C15.png" xlink:type="simple" xlink:show="embed"
                        xlink:actuate="onLoad" loext:mime-type="image/png">
                <text:p/>
            </draw:image>
        </draw:frame>
    </table:shapes>
     */
    $shapes = $doc->createElementNS(Spreadsheet::NS_TABLE, 'shapes');
    $table->insertBefore($shapes, $table->firstChild);

    $frame = $doc->createElementNS(Spreadsheet::NS_DRAW, 'frame');
    $frame->setAttribute('draw:z-index', '0');
    $frame->setAttribute('draw:name', 'EGP Logo');
    $frame->setAttribute('draw:style-name', 'gr1');
    $frame->setAttribute('draw:text-style-name', 'P1');
    $frame->setAttribute('svg:width', '9.083cm');
    $frame->setAttribute('svg:height', '4cm');
    $frame->setAttribute('svg:x', '2.2cm');
    $frame->setAttribute('svg:y', '0.645cm');
    $shapes->appendChild($frame);

    $image = $doc->createElementNS(Spreadsheet::NS_DRAW, 'image');
    $image->setAttribute('xlink:href', 'Pictures/10000201000008920000049A91CDC9EE8EC46C15.png');
    $image->setAttribute('xlink:type', 'simple');
    $image->setAttribute('xlink:show', 'embed');
    $image->setAttribute('xlink:actuate', 'onLoad');
    $image->setAttribute('loext:mime-type', 'image/png');
    $frame->appendChild($image);

    $p = $doc->createElementNS(Spreadsheet::NS_TEXT, 'p');
    $image->appendChild($p);
});

$currCol = $firstCol = 1;

$COL_PREFIX = $currCol++;
$COL_INITIATOR = $currCol++;
$COL_UNCHANGED = $currCol++;
$COL_CHANGE = $currCol++;
$COL_REASON = $currCol++;
$COL_STATUS = $currCol++;
$COL_CONTACT = $currCol++;
$COL_PROCEDURE = $currCol++;
$LAST_COL = $COL_PROCEDURE;

$doc->setMinRowHeight(0, 13);

// Title

$initiatorNames = [];
foreach ($motion->getInitiators() as $supp) {
    $initiatorNames[] = $supp->organization;
}

$doc->setCell(1, $firstCol, Spreadsheet::TYPE_TEXT, 'Amendments to ' . $motion->getTitleWithPrefix());
$doc->setCellStyle(1, $firstCol, [], [
    'fo:font-size' => '16pt',
    'fo:font-weight' => 'bold',
]);
$doc->setMinRowHeight(1, 1.5);


// Heading

$doc->setCell(2, $COL_PREFIX, Spreadsheet::TYPE_TEXT, '№');
$doc->setCellStyle(2, $COL_PREFIX, [], ['fo:font-weight' => 'bold']);

$doc->setCell(2, $COL_INITIATOR, Spreadsheet::TYPE_TEXT, Yii::t('export', 'initiator'));
$doc->setCellStyle(2, $COL_INITIATOR, ['fo:wrap-option' => 'wrap'], []);
$doc->setColumnWidth($COL_INITIATOR, 6);

$doc->setCell(2, $COL_UNCHANGED, Spreadsheet::TYPE_TEXT, 'Original text');
$doc->setColumnWidth($COL_UNCHANGED, 10);

$doc->setCell(2, $COL_CHANGE, Spreadsheet::TYPE_TEXT, 'Proposed amendment');
$doc->setColumnWidth($COL_CHANGE, 10);

$doc->setCell(2, $COL_REASON, Spreadsheet::TYPE_TEXT, 'Explanation / comment');
$doc->setColumnWidth($COL_REASON, 10);

$doc->setCell(2, $COL_STATUS, Spreadsheet::TYPE_TEXT, Yii::t('export', 'status'));
$doc->setColumnWidth($COL_STATUS, 3);

$doc->setCell(2, $COL_CONTACT, Spreadsheet::TYPE_TEXT, Yii::t('export', 'contact'));
$doc->setColumnWidth($COL_CONTACT, 6);

$doc->setCell(2, $COL_PROCEDURE, Spreadsheet::TYPE_TEXT, Yii::t('export', 'procedure'));
$doc->setColumnWidth($COL_PROCEDURE, 6);

$doc->drawBorder(1, $firstCol, 2, $LAST_COL, 1.5);


// Amendments

$row = 3;

$doc->setMinRowHeight($row, 2);

$maxRows = 1;
$firstMotionRow = $row;

$amendments = $motion->getVisibleAmendmentsSorted(false);
foreach ($amendments as $amendment) {
    $initiatorNames = [];
    $initiatorContacts = [];
    foreach ($amendment->getInitiators() as $supp) {
        $initiatorNames[] = $supp->organization;
        if ($supp->name) {
            $initiatorContacts[] = Html::encode($supp->name);
        }
        if ($supp->contactEmail) {
            $initiatorContacts[] = Html::encode($supp->contactEmail);
        } elseif ($supp->user && $supp->user->email) {
            $initiatorContacts[] = Html::encode($supp->user->email);
        }
        if ($supp->contactPhone) {
            $initiatorContacts[] = Html::encode($supp->contactPhone);
        }
    }
    $firstLine = $amendment->getFirstDiffLine();

    $doc->setCell($row, $COL_PREFIX, Spreadsheet::TYPE_TEXT, $amendment->titlePrefix);
    $doc->setCell($row, $COL_INITIATOR, Spreadsheet::TYPE_TEXT, implode(', ', $initiatorNames));
    $doc->setCellStyle($row, $COL_INITIATOR, ['fo:wrap-option' => 'wrap'], []);
    $doc->setCell($row, $COL_CONTACT, Spreadsheet::TYPE_HTML, implode("<br>", $initiatorContacts));
    $doc->setCell($row, $COL_STATUS, Spreadsheet::TYPE_HTML, $amendment->getFormattedStatus());
    $changeExplanation = HTMLTools::correctHtmlErrors($amendment->changeExplanation);
    $doc->setCell($row, $COL_REASON, Spreadsheet::TYPE_HTML, $changeExplanation);

    $unchanged = [];
    foreach ($amendment->getSortedSections(false) as $section) {
        $type = $section->getSectionType();
        if (is_a($type, TextSimple::class)) {
            $unchanged[] = $type->getAmendmentUnchangedVersionODS();
        }
    }
    $doc->setCell($row, $COL_UNCHANGED, Spreadsheet::TYPE_HTML, implode('<br><br>', $unchanged));

    $change = '';
    if ($amendment->changeEditorial !== '') {
        $change .= '<h4>' . Yii::t('amend', 'editorial_hint') . '</h4><br>';
        $change .= $amendment->changeEditorial;
    }
    foreach ($amendment->getSortedSections(false) as $section) {
        $change .= $section->getSectionType()->getAmendmentODS();
    }
    $change = HTMLTools::correctHtmlErrors($change);
    $doc->setCell($row, $COL_CHANGE, Spreadsheet::TYPE_HTML, $change);

    $proposal = $amendment->getFormattedProposalStatus();
    if ($amendment->hasAlternativeProposaltext()) {
        $reference = $amendment->getMyProposalReference();
        /** @var AmendmentSection[] $sections */
        $sections = $reference->getSortedSections(false);
        foreach ($sections as $section) {
            $firstLine = $section->getFirstLineNumber();
            $lineLength = $section->getCachedConsultation()->getSettings()->lineLength;
            $originalData = $section->getOriginalMotionSection()->getData();
            $newData = $section->getData();
            $proposal .= TextSimple::formatAmendmentForOds($originalData, $newData, $firstLine, $lineLength);
        }
    }
    $doc->setCell($row, $COL_PROCEDURE, Spreadsheet::TYPE_HTML, $proposal);
    $row++;
}

$doc->drawBorder($firstMotionRow, $firstCol, $row - 1, $LAST_COL, 1.5);

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
