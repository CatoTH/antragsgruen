<?php

class OAuthLoginForm extends CFormModel
{
	public $openid_identifier;
	public $wurzelwerk;

	public function rules()
	{
        return array(
			array('openid_identifier', 'url'),
			array('wurzelwerk', 'safe'),
		);
	}

	public function attributeLabels() {
		return array(
			'openid_identifier' => Yii::t('app', 'OpenID-URL'),
			'wurzelwerk' => Yii::t('app', 'WurzelWerk-Account'),
		);
	}
}
