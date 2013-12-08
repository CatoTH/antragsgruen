<?php

class Setze_PasswortCommand extends CConsoleCommand {
        public function run($args) {
			if (count($args) != 2) {
				echo "Aufruf: ./yiic [Wurzelwerk-Benutzername|E-Mail] [NeuesPasswort]\n";
				return;
			}

			if (strpos($args[0], "@")) $auth = "email:" . $args[0];
			//else $auth = "openid:https://" . $args[0] . ".netzbegruener.in/";
			else $auth = "openid:https://service.gruene.de/openid/" . $args[0];

			/** @var Person $person  */
			$person = Person::model()->findByAttributes(array("auth" => $auth));
			if (!$person) {
				echo "Person nicht gefunden.\n";
				return;
			}

			$person->pwd_enc = Person::create_hash($args[1]);
			if ($person->save()) {
				echo "Passwort erfolgreich gesetzt.\n";
			} else {
				echo "Fehler beim Speichern.\n";
			}
		}
}
