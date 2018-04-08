<?php
$title = "Enter update key";
require(__DIR__ . '/layout-header.php');
?>
    <form method="POST">
        <label>
            Update Key:
            <input type="text" name="set_key" autocomplete="off">
        </label>
        <button type="submit">
            Send
        </button>
    </form>
<?php
require(__DIR__ . '/layout-footer.php');
?>