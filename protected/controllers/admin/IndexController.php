<?php

class IndexController extends AdminControllerBase
{
	public $layout = '//layouts/column1';

	public function actionIndex()
	{
		$this->render('index');
	}

}