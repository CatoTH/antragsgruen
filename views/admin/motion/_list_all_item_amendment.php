<?php
use app\components\HTMLTools;
use app\components\UrlHelper;
use app\models\db\Amendment;
use yii\helpers\Html;

/**
 * @var Amendment $entry
 * @var \app\models\forms\AdminMotionFilterForm $search
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;

$hasTags        = (count($controller->consultation->tags) > 0);
$amendmentStati = Amendment::getStati();
$editUrl        = UrlHelper::createUrl(['admin/amendment/update', 'amendmentId' => $entry->id]);
$viewUrl        = UrlHelper::createAmendmentUrl($entry);
$route          = 'admin/motion/listall';
echo '<tr class="amendment amendment' . $entry->id . '">';
echo '<td><input type="checkbox" name="amendments[]" value="' . $entry->id . '" class="selectbox"></td>';
echo '<td>' . \Yii::t('admin', 'list_amend_short') . '</td>';
echo '<td class="prefixCol">';
echo HTMLTools::amendmentDiffTooltip($entry, 'bottom');
echo '<a href="' . Html::encode($viewUrl) . '">';
if ($lastMotion && $entry->motionId == $lastMotion->id) {
    echo "&#8627;";
}
echo Html::encode($entry->titlePrefix != '' ? $entry->titlePrefix : '-') . '</a></td>';
echo '<td class="titleCol"><span>';
if ($lastMotion && $entry->motionId == $lastMotion->id) {
    echo "&#8627;";
}
$title = (trim($entry->getMyMotion()->title) != '' ? $entry->getMyMotion()->title : '-');
echo Html::a($title, $editUrl) . '</span></td>';
echo '<td>' . Html::encode($amendmentStati[$entry->status]);
if ($entry->status == Amendment::STATUS_COLLECTING_SUPPORTERS) {
    echo ' (' . count($entry->getSupporters()) . ')';
}
echo '</td>';
$initiators = [];
foreach ($entry->getInitiators() as $initiator) {
    if ($initiator->personType == \app\models\db\ISupporter::PERSON_ORGANIZATION) {
        $initiators[] = $initiator->organization;
    } else {
        $initiators[] = $initiator->name;
    }
}
echo '<td>' . Html::encode(implode(', ', $initiators)) . '</td>';
if ($hasTags) {
    echo '<td></td>';
}
echo '<td class="exportCol">';
if ($entry->getMyMotionType()->texTemplateId || $entry->getMyMotionType()->pdfLayout != -1) {
    echo Html::a('PDF', UrlHelper::createAmendmentUrl($entry, 'pdf'), ['class' => 'pdf']) . ' / ';
}
echo Html::a('ODT', UrlHelper::createAmendmentUrl($entry, 'odt'), ['class' => 'odt']);
echo '</td>';

echo '<td class="actionCol"><div class="btn-group">
  <button class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
    Aktion
    <span class="caret"></span>
  </button>
  <ul class="dropdown-menu">';
$screenable = [
    Amendment::STATUS_DRAFT,
    Amendment::STATUS_DRAFT_ADMIN,
    Amendment::STATUS_SUBMITTED_UNSCREENED,
    Amendment::STATUS_SUBMITTED_UNSCREENED_CHECKED,
];
if (in_array($entry->status, $screenable)) {
    $name = Html::encode(\Yii::t('admin', 'list_screen'));
    $link = Html::encode($search->getCurrentUrl($route, ['amendmentScreen' => $entry->id]));
    echo '<li><a tabindex="-1" href="' . $link . '" class="screen">' . $name . '</a>';
} else {
    $name = Html::encode(\Yii::t('admin', 'list_unscreen'));
    $link = Html::encode($search->getCurrentUrl($route, ['amendmentUnscreen' => $entry->id]));
    echo '<li><a tabindex="-1" href="' . $link . '" class="unscreen">' . $name . '</a>';
}
$name = Html::encode(\Yii::t('admin', 'list_template_amendment'));
$link = Html::encode(UrlHelper::createUrl([
    'amendment/create',
    'motionSlug' => $entry->getMyMotion()->getMotionSlug(),
    'cloneFrom'  => $entry->id
]));
echo '<li><a tabindex="-1" href="' . $link . '" class="asTemplate">' . $name . '</a>';

$delLink = Html::encode($search->getCurrentUrl($route, ['amendmentDelete' => $entry->id]));
echo '<li><a tabindex="-1" href="' . $delLink . '" ' .
    'onClick="return confirm(\'' . addslashes(\Yii::t('admin', 'list_confirm_del_amend')) . '\');">' .
    \Yii::t('admin', 'list_delete') . '</a></li>';
echo '</ul></div></td>';
echo '</tr>';
