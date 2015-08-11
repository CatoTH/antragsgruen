<?php

/**
 * @var Motion $motion
 */

use app\models\db\ISupporter;
use app\models\db\Motion;
use yii\helpers\Html;

/** @var \app\models\settings\AntragsgruenApp $config */
$config = \yii::$app->params;

$template = $motion->motionType->getOdtTemplate();

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
foreach ($motion->motionSupporters as $supp) {
    if ($supp->role == ISupporter::ROLE_INITIATOR) {
        $initiators[] = $supp->name;
    }
    if ($supp->role == ISupporter::ROLE_SUPPORTER) {
        $supporters[] = $supp->name;
    }
}
$doc->addReplace("/\{\{ANTRAGSGRUEN:TITLE\}\}/siu", $motion->title);
$doc->addReplace("/\{\{ANTRAGSGRUEN:INITIATORS\}\}/siu", implode(', ', $initiators));

foreach ($motion->getSortedSections() as $section) {
    $htmls = $section->getSectionType()->printMotionToODT($doc);
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
