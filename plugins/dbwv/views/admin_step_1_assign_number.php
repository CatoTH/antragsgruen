<?php

use app\components\UrlHelper;
use app\plugins\dbwv\workflow\Workflow;
use app\models\db\{ConsultationSettingsTag, Motion};
use yii\helpers\Html;

/**
 * @var Motion $motion
 */

$submitUrl = UrlHelper::createUrl(['/dbwv/admin-workflow/step1-assign-number', 'motionSlug' => $motion->getMotionSlug()]);

echo Html::beginForm($submitUrl, 'POST', [
    'id' => 'dbwv_step1_assign_number',
    'class' => 'dbwv_step dbwv_step1_assign_number',
]);

$tagSelect = ['' => ''];
$subtags = [];
if (count($motion->getPublicTopicTags()) > 0) {
    $subtags = $motion->getPublicTopicTags()[0]->getSubtagsOfType(ConsultationSettingsTag::TYPE_PROPOSED_PROCEDURE);
}
foreach ($subtags as $tag) {
    $tagSelect[$tag->id] = $tag->title;
}
$selectedTagId = (count($motion->getProposedProcedureTags()) > 0 ? (string)array_values($motion->getProposedProcedureTags())[0]->id : '');

$titlePrefix = $motion->titlePrefix ?? '';
if (!$motion->titlePrefix) {
    $titlePrefix = $motion->getMyConsultation()->getNextMotionPrefix($motion->motionTypeId, $motion->tags);
}

?>
    <h2>Aufbereitung für die Antragsversammlung</h2>
    <div class="holder">
        <div>
            <div style="padding: 10px; clear:both;">
                <label for="dbwv_step1_subtagSelect" style="display: inline-block; width: 200px;">
                    Themenbereich:
                </label>
                <div style="display: inline-block; width: 400px;">
                    <?php
                    $options = ['id' => 'dbwv_step1_subtagSelect', 'class' => 'stdDropdown'];
                    echo Html::dropDownList('subtag', $selectedTagId, $tagSelect, $options);
                    ?>
                </div>
                <br>

                <label for="dbwv_step1_prefix" style="display: inline-block; width: 200px; padding-top: 7px;">
                    Antragsnummer:
                </label>
                <div style="display: inline-block; width: 400px; padding-top: 7px;">
                    <input type="text" value="<?= Html::encode($titlePrefix) ?>" name="motionPrefix" class="form-control" id="dbwv_step1_prefix">
                </div>
                <br>

                <div style="display: inline-block; width: 200px; height: 40px; vertical-align: middle; padding-top: 14px;"></div>
                <?php
                if ($motion->version === Workflow::STEP_V2) {
                    $editUrl = UrlHelper::createMotionUrl($motion, 'edit');
                    ?>
                <div
                    style="display: inline-block; width: 400px; height: 40px; vertical-align: middle; padding-top: 14px;">
                    <a href="<?= Html::encode($editUrl) ?>"><span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span> Text bearbeiten</a>
                </div>
                    <?php
                } else {
                    ?>
                    <label
                        style="display: inline-block; width: 400px; height: 40px; vertical-align: middle; padding-top: 14px;">
                        <input type="checkbox" name="textchanges" id="dbwv_step1_textchanges">
                        Redaktionelle Änderungen vornehmen
                    </label>
                <?php } ?>
                <br>
            </div>
            <div style="text-align: right;">
                <button type="submit" class="btn btn-primary">
                    <span class="glyphicon glyphicon-ok" aria-hidden="true"></span>
                    <?= ($motion->version === Workflow::STEP_V2 ? 'Speichern' : 'V2 erstellen') ?>
                </button>
            </div>
        </div>
    </div>
<?php
echo Html::endForm();
