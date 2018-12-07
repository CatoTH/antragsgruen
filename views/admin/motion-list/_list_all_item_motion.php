<?php
use app\components\UrlHelper;
use app\models\db\Motion;
use app\models\db\User;
use yii\helpers\Html;

/**
 * @var Motion $entry
 * @var \app\models\forms\AdminMotionFilterForm $search
 * @var boolean $colMark
 * @var boolean $colProposals
 * @var boolean $colAction
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;

$hasTags        = (count($controller->consultation->tags) > 0);
$motionStatuses = Motion::getStatusNames();
$viewUrl        = UrlHelper::createMotionUrl($entry);
if (User::havePrivilege($controller->consultation, User::PRIVILEGE_CONTENT_EDIT)) {
    $editUrl = UrlHelper::createUrl(['admin/motion/update', 'motionId' => $entry->id]);
} else {
    $editUrl = null;
}
$route       = 'admin/motion-list/index';
echo '<tr class="motion motion' . $entry->id . '">';
if ($colMark) {
    echo '<td><input type="checkbox" name="motions[]" value="' . $entry->id . '" class="selectbox"></td>';
}
echo '<td>' . \Yii::t('admin', 'list_motion_short') . '</td>';
echo '<td class="prefixCol"><a href="' . Html::encode($viewUrl) . '">';
echo Html::encode($entry->titlePrefix !== '' ? $entry->titlePrefix : '-') . '</a></td>';
echo '<td class="titleCol"><span>';
if ($editUrl) {
    echo Html::a(Html::encode(trim($entry->title) != '' ? $entry->title : '-'), $editUrl);
} else {
    echo Html::encode(trim($entry->title) !== '' ? $entry->title : '-');
}
echo '</span></td>';
echo '<td>' . Html::encode($motionStatuses[$entry->status]);
if ($entry->status === Motion::STATUS_COLLECTING_SUPPORTERS) {
    echo ' (' . count($entry->getSupporters()) . ')';
}
if ($entry->statusString !== '') {
    echo ' <small>(' . Html::encode($entry->statusString) . ')</small>';
}
echo '</td>';
if ($colProposals) {
    echo '<td class="proposalCol">';

    echo $this->render('../proposed-procedure/_status_icons', ['entry' => $entry]);

    $name = $entry->getFormattedProposalStatus();
    echo Html::a(($name ? $name : '-'), UrlHelper::createMotionUrl($entry));
    echo '</td>';
}
$initiators = [];
foreach ($entry->getInitiators() as $initiator) {
    if ($initiator->personType === \app\models\db\ISupporter::PERSON_ORGANIZATION) {
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
if ($entry->getMyMotionType()->texTemplateId || $entry->getMyMotionType()->pdfLayout !== -1) {
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

if ($colAction) {
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
}
echo '</tr>';
