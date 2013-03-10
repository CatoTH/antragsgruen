<?php

/**
 * @property integer $id
 * @property integer $antrag_kommentar_id
 * @property string $ip_hash
 * @property integer $cookie_id
 * @property integer $dafuer
 *
 * @property AntragKommentar $antragKommentar
 */
class AntragKommentarUnterstuetzer extends GxActiveRecord {

	public static function model($className=__CLASS__) {
		return parent::model($className);
	}

	/**
	 * @static
	 * @param int $antrag_id
	 * @return null|AntragKommentarUnterstuetzer
	 */
	public static function meineUnterstuetzung($antrag_id) {
		if (isset(Yii::app()->request->cookies['kommentar_bewertung'])) {
			$unt = AntragKommentarUnterstuetzer::model()->findByAttributes(array("antrag_kommentar_id" => $antrag_id, "cookie_id" => Yii::app()->request->cookies['kommentar_bewertung']->value));
			if ($unt !== null) return $unt;
		}
		$unt = AntragKommentarUnterstuetzer::model()->findByAttributes(array("antrag_kommentar_id" => $antrag_id, "ip_hash" => md5($_SERVER["REMOTE_ADDR"])));
		return $unt;
	}

	/**
	 *
	 */
	public function setIdentityParams() {
		$this->ip_hash = md5($_SERVER["REMOTE_ADDR"]);
		if (isset(Yii::app()->request->cookies['kommentar_bewertung'])) $this->cookie_id = Yii::app()->request->cookies['kommentar_bewertung']->value;
		else {
			$cookie_val = rand(0, 2147483647);
			$this->cookie_id = $cookie_val;

			$cookie = new CHttpCookie('kommentar_bewertung', $cookie_val);
			$cookie->expire = time()+60*60*24*180;
			Yii::app()->request->cookies['kommentar_bewertung'] = $cookie;
		}
	}

	public function tableName() {
		return 'antrag_kommentar_unterstuetzer';
	}

	public static function label($n = 1) {
		return Yii::t('app', 'Antragskommentar-UnterstützerIn|Antragskommentar-UnterstützerInnen', $n);
	}

	public function rules() {
		return array(
			array('antrag_kommentar_id, ip_hash, cookie_id, dafuer', 'required'),
			array('antrag_kommentar_id, cookie_id, dafuer', 'numerical', 'integerOnly'=>true),
			array('ip_hash', 'length', 'max'=>32),
			array('id, antrag_kommentar_id, ip_hash, cookie_id, dafuer', 'safe', 'on'=>'search'),
		);
	}

	public function relations() {
		return array(
			'antragKommentar' => array(CActiveRecord::BELONGS_TO, 'AntragKommentar', 'antrag_kommentar_id'),
		);
	}

	public function pivotModels() {
		return array(
		);
	}

	public function attributeLabels() {
		return array(
			'id' => null,
			'antrag_kommentar_id' => null,
			'antragKommentar' => Yii::t('app', 'Antragskommentar'),
			'ip_hash' => Yii::t('app', 'IP-Hash (MD5)'),
			'cookie_id' => Yii::t('app', 'Cookie-Wert'),
			'dafuer' => Yii::t('app', 'Dafür'),
		);
	}

	public function search() {
		$criteria = new CDbCriteria;

		$criteria->compare('id', $this->id);
		$criteria->compare('antrag_kommentar_id', $this->antrag_kommentar_id);
		$criteria->compare('ip_hash', $this->ip_hash);
		$criteria->compare('cookie_id', $this->cookie_id);
		$criteria->compare('dafuer', $this->dafuer);

		return new CActiveDataProvider($this, array(
			'criteria' => $criteria,
		));
	}
}