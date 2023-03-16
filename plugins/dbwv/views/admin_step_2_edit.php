<?php

use app\components\UrlHelper;
use app\models\db\Motion;
use yii\helpers\Html;

/**
 * @var Motion $motion
 */

$submitUrl = UrlHelper::createUrl(['/dbwv/admin-workflow/step2edit', 'motionSlug' => $motion->getMotionSlug()]);

echo Html::beginForm($submitUrl, 'POST', [
    'id' => 'dbwv_step2_edit',
    'class' => 'dbwv_step dbwv_step2_edit',
]);

$agendaItemsSelect = ['' => ''];
foreach ($motion->getMyConsultation()->agendaItems as $agendaItem) {
    $agendaItemsSelect[$agendaItem->id] = $agendaItem->title;
}
?>
    <h2>V2 - Administration <small>(AL Recht)</small></h2>
    <div>
        <div style="padding: 10px; clear:both;">
            <label for="dbwv_step1_agendaSelect" style="display: inline-block; width: 200px;">
                Sachgebiet:
            </label>
            <div style="display: inline-block; width: 400px;">
                <?php
                $options = ['id' => 'dbwv_step1_agendaSelect', 'class' => 'stdDropdown', 'required' => 'required'];
                echo Html::dropDownList('agendaItem', (string)$motion->agendaItemId, $agendaItemsSelect, $options);
                ?>
            </div>
            <br>

            <label for="dbwv_step1_prefix" style="display: inline-block; width: 200px; padding-top: 7px;">
                Antragsnummer:
            </label>
            <div style="display: inline-block; width: 400px; padding-top: 7px;">
                <input type="text" value="<?= Html::encode($motion->titlePrefix) ?>" name="motionPrefix" class="form-control" id="dbwv_step1_prefix">
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
