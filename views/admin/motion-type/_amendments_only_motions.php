<?php

use app\components\UrlHelper;
use app\models\db\ConsultationMotionType;
use yii\helpers\Html;

/**
 * @var ConsultationMotionType $motionType
 */

$statutes = $motionType->getAmendableOnlyMotions(true, true);

?>
<section aria-labelledby="motionTypeAmendmentOnlyTitle">
<h2 class="green" id="motionTypeAmendmentOnlyTitle"><?= Yii::t('admin', 'motion_type_amendonly_title') ?></h2>
<div class="content">
<div class="alert alert-info">
    <p>
        <?= Yii::t('admin', 'motion_type_amendonly_hint') ?>
    </p>
</div>

<?php
if (count($statutes) > 0) {
    echo '<ul class="baseStatutesList">';
    foreach ($statutes as $statute) {
        echo '<li>';
        $editUrl = UrlHelper::createUrl(['admin/motion/update', 'motionId' => $statute->id]);
        echo Html::a(Html::encode($statute->title), $editUrl, ['class' => 'statute' . $statute->id]);
        echo '</li>';
    }
    echo '</ul>';
} else {
    echo '<em class="baseStatutesNone">' . Yii::t('admin', 'motion_type_amendonly_noyet') . '</em>';
}

$createLink = UrlHelper::createUrl(['/motion/create', 'motionTypeId' => $motionType->id]);
$cssType = (count($statutes) === 0 ? 'btn-primary' : 'btn-default');
?>
<a class="btn <?= $cssType ?> statuteCreateLnk" href="<?= Html::encode($createLink) ?>">
    <span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span>
    <?= Yii::t('admin', 'motion_type_amendonly_new') ?>
</a>
</div>
</section>
