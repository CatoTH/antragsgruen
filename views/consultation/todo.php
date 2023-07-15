<?php

use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var \app\models\AdminTodoItem[] $todo
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;

$this->title = Yii::t('admin', 'todo_title');
$layout->addCSS('css/backend.css');
$layout->addBreadcrumb(Yii::t('admin', 'bread_todo'));


echo '<h1>' . Yii::t('admin', 'index_todo') . '</h1>';
echo '<div class="content adminTodo">';

if (count($todo) > 0) {
    echo '<div class="motionListLink">';
    $motionListText = Html::encode(Yii::t('admin', 'index_todo_motions')) . ' <span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span>';
    echo Html::a($motionListText, \app\components\UrlHelper::createUrl(['/admin/motion-list/index', 'Search[onlyTodo]' => '1']));
    echo '</div>';
    echo '<ul>';
    foreach ($todo as $do) {
        echo '<li class="' . Html::encode($do->todoId) . '">';
        echo '<div class="action">' . Html::encode($do->action) . '</div>';
        echo Html::a(Html::encode($do->title), $do->link);
        if ($do->description) {
            echo '<div class="description">' . Html::encode($do->description) . '</div>';
        }
        echo '</li>';
    }
    echo '</ul>';
} else {
    echo '<div class="alert alert-info"><p>';
    echo Html::encode(Yii::t('admin', 'index_todo_none'));
    echo '</p></div>';
}

echo '</div>';
