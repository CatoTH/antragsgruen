<?php
/**
 * @var \yii\web\View $this
 * @var string $content
 */

$this->beginContent('@app/views/layouts/main.php');

/** @var \app\controllers\Base $controller */
$controller = $this->context;

$row_classes = array("row");
if (isset($controller->shrink_cols) && $controller->shrink_cols) $row_classes[] = "shrink_cols";
if (isset($controller->text_comments) && $controller->text_comments) $row_classes[] = "text_comments";
?>


<div class="<?= implode(" ", $row_classes) ?>">
    <div class="col-md-9 well">
        <?php echo $content; ?>
    </div>
    <?php if ($controller->menu || isset($controller->multimenu) || isset($controller->menus_html) || isset($controller->menus_html_presidebar)) { ?>
        <div class="col-md-3" id="sidebar">
            <?php if (isset($controller->menus_html_presidebar)) echo $controller->menus_html_presidebar; ?>
            <div class="well<?php if (isset($controller->text_comments) && $controller->text_comments) echo " visible-desktop"; ?>">
                <?php
                $menus = array();
                if ($controller->menu) $menus[] = array("name" => "Aktionen", "items" => $controller->menu);
                if (isset($controller->multimenu)) foreach ($controller->multimenu as $m) $menus[] = $m;
                foreach ($menus as $menu) {
                    /*
                    $this->widget('bootstrap.widgets.TbMenu', array(
                        'type'  => 'list',
                        'items' => array_merge(array(
                            array('label' => $menu["name"]),
                        ), $menu["items"]),
                    ));
                    */
                }
                if (isset($controller->menus_html)) foreach ($controller->menus_html as $html) echo $html;
                ?>
            </div>
        </div>
    <?php } ?>
</div>

<?php $this->endContent(); ?>
