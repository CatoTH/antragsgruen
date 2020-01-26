<?php

/**
 * @var \yii\web\View $this
 * @var \app\models\mergeAmendments\Init $form
 * @var MotionSection $section
 * @var bool $twoCols
 */

use app\models\db\MotionSection;

$type = $section->getSettings();

if ($twoCols) {
    echo '<div class="twoColsHolder">';
    ?>
    <div class="twoColsLeft content sectionType<?= $type->type ?>">
        <?= $form->getRegularSection($section)->getSectionType()->showMotionView(null, []) ?>
    </div>
    <?php
}

?>
<div class="twoColsRight content sectionType<?= $type->type ?>" data-section-id="<?= $section->sectionId ?>">
    <?= $form->getRegularSection($section)->getSectionType()->getMotionFormField() ?>
</div>

<?php
if ($twoCols) {
    echo '</div>';
}
