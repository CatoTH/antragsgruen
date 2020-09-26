<?php
$title = "Enter update key";
require(__DIR__ . '/layout-header.php');
?>
    <form method="POST" class="content">
        <p>To access the update tool, please authenticate as the site administrator:</p>

        <div class="input-group">
            <label for="update_key" class="input-group-addon">
                Update Key:
            </label>
            <input type="text" name="set_key" autocomplete="off" id="update_key" class="form-control">
            <span class="input-group-btn">
                <button type="submit" class="btn btn-primary">
                    Send
                </button>
             </span>
        </div>

        <p style="margin-top: 30px;">
            You can find the update key in the file <strong>config/config.json</strong> in the line <strong>updateKey.</strong>.<br>
            If you have problems using this tool, please have a look at the
            <a href="https://github.com/CatoTH/antragsgruen/blob/main/docs/update-troubleshooting.md">Troubleshooting FAQ</a>.
        </p>
    </form>
<?php
require(__DIR__ . '/layout-footer.php');
?>
