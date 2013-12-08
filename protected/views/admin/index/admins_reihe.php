<?php
/**
 * @var IndexController $this
 * @var Person[] $admins
 * @var Person $ich
 * @var Sprache $sprache
 * @var string $del_url
 * @var string $add_url
 */
$this->breadcrumbs = array(
	'Administration' => $this->createUrl("admin/index"),
	'Reihen-Admins',
);

?>
<h1>Administratoren der Reihe</h1>

<?php
$this->widget('bootstrap.widgets.TbAlert', array(
	'block' => true,
	'fade'  => true,
));
?>

<div class="content">
	<h2>Eingetragen</h2>
	<ul>
		<?php foreach ($admins as $admin) {
			echo "<li>" . CHtml::encode($admin->name) . " (" . CHtml::encode($admin->getWurzelwerkName()) . ")";
			if ($admin->id != $ich->id) echo " [<a href='" . CHtml::encode(str_replace("REMOVEID", $admin->id, $del_url)) . "'>entfernen</a>]";
			echo "</li>";
		} ?>
	</ul>
	<br><br>

	<h2>Neu eintragen</h2>

	<form method="POST" action="<?php echo CHtml::encode($add_url); ?>">
		<label>Wurzelwerk-BenutzerInnenname: <input type="text" name="username" value=""></label>
		<button type="submit" name="<?php echo AntiXSS::createToken("adduser") ?>" class="btn btn-primary">Hinzufügen
		</button>
	</form>
</div>
