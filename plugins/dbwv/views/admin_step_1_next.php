<?php

use app\components\UrlHelper;
use app\models\db\Motion;
use yii\helpers\Html;

/**
 * @var Motion $motion
 */

$submitUrl = UrlHelper::createUrl(['/dbwv/admin-workflow/step1next', 'motionSlug' => $motion->getMotionSlug()]);

echo Html::beginForm($submitUrl, 'POST', [
    'id' => 'dbwv_step1_next',
    'class' => 'dbwv_step dbwv_step1_next',
]);

$agendaItemsSelect = ['' => ''];
foreach ($motion->getMyConsultation()->agendaItems as $agendaItem) {
    $agendaItemsSelect[$agendaItem->id] = $agendaItem->title;
}

?>
    <h2>V1 - Administration <small>(AL Recht)</small></h2>
    <div class="holder">
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

                <label for="dbwv_step1_publish"
                    style="display: inline-block; width: 200px; height: 40px; vertical-align: middle; padding-top: 7px;">
                    Sofort veröffentlichen:
                </label>
                <div style="display: inline-block; width: 400px; height: 40px; vertical-align: middle; padding-top: 7px;">
                    <input type="checkbox" name="publish" id="dbwv_step1_publish">
                </div>
                <br>
            </div>
            <div style="text-align: right;">
                <button type="submit" class="btn btn-default" name="withChanges">
                    <span class="glyphicon glyphicon-edit" aria-hidden="true"></span>
                    V2 erstellen mit Änderung
                </button>
                <button type="submit" class="btn btn-primary" name="noChanges">
                    <span class="glyphicon glyphicon-ok" aria-hidden="true"></span>
                    V2 erstellen ohne Änderung
                </button>
            </div>
        </div>
    </div>
<?php
echo Html::endForm();
