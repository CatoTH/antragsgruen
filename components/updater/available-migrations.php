<?php
/** @var \app\components\updater\MigrateHelper $helper */
$helper     = \Yii::createObject(\app\components\updater\MigrateHelper::class, ['migration', \Yii::$app]);
$migrations = $helper->getAvailableMigrations();

if (count($migrations) > 0) {
    ?>
    <h2 class="green">2. Update database</h2>
    <div class="content updateAvailable">
        <div style="margin-bottom: 10px;">
            <strong>Database upgrades are necessary.</strong><br>
            <br>
            If you have shell access to your web server, the safest way to do this is by entering the following command
            on the command line:<br>
            <pre class="code">./yii migrate</pre>
            <br>
            If you don't have shell access, you can upgrade using this web update program. However, for big
            installations with hundreds or thousands of motions, please make sure the timeout of PHP scripts is set to a
            high value, to prevent the update process from being forcefully interrupted.<br>
            <pre class="code">Current timeout: <?= ini_get('max_execution_time') ?> seconds</pre>
        </div>


        <form method="POST" action="update.php">
            <button type="submit" class="btn btn-primary" name="perform_migrations">
                Perform updates
            </button>
        </form>
    </div>
    <?php
}
