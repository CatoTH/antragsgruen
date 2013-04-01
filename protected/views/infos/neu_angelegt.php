<?php
/**
 * @var InfosController $this
 * @var array|Veranstaltungsreihe[] $reihen
 * @var Veranstaltungsreihe $reihe
 * @var string $login_id
 * @var string $login_code
 */

?>
<h1>Veranstaltung angelegt</h1>
<div class="form well">
	Die Veranstaltung wurde angelegt.
	<p><?php

		/** @var TbActiveForm $form */
		$form = $this->beginWidget('bootstrap.widgets.TbActiveForm', array(
			'id'  => 'horizontalForm',
			'type'=> 'horizontal',
		));

		$this->widget('bootstrap.widgets.TbButton', array(
			'type'      => 'primary',
			'size'      => 'large',
			'buttonType'=> 'submitlink',
			'url'       => $this->createUrl("veranstaltung/index", array("veranstaltungsreihe_id" => $reihe["subdomain"], "login" => $login_id, "login_sec" => $login_code)),
			'label'     => 'Zur neu angelegten Veranstaltung',
		));
		$this->endWidget();

		?></p>
</div>