<?php

/**
 * @var $this yii\web\View
 * @var Consultation $consultation
 * @var string $category
 */

use app\components\HTMLTools;
use app\components\yii\MessageSource;
use app\components\UrlHelper;
use app\models\db\Consultation;
use yii\helpers\Html;
use yii\i18n\I18N;

/** @var \app\controllers\admin\IndexController $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;

$layout->addCSS('css/backend.css');

$this->title = \Yii::t('admin', 'Translation / Wording');
$layout->addBreadcrumb(\Yii::t('admin', 'bread_settings'), UrlHelper::createUrl('admin/index'));
$layout->addBreadcrumb(\Yii::t('admin', 'bread_language'));
$layout->bodyCssClasses[] = 'adminTranslationForm';

echo '<h1>' . \Yii::t('admin', 'Translation / Wording') . '</h1>
<div class="content">

<div class="alert alert-info" role="alert">' . \Yii::t('admin', 'translating_hint') . '</div>';


echo Html::beginForm('', 'post', ['id' => 'wordingBaseForm', 'class' => 'adminForm form-horizontal']);
echo '<input type="hidden" name="category" value="' . Html::encode($category) . '">';
echo $controller->showErrors();


echo '<fieldset class="form-group">
        <label class="col-sm-4 control-label" for="startLayoutType">' .
    Yii::t('admin', 'translating_base') . ':</label>
        <div class="col-sm-8">';
echo Html::dropDownList(
    'wordingBase',
    $consultation->wordingBase,
    MessageSource::getLanguageVariants(Yii::$app->language),
    ['class' => 'form-control']
);
echo '</div></fieldset>';

echo '<div class="saveholder">
<button type="submit" name="save" class="btn btn-primary">' . \Yii::t('base', 'save') . '</button>
</div>';

echo Html::endForm();

echo '<br><br>';


foreach (MessageSource::getTranslatableCategories() as $catId => $catName) {
    if ($catId === $category) {
        echo '[<strong>' . Html::encode($catName) . '</strong>] ';
    } else {
        $link = UrlHelper::createUrl(['admin/index/translation', 'category' => $catId]);
        echo '[' . Html::a(Html::encode($catName), $link) . '] ';
    }
}


echo Html::beginForm('', 'post', ['id' => 'translationForm', 'class' => 'adminForm form-horizontal']);
echo '<input type="hidden" name="category" value="' . Html::encode($category) . '">';

/** @var I18N $i18n */
$i18n = Yii::$app->get('i18n');
/** @var MessageSource $messagesource */
$messagesource = $i18n->getMessageSource($category);
$strings       = $messagesource->getBaseMessagesWithHints($category, $consultation->wordingBase);


$consStrings = [];
foreach ($consultation->texts as $text) {
    if ($text->category === $category) {
        $consStrings[$text->textId] = $text->text;
    }
}

echo '<br><br>';


foreach ($strings as $string) {
    $encKey = Html::encode(urlencode($string['id']));
    $value  = (isset($consStrings[$string['id']]) ? $consStrings[$string['id']] : '');
    echo '<div class="form-group"><label class="col-sm-6 control-label" for="consultationPath">';
    echo nl2br(Html::encode($string['text']));
    if (isset($string['description']) && $string['description'] !== '') {
        echo '<br><span class="description">' . Html::encode($string['description']) . '</span>';
    }
    echo '</label><div class="col-sm-6">';

    echo HTMLTools::smallTextarea('string[' . $encKey . ']', ['class' => 'form-control'], $value);
    echo '</div></div>';
}

if (count($strings) === 0) {
    echo '<div class="alert alert-info"><p>';
    echo Yii::t('admin', 'translating_none');
    echo '</p></div>';
}


echo '<div class="saveholder">
<button type="submit" name="save" class="btn btn-primary">' . \Yii::t('base', 'save') . '</button>
</div>';


echo Html::endForm();
echo '</div>';
