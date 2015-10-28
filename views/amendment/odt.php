<?php

use app\models\db\Amendment;
use app\models\db\ISupporter;
use yii\helpers\Html;

/**
 * @var Amendment $amendment
 */

/** @var \app\models\settings\AntragsgruenApp $config */
$config = \yii::$app->params;

$template = $amendment->motion->motionType->getOdtTemplate();

$tmpZipFile = $config->tmpDir . uniqid('zip-');
file_put_contents($tmpZipFile, $template);

$zip = new ZipArchive();
if ($zip->open($tmpZipFile) !== true) {
    die("cannot open <$tmpZipFile>\n");
}

$content = $zip->getFromName('content.xml');

$DEBUG = (isset($_REQUEST['src']) && YII_ENV == 'dev');

if ($DEBUG) {
    echo "<pre>";
}

$doc = new \app\components\opendocument\Text($content);

$initiators = [];
$supporters = [];
foreach ($amendment->amendmentSupporters as $supp) {
    if ($supp->role == ISupporter::ROLE_INITIATOR) {
        $initiators[] = $supp->name;
    }
    if ($supp->role == ISupporter::ROLE_SUPPORTER) {
        $supporters[] = $supp->name;
    }
}
$doc->addReplace("/\{\{ANTRAGSGRUEN:TITLE\}\}/siu", $amendment->getTitle());
$doc->addReplace("/\{\{ANTRAGSGRUEN:INITIATORS\}\}/siu", implode(', ', $initiators));


//    $htmls = $section->getSectionType()->printMotionToODT($doc);
if ($amendment->changeEditorial != '') {
    $doc->addHtmlTextBlock('<h2>' . Html::encode(\Yii::t('amend', 'editorial_hint')) . '</h2>', false);
    $doc->addHtmlTextBlock($amendment->changeEditorial, false);
}

foreach ($amendment->getSortedSections(false) as $section) {
    $section->getSectionType()->printAmendmentToODT($doc);
}

if ($amendment->changeExplanation != '') {
    $doc->addHtmlTextBlock('<h2>' . Html::encode(\Yii::t('amend', 'reason')) . '</h2>', false);
    $doc->addHtmlTextBlock($amendment->changeExplanation, false);
}


$content = $doc->convert();

if ($DEBUG) {
    $doc->debugOutput();
}


$zip->deleteName('content.xml');
$zip->addFromString('content.xml', $content);
$zip->close();

readfile($tmpZipFile);
unlink($tmpZipFile);
