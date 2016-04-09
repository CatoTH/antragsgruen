<?php

use app\models\db\ISupporter;
use yii\helpers\Html;

/**
 * @var ISupporter[] $supporters
 * @var ISupporter $newTemplate
 */


/**
 * @param ISupporter $supporter
 * @return string
 */
$getSupporterRow = function (\app\models\db\ISupporter $supporter) {
    $str = '<li><div class="row">';
    $str .= '<input type="hidden" name="supporterId[]" value="' . Html::encode($supporter->id) . '">';

    $title = Html::encode(\Yii::t('admin', 'motion_supp_name'));
    $str .= '<div class="col-md-4 nameCol">';

    $str .= '<span class="glyphicon glyphicon-resize-vertical moveHandle"></span> ';

    $str .= '<input type="text" name="supporterName[]" value="' . Html::encode($supporter->name) . '" ';
    $str .= ' class="form-control supporterName" placeholder="' . $title . '" title="' . $title . '">';
    $str .= '</div>';

    $title = Html::encode(\Yii::t('admin', 'motion_supp_orga'));
    $str .= '<div class="col-md-4">';
    $str .= '<input type="text" name="supporterOrga[]" value="' . Html::encode($supporter->organization) . '" ';
    $str .= ' class="form-control supporterOrga" placeholder="' . $title . '" title="' . $title . '">';
    $str .= '</div>';

    $str .= '<div class="col-md-4">';
    $str .= '<a href="#" class="delSupporter"><span class="glyphicon glyphicon-minus-sign"></span></a>';
    if ($supporter->user) {
        $str .= Html::encode($supporter->user->getAuthName());
    }
    $str .= '</div>';


    $str .= '</div></li>';
    return $str;
};

echo '<h2 class="green">' . \Yii::t('admin', 'motion_edit_supporters') . '</h2>
<div class="content" id="motionSupporterHolder">
<ul>';

foreach ($supporters as $supporter) {
    echo $getSupporterRow($supporter);
}

$template = $getSupporterRow($newTemplate);
echo '</li>
</ul>';

echo '<div class="fullTextAdder"><a href="#">' . Yii::t('initiator', 'fullTextField') . '</a></div>';

echo '<a href="#" class="supporterRowAdder" data-content="' . Html::encode($template) . '">
    <span class="glyphicon glyphicon-plus-sign"></span> ' . \Yii::t('admin', 'motion_edit_supporters_add') . '
</a>';

$fullTextSyntax = Yii::t('initiator', 'fullTextSyntax');
echo '<div class="form-group hidden" id="fullTextHolder">';
echo '<div class="col-md-9">';
echo '<textarea class="form-control" placeholder="' . Html::encode($fullTextSyntax) . '" rows="10"></textarea>';
echo '</div><div class="col-md-3">';
echo '<button type="button" class="btn btn-success fullTextAdd">';
echo '<span class="glyphicon glyphicon-plus"></span> ' . Yii::t('initiator', 'fullTextAdd') . '</button>';
echo '</div>';
echo '</div>';

echo '</div>';

