<?php

use app\components\UrlHelper;
use yii\helpers\Html;

try {
    $updates = \app\components\updater\UpdateChecker::getAvailableUpdates();
    $migrations = \app\components\updater\MigrateHelper::getAvailableMigrations();
    if (count($updates) === 0 && count($migrations) === 0) {
        echo Yii::t('admin', 'updates_none');
    } else {
        echo '<ul>';
        foreach ($updates as $update) {
            echo '<li>' . Html::encode($update->version);
            echo ' <button class="showChanges btn-link">' . Yii::t('admin', 'updates_show_changes') . '</button>';
            echo '<div class="changes hidden">' . nl2br(Html::encode($update->changelog)) . '</div>';
            echo '</li>';
        }
        echo '</ul>';

        if (count($migrations) > 0) {
            echo '<div>' . Yii::t('admin', 'updates_migrate') . '</div>';
        }

        echo Html::beginForm(UrlHelper::createUrl('admin/index/goto-update'), 'post', ['class' => 'updateForm']);
        echo '<button type="submit" name="flushCaches" class="btn btn-small btn-success">' .
            Yii::t('admin', 'updates_start') . '</button>';
        echo Html::endForm();
    }
} catch (Exception $e) {
    echo Yii::t('admin', 'updates_error');
}
