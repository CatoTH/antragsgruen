<?php
/**
 * Created by JetBrains PhpStorm.
 * User: tobias
 * Date: 07.04.12
 * Time: 11:41
 * To change this template use File | Settings | File Templates.
 */
class AdminControllerBase extends GxController {

    public function filters() {
        return array(
            'accessControl',
        );
    }

    public function accessRules() {
        return array(
            array('allow',
                'actions'=>array('minicreate', 'create', 'update', 'admin', 'delete', 'index', 'view'),
                //'roles'=>array('admin'),
                'expression' => function($user, $rule) {
                    /* @var $user CWebUser */
                    return ($user->getState("role") === "admin");
                }
            ),
            array('deny',
                'users'=>array('*'),
            ),
        );
    }
}
