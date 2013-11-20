<?php

/**
 * @property integer $id
 * @property string $typ
 * @property string $name
 * @property string $email
 * @property integer $email_bestaetigt
 * @property string $telefon
 * @property string $auth
 * @property string $angelegt_datum
 * @property integer $admin
 * @property integer $status
 * @property string $pwd_enc
 * @property string $benachrichtigungs_typ
 *
 * @property AenderungsantragKommentar[] $aenderungsantragKommentare
 * @property AenderungsantragUnterstuetzerInnen[] $aenderungsantragUnterstuetzerInnen
 * @property AntragKommentar[] $antragKommentare
 * @property AntragUnterstuetzerInnen[] $antragUnterstuetzerInnen
 * @property Veranstaltung[] $admin_veranstaltungen
 * @property Veranstaltungsreihe[] $admin_veranstaltungsreihen
 * @property VeranstaltungsreihenAbo[] $veranstaltungsreihenAbos
 * @property Antrag[] $abonnierte_antraege
 */
class Person extends GxActiveRecord
{
	public static $TYP_ORGANISATION = 'organisation';
	public static $TYP_PERSON = 'person';
	public static $TYPEN = array(
		'organisation' => "Organisation",
		'person'       => "Natürliche Person",
	);

	public static $STATUS_UNCONFIRMED = 1;
	public static $STATUS_CONFIRMED = 0;
	public static $STATUS_DELETED = -1;
	public static $STATUS = array(
		1  => "Nicht bestätigt",
		0  => "Bestätigt",
		-1 => "Gelöscht",
	);

	/** @var bool */
	private $email_required = false;

	/**
	 * @var $className string
	 * @return GxActiveRecord
	 */
	public static function model($className = __CLASS__)
	{
		return parent::model($className);
	}

	/**
	 * @return string
	 */
	public function tableName()
	{
		return 'person';
	}

	/**
	 * @return string
	 */
	public static function representingColumn()
	{
		return 'name';
	}

	/**
	 * @return array
	 */
	public function relations()
	{
		return array(
			'aenderungsantragKommentare'         => array(self::HAS_MANY, 'AenderungsantragKommentar', 'verfasserIn_id'),
			'aenderungsantragUnterstuetzerInnen' => array(self::HAS_MANY, 'AenderungsantragUnterstuetzer', 'unterstuetzerIn_id'),
			'antragKommentare'                   => array(self::HAS_MANY, 'AntragKommentar', 'verfasserIn_id'),
			'antragUnterstuetzerInnen'           => array(self::HAS_MANY, 'AntragUnterstuetzerInnen', 'unterstuetzerIn_id'),
			'admin_veranstaltungen'              => array(self::MANY_MANY, 'Veranstaltung', 'veranstaltungs_admins(person_id, veranstaltung_id)'),
			'admin_veranstaltungsreihen'         => array(self::MANY_MANY, 'Veranstaltungsreihe', 'veranstaltungsreihen_admins(person_id, veranstaltungsreihe_id)'),
			'veranstaltungsreihenAbos'           => array(self::HAS_MANY, 'VeranstaltungsreihenAbo', 'person_id'),
			'abonnierte_antraege'                => array(self::MANY_MANY, 'Antrag', 'antrag_abos(person_id, antrag_id)'),
		);
	}

	/**
	 * @return array
	 */
	public function attributeLabels()
	{
		return array(
			'id'                                 => Yii::t('app', 'ID'),
			'typ'                                => Yii::t('app', 'Typ'),
			'name'                               => Yii::t('app', 'Name(n)'),
			'email'                              => Yii::t('app', 'E-Mail'),
			'email_bestaetigt'                   => Yii::t('app', 'E-Mail-Adresse bestätigt'),
			'telefon'                            => Yii::t('app', 'Telefon'),
			'auth'                               => Yii::t('app', 'Auth'),
			'pwd_enc'                            => Yii::t('app', 'Passwort-Hash'),
			'angelegt_datum'                     => Yii::t('app', 'Angelegt Datum'),
			'admin'                              => Yii::t('app', 'Admin'),
			'status'                             => Yii::t('app', 'Status'),
			'benachrichtigung_typ'               => Yii::t('app', 'Benachrichtigungszeitpunkt'),
			'aenderungsantragKommentare'         => null,
			'aenderungsantragUnterstuetzerInnen' => null,
			'antragKommentare'                   => null,
			'antragUnterstuetzerInnen'           => null,
			'admin_veranstaltungen'              => null,
			'admin_veranstaltungsreihen'         => null,
			'veranstaltungsreihenAbos'           => null,
			'antrag_abos'                        => null,
		);
	}

	/**
	 * @return CActiveDataProvider
	 */
	public function search()
	{
		$criteria = new CDbCriteria;

		$criteria->compare('id', $this->id);
		$criteria->compare('typ', $this->typ, true);
		$criteria->compare('name', $this->name, true);
		$criteria->compare('email', $this->email, true);
		$criteria->compare('telefon', $this->telefon, true);
		$criteria->compare('auth', $this->auth, true);
		$criteria->compare('angelegt_datum', $this->angelegt_datum, true);
		$criteria->compare('admin', $this->admin);
		$criteria->compare('status', $this->status);

		return new CActiveDataProvider($this, array(
			'criteria' => $criteria,
		));
	}

	/**
	 * @param int $n
	 * @return string
	 */
	public static function label($n = 1)
	{
		return Yii::t('app', 'Person|Personen', $n);
	}

	/**
	 * @return array
	 */
	public function rules()
	{
		$rules = array(
			array('typ, angelegt_datum, admin, status', 'required'),
			array('admin, status', 'numerical', 'integerOnly' => true),
			array('typ', 'length', 'max' => 12),
			array('name, telefon', 'length', 'max' => 100),
			array('email, auth', 'length', 'max' => 200),
			array('email, telefon, auth, pwd_enc', 'default', 'setOnEmpty' => true, 'value' => null),
			array('id, typ, name, email, telefon, auth, pwd_enc, angelegt_datum, admin, status', 'safe', 'on' => 'search'),
		);
		if ($this->email_required) $rules[] = array('email', 'required');
		return $rules;
	}


	/**
	 * @param bool $required
	 */
	public function setEmailRequired($required)
	{
		$this->email_required = $required;
	}


	/**
	 * @return string
	 */
	public static function createPassword() {
		$chars =  'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
		$max = strlen($chars) - 1;
		$pw = "";
		for ($i=0; $i < 8; $i++) $pw .= $chars[rand(0, $max)];
		return $pw;
	}

	/**
	 * @param string $date
	 * @return string
	 */
	public function createEmailBestaetigungsCode($date = "") {
		if ($date == "") $date=date("Ymd");
		$code = $this->id . "-" . substr(md5($this->id . $date . SEED_KEY), 0, 8);
		return $code;
	}

	/**
	 * @param string $code
	 * @return bool
	 */
	public function checkEmailBestaetigungsCode($code) {
		if ($code == $this->createEmailBestaetigungsCode()) return true;
		if ($code == $this->createEmailBestaetigungsCode(date("Ymd", time() - 24*3600))) return true;
		if ($code == $this->createEmailBestaetigungsCode(date("Ymd", time() - 2*24*3600))) return true;
		return false;
	}

	/**
	 * @param Veranstaltungsreihe $veranstaltungsreihe
	 * @param string $betreff
	 * @param string $text
	 */
	public function benachrichtigen($veranstaltungsreihe, $betreff, $text) {
		if ($this->email == "" || !$this->email_bestaetigt) return;
		$abbestellen_url = yii::app()->getBaseUrl(true) . yii::app()->createUrl("veranstaltung/benachrichtigungenAbmelden", array("veranstaltungsreihe_id" => $veranstaltungsreihe->subdomain, "code" => $this->getBenachrichtigungAbmeldenCode()));
		$gruss = "Hallo " . $this->name . ",\n\n";
		$sig = "\n\nLiebe Grüße,\n   Das Antragsgrün-Team\n\n--\n\nFalls du diese Benachrichtigung abbestellen willst, kannst du das hier tun:\n" . $abbestellen_url;
		mail($this->email, $betreff, $gruss . $text . $sig, "From: " . Yii::app()->params['mail_from']);
	}

	/**
	 * @param Antrag $antrag
	 */
	public function benachrichtigenAntrag($antrag) {
		$betreff = "[Antragsgrün] Neuer Antrag: " . $antrag->nameMitRev();
		$text = "Es wurde ein neuer Antrag eingereicht:\nAnlass: ". $antrag->veranstaltung->name . "\nName: " . $antrag->nameMitRev() . "\nLink: " . $antrag->getLink(true);
		$this->benachrichtigen($antrag->veranstaltung->veranstaltungsreihe, $betreff, $text);
	}

	/**
	 * @param Aenderungsantrag $aenderungsantrag
	 */
	public function benachrichtigenAenderungsantrag($aenderungsantrag) {
		$betreff = "[Antragsgrün] Neuer Änderungsantrag zu " . $aenderungsantrag->antrag->nameMitRev();
		$text = "Es wurde ein neuer Änderungsantrag eingereicht:\nAnlass: ". $aenderungsantrag->antrag->veranstaltung->name . "\nAntrag: " . $aenderungsantrag->antrag->nameMitRev() . "\nLink: " . $aenderungsantrag->getLink(true);
		$this->benachrichtigen($aenderungsantrag->antrag->veranstaltung->veranstaltungsreihe, $betreff, $text);
	}

	/**
	 * @param IKommentar $kommentar
	 */
	public function benachrichtigenKommentar($kommentar) {
		$betreff = "[Antragsgrün] Neuer Kommentar zu: " . $kommentar->getAntragName();
		$text = "Es wurde ein neuer Kommentar zu " . $kommentar->getAntragName() . " geschrieben:\n" . $kommentar->getLink(true);
		$this->benachrichtigen($kommentar->getVeranstaltung()->veranstaltungsreihe, $betreff, $text);
	}

	/**
	 * @return string
	 */
	public function getBenachrichtigungAbmeldenCode()
	{
		$code = $this->id . "-" . substr(md5($this->id . "abmelden" . SEED_KEY), 0, 8);
		return $code;
	}

    /**
     * @param string $username
     * @return Person|null
     */
    public static function getWurzelwerkler($username) {
        $user = Person::model()->findByAttributes(array("auth" => "openid:https://service.gruene.de/openid/" . $username));
        if ($user) return $user;
        else return null;
    }

	/**
	 * @return bool
	 */
	public function istWurzelwerklerIn()
	{
		if (preg_match("/https:\/\/[a-z0-9_-]+\.netzbegruener\.in\//siu", $this->auth)) return true;
		return preg_match("/https:\/\/service\.gruene.de\/openid\/[a-z0-9_-]+/siu", $this->auth);
	}

	/**
	 * @return null|string
	 */
	public function getWurzelwerkName()
	{
		$x = preg_match("/https:\/\/([a-z0-9_-]+)\.netzbegruener\.in\//siu", $this->auth, $matches);
		if ($x) return $matches[1];
		$x = preg_match("/https:\/\/service\.gruene.de\/openid\/([a-z0-9_-]+)/siu", $this->auth, $matches);
		if ($x) return $matches[1];
		return null;
	}


	/**
	 * @param string $a
	 * @param string $b
	 * @return bool
	 */
	private function slow_equals($a, $b)
	{
		$diff = strlen($a) ^ strlen($b);
		for ($i = 0; $i < strlen($a) && $i < strlen($b); $i++) {
			$diff |= ord($a[$i]) ^ ord($b[$i]);
		}
		return $diff === 0;
	}


	/**
	 * @static
	 * @param string $password
	 * @return string
	 */
	public static function create_hash($password)
	{
		// from: http://crackstation.net/hashing-security.htm
		// format: algorithm:iterations:salt:hash
		$salt = base64_encode(mcrypt_create_iv(24, MCRYPT_DEV_URANDOM));
		return "sha256:1000:" . $salt . ":" . base64_encode(static::pbkdf2("sha256", $password, $salt, 1000, 24, true));
	}


	/*
	 * PBKDF2 key derivation function as defined by RSA's PKCS #5: https://www.ietf.org/rfc/rfc2898.txt
	 * $algorithm - The hash algorithm to use. Recommended: SHA256
	 * $password - The password.
	 * $salt - A salt that is unique to the password.
	 * $count - Iteration count. Higher is better, but slower. Recommended: At least 1000.
	 * $key_length - The length of the derived key in bytes.
	 * $raw_output - If true, the key is returned in raw binary format. Hex encoded otherwise.
	 * Returns: A $key_length-byte key derived from the password and salt.
	 *
	 * Test vectors can be found here: https://www.ietf.org/rfc/rfc6070.txt
	 *
	 * This implementation of PBKDF2 was originally created by https://defuse.ca
	 * With improvements by http://www.variations-of-shadow.com
	 */
	/**
	 * @param string $algorithm
	 * @param string $password
	 * @param string $salt
	 * @param int $count
	 * @param int $key_length
	 * @param bool $raw_output
	 * @return string
	 */
	private static function pbkdf2($algorithm, $password, $salt, $count, $key_length, $raw_output = false)
	{
		$algorithm = strtolower($algorithm);
		if (!in_array($algorithm, hash_algos(), true))
			die('PBKDF2 ERROR: Invalid hash algorithm.');
		if ($count <= 0 || $key_length <= 0)
			die('PBKDF2 ERROR: Invalid parameters.');

		$hash_length = strlen(hash($algorithm, "", true));
		$block_count = ceil($key_length / $hash_length);

		$output = "";
		for ($i = 1; $i <= $block_count; $i++) {
			// $i encoded as 4 bytes, big endian.
			$last = $salt . pack("N", $i);
			// first iteration
			$last = $xorsum = hash_hmac($algorithm, $last, $password, true);
			// perform the other $count - 1 iterations
			for ($j = 1; $j < $count; $j++) {
				$xorsum ^= ($last = hash_hmac($algorithm, $last, $password, true));
			}
			$output .= $xorsum;
		}

		if ($raw_output)
			return substr($output, 0, $key_length);
		else
			return bin2hex(substr($output, 0, $key_length));
	}


	/**
	 * @param string $password
	 * @return bool
	 */
	public function validate_password($password)
	{
		$params = explode(":", $this->pwd_enc);
		if (count($params) < 4)
			return false;
		$pbkdf2 = base64_decode($params[3]);
		return $this->slow_equals(
			$pbkdf2,
			static::pbkdf2(
				$params[0],
				$password,
				$params[2],
				(int)$params[1],
				strlen($pbkdf2),
				true
			)
		);
	}

}
