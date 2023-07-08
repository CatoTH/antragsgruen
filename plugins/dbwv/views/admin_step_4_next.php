<?php

use app\components\{MotionNumbering, UrlHelper};
use app\models\db\{ConsultationSettingsTag, Motion};
use app\plugins\dbwv\workflow\Workflow;
use yii\helpers\Html;

/**
 * @var Motion $motion
 */

$submitUrl = UrlHelper::createUrl(['/dbwv/admin-workflow/step4next', 'motionSlug' => $motion->getMotionSlug()]);

/*
$mainConsultation = \app\plugins\dbwv\Module::getBundConsultation();
$tagSelect = [
    '' => '- nicht zugeordnet -',
];
$selectedTagTitle = (count($motion->getPublicTopicTags()) > 0 ? (string)$motion->getPublicTopicTags()[0]->title : '');
$selectedTagId = '';
foreach ($mainConsultation->getSortedTags(ConsultationSettingsTag::TYPE_PUBLIC_TOPIC) as $tag) {
    $tagSelect[$tag->id] = $tag->title;
    if ($tag->title === $selectedTagTitle) {
        $selectedTagId = (string)$tag->id;
    }
}
*/

$v5Created = MotionNumbering::findMotionInHistoryOfVersion($motion, Workflow::STEP_V5) !== null;

echo Html::beginForm($submitUrl, 'POST', [
    'id' => 'dbwv_step4_next',
    'class' => 'dbwv_step dbwv_step4_next',
]);
?>
    <h2>V4 - Administration <small>(Koordinierungsausschuss)</small></h2>
    <div class="holder">
        <?php
        if ($v5Created) {
            ?>
            <div>
                <span class="glyphicon glyphicon-ok" aria-hidden="true"></span>
                In die Hauptversammlung übernommen
            </div>
            <?php
        } else {
            ?>
            <div style="text-align: right; padding: 10px;">
                <button type="submit" class="btn btn-primary">
                    <span class="glyphicon glyphicon-ok" aria-hidden="true"></span>
                    In die Hauptversammlung übernehmen (V5)
                </button>
            </div>
            <?php
        }
        ?>
    </div>
<?php
echo Html::endForm();
