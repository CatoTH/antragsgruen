<?php

use app\components\UrlHelper;
use app\models\db\Motion;
use yii\helpers\Html;

/**
 * @var Motion $motion
 */

$submitUrl = UrlHelper::createUrl(['/dbwv/admin-workflow/step2next', 'motionSlug' => $motion->getMotionSlug()]);

echo Html::beginForm($submitUrl, 'POST', [
    'id' => 'dbwv_step2_next',
    'class' => 'dbwv_step dbwv_step2_next',
]);
?>
    <h2>V2 - Administration <small>(Arbeitsgruppe)</small></h2>
    <div class="holder">
        <fieldset class="statusForm">
            <legend class="hidden">Vorgeschlagener Status</legend>
            <h3>Vorgeschlagener Status</h3>

            <label class="proposalStatus4">
                <input type="radio" name="proposalStatus" value="4"> Übernahme </label><br>
            <label class="proposalStatus10">
                <input type="radio" name="proposalStatus" value="10"> <?= Html::encode(Yii::t('structure', 'PROPOSED_MODIFIED_ACCEPTED')) ?><br>
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(Vorgeschlagene Änderungen werden im nächsten Bildschirm angezeigt)
            </label><br>

            <label class="proposalStatus5">
                <input type="radio" name="proposalStatus" value="5"> Ablehnung </label><br>
            <label class="proposalStatus10">
                <input type="radio" name="proposalStatus" value="10"> Überweisung </label><br>
            <label class="proposalStatus11">
                <input type="radio" name="proposalStatus" value="11"> Abstimmung </label><br>
            <label class="proposalStatus22">
                <input type="radio" name="proposalStatus" value="22"> Erledigt durch anderen ÄA </label><br>
            <label class="proposalStatus23">
                <input type="radio" name="proposalStatus" value="23"> Sonstiger Status </label><br>
            <label>
                <input type="radio" name="proposalStatus" value="0" checked=""> - nicht festgelegt -
            </label>
        </fieldset>

        <div style="text-align: right; padding: 10px;">
            <button type="submit" class="btn btn-primary">
                <span class="glyphicon glyphicon-ok" aria-hidden="true"></span>
                V3 erstellen
            </button>
        </div>
    </div>
<?php
echo Html::endForm();
