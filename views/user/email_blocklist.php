<?php

use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var bool $isBlocked
 */

/** @var \app\controllers\UserController $controller */
$controller = $this->context;
$consultation = $controller->consultation;
$layout = $controller->layoutParams;

$this->title = Yii::t('user', 'no_noti_title');
$layout->addBreadcrumb(Yii::t('user', 'no_noti_bc'));
$layout->robotsNoindex = true;


echo '<h1>' . Yii::t('user', 'no_noti_title') . '</h1>';

echo Html::beginForm('', 'post', ['class' => 'emailBlocklistForm content']);

echo $controller->showErrors();

?>
<div class="radio">
    <label>
        <?= Html::radio('unsubscribeOption', false, ['class' => 'unsubscribeNone', 'value' => 'nothing']) ?>
        <?= Yii::t('user', 'no_noti_unchanged') ?>
    </label>
</div>
<?php if ($consultation) { ?>
    <div class="radio">
        <label>
            <?= Html::radio('unsubscribeOption', true, ['class' => 'unsubscribeConsultation', 'value' => 'consultation']) ?>
            <?= str_replace('%NAME%', $consultation->title, Yii::t('user', 'no_noti_consultation')) ?>
        </label>
    </div>
<?php } ?>

<div class="radio">
    <label>
        <?= Html::radio('unsubscribeOption', false, ['class' => 'unsubscribeAll', 'value' => 'all']) ?>
        <?= Yii::t('user', 'no_noti_all') ?>
    </label>
</div>

<br>

<div class="checkbox">
    <label>
        <?= Html::checkbox('emailBlocklist', $isBlocked, ['class' => 'emailBlocklist']) ?>
        <?= Yii::t('user', 'no_noti_blocklist') ?>
    </label>
</div>

<br>

<div class="saveholder">
    <button type="submit" name="save" class="btn btn-primary"><?= Yii::t('user', 'no_noti_save') ?></button>
</div>

<br><br>

<?= Html::endForm() ?>
