<?php

use yii\helpers\Html;

/**
 * @var \yii\web\View $this
 * @var string $mode
 * @var \app\models\forms\AmendmentEditForm $form
 * @var \app\models\db\Consultation $consultation
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$params     = $controller->layoutParams;
$wording    = $consultation->getWording();

$this->title = $wording->get($mode == 'create' ? 'Änderungsantrag stellen' : 'Änderungsantrag bearbeiten');

$params->addJS('/js/ckeditor/ckeditor.js');
$params->breadcrumbs[] = $this->title;

echo '<h1>' . Html::encode($this->title) . '</h1>';

echo $controller->showErrors();

echo '<div class="form content">';


echo HTML::beginForm('', '', ['id' => 'amendmentForm']);


echo count($form->sections);

echo '</textarea><button type="submit">Abschicken</button> ';
echo Html::endForm();

?>
<script>
/*
$("#amendmentForm").submit(function() {
    CKEDITOR.instances.ckeditor_toedit.plugins.lite.findPlugin(CKEDITOR.instances.ckeditor_toedit).acceptAll();
})
*/
</script>

<?


echo '</div>';
