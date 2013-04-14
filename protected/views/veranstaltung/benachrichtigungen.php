<?php

/**
 * @var AntragsgruenController $this
 * @var bool $eingeloggt
 * @var bool $email_angegeben
 * @var bool $email_bestaetigt
 * @var Person $ich
 */

$this->pageTitle   = Yii::app()->name . ' - Benachrichtigungen';
$this->breadcrumbs = array(
	"Benachrichtigungen",
);

?>
<h1 class="well">E-Mail-Benachrichtigungen</h1>

<div class="well well_first">
	<?php

	if ($eingeloggt) {
		var_dump($ich->veranstaltungsreihenAbos);
	}
	?>
</div>