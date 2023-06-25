<?php

/**
 * @var \yii\web\View $this
 * @var \app\models\mergeAmendments\Init $form
 * @var MotionSection $section
 * @var bool $twoCols
 */

use app\models\db\MotionSection;
use yii\helpers\Html;

$type = $section->getSettings();

if ($twoCols) {
    ?>
    <div class="twoColsHolder">
        <div class="twoColsLeft content sectionType<?= $type->type ?>">
            <?= $form->getRegularSection($section)->getSectionType()->getMotionFormField() ?>
        </div>
        <div class="twoColsRight content sectionType<?= $type->type ?>" data-section-id="<?= $section->sectionId ?>">
            <?php
            $changes = $section->getMergingAmendingSections(true, true);
            /** @var \app\models\db\AmendmentSection[] $changes */
            if (count($changes) > 0) {
                ?>
                <div class="titleChanges">
                    <div class="title"><?= Yii::t('amend', 'merge_title_changes') ?></div>
                    <?php
                    foreach ($changes as $amendingSection) {
                        $titlePrefix = $amendingSection->getAmendment()->getFormattedTitlePrefix();
                        ?>
                        <div class="change">
                            <div class="prefix"><?= Html::encode($titlePrefix) ?></div>
                            <div class="text"><?= Html::encode($amendingSection->data) ?></div>
                        </div>
                        <?php
                    }
                    ?>
                </div>
                <?php
            }
            ?>
        </div>
    </div>
    <?php
} else {
    ?>
    <div class="content sectionType<?= $type->type ?>" data-section-id="<?= $section->sectionId ?>">
        <?php
        echo $form->getRegularSection($section)->getSectionType()->getMotionFormField();

        if ($type->type === \app\models\sectionTypes\ISectionType::TYPE_TITLE) {
            $changes = $section->getMergingAmendingSections(true, true);
            /** @var \app\models\db\AmendmentSection[] $changes */
            if (count($changes) > 0) {
                ?>
                <div class="titleChanges">
                    <div class="title"><?= Yii::t('amend', 'merge_title_changes') ?></div>
                    <?php
                    foreach ($changes as $amendingSection) {
                        $titlePrefix = $amendingSection->getAmendment()->getFormattedTitlePrefix();
                        ?>
                        <div class="change">
                            <div class="prefix"><?= Html::encode($titlePrefix) ?></div>
                            <div class="text"><?= Html::encode($amendingSection->data) ?></div>
                        </div>
                        <?php
                    }
                    ?>
                </div>
                <?php
            }
        }
        ?>
    </div>
    <?php
}
