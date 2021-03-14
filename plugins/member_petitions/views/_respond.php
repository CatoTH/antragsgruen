<?php

use app\components\UrlHelper;
use app\models\db\MotionSection;
use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var \app\models\db\Motion $motion
 */

$saveUrl = UrlHelper::createUrl(['/member_petitions/backend/write-response', 'motionSlug' => $motion->getMotionSlug()]);
echo Html::beginForm($saveUrl, 'post', [
    'class'                    => 'petitionRespondForm',
    'data-antragsgruen-widget' => 'backend/MemberPetitionRespond',
]);

$user     = \app\models\db\User::getCurrentUser();
$username = ($user ? $user->name : '');
?>

    <h2 class="green"><?= Yii::t('member_petitions', 'respond_title') ?></h2>
    <div class="content">
        <div class="form-group">
            <label for="responseFrom"><?= Yii::t('member_petitions', 'respond_from') ?></label>
            <input type="text" class="form-control" id="responseFrom" autocomplete="off"
                   value="<?= Html::encode($username) ?>">
        </div>
        <?php
        foreach ($motion->getSortedSections(true) as $section) {
            $answerSection            = new MotionSection();
            $answerSection->motionId  = $motion->id;
            $answerSection->sectionId = $section->sectionId;

            echo $answerSection->getSectionType()->getMotionFormField();
        }
        ?>

        <div class="save-row">
            <button class="btn btn-primary" type="submit"><?= Yii::t('member_petitions', 'respond_btn') ?></button>
        </div>
    </div>

<?php
echo Html::endForm();
