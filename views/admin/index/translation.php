<?php

/**
 * @var $this yii\web\View
 * @var Consultation $consultation
 * @var string $category
 */

use app\components\{HTMLTools, yii\MessageSource, UrlHelper};
use app\models\db\Consultation;
use yii\helpers\Html;
use yii\i18n\I18N;

/** @var \app\controllers\admin\IndexController $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;

$layout->addCSS('css/backend.css');

$this->title = Yii::t('admin', 'Translation / Wording');
$layout->addBreadcrumb(Yii::t('admin', 'bread_settings'), UrlHelper::createUrl('admin/index'));
$layout->addBreadcrumb(Yii::t('admin', 'bread_language'));
$layout->bodyCssClasses[] = 'adminTranslationForm';

echo '<h1>' . Yii::t('admin', 'Translation / Wording') . '</h1>
<div class="content">

<div class="alert alert-info">' . Yii::t('admin', 'translating_hint') . '</div>';


echo Html::beginForm('', 'post', ['id' => 'wordingBaseForm', 'class' => 'adminForm']);
echo '<input type="hidden" name="category" value="' . Html::encode($category) . '">';
echo $controller->showErrors();


echo '<div class="stdTwoCols">
        <label class="halfColumn" for="wordingBase">' .
    Yii::t('admin', 'translating_base') . ':</label>
        <div class="halfColumn">';
echo Html::dropDownList(
    'wordingBase',
    $consultation->wordingBase,
    MessageSource::getLanguageVariants(Yii::$app->language),
    ['class' => 'form-control', 'id' => 'wordingBase']
);
echo '</div></div>';

echo '<div class="saveholder">
<button type="submit" name="save" class="btn btn-primary">' . Yii::t('base', 'save') . '</button>
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

echo '<br><br>';
echo Yii::t('admin', 'translation_motion_types') . ': ';
foreach ($consultation->motionTypes as $motionType) {
    echo Html::a(
        '<span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span>' . Html::encode($motionType->titlePlural),
        UrlHelper::createUrl(['admin/index/translation-motion-type', 'motionTypeId' => $motionType->id]),
        ['class' => 'motionTypeTranslation' . $motionType->id]
    ) . ' ';
}


echo Html::beginForm('', 'post', ['id' => 'translationForm', 'class' => 'adminForm']);
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


foreach ($strings as $i => $string) {
    $encKey = Html::encode(urlencode($string['id']));
    $value  = $consStrings[$string['id']] ?? '';
    $htmlId = 'string' . $i;
    echo '<div class="stdTwoCols"><label class="halfColumn control-label" for="' . $htmlId . '">';
    if (isset($string['title']) && $string['title'] !== '') {
        echo Html::encode($string['title']);
    } else {
        echo nl2br(Html::encode($string['text']));
    }
    if (isset($string['description']) && $string['description'] !== '') {
        echo '<br><span class="description">' . Html::encode($string['description']) . '</span>';
    }
    echo '<span class="identifier">' . Html::encode($string['id']) . '</span>';
    echo '</label><div class="halfColumn">';

    echo HTMLTools::smallTextarea('string[' . $encKey . ']', ['class' => 'form-control', 'id' => $htmlId], $value);
    echo '</div></div>';
}

if (count($strings) === 0) {
    echo '<div class="alert alert-info"><p>';
    echo Yii::t('admin', 'translating_none');
    echo '</p></div>';
}


echo '<div class="saveholder">
<button type="submit" name="save" class="btn btn-primary">' . Yii::t('base', 'save') . '</button>
</div>';


echo Html::endForm();
echo '</div>';
