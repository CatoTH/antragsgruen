<?php

use app\components\{HTMLTools, Tools, UrlHelper};
use app\models\db\{Amendment, User};
use yii\helpers\Html;
use app\views\motion\LayoutHelper as MotionLayoutHelper;

/**
 * @var Yii\web\View $this
 * @var Amendment $amendment
 */

$motion       = $amendment->getMyMotion();
$consultation = $motion->getMyConsultation();

?>
<table class="motionDataTable">
    <tr>
        <th><?= Yii::t('amend', 'motion') ?>:</th>
        <td><?= Html::a(Html::encode($motion->title), UrlHelper::createMotionUrl($motion)) ?></td>
    </tr>
    <tr>
        <th><?= Yii::t('amend', 'initiator') ?>:</th>
        <td><?= MotionLayoutHelper::formatInitiators($amendment->getInitiators(), $consultation) ?></td>
    </tr>
    <tr class="statusRow">
        <th><?= Yii::t('amend', 'status') ?>:</th>
        <td>
            <?php
            $screeningMotionsShown = $consultation->getSettings()->screeningMotionsShown;
            echo $amendment->getFormattedStatus();
            ?>
        </td>
    </tr>
    <?php

    $votingData = $amendment->getVotingData();
    if ($votingData->hasAnyData()) {
        $part1 = [];
        if ($votingData->votesYes !== null) {
            $part1[] = Yii::t('motion', 'voting_yes') . ': ' . $votingData->votesYes;
        }
        if ($votingData->votesNo !== null) {
            $part1[] = Yii::t('motion', 'voting_no') . ': ' . $votingData->votesNo;
        }
        if ($votingData->votesAbstention !== null) {
            $part1[] = Yii::t('motion', 'voting_abstention') . ': ' . $votingData->votesAbstention;
        }
        if ($votingData->votesInvalid !== null) {
            $part1[] = Yii::t('motion', 'voting_invalid') . ': ' . $votingData->votesInvalid;
        }
        $part1 = implode(", ", $part1);
        if ($part1 && $votingData->comment) {
            $str = Html::encode($votingData->comment) . '<br><small>' . $part1 . '</small>';
        } elseif ($part1) {
            $str = $part1;
        } else {
            $str = $votingData->comment;
        }
        ?>
        <tr class="votingResultRow">
            <th><?= Yii::t('motion', 'voting_result') ?>:</th>
            <td><?= $str ?></td>
        </tr>
        <?php
    }

    $proposalAdmin = User::havePrivilege($consultation, User::PRIVILEGE_CHANGE_PROPOSALS);
    if (($amendment->isProposalPublic() && $amendment->proposalStatus) || $proposalAdmin) {
        ?>
        <tr class="proposedStatusRow">
            <th><?= Yii::t('amend', 'proposed_status') ?>:</th>
            <td class="str"><?= $amendment->getFormattedProposalStatus(true) ?></td>
        </tr>
        <?php
    }
    if ($amendment->dateResolution) {
        ?>
        <tr>
            <th><?= Yii::t('amend', 'resoluted_on') ?>:</th>
            <td><?= Tools::formatMysqlDate($amendment->dateResolution) ?></td>
        </tr>
        <?php
    }
    ?>
    <tr>
        <th><?= Yii::t('amend', ($amendment->isSubmitted() ? 'submitted_on' : 'created_on')) ?>:</th>
        <td><?= Tools::formatMysqlDateTime($amendment->dateCreation) ?></td>
    </tr>
    <?php


    if (User::getCurrentUser()) {
        $comment = $amendment->getPrivateComment();

        $str = '';
        if ($comment) {
            $str .= '<blockquote class="privateNote" id="comm' . $comment->id . '">';
            $str .= '<button class="btn btn-link btn-xs btnEdit"><span class="glyphicon glyphicon-edit">' .
                    '</span></button>';
            $str .= HTMLTools::textToHtmlWithLink($comment ? $comment->text : '') . '</blockquote>';
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

        ?>
        <tr class="privateNotes<?= ($comment ? '' : ' hidden') ?>">
            <th><?= Yii::t('motion', 'private_notes') ?></th>
            <td><?= $str ?></td>
        </tr>
        <?php

        $motionData[] = [
            'rowClass' => 'privateNotes' . ($comment ? '' : ' hidden'),
            'title'    => Yii::t('motion', 'private_notes'),
            'content'  => $str,
        ];
    }

    ?>
</table>
