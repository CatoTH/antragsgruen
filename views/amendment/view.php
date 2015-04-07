<?php

use app\components\UrlHelper;
use app\models\db\Amendment;
use app\models\forms\CommentForm;
use app\models\sectionTypes\ISectionType;
use yii\helpers\Html;

/**
 * @var \yii\web\View $this
 * @var Amendment $amendment
 * @var bool $editLink
 * @var int[] $openedComments
 * @var string|null $adminEdit
 * @var null|string $supportStatus
 * @var null|CommentForm $commentForm
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;
$wording    = $consultation->getWording();


$layout->breadcrumbs[UrlHelper::createMotionUrl($amendment->motion)] = $amendment->motion->getTypeName();
$layout->breadcrumbs[]                                               = $amendment->titlePrefix;

$this->title = $amendment->getTitle() . " (" . $amendment->motion->consultation->title . ", Antragsgrün)";


$html = '<ul class="sidebarActions">';

$html .= '</ul>';
$layout->menusHtml[] = $html;


echo '<h1>' . Html::encode($amendment->getTitle()) . '</h1>';


echo '<div class="content">';


foreach ($amendment->sections as $section) {
    if ($section->consultationSetting->type == ISectionType::TYPE_TEXT_SIMPLE) {
        echo $section->getInlineDiffHtml();
    }
}

echo '</div>';
