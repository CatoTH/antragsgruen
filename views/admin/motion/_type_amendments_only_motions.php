<?php

use app\components\{HTMLTools, UrlHelper};
use app\models\db\ConsultationMotionType;
use yii\helpers\Html;

/**
 * @var ConsultationMotionType $motionType
 */

$statutes = $motionType->getAmendableOnlyMotions(true, true);

?>
<h2 class="h3"><?= Yii::t('admin', 'motion_type_amendonly_title') ?></h2>

<div class="alert alert-info">
    <p>
        <?= Yii::t('admin', 'motion_type_amendonly_hint') ?>
    </p>
</div>

<?php
if (count($statutes) > 0) {
    echo '<ul>';
    foreach ($statutes as $statute) {
        echo '<li>';
        $editUrl = UrlHelper::createUrl(['admin/motion/update', 'motionId' => $statute->id]);
        echo Html::a(Html::encode($statute->title), $editUrl, ['class' => 'statute' . $statute->id]);
        echo '</li>';
    }
    echo '</ul>';
} else {
    echo '<em>' . Yii::t('admin', 'motion_type_amendonly_noyet') . '</em>';
}

$createLink = UrlHelper::createUrl(['/motion/create', 'motionTypeId' => $motionType->id]);
$cssType = (count($statutes) === 0 ? 'btn-primary' : 'btn-default');
?>
<a class="btn <?= $cssType ?> statuteCreateLnk" href="<?= Html::encode($createLink) ?>">
    <span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span>
    <?= Yii::t('admin', 'motion_type_amendonly_new') ?>
</a>
