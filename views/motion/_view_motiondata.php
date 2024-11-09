<?php

use app\models\settings\PrivilegeQueryContext;
use app\models\settings\Privileges;
use app\components\{HTMLTools, MotionNumbering, Tools, UrlHelper};
use app\models\db\{Motion, MotionSupporter, User, Consultation};
use yii\helpers\Html;
use app\views\motion\LayoutHelper as MotionLayoutHelper;

/**
 * @var Yii\web\View $this
 * @var bool $reducedNavigation
 * @var Motion $motion
 * @var Consultation $consultation
 * @var int[] $openedComments
 * @var null|string $supportStatus
 * @var bool $consolidatedAmendments
 * @var bool $hasPrivateComments
 * @var \app\controllers\Base $controller
 */

$motionDataMode = $motion->getMyConsultation()->getSettings()->motiondataMode;
if ($motionDataMode === \app\models\settings\Consultation::MOTIONDATA_NONE) {
    return;
}

echo '<div class="content">';


echo $this->render('@app/views/shared/translate', ['toTranslateUrl' => UrlHelper::createMotionUrl($motion)]);

$motionHistory = array_values(array_filter(
    MotionNumbering::getSortedHistoryForMotion($motion, false),
    fn($motion) => $motion->isReadable()
));

$replacedByMotion = MotionNumbering::findMostRecentVersionOfMotion($motion, true);
if ($replacedByMotion) {
    echo '<div class="alert alert-danger motionReplacedBy" role="alert">';
    echo Yii::t('motion', 'replaced_by_hint');
    echo '<br>';
    $newLink = UrlHelper::createMotionUrl($replacedByMotion);
    echo Html::a(Html::encode($replacedByMotion->getTitleWithPrefix()), $newLink);
    echo '</div>';
}

$motionData   = [];

if ($motionDataMode === \app\models\settings\Consultation::MOTIONDATA_ALL && !$reducedNavigation) {
    $motionData[] = [
        'title'   => Yii::t('motion', 'consultation'),
        'content' => Html::a(Html::encode($motion->getMyConsultation()->title), UrlHelper::createUrl('consultation/index')),
    ];
}

if ($motion->agendaItem && $motionDataMode === \app\models\settings\Consultation::MOTIONDATA_ALL) {
    $motionData[] = [
        'title'   => Yii::t('motion', 'agenda_item'),
        'content' => Html::encode($motion->agendaItem->getShownCode(true) . ' ' . $motion->agendaItem->title),
    ];
}

$initiators = $motion->getInitiators();
if (count($initiators) > 0 && !$motion->isResolution()) {
    $title        = (count($initiators) === 1 ? Yii::t('motion', 'initiators_1') : Yii::t('motion', 'initiators_x'));
    $motionData[] = [
        'title'   => $title,
        'content' => MotionLayoutHelper::formatInitiators($initiators, $motion),
    ];
}

if ($motionDataMode === \app\models\settings\Consultation::MOTIONDATA_ALL || $motion->status !== Motion::STATUS_SUBMITTED_SCREENED) {
    $motionData[] = [
        'rowClass' => 'statusRow',
        'title'    => Yii::t('motion', 'status'),
        'content'  => $motion->getFormattedStatus(),
    ];
}

MotionLayoutHelper::addVotingResultsRow($motion->getVotingData(), $motionData);

if (!$motion->isResolution()) {
    $proposalAdmin = User::havePrivilege($consultation, Privileges::PRIVILEGE_CHANGE_PROPOSALS, PrivilegeQueryContext::motion($motion));
    if (($motion->isProposalPublic() && $motion->proposalStatus) || $proposalAdmin) {
        $motionData[] = [
            'rowClass' => 'proposedStatusRow',
            'title'    => Yii::t('amend', 'proposed_status'),
            'tdClass'  => 'str',
            'content'  => $motion->getFormattedProposalStatus(true),
        ];
    }
}

if (count($initiators) > 0 && $motion->isResolution()) {
    $title        = Yii::t('motion', 'resolution_organisation');
    $names        = array_map(function (MotionSupporter $supp) {
        return ($supp->personType === MotionSupporter::PERSON_ORGANIZATION ? $supp->organization : $supp->name);
    }, $initiators);
    $motionData[] = [
        'title'   => $title,
        'content' => Html::encode(implode(', ', $names)),
    ];
}


if ($motion->dateResolution) {
    $motionData[] = [
        'title'   => Yii::t('motion', 'resoluted_on'),
        'content' => Tools::formatMysqlDate($motion->dateResolution, false),
    ];
}

if ($motion->version === Motion::VERSION_DEFAULT && $motionDataMode === \app\models\settings\Consultation::MOTIONDATA_ALL) {
    $motionData[] = [
        'title'   => Yii::t('motion', ($motion->isSubmitted() ? 'submitted_on' : 'created_on')),
        'content' => Tools::formatMysqlDateTime($motion->dateCreation, false),
    ];
}

MotionLayoutHelper::addTagsRow($motion, $motion->getPublicTopicTags(), $motionData);

if (count($motionHistory) > 1) {
    $historyIsOpen = ($motionHistory[count($motionHistory) - 1]->id !== $motion->id);
    $historyContent = '<div class="fullHistory' . ($historyIsOpen ? '' : ' hidden') . '">';
    $historyMotionIds = array_map(fn(Motion $motion) => $motion->id, $motionHistory);
    foreach ($motionHistory as $motionHis) {
        $historyLine = '';
        $versionName = $motionHis->getFormattedVersion();
        if ($motionHis->id === $motion->id) {
            $historyLine .= '<div class="historyLine currentVersion">';
            $historyLine .= '<span class="currVersion">' . Html::encode($versionName) . '</span>';
        } else {
            $historyLine .= '<div class="historyLine otherVersion">';
            $className = 'motion' . $motionHis->id;
            $historyLine .= Html::a(Html::encode($versionName), UrlHelper::createMotionUrl($motionHis), ['class' => $className]);
        }

        $historyLine .= '<span class="date">(' . Tools::formatMysqlDate($motionHis->dateCreation, false) . ')</span>';

        if ($motionHis->version > Motion::VERSION_DEFAULT && in_array($motionHis->parentMotionId, $historyMotionIds)) {
            $changesUrl = UrlHelper::createMotionUrl($motionHis, 'view-changes');
            $changesLink = '<span class="changesLink">';
            $changesLink .= '<span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span> ';
            $changesLink .= Html::a(Yii::t('motion', 'replaces_motion_diff'), $changesUrl);
            $changesLink .= '</span>';
            $historyLine .= $changesLink;
        }

        $historyLine .= '</div>';

        $historyContent .= $historyLine;
    }
    $historyContent .= '</div><div class="historyOpener' . ($historyIsOpen ? ' hidden' : '') . '">';
    $historyContent .= '<div class="historyLine currentVersion">';
    $historyContent .= '<span class="currVersion">' . Html::encode($motion->getFormattedVersion()) . '</span>';
    $historyContent .= ' <button class="btn btn-link btnHistoryOpener" type="button">';
    $historyContent .= '<span class="glyphicon glyphicon-chevron-up" aria-hidden="true"></span>';
    $historyContent .= Yii::t('motion', 'replaces_show_history') . '</button>';
    $historyContent .= '</div>';

    $motionData[] = [
        'rowClass' => 'motionHistory',
        'title' => Yii::t('motion', 'version_history'),
        'content' => $historyContent,
    ];
}

$protocol = $motion->getProtocol();
if ($protocol && $protocol->status === \app\models\db\IAdminComment::TYPE_PROTOCOL_PUBLIC) {
    $motionData[] = [
        'rowClass' => 'motionProtocol',
        'title' => Yii::t('motion', 'protocol_show'),
        'content' => '<button type="button" class="btn btn-link protocolOpener">' .
            '<span class="glyphicon glyphicon-chevron-down" aria-hidden="true"></span>' .
            Yii::t('motion', 'protocol_show') . '</button>'
        ,
    ];
}

if ((!isset($skip_drafts) || !$skip_drafts) && $motion->getMergingDraft(true)) {
    $url          = UrlHelper::createMotionUrl($motion, 'merge-amendments-public');
    $motionData[] = [
        'rowClass' => 'mergingDraft',
        'title'    => Yii::t('motion', 'merging_draft_th'),
        'content'  => str_replace('%URL%', Html::encode($url), Yii::t('motion', 'merging_draft_td')),
    ];
}

$motionData = \app\models\layoutHooks\Layout::getMotionViewData($motionData, $motion);


if (User::getCurrentUser()) {
    $comment = $motion->getPrivateComment(null, -1);

    $str = '';
    if ($comment) {
        $str .= '<blockquote class="privateNote" id="comm' . $comment->id . '">';
        $str .= '<button class="btn btn-link btn-xs btnEdit">';
        $str .= '<span class="glyphicon glyphicon-edit" aria-hidden="true"></span>';
        $str .= '<span class="sr-only">' . Yii::t('motion', 'private_notes_edit') . '</span>';
        $str .= '</button>';
        $str .= HTMLTools::textToHtmlWithLink($comment ? $comment->text : '') . '</blockquote>';
    }
    $str .= Html::beginForm('', 'post', ['class' => 'form-inline' . ($comment ? ' hidden' : '')]);
    $str .= '<textarea class="form-control" name="noteText" title="' . Yii::t('motion', 'private_notes_write') . '">';
    if ($comment) {
        $str .= Html::encode($comment->text);
    }
    $str .= '</textarea>';
    $str .= '<input type="hidden" name="paragraphNo" value="-1">';
    $str .= '<input type="hidden" name="sectionId" value="">';
    $str .= '<button type="submit" name="savePrivateNote" class="btn btn-success">' .
            Yii::t('base', 'save') . '</button>';
    $str .= Html::endForm();

    $motionData[] = [
        'rowClass' => 'privateNotes' . ($comment ? '' : ' hidden'),
        'title'    => Yii::t('motion', 'private_notes_open'),
        'content'  => $str,
    ];
}

echo '<div class="sr-only" id="motionDataTableDescription">' . Yii::t('motion', 'table_description') . '</div>';
echo '<table class="motionDataTable" aria-describedby="motionDataTableDescription">';
echo '<caption>' . Yii::t('motion', 'table_caption') . '</caption>';
foreach ($motionData as $row) {
    if (isset($row['rowClass'])) {
        echo '<tr class="' . $row['rowClass'] . '">';
    } else {
        echo '<tr>';
    }
    echo '<th scope="row">' . $row['title'] . ':</th>';
    if (isset($row['tdClass'])) {
        echo '<td class="' . $row['tdClass'] . '">' . $row['content'] . '</td>';
    } else {
        echo '<td>' . $row['content'] . '</td>';
    }
    echo '</tr>' . "\n";
}
echo '</table></div>';
