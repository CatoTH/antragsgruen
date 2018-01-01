<?php

use app\components\UrlHelper;
use app\models\db\MotionSection;
use yii\helpers\Html;

/**
 * @var \yii\web\View $this
 * @var \app\models\db\Motion $motion
 */

$saveUrl = UrlHelper::createUrl(['memberpetitions/write-response', 'motionSlug' => $motion->getMotionSlug()]);
echo Html::beginForm($saveUrl, 'post', [
    'class'                    => 'petitionRespondForm',
    'data-antragsgruen-widget' => 'backend/MemberPetitionRespond',
]);
?>

    <h2 class="green">Auf die Mitgliederpetition antworten</h2>
    <div class="content">
        <?php
        foreach ($motion->getSortedSections(true) as $section) {
            $answerSection            = new MotionSection();
            $answerSection->motionId  = $motion->id;
            $answerSection->sectionId = $section->sectionId;

            echo $answerSection->getSectionType()->getMotionFormField();
        }
        ?>

        <div class="save-row">
            <button class="btn btn-primary" type="submit"><?= \Yii::t('memberpetitions', 'respond_btn') ?></button>
        </div>
    </div>

<?php
echo Html::endForm();
