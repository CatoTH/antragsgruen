<?php

use app\components\Tools;
use app\components\UrlHelper;
use app\models\db\Motion;
use app\models\db\User;
use yii\helpers\Html;
use app\views\motion\LayoutHelper as MotionLayoutHelper;

/**
 * @var \yii\web\View $this
 * @var Motion $motion
 * @var int[] $openedComments
 * @var string|null $adminEdit
 * @var null|string $supportStatus
 * @var bool $consolidatedAmendments
 * @var \app\controllers\Base $controller
 */

echo '<div class="content">';

$replacedByMotions = $motion->getVisibleReplacedByMotions();
if (count($replacedByMotions) > 0) {
    echo '<div class="alert alert-danger motionReplayedBy" role="alert">';
    echo \Yii::t('motion', 'replaced_by_hint');
    if (count($replacedByMotions) > 1) {
        echo '<ul>';
        foreach ($replacedByMotions as $newMotion) {
            echo '<li>';
            $newLink = UrlHelper::createMotionUrl($newMotion);
            echo Html::a(Html::encode($newMotion->getTitleWithPrefix()), $newLink);
            echo '</li>';
        }
        echo '</ul>';
    } else {
        echo '<br>';
        $newLink = UrlHelper::createMotionUrl($replacedByMotions[0]);
        echo Html::a(Html::encode($replacedByMotions[0]->getTitleWithPrefix()), $newLink);
    }
    echo '</div>';
}

$motionData   = [];
$motionData[] = [
    'title'   => Yii::t('motion', 'consultation'),
    'content' => Html::a(Html::encode($motion->getMyConsultation()->title), UrlHelper::createUrl('consultation/index')),
];

if ($motion->agendaItem) {
    $motionData[] = [
        'title'   => \Yii::t('motion', 'agenda_item'),
        'content' => Html::encode($motion->agendaItem->getShownCode(true) . ' ' . $motion->agendaItem->title),
    ];
}

$initiators = $motion->getInitiators();
if (count($initiators) > 0) {
    $title        = (count($initiators) === 1 ? Yii::t('motion', 'initiators_1') : Yii::t('motion', 'initiators_x'));
    $motionData[] = [
        'title'   => $title,
        'content' => MotionLayoutHelper::formatInitiators($initiators, $controller->consultation),
    ];
}

$motionData[] = [
    'rowClass' => 'statusRow',
    'title'    => \Yii::t('motion', 'status'),
    'content'  => $motion->getFormattedStatus(),
];


if ($motion->replacedMotion) {
    $oldLink = UrlHelper::createMotionUrl($motion->replacedMotion);
    $content = Html::a(Html::encode($motion->replacedMotion->getTitleWithPrefix()), $oldLink);

    $changesLink = UrlHelper::createMotionUrl($motion, 'view-changes');
    $content     .= '<div class="changesLink">';
    $content     .= '<span class="glyphicon glyphicon-chevron-right"></span> ';
    $content     .= Html::a(\Yii::t('motion', 'replaces_motion_diff'), $changesLink);
    $content     .= '</div>';

    $motionData[] = [
        'rowClass' => 'replacesMotion',
        'title'    => Yii::t('motion', 'replaces_motion'),
        'content'  => $content,
    ];
}

$proposalAdmin = User::havePrivilege($consultation, User::PRIVILEGE_CHANGE_PROPOSALS);
if (($motion->isProposalPublic() && $motion->proposalStatus) || $proposalAdmin) {
    $motionData[] = [
        'rowClass' => 'proposedStatusRow',
        'title'    => \Yii::t('amend', 'proposed_status'),
        'tdClass'  => 'str',
        'content'  => $motion->getFormattedProposalStatus(true),
    ];
}

if ($motion->dateResolution != '') {
    $motionData[] = [
        'title'   => \Yii::t('motion', 'resoluted_on'),
        'content' => Tools::formatMysqlDate($motion->dateResolution, null, false),
    ];
}
$motionData[] = [
    'title'   => \Yii::t('motion', ($motion->isSubmitted() ? 'submitted_on' : 'created_on')),
    'content' => Tools::formatMysqlDateTime($motion->dateCreation, null, false),
];


$admin = User::havePrivilege($controller->consultation, User::PRIVILEGE_SCREENING);
if ($admin && count($motion->getMyConsultation()->tags) > 0) {
    $tags         = [];
    $used_tag_ids = [];
    foreach ($motion->tags as $tag) {
        $used_tag_ids[] = $tag->id;
        $str            = Html::encode($tag->title);
        $str            .= Html::beginForm('', 'post', ['class' => 'form-inline delTagForm delTag' . $tag->id]);
        $str            .= '<input type="hidden" name="tagId" value="' . $tag->id . '">';
        $str            .= '<button type="submit" name="motionDelTag">' . \Yii::t('motion', 'tag_del') . '</button>';
        $str            .= Html::endForm();
        $tags[]         = $str;
    }
    $content = implode(', ', $tags);

    $content .= '&nbsp; &nbsp; <a href="#" class="tagAdderHolder">' . \Yii::t('motion', 'tag_new') . '</a>';
    $content .= Html::beginForm('', 'post', ['id' => 'tagAdderForm', 'class' => 'form-inline hidden']);
    $content .= '<select name="tagId" title="' . \Yii::t('motion', 'tag_select') . '" class="form-control">
        <option>-</option>';

    foreach ($motion->getMyConsultation()->tags as $tag) {
        if (!in_array($tag->id, $used_tag_ids)) {
            $content .= '<option value="' . IntVal($tag->id) . '">' . Html::encode($tag->title) . '</option>';
        }
    }
    $content .= '</select>
            <button class="btn btn-primary" type="submit" name="motionAddTag">' .
        \Yii::t('motion', 'tag_add') .
        '</button>';
    $content .= Html::endForm();
    $content .= '</td> </tr>';

    $motionData[] = [
        'title'   => \Yii::t('motion', 'tag_tags'),
        'tdClass' => 'tags',
        'content' => $content,
    ];
} elseif (count($motion->tags) > 0) {
    $tags = [];
    foreach ($motion->tags as $tag) {
        $tags[] = $tag->title;
    }
    $motionData[] = [
        'title'   => (count($motion->tags) > 1 ? \Yii::t('motion', 'tags') : \Yii::t('motion', 'tag')),
        'content' => Html::encode(implode(', ', $tags)),
    ];
}

if ((!isset($skip_drafts) || !$skip_drafts) && $motion->getMergingDraft(true)) {
    $url          = UrlHelper::createMotionUrl($motion, 'merge-amendments-public');
    $motionData[] = [
        'rowClass' => 'mergingDraft',
        'title'    => \Yii::t('motion', 'merging_draft_th'),
        'content'  => str_replace('%URL%', Html::encode($url), \Yii::t('motion', 'merging_draft_td')),
    ];
}

$motionData = \app\models\layoutHooks\Layout::getMotionViewData($motionData, $motion);


echo '<table class="motionDataTable">';
foreach ($motionData as $row) {
    if (isset($row['rowClass'])) {
        echo '<tr class="' . $row['rowClass'] . '">';
    } else {
        echo '<tr>';
    }
    echo '<th>' . $row['title'] . ':</th>';
    if (isset($row['tdClass'])) {
        echo '<td class="' . $row['tdClass'] . '">' . $row['content'] . '</td>';
    } else {
        echo '<td>' . $row['content'] . '</td>';
    }
    echo '</tr>' . "\n";
}
echo '</table></div>';
