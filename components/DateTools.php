<?php

namespace app\components;

use app\models\db\Consultation;
use app\models\db\User;

class DateTools
{
    /**
     * @param Consultation|null $consultation
     * @return boolean
     */
    public static function isDeadlineDebugModeActive($consultation = null)
    {
        if (!$consultation || !User::havePrivilege($consultation, User::PRIVILEGE_CONSULTATION_SETTINGS)) {
            return false;
        }
        return (\Yii::$app->session->get('deadline_debug_mode', null) === '1');
    }

    /**
     * @param Consultation|null $consultation
     * @param boolean $active
     */
    public static function setDeadlineDebugMode($consultation, $active)
    {
        if ($consultation && User::havePrivilege($consultation, User::PRIVILEGE_CONSULTATION_SETTINGS)) {
            if ($active) {
                \Yii::$app->session->set('deadline_debug_mode', '1');
            } else {
                \Yii::$app->session->remove('deadline_debug_mode');
                \Yii::$app->session->remove('deadline_simulate_time');
            }
        }
    }

    /**
     * @param Consultation|null $consultation
     * @param string|null $time
     */
    public static function setDeadlineTime($consultation, $time)
    {
        if ($consultation && User::havePrivilege($consultation, User::PRIVILEGE_CONSULTATION_SETTINGS)) {
            if ($time) {
                \Yii::$app->session->set('deadline_simulate_time', $time);
            } else {
                \Yii::$app->session->remove('deadline_simulate_time');
            }
        }
    }

    /**
     * @param Consultation|null $consultation
     * @return string|null
     */
    public static function getSimulatedTime($consultation)
    {
        if (!$consultation || !User::havePrivilege($consultation, User::PRIVILEGE_CONSULTATION_SETTINGS)) {
            return null;
        }
        $time = \Yii::$app->session->get('deadline_simulate_time');
        return ($time ? $time : null);
    }
}
