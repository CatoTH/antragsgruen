<?php

class Test_EmailCommand extends CConsoleCommand {
	public function run($args) {
		if (count($args) != 1) {
			echo "Aufruf: ./yiic testemail test@email.de\n";
			return;
		}

		AntraegeUtils::send_mail_log(EmailLog::$EMAIL_TYP_SONSTIGES, $args[0], null, "Test", "Test");

	}
}
