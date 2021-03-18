<?php

use app\components\{HTMLTools, UrlHelper};
use app\models\db\{Amendment, User};
use yii\helpers\Html;

/**
 * @var Yii\web\View $this
 * @var Amendment $entry
 * @var \app\models\forms\AdminMotionFilterForm $search
 * @var boolean $colMark
 * @var boolean $colProposals
 * @var boolean $colAction
 * @var boolean $colResponsible
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;

$hasTags           = (count($controller->consultation->tags) > 0);
$amendmentStatuses = Amendment::getStatusNames();
if (User::havePrivilege($controller->consultation, User::PRIVILEGE_CONTENT_EDIT)) {
    $editUrl = UrlHelper::createUrl(['admin/amendment/update', 'amendmentId' => $entry->id]);
} else {
    $editUrl = null;
}
$viewUrl = UrlHelper::createAmendmentUrl($entry);
$route   = 'admin/motion-list/index';
echo '<tr class="amendment amendment' . $entry->id . '">';
if ($colMark) {
    echo '<td><input type="checkbox" name="amendments[]" value="' . $entry->id . '" class="selectbox"></td>';
}
echo '<td>' . Yii::t('admin', 'list_amend_short') . '</td>';
echo '<td class="prefixCol">';
echo HTMLTools::amendmentDiffTooltip($entry, 'bottom');
echo '<a href="' . Html::encode($viewUrl) . '">';
if ($lastMotion && $entry->motionId === $lastMotion->id) {
    echo "&#8627;";
}
echo Html::encode($entry->titlePrefix !== '' ? $entry->titlePrefix : '-') . '</a></td>';
echo '<td class="titleCol"><span>';
if ($lastMotion && $entry->motionId === $lastMotion->id) {
    echo "&#8627;";
}
$title = (trim($entry->getMyMotion()->title) !== '' ? $entry->getMyMotion()->title : '-');
if ($editUrl) {
    echo Html::a(Html::encode($title), $editUrl);
} else {
    echo Html::encode($title);
}
echo '</span></td>';
echo '<td>' . Html::encode($amendmentStatuses[$entry->status]);
if ($entry->status === Amendment::STATUS_COLLECTING_SUPPORTERS) {
    echo ' (' . count($entry->getSupporters(true)) . ')';
}
if ($entry->statusString !== null && $entry->statusString !== '') {
    echo ' <small>(' . Html::encode($entry->statusString) . ')</small>';
}
echo '</td>';

if ($colResponsible) {
    ?>
    <td class="responsibilityCol">
        <?= $this->render('_responsibility_dropdown', ['imotion' => $entry, 'type' => 'amendment']) ?>
    </td>
<?php
}
if ($colProposals) {
    echo '<td class="proposalCol">';

    echo $this->render('../proposed-procedure/_status_icons', ['entry' => $entry, 'show_visibility' => true]);

    $name = $entry->getFormattedProposalStatus();
    echo Html::a(($name ? $name : '-'), UrlHelper::createAmendmentUrl($entry));

    if ($entry->proposalStatus === Amendment::STATUS_MODIFIED_ACCEPTED) {
        $url = UrlHelper::createAmendmentUrl($entry, 'edit-proposed-change');
        echo '<div class="editModified"><span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span> ' .
            Html::a(Yii::t('admin', 'amend_edit_text'), $url) . '</div>';
    }
    echo '</td>';
}

echo '<td>' . Html::encode($entry->getInitiatorsStr()) . '</td>';
if ($hasTags) {
    echo '<td></td>';
}
echo '<td class="exportCol">';
if ($entry->getMyMotionType()->texTemplateId || $entry->getMyMotionType()->pdfLayout !== -1) {
    echo Html::a('PDF', UrlHelper::createAmendmentUrl($entry, 'pdf'), ['class' => 'pdf']) . ' / ';
}
echo Html::a('ODT', UrlHelper::createAmendmentUrl($entry, 'odt'), ['class' => 'odt']);
echo '</td>';

if ($colAction) {
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
        $name = Html::encode(Yii::t('admin', 'list_screen'));
        $link = Html::encode($search->getCurrentUrl($route, ['amendmentScreen' => $entry->id]));
        echo '<li><a tabindex="-1" href="' . $link . '" class="screen">' . $name . '</a>';
    } else {
        $name = Html::encode(Yii::t('admin', 'list_unscreen'));
        $link = Html::encode($search->getCurrentUrl($route, ['amendmentUnscreen' => $entry->id]));
        echo '<li><a tabindex="-1" href="' . $link . '" class="unscreen">' . $name . '</a>';
    }
    $name = Html::encode(Yii::t('admin', 'list_template_amendment'));
    $link = Html::encode(UrlHelper::createUrl([
        'amendment/create',
        'motionSlug' => $entry->getMyMotion()->getMotionSlug(),
        'cloneFrom'  => $entry->id
    ]));
    echo '<li><a tabindex="-1" href="' . $link . '" class="asTemplate">' . $name . '</a>';

    $delLink = Html::encode($search->getCurrentUrl($route, ['amendmentDelete' => $entry->id]));
    echo '<li><a tabindex="-1" href="' . $delLink . '" ' .
        'onClick="return confirm(\'' . addslashes(Yii::t('admin', 'list_confirm_del_amend')) . '\');">' .
        Yii::t('admin', 'list_delete') . '</a></li>';
    echo '</ul></div></td>';
}
echo '</tr>';
