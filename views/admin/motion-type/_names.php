<?php

use app\components\{HTMLTools, UrlHelper};
use app\models\db\ConsultationMotionType;
use yii\helpers\Html;

/**
 * @var ConsultationMotionType $motionType
 */

?>
<section aria-labelledby="motionTypeNamesTitle">
<h2 class="green" id="motionTypeNamesTitle"><?= Yii::t('admin', 'motion_type_names') ?></h2>
<div class="content">
    <div class="stdTwoCols">
        <label class="leftColumn" for="typeTitleSingular">
            <?= Yii::t('admin', 'motion_type_singular') ?>
        </label>
        <div class="rightColumn"><?php
            $options = [
                'class' => 'form-control',
                'id' => 'typeTitleSingular',
                'placeholder' => Yii::t('admin', 'motion_type_singular_pl'),
            ];
            echo Html::textInput('type[titleSingular]', $motionType->titleSingular, $options);
            ?></div>
    </div>

    <div class="stdTwoCols">
        <label class="leftColumn" for="typeTitlePlural">
            <?= Yii::t('admin', 'motion_type_plural') ?>
        </label>
        <div class="rightColumn">
            <?php
            $options = [
                'class' => 'form-control',
                'id' => 'typeTitlePlural',
                'placeholder' => Yii::t('admin', 'motion_type_plural_pl'),
            ];
            echo Html::textInput('type[titlePlural]', $motionType->titlePlural, $options);
            ?>
        </div>
    </div>

    <div class="stdTwoCols">
        <label class="leftColumn" for="typeCreateTitle">
            <?= Yii::t('admin', 'motion_type_create_title') ?>
        </label>
        <div class="rightColumn">
            <?php
            $options = [
                'class' => 'form-control',
                'id' => 'typeCreateTitle',
                'placeholder' => Yii::t('admin', 'motion_type_create_placeh')
            ];
            echo HTMLTools::smallTextarea('type[createTitle]', $options, $motionType->createTitle);
            ?>
        </div>
    </div>
    <div class="stdTwoCols checkboxNoPadding">
        <div class="leftColumn"></div>
        <div class="rightColumn">
            <?php
            echo HTMLTools::labeledCheckbox(
                'type[sidebarCreateButton]',
                Yii::t('admin', 'motion_type_create_sidebar'),
                $motionType->sidebarCreateButton,
                'typeCreateSidebar'
            );
            ?>
        </div>
    </div>

    <div class="stdTwoCols">
        <label class="leftColumn" for="typeMotionPrefix">
            <?= Yii::t('admin', 'motion_type_title_prefix') ?>
        </label>
        <div class="rightColumn">
            <?php
            $options = ['class' => 'form-control', 'id' => 'typeMotionPrefix', 'placeholder' => 'A'];
            echo Html::textInput('type[motionPrefix]', $motionType->motionPrefix, $options);
            ?>
        </div>
    </div>

    <div class="stdTwoCols">
        <label class="leftColumn" for="typeMotionIntro">
            <?= Yii::t('admin', 'motion_type_title_intro') ?>
        </label>
        <div class="rightColumn">
            <?php
            $options = ['class' => 'form-control', 'id' => 'typeMotionIntro'];
            echo Html::textInput('type[typeMotionIntro]', $motionType->getSettingsObj()->motionTitleIntro, $options);
            ?>
        </div>
    </div>

    <div class="stdTwoCols">
        <label class="leftColumn" for="typeProposedProcedure">
            <?= Yii::t('admin', 'motion_type_proposed') ?>
        </label>
        <div class="rightColumn">
            <?php
            echo HTMLTools::labeledCheckbox(
                'type[proposedProcedure]',
                Yii::t('admin', 'motion_type_proposed_label'),
                $motionType->getSettingsObj()->hasProposedProcedure,
                'typeProposedProcedure'
            );
            ?>
        </div>
    </div>

    <div class="stdTwoCols">
        <label class="leftColumn" for="typeResponsibilities">
            <?= Yii::t('admin', 'motion_type_respons') ?>
        </label>
        <div class="rightColumn">
            <?php
            echo HTMLTools::labeledCheckbox(
                'type[responsibilities]',
                Yii::t('admin', 'motion_type_respons_label'),
                $motionType->getSettingsObj()->hasResponsibilities,
                'typeResponsibilities'
            );
            ?>
        </div>
    </div>
<?php

$furtherTranslation = UrlHelper::createUrl(['admin/index/translation-motion-type', 'motionTypeId' => $motionType->id]);
echo Html::a(
    '<span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span> ' . Yii::t('admin', 'motion_type_translations'),
    $furtherTranslation,
    ['class' => 'motionTypeTranslations']
);

?>
</div>
</section>
