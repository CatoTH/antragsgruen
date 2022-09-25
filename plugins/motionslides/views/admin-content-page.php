<?php

use app\components\UrlHelper;
use app\models\db\ConsultationText;
use yii\helpers\Html;

/**
 * @var ConsultationText $pageData
 */

echo '<hr>';
echo Html::beginForm(UrlHelper::createUrl(['/motionslides/page/from-imotions', 'pageSlug' => $pageData->textId]));
?>

<div class="form-horizontal">
    <label>
        Komma-getrennte Kürzel der (Änderungs-)Anträge. Für Änderungsanträge: „A1: Ä2”. Beispiel: „A2, A3, A4: Ä1, A4: Ä2”.
        <input type="text" name="imotions" class="form-control">
    </label>
    <button type="submit" class="btn btn-primary">Aktuelle Seite überschreiben</button>
</div>

<?php
echo Html::endForm();
