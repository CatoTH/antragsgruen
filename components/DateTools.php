<?php

namespace app\components;

use app\models\settings\Privileges;
use app\models\db\{Consultation, User};

class DateTools
{
    /**
     * The deadline debug mode lets an admin pretend it is a different point in time, to try out how
     * the site behaves before or after a deadline. Which point in time that is lives in the session,
     * which the API must not touch (see RequestContext::isRestApiRequest()) - and which it could not
     * honour anyway, as a request authenticated by a bearer token carries no session cookie of its
     * own. API requests therefore always see the real time, even for an admin who has the simulation
     * turned on in the browser tab the request originates from.
     */
    private static function canSimulateTime(?Consultation $consultation): bool
    {
        if (!$consultation || RequestContext::isRestApiRequest()) {
            return false;
        }

        return User::havePrivilege($consultation, Privileges::PRIVILEGE_CONSULTATION_SETTINGS, null);
    }

    public static function isDeadlineDebugModeActive(?Consultation $consultation = null): bool
    {
        if (!self::canSimulateTime($consultation)) {
            return false;
        }
        return (RequestContext::getSession()->get('deadline_debug_mode', null) === '1');
    }

    public static function setDeadlineDebugMode(?Consultation $consultation, bool $active): void
    {
        if (self::canSimulateTime($consultation)) {
            if ($active) {
                RequestContext::getSession()->set('deadline_debug_mode', '1');
            } else {
                RequestContext::getSession()->remove('deadline_debug_mode');
                RequestContext::getSession()->remove('deadline_simulate_time');
            }
        }
    }

    public static function setDeadlineTime(?Consultation $consultation, ?string $time): void
    {
        if (self::canSimulateTime($consultation)) {
            if ($time) {
                RequestContext::getSession()->set('deadline_simulate_time', $time);
            } else {
                RequestContext::getSession()->remove('deadline_simulate_time');
            }
        }
    }

    public static function formatDeadlineRange(array $deadline, bool $allowRelativeDates = true): string
    {
        if ($deadline['start'] && $deadline['end']) {
            $start = Tools::formatMysqlDateTime($deadline['start'], $allowRelativeDates);
            $end   = Tools::formatMysqlDateTime($deadline['end'], $allowRelativeDates);
            return str_replace(['%from%', '%to%'], [$start, $end], \Yii::t('structure', 'policy_deadline_from_to'));
        } elseif ($deadline['start']) {
            $start = Tools::formatMysqlDateTime($deadline['start'], $allowRelativeDates);
            return str_replace('%from%', $start, \Yii::t('structure', 'policy_deadline_from'));
        } elseif ($deadline['end']) {
            $end   = Tools::formatMysqlDateTime($deadline['end'], $allowRelativeDates);
            return str_replace('%to%', $end, \Yii::t('structure', 'policy_deadline_to'));
        } else {
            return \Yii::t('structure', 'policy_deadline_na');
        }
    }

    public static function formatDeadlineRanges(array $deadlines, bool $allowRelativeDates = true): string
    {
        $formatted = [];
        foreach ($deadlines as $deadline) {
            $formatted[] = static::formatDeadlineRange($deadline, $allowRelativeDates);
        }
        return implode(', ', $formatted);
    }

    public static function getSimulatedTime(?Consultation $consultation): ?string
    {
        if (!self::canSimulateTime($consultation)) {
            return null;
        }
        $time = RequestContext::getSession()->get('deadline_simulate_time');
        return $time ?: null;
    }

    public static function getCurrentTimestamp(): int
    {
        if (!self::canSimulateTime(UrlHelper::getCurrentConsultation())) {
            return time();
        }
        if (RequestContext::getSession()->get('deadline_debug_mode', null) !== '1') {
            return time();
        }
        $time = RequestContext::getSession()->get('deadline_simulate_time');
        if ($time) {
            return Tools::dateSql2timestamp($time);
        } else {
            return time();
        }
    }
}
