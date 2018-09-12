<?php

use app\components\HTMLTools;
use app\models\db\ISupporter;
use CatoTH\HTML2OpenDocument\Text;
use yii\helpers\Html;

/**
 * @var \app\models\db\Amendment $amendment
 * @var \app\models\settings\AntragsgruenApp $config
 */

$config = \yii::$app->params;

$template = $amendment->getMyMotion()->motionType->getOdtTemplateFile();
$doc      = new Text([
    'templateFile' => $template,
    'tmpPath'      => $config->getTmpDir(),
    'trustHtml'    => true,
]);

$DEBUG = (isset($_REQUEST['src']) && YII_ENV == 'dev');

if ($DEBUG) {
    echo "<pre>";
}

$initiators = [];
$supporters = [];
foreach ($amendment->amendmentSupporters as $supp) {
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
if ($amendment->getMyMotion()->agendaItem) {
    $doc->addReplace('/\{\{ANTRAGSGRUEN:ITEM\}\}/siu', $amendment->getMyMotion()->agendaItem->title);
} else {
    $doc->addReplace('/\{\{ANTRAGSGRUEN:ITEM\}\}/siu', '');
}
$doc->addReplace('/\{\{ANTRAGSGRUEN:TITLE\}\}/siu', $amendment->getTitle());
$doc->addReplace('/\{\{ANTRAGSGRUEN:INITIATORS\}\}/siu', $initiatorStr);


if ($amendment->changeEditorial != '') {
    $doc->addHtmlTextBlock('<h2>' . Html::encode(\Yii::t('amend', 'editorial_hint')) . '</h2>', false);
    $editorial = HTMLTools::correctHtmlErrors($amendment->changeEditorial);
    $doc->addHtmlTextBlock($editorial, false);
}

foreach ($amendment->getSortedSections(false) as $section) {
    $section->getSectionType()->printAmendmentToODT($doc);
}

if ($amendment->changeExplanation != '') {
    $doc->addHtmlTextBlock('<h2>' . Html::encode(\Yii::t('amend', 'reason')) . '</h2>', false);
    $explanation = HTMLTools::correctHtmlErrors($amendment->changeExplanation);
    $doc->addHtmlTextBlock($explanation, false);
}

echo $doc->finishAndGetDocument();
