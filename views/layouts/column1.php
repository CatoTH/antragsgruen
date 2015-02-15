<?php
/**
 * @var \yii\web\View $this
 * @var string $content
 */

$this->beginContent('@app/views/layouts/main.php');

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$params     = $controller->layoutParams;

$row_classes = array("row", "antragsgruen-content");

?>


    <div class="<?= implode(" ", $row_classes) ?>">
        <main class="col-md-9 well">
            <?php echo $content; ?>
        </main>
    </div>

<?php $this->endContent();