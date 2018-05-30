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
     * @param array $deadline
     * @param bool $allowRelativeDates
     * @return string
     */
    public static function formatDeadlineRange($deadline, $allowRelativeDates = true)
    {
        if ($deadline['start'] && $deadline['end']) {
            $start = Tools::formatMysqlDateTime($deadline['start'], null, $allowRelativeDates);
            $end   = Tools::formatMysqlDateTime($deadline['end'], null, $allowRelativeDates);
            return str_replace(['%from%', '%to%'], [$start, $end], \Yii::t('structure', 'policy_deadline_from_to'));
        } elseif ($deadline['start']) {
            $start = Tools::formatMysqlDateTime($deadline['start'], null, $allowRelativeDates);
            return str_replace('%from%', $start, \Yii::t('structure', 'policy_deadline_from'));
        } elseif ($deadline['end']) {
            $end   = Tools::formatMysqlDateTime($deadline['end'], null, $allowRelativeDates);
            return str_replace('%to%', $end, \Yii::t('structure', 'policy_deadline_to'));
        } else {
            return \Yii::t('structure', 'policy_deadline_na');
        }
    }

    /**
     * @param array $deadlines
     * @param bool $allowRelativeDates
     * @return string
     */
    public static function formatDeadlineRanges($deadlines, $allowRelativeDates = true)
    {
        $formatted = [];
        foreach ($deadlines as $deadline) {
            $formatted[] = static::formatDeadlineRange($deadline, $allowRelativeDates);
        }
        return implode(', ', $formatted);
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

    /**
     * @return int
     */
    public static function getCurrentTimestamp()
    {
        $consultation = UrlHelper::getCurrentConsultation();
        if (!$consultation || !User::havePrivilege($consultation, User::PRIVILEGE_CONSULTATION_SETTINGS)) {
            return time();
        }
        if (\Yii::$app->session->get('deadline_debug_mode', null) !== '1') {
            return time();
        }
        $time = \Yii::$app->session->get('deadline_simulate_time');
        if ($time) {
            return Tools::dateSql2timestamp($time);
        } else {
            return time();
        }
    }
}
