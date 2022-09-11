<?php

use app\components\UrlHelper;
use yii\helpers\Html;

echo Html::beginForm(UrlHelper::createUrl('/admin/users/add-multiple-ww'), 'post', [
    'class' => 'addUsersByLogin multiuser samlWW hidden',
]);
?>
    <div class="row">
        <label class="col-md-4 col-md-offset-4">
            <?= Yii::t('admin', 'siteacc_new_saml_ww') ?>
            <textarea id="samlWW" name="samlWW" rows="15"></textarea>
        </label>
    </div>

    <br><br>
    <div class="saveholder">
        <button type="submit" name="addUsers" class="btn btn-primary">
            <?= Yii::t('admin', 'siteacc_new_do') ?>
        </button>
    </div>
<?php
echo Html::endForm();
