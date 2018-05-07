<?php

/**
 * @var string[] $errors
 */

$title = "Available Updates";
require(__DIR__ . '/layout-header.php');

foreach ($errors as $error) {
    echo '<div class="alert alert-danger">' . htmlentities($error, ENT_COMPAT, 'UTF-8') . '</div>';
}
foreach ($success as $msg) {
    echo '<div class="alert alert-success">' . htmlentities($msg, ENT_COMPAT, 'UTF-8') . '</div>';
}

?>
    <div class="currentVersion content">
        <strong>Current version: </strong>
        <?= ANTRAGSGRUEN_VERSION ?>
    </div>
    <section class="updateFiles">
        <h2 class="green">1. Update files</h2>
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
                        <tr>
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

    <section class="updateDatabase">
        <h2 class="green">2. Update database</h2>
        <div class="content migrationContent"></div>
            <script>
                $.get('update.php?check_migrations=1', function(ret) {
                   $(".migrationContent").html(ret);
                });
            </script>
    </section>

    <br><br>
    <form method="POST" style="text-align: center;" class="content">
        <button name="cancel_update" class="btn btn-default">
            Abort Update
        </button>
    </form>
<?php
require(__DIR__ . '/layout-footer.php');
?>