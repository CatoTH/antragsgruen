<?php

/**
 * @var $this yii\web\View
 * @var Consultation $consultation
 * @var string $category
 */
use app\components\MessageSource;
use app\components\UrlHelper;
use app\models\db\Consultation;
use yii\helpers\Html;
use yii\i18n\I18N;

/** @var \app\controllers\admin\IndexController $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;

$layout->addCSS('/css/backend.css');

$this->title = 'Einstellungen';
$layout->addBreadcrumb('Administration', UrlHelper::createUrl('admin/index'));
$layout->addBreadcrumb('Erweitert');

echo '<h1>' . Yii::t('backend', 'Translation / Wording') . '</h1>';
echo '<div class="content">';


echo Html::beginForm('', 'post', ['id' => 'wordingBaseForm', 'class' => 'adminForm form-horizontal']);
echo '<input type="hidden" name="category" value="' . Html::encode($category) . '">';
echo $controller->showErrors();


echo '<fieldset class="form-group">
        <label class="col-sm-4 control-label" for="startLayoutType">' .
    Yii::t('backend', 'Base language variant') . ':</label>
        <div class="col-sm-8">';
echo Html::dropDownList(
    'wordingBase',
    $consultation->wordingBase,
    MessageSource::getLanguageVariants(Yii::$app->language),
    ['class' => 'form-control']
);
echo '</div></fieldset>';

echo '<div class="saveholder">
<button type="submit" name="save" class="btn btn-primary">Speichern</button>
</div>';

echo Html::endForm();

echo '<br><br>';


foreach (MessageSource::getTranslatableCategories() as $catId => $catName) {
    if ($catId == $category) {
        echo '[<strong>' . Html::encode($catName) . '</strong>] ';
    } else {
        $link = UrlHelper::createUrl(['admin/index/translation', 'category' => $catId]);
        echo '[' . Html::a($catName, $link) . '] ';
    }
}


echo Html::beginForm('', 'post', ['id' => 'translationForm', 'class' => 'adminForm form-horizontal']);
echo '<input type="hidden" name="category" value="' . Html::encode($category) . '">';

/** @var I18N $i18n */
$i18n = Yii::$app->get('i18n');
/** @var MessageSource $messagesource */
$messagesource = $i18n->getMessageSource($category);
$strings       = $messagesource->getBaseMessages($category, $consultation->wordingBase);


$consStrings = [];
foreach ($consultation->texts as $text) {
    if ($text->category == $category) {
        $consStrings[$text->textId] = $text->text;
    }
}

echo '<br><br>';


foreach ($strings as $stringKey => $stringOrig) {
    $encKey = Html::encode(urlencode($stringKey));
    $value = (isset($consStrings[$stringKey]) ? $consStrings[$stringKey] : '');
    echo '<fieldset class="form-group">
    <label class="col-sm-6 control-label" for="consultationPath">';
    echo Html::encode($stringOrig);
    echo '</label>
    <div class="col-sm-6">
        <input type="text" name="string[' . $encKey . ']" value="' . Html::encode($value) . '"
        class="form-control" placeholder="' . Html::encode($stringOrig) . '">
    </div>
    </fieldset>';
}


echo '<div class="saveholder">
<button type="submit" name="save" class="btn btn-primary">Speichern</button>
</div>';



echo Html::endForm();
echo '</div>';
