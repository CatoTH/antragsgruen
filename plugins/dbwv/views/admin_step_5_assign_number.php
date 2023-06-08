<?php

use app\components\UrlHelper;
use app\models\db\{ConsultationSettingsTag, Motion};
use yii\helpers\Html;

/**
 * @var Motion $motion
 */

$submitUrl = UrlHelper::createUrl(['/dbwv/admin-workflow/step5-assign-number', 'motionSlug' => $motion->getMotionSlug()]);

echo Html::beginForm($submitUrl, 'POST', [
    'id' => 'dbwv_step5_assign_number',
    'class' => 'dbwv_step dbwv_step5_assign_number',
]);

$tagSelect = ['' => ''];
foreach ($motion->getMyConsultation()->getSortedTags(ConsultationSettingsTag::TYPE_PUBLIC_TOPIC) as $tag) {
    $tagSelect[$tag->id] = $tag->title;
}

$selectedTagId = (count($motion->getPublicTopicTags()) > 0 ? (string)array_values($motion->getPublicTopicTags())[0]->id : '');

$titlePrefix = $motion->titlePrefix ?? '';
if (!$motion->titlePrefix) {
    $titlePrefix = $motion->getMyConsultation()->getNextMotionPrefix($motion->motionTypeId, $motion->tags);
}

?>
    <h2>Aufbereitung für die Antragsversammlung</h2>
    <div class="holder">
        <div>
            <div style="padding: 10px; clear:both;">
                <label for="dbwv_step5_subtagSelect" style="display: inline-block; width: 200px;">
                    Sachgebiet:
                </label>
                <div style="display: inline-block; width: 400px;">
                    <?php
                    $options = ['id' => 'dbwv_step5_tagSelect', 'class' => 'stdDropdown', 'required' => 'required'];
                    echo Html::dropDownList('tag', $selectedTagId, $tagSelect, $options);
                    ?>
                </div>
                <br>

                <label for="dbwv_step5_prefix" style="display: inline-block; width: 200px; padding-top: 7px;">
                    Antragsnummer:
                </label>
                <div style="display: inline-block; width: 400px; padding-top: 7px;">
                    <input type="text" value="<?= Html::encode($titlePrefix) ?>" name="motionPrefix" class="form-control" id="dbwv_step5_prefix">
                </div>
                <br>

                <div style="display: inline-block; width: 200px; height: 40px; vertical-align: middle; padding-top: 14px;"></div>
                <?php
                $editUrl = UrlHelper::createMotionUrl($motion, 'edit');
                ?>
                <div
                    style="display: inline-block; width: 400px; height: 40px; vertical-align: middle; padding-top: 14px;">
                    <a href="<?= Html::encode($editUrl) ?>"><span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span> Text bearbeiten</a>
                </div>
                <br>
            </div>
            <div style="text-align: right;">
                <button type="submit" class="btn btn-primary">
                    <span class="glyphicon glyphicon-ok" aria-hidden="true"></span>
                    Speichern
                </button>
            </div>
        </div>
    </div>
<?php
echo Html::endForm();
