<?php

class AntraegeUtils
{

	/**
	 * @param string $input
	 * @return int
	 */
	public static function date_sql2timestamp($input)
	{
		$x    = explode(" ", $input);
		$date = array_map("IntVal", explode("-", $x[0]));

		if (count($x) == 2) $time = array_map("IntVal", explode(":", $x[1]));
		else $time = array(0, 0, 0);

		return mktime($time[0], $time[1], $time[2], $date[1], $date[2], $date[0]);
	}

    /**
     * @param string $input
     * @return string
     */
    public static function date_sql2de($input) {
        $x = explode("-", $input);
        return $x[2] . "." . $x[1] . "." . $x[0];
    }

	private static $last_time = 0;
	public static function debug_time($name) {
		list($usec, $sec) = explode(" ", microtime());
		$time = sprintf("%14.0f", $sec * 10000 + $usec * 10000);
		if (static::$last_time) {
			echo "Zeit ($name): " . ($time - static::$last_time) . " (" . date("Y-m-d H:i:s") . ")<br>";
		}
		static::$last_time = $time;
	}

	public static function send_email_mandrill($mail_typ, $mail_to_email, $mail_to_person_id = null, $betreff, $text, $mail_from_email = null)
	{
		$mandrill = new Mandrill(MANDRILL_API_KEY);

		$tags     = array(EmailLog::$EMAIL_TYP_TAGS[$mail_typ]);

		$headers = array();
		$headers['Auto-Submitted'] = 'auto-generated';

		$message = array(
			'html'              => null,
			'text'              => $text,
			'subject'           => $betreff,
			'from_email'        => Yii::app()->params["mail_from_email"],
			'from_name'         => Yii::app()->params["mail_from_name"],
			'to'                => array(
				array(
					"name"  => null,
					"email" => $mail_to_email,
					"type"  => "to",
				)
			),
			'important'         => false,
			'tags'              => $tags,
			'track_clicks'      => false,
			'track_opens'       => false,
			'inline_css'        => true,
			'headers'           => $headers,
		);
		$mandrill->messages->send($message, false);
	}

	/**
	 * @param int $mail_typ
	 * @param string $mail_to_email
	 * @param null|int $mail_to_person_id
	 * @param string $betreff
	 * @param string $text
	 * @param null|string $mail_from_email
	 * @param null|array $no_log_replaces
	 */
	public static function send_mail_log($mail_typ, $mail_to_email, $mail_to_person_id = null, $betreff, $text, $mail_from_email = null, $no_log_replaces = null)
	{
		$send_text      = ($no_log_replaces ? str_replace(array_keys($no_log_replaces), array_values($no_log_replaces), $text) : $text);
		$send_mail_from = ($mail_from_email ? $mail_from_email : Yii::app()->params['mail_from']);

		if (defined("MANDRILL_API_KEY") && MANDRILL_API_KEY != "") {
			static::send_email_mandrill($mail_typ, $mail_to_email, $mail_to_person_id, $betreff, $send_text, $mail_from_email);
		} else {
			mb_send_mail($mail_to_email, $betreff, $send_text, "From: " . $send_mail_from);
		}

		$obj = new EmailLog();
		if ($mail_to_person_id) $obj->an_person = $mail_to_person_id;
		$obj->an_email  = $mail_to_email;
		$obj->typ       = $mail_typ;
		$obj->von_email = $send_mail_from;
		$obj->betreff   = $betreff;
		$obj->text      = $text;
		$obj->datum     = new CDbExpression('NOW()');
		$obj->save();
	}


}