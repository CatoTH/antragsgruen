<?php

/**
 * @var string[] $errors
 */
$title = "Available Updates";
require(__DIR__ . '/layout-header.php');

foreach ($errors as $error) {
    echo '<div class="alert alert-danger">' . htmlentities($error, ENT_COMPAT, 'UTF-8') . '</div>';
}

$updates = \app\components\updater\UpdateChecker::getAvailableUpdates();
if (count($updates) === 0) {
    echo "No updates are available";
    ?>

    <form method="POST" style="text-align: center;">
        <button name="cancel_update" class="btn btn-default">
            Abort Update
        </button>
    </form>

    <?php
    require(__DIR__ . '/layout-footer.php');
    die();
}

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

    <br><br>
    <form method="POST" style="text-align: center;">
        <button name="cancel_update" class="btn btn-default">
            Abort Update
        </button>
    </form>
<?php
require(__DIR__ . '/layout-footer.php');
?>