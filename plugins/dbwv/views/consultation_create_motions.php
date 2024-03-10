<?php
/**
 * @var \app\models\db\Consultation $consultation
 */

use app\components\UrlHelper;
use yii\helpers\Html;

$leitantraege = \app\plugins\dbwv\LayoutHooks::getLeitantraege($consultation);
if (count($leitantraege) > 0) {
    ?>
    <section aria-labelledby="leitantraegeTitle">
        <h2 class="green" id="leitantraegeTitle">Leitanträge</h2>
        <div class="content homeTagList">
            <ol>
            <?php
            foreach ($leitantraege as $leitantrag) {
                echo '<li><span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span> ';
                echo Html::a(Html::encode($leitantrag->getTitleWithPrefix()), UrlHelper::createIMotionUrl($leitantrag), ['class' => 'tagLink']);
                echo '</li>';
            }
            ?>
            </ol>
        </div>
    </section>
<?php
}
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
