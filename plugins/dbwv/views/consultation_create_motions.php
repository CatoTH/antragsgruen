<?php
/**
 * @var \app\models\db\Consultation $consultation
 */

use yii\helpers\Html;

?>

<div class="content" style="text-align: center;">
    <?php
    $creatableTypes = [];
    foreach ($consultation->motionTypes as $type) {
        if ($type->amendmentsOnly) {
            $creatable = (count($type->getAmendableOnlyMotions(false, true)) > 0);
        } else {
            $creatable = $type->getMotionPolicy()->checkCurrUserMotion(false, true);
        }
        if ($creatable) {
            $creatableTypes[] = $type;
        }
    }

    if (count($creatableTypes) > 0) {
        foreach ($creatableTypes as $creatableType) {
            $motionCreateLink = $creatableType->getCreateLink(false, true);

            echo '<a href="' . Html::encode($motionCreateLink) . '" class="btn btn-primary btnCreateMotion">';
            echo Html::encode($creatableType->createTitle);
            echo '</a>';
        }
    } else {
        echo 'Es können keine Anträge (mehr) angelegt werden.';
    }
    ?>
</div>
