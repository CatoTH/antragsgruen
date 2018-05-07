<?php
/** @var \app\components\updater\MigrateHelper $helper */
$helper     = \Yii::createObject(\app\components\updater\MigrateHelper::class, ['migration', \Yii::$app]);
$migrations = $helper->getAvailableMigrations();

if (count($migrations) === 0) {
    echo 'No database upgrades necessary';
} else {
    ?>
    <div style="margin-bottom: 10px;"><strong>Database upgrades are necessary.</strong></div>
    <form method="POST">
        <button type="submit" class="btn btn-primary" name="perform_migrations">
            Perform updates
        </button>
    </form>
    <?php
}
