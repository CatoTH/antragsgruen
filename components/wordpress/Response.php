<?php


namespace app\components\wordpress;


class Response extends \yii\web\Response {
	public function send() {
		$this->sendHeaders();
	}
}
