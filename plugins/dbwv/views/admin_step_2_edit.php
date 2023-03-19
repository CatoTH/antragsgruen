<?php

use app\components\UrlHelper;
use app\models\db\{ConsultationSettingsTag, Motion};
use yii\helpers\Html;

/**
 * @var Motion $motion
 */

$submitUrl = UrlHelper::createUrl(['/dbwv/admin-workflow/step2edit', 'motionSlug' => $motion->getMotionSlug()]);

echo Html::beginForm($submitUrl, 'POST', [
    'id' => 'dbwv_step2_edit',
    'class' => 'dbwv_step dbwv_step2_edit',
]);

$tagSelect = ['' => ''];
foreach ($motion->getMyConsultation()->getSortedTags(ConsultationSettingsTag::TYPE_PUBLIC_TOPIC) as $tag) {
    $tagSelect[$tag->id] = $tag->title;
}
$selectedTagId = (count($motion->getPublicTopicTags()) > 0 ? (string)$motion->getPublicTopicTags()[0]->id : '');

?>
    <h2>V2 - Administration <small>(AL Recht)</small></h2>
    <div>
        <div style="padding: 10px; clear:both;">
            <label for="dbwv_step2_tagSelect" style="display: inline-block; width: 200px;">
                Sachgebiet:
            </label>
            <div style="display: inline-block; width: 400px;">
                <?php
                $options = ['id' => 'dbwv_step2_tagSelect', 'class' => 'stdDropdown', 'required' => 'required'];
                echo Html::dropDownList('tag', $selectedTagId, $tagSelect, $options);
                ?>
            </div>
            <br>

            <label for="dbwv_step2_prefix" style="display: inline-block; width: 200px; padding-top: 7px;">
                Antragsnummer:
            </label>
            <div style="display: inline-block; width: 400px; padding-top: 7px;">
                <input type="text" value="<?= Html::encode($motion->titlePrefix) ?>" name="motionPrefix" class="form-control" id="dbwv_step2_prefix">
            </div>
            <br>

            <div style="display: inline-block; width: 200px; height: 40px; vertical-align: middle; padding-top: 7px;">
                Sofort veröffentlichen:
            </div>
            <div style="display: inline-block; width: 400px; height: 40px; vertical-align: middle; padding-top: 7px;">
                <input type="checkbox">
            </div>
            <br>
        </div>
    </div>
    <div class="holder">
        <div class="statusForm">
            <button type="button" class="btn btn-default">
                <span class="glyphicon glyphicon-edit" aria-hidden="true"></span>
                Text bearbeiten
            </button>
        </div>
        <div style="text-align: right; padding: 10px;">
            <button type="submit" class="btn btn-primary">
                <span class="glyphicon glyphicon-ok" aria-hidden="true"></span>
                Veröffentlichen
            </button>
        </div>
    </div>
<?php
echo Html::endForm();

/*
 * Hint: if this method should be called here, it would be necessary to pass the motionId and exclude the current motion from "getNextMotionPrefix"
$proposeUrl = UrlHelper::createUrl(['/dbwv/ajax-helper/propose-title-prefix', 'motionTypeId' => $motion->motionTypeId, 'tagId' => 'TAGID']);
?>
<script>
    const proposePrefixUrlTmpl = <?= json_encode($proposeUrl) ?>;
    $(function() {
        $("#dbwv_step2_tagSelect").on("change", function() {
            const proposePrefixUrl = proposePrefixUrlTmpl.replace(/TAGID/, $(this).val());
            $.get(proposePrefixUrl, function(data) {
                $("#dbwv_step2_prefix").val(data['prefix']);
            });
        }).trigger("change");
    });
</script>
*/
