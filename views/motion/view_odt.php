<?php

use app\models\db\ISupporter;

/**
 * @var \app\models\settings\AntragsgruenApp $config
 * @var \app\models\db\Motion $motion
 */

$config = \yii::$app->params;

$template = $motion->motionType->getOdtTemplateFile();
$doc      = new \CatoTH\HTML2OpenDocument\Text([
    'templateFile' => $template,
    'tmpPath'      => $config->tmpDir,
    'trustHtml'    => true,
]);

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
if (count($initiators) == 1) {
    $initiatorStr = \Yii::t('export', 'InitiatorSingle');
} else {
    $initiatorStr = \Yii::t('export', 'InitiatorMulti');
}
$initiatorStr .= ': ' . implode(', ', $initiators);
$doc->addReplace('/\{\{ANTRAGSGRUEN:ITEM\}\}/siu', $motion->agendaItem ? $motion->agendaItem->title : '');
$doc->addReplace('/\{\{ANTRAGSGRUEN:TITLE\}\}/siu', $motion->getTitleWithPrefix());
$doc->addReplace('/\{\{ANTRAGSGRUEN:INITIATORS\}\}/siu', $initiatorStr);

foreach ($motion->getSortedSections() as $section) {
    $section->getSectionType()->printMotionToODT($doc);
}

echo $doc->finishAndGetDocument();
