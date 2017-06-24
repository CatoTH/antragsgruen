<?php
use app\components\UrlHelper;
use app\models\db\Motion;
use yii\helpers\Html;

/**
 * @var Motion $entry
 * @var \app\models\forms\AdminMotionFilterForm $search
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;

$hasTags     = (count($controller->consultation->tags) > 0);
$motionStati = Motion::getStati();
$viewUrl     = UrlHelper::createMotionUrl($entry);
$editUrl     = UrlHelper::createUrl(['admin/motion/update', 'motionId' => $entry->id]);
$route       = 'admin/motion/listall';
echo '<tr class="motion motion' . $entry->id . '">';
echo '<td><input type="checkbox" name="motions[]" value="' . $entry->id . '" class="selectbox"></td>';
echo '<td>' . \Yii::t('admin', 'list_motion_short') . '</td>';
echo '<td class="prefixCol"><a href="' . Html::encode($viewUrl) . '">';
echo Html::encode($entry->titlePrefix != '' ? $entry->titlePrefix : '-') . '</a></td>';
echo '<td class="titleCol"><span>';
echo Html::a((trim($entry->title) != '' ? $entry->title : '-'), $editUrl);
echo '</span></td>';
echo '<td>' . Html::encode($motionStati[$entry->status]);
if ($entry->status == Motion::STATUS_COLLECTING_SUPPORTERS) {
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
    $tags = [];
    foreach ($entry->tags as $tag) {
        $tags[] = $tag->title;
    }
    echo '<td>' . Html::encode(implode(', ', $tags)) . '</td>';
}
echo '<td class="exportCol">';
if ($entry->getMyMotionType()->texTemplateId || $entry->getMyMotionType()->pdfLayout != -1) {
    echo Html::a('PDF', UrlHelper::createMotionUrl($entry, 'pdf'), ['class' => 'pdf']) . ' / ';
    echo Html::a(
        \Yii::t('admin', 'list_pdf_amend'),
            UrlHelper::createMotionUrl($entry, 'pdfamendcollection'),
            ['class' => 'pdfamend']
        ) . ' / ';
}
echo Html::a('ODT', UrlHelper::createMotionUrl($entry, 'odt'), ['class' => 'odt']) . ' / ';
echo Html::a('HTML', UrlHelper::createMotionUrl($entry, 'plainhtml'), ['class' => 'plainHtml']);
echo '</td>';

echo '<td class="actionCol"><div class="btn-group">
  <button class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
    ' . \Yii::t('admin', 'list_action') . '
    <span class="caret"></span>
  </button>
  <ul class="dropdown-menu">';
$screenable = [
    Motion::STATUS_DRAFT,
    Motion::STATUS_DRAFT_ADMIN,
    Motion::STATUS_SUBMITTED_UNSCREENED,
    Motion::STATUS_SUBMITTED_UNSCREENED_CHECKED,
];
if (in_array($entry->status, $screenable)) {
    $link = Html::encode($search->getCurrentUrl($route, ['motionScreen' => $entry->id]));
    $name = Html::encode(\Yii::t('admin', 'list_screen'));
    echo '<li><a tabindex="-1" href="' . $link . '" class="screen">' . $name . '</a>';
} else {
    $link = Html::encode($search->getCurrentUrl($route, ['motionUnscreen' => $entry->id]));
    $name = Html::encode(\Yii::t('admin', 'list_unscreen'));
    echo '<li><a tabindex="-1" href="' . $link . '" class="unscreen">' . $name . '</a>';
}
$link = Html::encode(UrlHelper::createUrl(['motion/create', 'cloneFrom' => $entry->id]));
$name = Html::encode(\Yii::t('admin', 'list_template_motion'));
echo '<li><a tabindex="-1" href="' . $link . '" class="asTemplate">' . $name . '</a>';

$delLink = Html::encode($search->getCurrentUrl($route, ['motionDelete' => $entry->id]));
echo '<li><a tabindex="-1" href="' . $delLink . '" class="delete" ' .
    'onClick="return confirm(\'' . addslashes(\Yii::t('admin', 'list_confirm_del_motion')) . '\');">' .
    \Yii::t('admin', 'list_delete') . '</a></li>';
echo '</ul></div></td>';
echo '</tr>';
