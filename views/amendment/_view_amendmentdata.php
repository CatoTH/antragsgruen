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
echo $amendment->getFormattedStatus();
echo '</td>
                </tr>';

if ($amendment->isProposalPublic() && $amendment->proposalStatus) {
    echo '<tr class="proposedStatusRow"><th>' . \Yii::t('amend', 'proposed_status') . ':</th><td>';
    echo $amendment->getFormattedProposalStatus();
    if ($amendment->proposalExplanation) {
        echo ' <span class="explanation">(' . \Yii::t('con', 'proposal_explanation') . ': ';
        echo Html::encode($amendment->proposalExplanation);
        echo ')</span>';
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
