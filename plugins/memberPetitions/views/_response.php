<?php

use yii\helpers\Html;

/**
 * @var \yii\web\View $this
 * @var \app\models\db\Motion $motion
 * @var \app\models\db\Motion $response
 */

?>

<h2 class="darkgreen"><?= \Yii::t('memberPetitions', 'response_title') ?></h2>

<div class="content">
    <table class="motionDataTable">
        <tr>
            <th><?= \Yii::t('memberPetitions', 'response_from') ?>:</th>
            <td><?= Html::encode($response->getInitiatorsStr()) ?></td>
        </tr>
        <tr>
            <th><?= \Yii::t('memberPetitions', 'response_date') ?>:</th>
            <td><?= \app\components\Tools::formatMysqlDate($response->dateCreation) ?></td>
        </tr>
    </table>
</div>

<?php
$main = '';
foreach ($response->getSortedSections(true) as $i => $section) {
    if (trim($section->data) === '') {
        continue;
    }
    $sectionType = $section->getSettings()->type;
    $main        .= '<section class="motionTextHolder sectionType' . $section->getSettings()->type;
    if ($response->getMyConsultation()->getSettings()->lineLength > 80) {
        $main .= ' smallFont';
    }
    $main .= ' motionTextHolder' . $i . '" id="resp_section_' . $section->sectionId . '">';
    $main .= '<h3 class="green">' . \Yii::t('memberPetitions', 'response_text') . '</h3>';
    $main .= $section->getSectionType()->showMotionView(null, []);
    $main .= '</section>';
}
echo $main;
