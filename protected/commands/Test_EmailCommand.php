<?php

class Test_EmailCommand extends CConsoleCommand {
	public function run($args) {
		AntraegeUtils::send_email_mandrill("tobias@hoessl.eu", "Test", "Test", null, "test", "Antragsgrün", "info@antragsgruen.de", null);

		//AntraegeUtils::send_mail_log(EmailLog::$EMAIL_TYP_SONSTIGES, $args[0], null, "Test", "Test");

	}
}
