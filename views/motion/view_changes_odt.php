<?php

use app\components\HTMLTools;
use app\models\db\ISupporter;
use app\models\sectionTypes\{ISectionType, TextSimple};
use CatoTH\HTML2OpenDocument\Text;
use yii\helpers\Html;

/**
 * @var \app\models\db\Motion $oldMotion
 * @var \app\models\MotionSectionChanges[] $changes
 */

$template = $oldMotion->motionType->getOdtTemplateFile();
$doc      = new Text([
    'templateFile' => $template,
    'tmpPath'      => \app\models\settings\AntragsgruenApp::getInstance()->getTmpDir(),
    'trustHtml'    => true,
]);

$DEBUG = (isset($_REQUEST['src']) && YII_ENV == 'dev');

if ($DEBUG) {
    echo "<pre>";
}

$initiators = [];
$supporters = [];
foreach ($oldMotion->motionSupporters as $supp) {
    if ($supp->role == ISupporter::ROLE_INITIATOR) {
        $initiators[] = $supp->getNameWithOrga();
    }
    if ($supp->role == ISupporter::ROLE_SUPPORTER) {
        $supporters[] = $supp->getNameWithOrga();
    }
}
if (count($initiators) === 1) {
    $initiatorStr = Yii::t('export', 'InitiatorSingle');
} else {
    $initiatorStr = Yii::t('export', 'InitiatorMulti');
}
$initiatorStr .= ': ' . implode(', ', $initiators);
$doc->addReplace('/\{\{ANTRAGSGRUEN:TITLE\}\}/siu', $oldMotion->getTitleWithPrefix());
$doc->addReplace('/\{\{ANTRAGSGRUEN:INITIATORS\}\}/siu', $initiatorStr);
$doc->addReplace('/\{\{ANTRAGSGRUEN:STATUS\}\}/siu', '');


foreach ($changes as $change) {
    $doc->addHtmlTextBlock('<h2>' . Html::encode($change->getSectionTitle()) . '</h2>', false);
    if (!$change->hasChanges()) {
        $doc->addHtmlTextBlock('<p>' . Yii::t('motion', 'diff_no_change') . '</p>', false);
        continue;
    }

    switch ($change->getSectionTypeId()) {
        case ISectionType::TYPE_TEXT_SIMPLE:
            $firstLine  = $change->getFirstLineNumber();
            $diffGroups = $change->getSimpleTextDiffGroups();

            $wrapStart = '<div>';
            $wrapEnd   = '</div>';
            $html      = TextSimple::formatDiffGroup($diffGroups, $wrapStart, $wrapEnd, $firstLine);

            $doc->addHtmlTextBlock(HTMLTools::correctHtmlErrors($html), false);
            break;
        default:
            $doc->addHtmlTextBlock('<p>' . Yii::t('motion', 'diff_err_display') . '</p>', false);
    }
}

echo $doc->finishAndGetDocument();
