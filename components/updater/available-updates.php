<?php

$title = "Available Updates";
require(__DIR__ . '/layout-header.php');

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
                <td>
                    <form method="POST">
                        <button type="submit" class="btn btn-primary">
                            Perform update
                        </button>
                    </form>
                </td>
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