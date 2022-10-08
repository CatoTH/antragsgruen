<?php

use app\components\UrlHelper;
use app\models\db\ConsultationText;
use yii\helpers\Html;

/**
 * @var ConsultationText $pageData
 */

echo '<hr>';

echo Html::beginForm(
    UrlHelper::createUrl(['/motionslides/page/from-imotions', 'pageSlug' => $pageData->textId]),
    'post',
    ['class' => 'content']
);
?>
<button type="button" class="btn btn-default"
        onclick="document.querySelector('.motionslidesCreaterHolder').classList.toggle('hidden')">
    Antragsübersicht erstellen
</button>

<div class="form-horizontal hidden motionslidesCreaterHolder">
    <br><br>
    <label>
        Komma-getrennte Kürzel der (Änderungs-)Anträge:<br>
        <input type="text" name="imotions" class="form-control"><br>
        <small style="font-weight: normal;">Für Änderungsanträge: „A1: Ä2”. Beispiel: „A2, A3, A2: Ä1, A2: Ä2”.</small>
    </label><br>
    <button type="submit" class="btn btn-primary">Aktuelle Seite überschreiben</button>
</div>

<?php
echo Html::endForm();
