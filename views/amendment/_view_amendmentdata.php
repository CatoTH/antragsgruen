<?php

use app\components\Tools;
use app\components\UrlHelper;
use app\models\db\Amendment;
use app\models\policies\IPolicy;
use yii\helpers\Html;
use app\views\motion\LayoutHelper as MotionLayoutHelper;

/**
 * @var \yii\web\View $this
 * @var Amendment $amendment
 */

$motion       = $amendment->getMyMotion();
$consultation = $motion->getMyConsultation();

echo '<table class="motionDataTable">
                <tr>
                    <th>' . Yii::t('amend', 'motion') . ':</th>
                    <td>' .
    Html::a($motion->title, UrlHelper::createMotionUrl($motion)) . '</td>
                </tr>
                <tr>
                    <th>' . Yii::t('amend', 'initiator'), ':</th>
                    <td>';

echo MotionLayoutHelper::formatInitiators($amendment->getInitiators(), $consultation);

echo '</td></tr>
                <tr class="statusRow"><th>' . \Yii::t('amend', 'status') . ':</th><td>';

$screeningMotionsShown = $consultation->getSettings()->screeningMotionsShown;
$statiNames            = Amendment::getStati();
switch ($amendment->status) {
    case Amendment::STATUS_SUBMITTED_UNSCREENED:
    case Amendment::STATUS_SUBMITTED_UNSCREENED_CHECKED:
        echo '<span class="unscreened">' . Html::encode($statiNames[$amendment->status]) . '</span>';
        break;
    case Amendment::STATUS_SUBMITTED_SCREENED:
        echo '<span class="screened">' . \Yii::t('amend', 'screened_hint') . '</span>';
        break;
    case Amendment::STATUS_COLLECTING_SUPPORTERS:
        echo Html::encode($statiNames[$amendment->status]);
        echo ' <small>(' . \Yii::t('motion', 'supporting_permitted') . ': ';
        echo IPolicy::getPolicyNames()[$motion->motionType->policySupportAmendments] . ')</small>';
        break;
    default:
        echo Html::encode($statiNames[$amendment->status]);
}
if (trim($amendment->statusString) != '') {
    echo " <small>(" . Html::encode($amendment->statusString) . ")</string>";
}
echo '</td>
                </tr>';

if ($amendment->isProposalPublic() && $amendment->proposalStatus) {
    echo '<tr class="proposedStatusRow"><th>' . \Yii::t('amend', 'proposed_status') . ':</th><td>';
    switch ($amendment->proposalStatus) {
        case Amendment::STATUS_REFERRED:
            echo Html::encode(\Yii::t('amend', 'refer_to') . ': ' . $amendment->proposalComment);
            break;
        default:
            echo Html::encode($statiNames[$amendment->proposalStatus]);
    }
    echo '</td></tr>';
}

if ($amendment->dateResolution != '') {
    echo '<tr><th>' . \Yii::t('amend', 'resoluted_on') . ':</th>
       <td>' . Tools::formatMysqlDate($amendment->dateResolution) . '</td>
     </tr>';
}
echo '<tr><th>' . \Yii::t('amend', ($amendment->isSubmitted() ? 'submitted_on' : 'created_on')) . ':</th>
       <td>' . Tools::formatMysqlDateTime($amendment->dateCreation) . '</td>
                </tr>';
echo '</table>';
