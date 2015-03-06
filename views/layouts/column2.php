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

/*
if (isset($controller->text_comments) && $controller->text_comments) {
    $row_classes[] = "text_comments";
}
*/

$menus = array();
if ($params->menu) {
    $menus[] = array("name" => "Aktionen", "items" => $controller->layoutParams->menu);
}
foreach ($params->multimenu as $m) {
    $menus[] = $m;
}
/*
foreach ($menus as $menu) {
$this->widget('bootstrap.widgets.TbMenu', array(
    'type'  => 'list',
    'items' => array_merge(array(
        array('label' => $menu["name"]),
    ), $menu["items"]),
));
}
*/

?>


    <div class="<?= implode(" ", $row_classes) ?>">
        <main class="col-md-9 well">
            <?php echo $content; ?>
        </main>
        <aside class="col-md-3" id="sidebar">
            <?= $params->preSidebarHtml?>
            <div class="well hidden-xs">
                <?= implode("", $params->menusHtml) ?>
            </div>
            <?= $params->postSidebarHtml?>
        </aside>
    </div>

<?php $this->endContent();