<?php

/**
 * @var string[] $errors
 * @var string[] $success
 */

$title = "Available Updates";
require(__DIR__ . '/layout-header.php');

foreach ($errors as $error) {
    echo '<div class="alert alert-danger">' . $error . '</div>';
}
foreach ($success as $msg) {
    echo '<div class="alert alert-success">' . $msg . '</div>';
}

?>
    <div class="currentVersion content">
        <strong>Current version: </strong>
        <?= ANTRAGSGRUEN_VERSION ?>

        <?php

        if (version_compare(PHP_VERSION, '8.0.0', '<')) {
            echo '<br><br><div class="alert alert-danger">' .
                'You are using PHP version ' . PHP_VERSION . '. Please upgrade to PHP 8.1 or higher soon, ' .
                'as PHP 8.0 and below <a href="https://secure.php.net/supported-versions.php">will not be supported after November 2023</a> anymore. ' .
                'Also, the next version of Antragsgrün will not support PHP 7 anymore, so at the <em>very least</em> an upgrade to PHP 8.0 will be necessary.' .
                '</div>';
        } elseif (version_compare(PHP_VERSION, '8.1.0', '<')) {
            echo '<br><br><div class="alert alert-danger">' .
                'You are using PHP version ' . PHP_VERSION . '. Please upgrade to PHP 8.1 or higher soon, ' .
                'as PHP 8.0 and below <a href="https://secure.php.net/supported-versions.php">will not be supported after November 2023</a> anymore. ' .
                'Also, Antragsgrün runs seriously faster with newer versions.' .
                '</div>';
        } elseif (!extension_loaded('sodium')) {
            echo '<br><br><div class="alert alert-info">' .
                'It seems the <strong>libsodium</strong> extension is missing in this PHP environment. ' .
                'The updater works without it, but much slower and less secure. ' .
                'Therefore, we strongly advice to install this extension at your web hoster.' .
                '</div>';
        }

        $maxtime = ini_get('max_execution_time');
        if ($maxtime < 30) {
            echo '<br><br><div class="alert alert-danger">' .
                'The maximum execution time of scripts is only ' . $maxtime . ' seconds. ' .
                'If you upgrade with such a low timeout, there is a serious risk of the script aborting during the ' .
                'upgrade process, which could lead to unpredictable results. It is highly advised to increase ' .
                'the value of the PHP environment variable <strong>max_execution_time</strong>.' .
                '</div>';
        } elseif ($maxtime < 60) {
            echo '<br><br><div class="alert alert-info">' .
                'The maximum execution time of scripts is only ' . $maxtime . ' seconds. ' .
                'This should be enough, but when upgrading there still is a risk of the script aborting during the ' .
                'upgrade process, which could lead to unpredictable results. It is advised to increase ' .
                'the value of the PHP environment variable <strong>max_execution_time</strong>.' .
                '</div>';
        }

        ?>
    </div>
    <section class="updateFiles">
        <h2 class="green">Update files</h2>
        <div class="content">
            <?php

            $updates = \app\components\updater\UpdateChecker::getAvailableUpdates();
            if (count($updates) === 0) {
                echo "No updates are available";
            } else {
                ?>
                <table class="availableUpdateList">
                    <thead>
                    <tr>
                        <th>Version</th>
                        <th>Description</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    foreach ($updates as $update) {
                        ?>
                        <tr class="updateAvailable">
                            <th><?= htmlentities($update->version, ENT_COMPAT, 'UTF-8') ?></th>
                            <td><?= nl2br(htmlentities($update->changelog, ENT_COMPAT, 'UTF-8')) ?></td>
                            <?php
                            if (!$update->isDownloaded()) {
                                ?>
                                <td>
                                    <form method="POST">
                                        <input type="hidden" name="version"
                                               value="<?= htmlentities($update->version, ENT_COMPAT, 'UTF-8') ?>">
                                        <button type="submit" class="btn btn-primary" name="download_update">
                                            Download
                                        </button>
                                    </form>
                                </td>
                                <?php
                            } else {
                                ?>
                                <td>
                                    <span class="glyphicon glyphicon-check"></span> Downloaded<br>
                                    <form method="POST">
                                        <input type="hidden" name="version"
                                               value="<?= htmlentities($update->version, ENT_COMPAT, 'UTF-8') ?>">
                                        <button type="submit" class="btn btn-primary" name="perform_update">
                                            Perform update
                                        </button>
                                    </form>
                                </td>
                                <?php
                            }
                            ?>
                        </tr>
                        <?php
                    }
                    ?>
                    </tbody>
                </table>
                <?php
            }
            ?>
        </div>
    </section>

    <section class="updateDatabase migrationContent"></section>

    <br><br>
    <form method="POST" style="text-align: center;" class="content leaveUpdateButtons">
        <button name="cancel_update" class="btn btn-primary exitUpdate" style="display: none;">
            Leave update mode
        </button>
        <button name="cancel_update" class="btn btn-default abortUpdate">
            Abort Update
        </button>
    </form>

    <script>
        $.get('update.php?check_migrations=1', function (ret) {
            $(".migrationContent").html(ret);
            if ($(".updateAvailable").length === 0) {
                $(".leaveUpdateButtons .exitUpdate").css("display", "inline");
                $(".leaveUpdateButtons .abortUpdate").css("display", "none");
            }
        });
    </script>
<?php
require(__DIR__ . '/layout-footer.php');
?>
