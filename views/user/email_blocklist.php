<?php

use app\components\Html;

/**
 * @var \app\components\view\View $this
 * @var \app\models\settings\Layout $layout
 * @var \app\models\db\Consultation|null $consultation
 * @var bool $isBlocked
 */


$this->title = Yii::t('user', 'no_noti_title');
$layout->addBreadcrumb(Yii::t('user', 'no_noti_bc'));
$layout->robotsNoindex = true;


echo '<h1>' . Yii::t('user', 'no_noti_title') . '</h1>';

echo Html::beginForm('', 'post', ['class' => 'emailBlocklistForm content']);

echo \app\components\FlashMessages::render();

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
