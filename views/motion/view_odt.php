<?php

use app\models\db\ISupporter;
use app\models\LimitedSupporterList;
use yii\helpers\Html;

/**
 * @var \app\models\settings\AntragsgruenApp $config
 * @var \app\models\db\Motion $motion
 */

$config = Yii::$app->params;

$template = $motion->motionType->getOdtTemplateFile();
/** @noinspection PhpUnhandledExceptionInspection */
$doc = new \CatoTH\HTML2OpenDocument\Text([
    'templateFile' => $template,
    'tmpPath'      => $config->getTmpDir(),
    'trustHtml'    => true,
]);

$initiators = [];
$supporters = [];
foreach ($motion->motionSupporters as $supp) {
    if ($supp->role === ISupporter::ROLE_INITIATOR) {
        $initiators[] = $supp->getNameWithOrga();
    }
    if ($supp->role === ISupporter::ROLE_SUPPORTER) {
        $supporters[] = $supp->getNameWithOrga();
    }
}
if (count($initiators) === 1) {
    $initiatorStr = Yii::t('export', 'InitiatorSingle');
} else {
    $initiatorStr = Yii::t('export', 'InitiatorMulti');
}
$initiatorStr .= ': ' . implode(', ', $initiators);
$doc->addReplace('/\{\{ANTRAGSGRUEN:ITEM\}\}/siu', $motion->agendaItem ? $motion->agendaItem->title : '');
$doc->addReplace('/\{\{ANTRAGSGRUEN:TITLE\}\}/siu', $motion->getTitleWithPrefix());
$doc->addReplace('/\{\{ANTRAGSGRUEN:INITIATORS\}\}/siu', $initiatorStr);

foreach ($motion->getSortedSections() as $section) {
    $section->getSectionType()->printMotionToODT($doc);
}

$limitedSupporters = LimitedSupporterList::createFromIMotion($motion);
if (count($limitedSupporters->supporters) > 0) {
    $doc->addHtmlTextBlock('<h2>' . Html::encode(Yii::t('motion', 'supporters_heading')) . '</h2>', false);

    $supps = [];
    foreach ($limitedSupporters->supporters as $supp) {
        $supps[] = $supp->getNameWithOrga();
    }

    $doc->addHtmlTextBlock('<p>' . Html::encode(implode('; ', $supps)) . $limitedSupporters->truncatedToString(';') . '</p>', false);
}

echo $doc->finishAndGetDocument();
