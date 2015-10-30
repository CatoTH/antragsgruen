<?php

use app\models\db\ISupporter;
use app\models\db\Motion;

/**
 * @var Motion $motion
 */

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
        $initiators[] = $supp->getNameWithOrga();
    }
    if ($supp->role == ISupporter::ROLE_SUPPORTER) {
        $supporters[] = $supp->getNameWithOrga();
    }
}
$initiatorStr = (count($initiators) == 1 ? \Yii::t('pdf', 'InitiatorSingle') : \Yii::t('pdf', 'InitiatorMulti'));
$initiatorStr .= ': ' . implode(', ', $initiators);
$doc->addReplace('/\{\{ANTRAGSGRUEN:ITEM\}\}/siu', $motion->agendaItem ? $motion->agendaItem->title : '');
$doc->addReplace('/\{\{ANTRAGSGRUEN:TITLE\}\}/siu', $motion->getTitleWithPrefix());
$doc->addReplace('/\{\{ANTRAGSGRUEN:INITIATORS\}\}/siu', $initiatorStr);

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
