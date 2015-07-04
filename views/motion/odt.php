<?php

/**
 * @var Motion $motion
 */

use app\models\db\ISupporter;
use app\models\db\Motion;

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

$DEBUG = false;

if ($DEBUG) {
    echo "<pre>";
}

$doc = new \app\components\opendocument\Text($content);

/** @var array|string[] $initiatorinnen */
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
$doc->addReplace("/\{\{ANTRAGSGRUEN:TITEL\}\}/siu", $motion->title);
$doc->addReplace("/\{\{ANTRAGSGRUEN:ANTRAGSTELLERINNEN\}\}/siu", implode(', ', $supporters));


if ($DEBUG) {
    $doc->debugOutput();
}

$absae   = $motion->getParagraphs();
$content = $doc->convert($absae, $model->begruendung);

$zip->deleteName("content.xml");
$zip->addFromString("content.xml", $content);
$zip->close();

readfile($tmpZipFile);
unlink($tmpZipFile);
