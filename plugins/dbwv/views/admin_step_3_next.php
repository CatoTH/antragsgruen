<?php

use app\components\UrlHelper;
use app\models\db\Motion;
use yii\helpers\Html;

/**
 * @var Motion $motion
 */

$submitUrl = UrlHelper::createUrl(['/dbwv/admin-workflow/step3next', 'motionSlug' => $motion->getMotionSlug()]);

echo Html::beginForm($submitUrl, 'POST', [
    'id' => 'dbwv_step3_next',
    'class' => 'dbwv_step dbwv_step3_next',
]);
?>
    <h2>V3 - Administration <small>(Redaktionsausschuss) - DUMMY</small></h2>
    <div class="holder">
        <div style="text-align: right; padding: 10px;">
            <button type="submit" class="btn btn-primary">
                <span class="glyphicon glyphicon-ok" aria-hidden="true"></span>
                V4 Beschluss erstellen (DUMMY)
            </button><br><br>
            (Dieser Button kann auch auf die Antragsliste für einen schnelleren Zugriff und führt zur Beschlusserstellungs-Seite)
        </div>
    </div>
<?php
echo Html::endForm();
