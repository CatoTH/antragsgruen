<?php

use app\components\UrlHelper;
use app\models\db\Motion;
use yii\helpers\Html;

/**
 * @var Motion $motion
 */

$submitUrl = UrlHelper::createUrl(['/dbwv/admin-workflow/step4next', 'motionSlug' => $motion->getMotionSlug()]);

echo Html::beginForm($submitUrl, 'POST', [
    'id' => 'dbwv_step4_next',
    'class' => 'dbwv_step dbwv_step4_next',
]);
?>
    <h2>V4 - Administration <small>(Koordinierungsausschuss)</small></h2>
    <div class="holder">
        <div style="text-align: right; padding: 10px;">
            <button type="submit" class="btn btn-default">
                <span class="glyphicon glyphicon-edit" aria-hidden="true"></span>
                V5 (BV) mit Änderung erstellen
            </button>
            <button type="submit" class="btn btn-primary">
                <span class="glyphicon glyphicon-ok" aria-hidden="true"></span>
                V5 (BV) ohne Änderung erstellen
            </button>
        </div>
    </div>
<?php
echo Html::endForm();
