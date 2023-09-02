<?php

use app\models\settings\{PrivilegeQueryContext, Privileges};
use app\components\{HTMLTools, Tools, UrlHelper};
use app\models\db\{Amendment, User};
use yii\helpers\Html;
use app\views\motion\LayoutHelper as MotionLayoutHelper;

/**
 * @var Yii\web\View $this
 * @var Amendment $amendment
 */

$motion       = $amendment->getMyMotion();
$consultation = $amendment->getMyConsultation();
$motionType   = $amendment->getMyMotionType();

$motionDataMode = $motion->getMyConsultation()->getSettings()->motiondataMode;
if ($motionDataMode === \app\models\settings\Consultation::MOTIONDATA_NONE) {
    return;
}

echo $this->render('@app/views/shared/translate', ['toTranslateUrl' => UrlHelper::createAmendmentUrl($amendment)]);

$amendmentData = [];

if ($amendment->amendingAmendmentId && $amendment->amendedAmendment) {
    $amendmentData[] = [
        'rowClass' => 'amendingAmendmentRow',
        'title' => Yii::t('amend', 'based_on_amend'),
        'content' => Html::a(Html::encode($amendment->amendedAmendment->getTitleWithPrefix()), UrlHelper::createAmendmentUrl($amendment->amendedAmendment)),
    ];
}
if (!$motionType->amendmentsOnly) {
    $amendmentData[] = [
        'rowClass' => 'motionRow',
        'title' => $motionType->titleSingular,
        'content' => Html::a(Html::encode($motion->title), UrlHelper::createMotionUrl($motion)),
    ];
}

$amendmentData[] = [
    'title'   => Yii::t('amend', 'initiator'),
    'content' => MotionLayoutHelper::formatInitiators($amendment->getInitiators(), $amendment),
];

$amendmentData[] = [
    'rowClass' => 'statusRow',
    'title'    => Yii::t('amend', 'status'),
    'content'  => $amendment->getFormattedStatus(),
];

MotionLayoutHelper::addVotingResultsRow($amendment->getVotingData(), $amendmentData);

$proposalAdmin = User::havePrivilege($consultation, Privileges::PRIVILEGE_CHANGE_PROPOSALS, PrivilegeQueryContext::amendment($amendment));
if (($amendment->isProposalPublic() && $amendment->proposalStatus) || $proposalAdmin) {
    $amendmentData[] = [
        'title'   => Yii::t('amend', 'proposed_status'),
        'content' => $amendment->getFormattedProposalStatus(true),
    ];
    // CSS-Class "str" ?
}
if ($amendment->dateResolution) {
    $amendmentData[] = [
        'title'   => Yii::t('amend', 'resoluted_on'),
        'content' => Tools::formatMysqlDate($amendment->dateResolution),
    ];
}
$amendmentData[] = [
    'title'   => Yii::t('amend', ($amendment->isSubmitted() ? 'submitted_on' : 'created_on')),
    'content' => Tools::formatMysqlDateTime($amendment->dateCreation),
];

MotionLayoutHelper::addTagsRow($amendment, $amendment->getPublicTopicTags(), $amendmentData);

$amendmentData = \app\models\layoutHooks\Layout::getAmendmentViewData($amendmentData, $amendment);

if (User::getCurrentUser()) {
    $comment = $amendment->getPrivateComment();

    $str = '';
    if ($comment) {
        $str .= '<blockquote class="privateNote" id="comm' . $comment->id . '">';
        $str .= '<button class="btn btn-link btn-xs btnEdit"><span class="glyphicon glyphicon-edit">' .
                '</span></button>';
        $str .= HTMLTools::textToHtmlWithLink($comment->text) . '</blockquote>';
    }
    $str .= Html::beginForm('', 'post', ['class' => 'form-inline' . ($comment ? ' hidden' : '')]);
    $str .= '<textarea class="form-control" name="noteText" title="' . Yii::t('motion', 'private_notes') . '">';
    if ($comment) {
        $str .= Html::encode($comment->text);
    }
    $str .= '</textarea>';
    $str .= '<input type="hidden" name="paragraphNo" value="-1">';
    $str .= '<input type="hidden" name="sectionId" value="">';
    $str .= '<button type="submit" name="savePrivateNote" class="btn btn-success">' .
            Yii::t('base', 'save') . '</button>';
    $str .= Html::endForm();

    $amendmentData[] = [
        'rowClass' => 'privateNotes' . ($comment ? '' : ' hidden'),
        'title'    => Yii::t('motion', 'private_notes'),
        'content'  => $str,
    ];
}


echo '<div class="sr-only" id="motionDataTableDescription">' . Yii::t('amend', 'table_description') . '</div>';
echo '<table class="motionDataTable" aria-describedby="motionDataTableDescription">';
echo '<caption>' . Yii::t('amend', 'table_caption') . '</caption>';
foreach ($amendmentData as $row) {
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
echo '</table>';
