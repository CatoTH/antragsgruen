<?php

/**
 * @var \yii\web\View $this
 * @var string $content
 */

$this->beginContent('@app/views/layouts/main.php');
?>
    <div class="row-fluid">
        <div class="span9 well">
            <?php echo $content; ?>
        </div>
    </div>
<?php $this->endContent(); ?>