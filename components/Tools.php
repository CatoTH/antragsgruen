<?php

namespace app\components;

use app\models\exceptions\Internal;

class Tools
{

    /**
     * @param string $input
     * @return int
     */
    public static function dateSql2timestamp($input)
    {
        $parts = explode(' ', $input);
        $date  = array_map('IntVal', explode('-', $parts[0]));

        if (count($parts) == 2) {
            $time = array_map('IntVal', explode(':', $parts[1]));
        } else {
            $time = array(0, 0, 0);
        }

        return mktime($time[0], $time[1], $time[2], $date[1], $date[2], $date[0]);
    }


    /**
     * @return string
     */
    public static function getCurrentDateLocale()
    {
        return 'de';
    }

    /**
     * @param string $time
     * @param string|null $locale
     * @return string
     * @throws Internal
     */
    public static function dateBootstraptime2sql($time, $locale = null)
    {
        if ($locale === null) {
            $locale = Tools::getCurrentDateLocale();
        }

        if ($locale == 'de') {
            $pattern = '/^(?<day>\\d{2})\.(?<month>\\d{2})\.(?<year>\\d{4}) (?<hour>\\d{2})\:(?<minute>\\d{2})$/';
            if (preg_match($pattern, $time, $matches)) {
                $date = $matches['year'] . '-' . $matches['month'] . '-' . $matches['day'] . ' ';
                $date .= $matches['hour'] . ':' . $matches['minute'] . ':00';
                return $date;
            }
        } else {
            throw new Internal('Unsupported Locale: ' . $locale);
        }
        return '';
    }

    /**
     * @param string $date
     * @param string $locale
     * @return string
     * @throws Internal
     */
    public static function dateSql2bootstrapdate($date, $locale = null)
    {
        if ($locale === null) {
            $locale = Tools::getCurrentDateLocale();
        }

        if ($locale == 'de') {
            $pattern = '/^(?<year>\\d{4})\-(?<month>\\d{2})\-(?<day>\\d{2})$/';
            if (preg_match($pattern, $date, $matches)) {
                return $matches['day'] . '.' . $matches['month'] . '.' . $matches['year'];
            }
        } else {
            throw new Internal('Unsupported Locale: ' . $locale);
        }
        return '';
    }

    /**
     * @param string $date
     * @param string|null $locale
     * @return string
     * @throws Internal
     */
    public static function dateBootstrapdate2sql($date, $locale = null)
    {
        if ($locale === null) {
            $locale = Tools::getCurrentDateLocale();
        }

        if ($locale == 'de') {
            $pattern = '/^(?<day>\\d{2})\.(?<month>\\d{2})\.(?<year>\\d{4})$/';
            if (preg_match($pattern, $date, $matches)) {
                return $matches['year'] . '-' . $matches['month'] . '-' . $matches['day'];
            }
        } else {
            throw new Internal('Unsupported Locale: ' . $locale);
        }
        return '';
    }

    /**
     * @param string $time
     * @param string|null $locale
     * @return string
     * @throws Internal
     */
    public static function dateSql2bootstraptime($time, $locale = null)
    {
        if ($locale === null) {
            $locale = Tools::getCurrentDateLocale();
        }
        if ($locale == 'de') {
            $pattern = '/^(?<year>\\d{4})\-(?<month>\\d{2})\-(?<day>\\d{2}) ' .
                '(?<hour>\\d{2})\:(?<minute>\\d{2})\:(?<second>\\d{2})$/';
            if (preg_match($pattern, $time, $matches)) {
                $date = $matches['day'] . '.' . $matches['month'] . '.' . $matches['year'] . ' ';
                $date .= $matches['hour'] . ':' . $matches['minute'];
                return $date;
            }
        } else {
            throw new Internal('Unsupported Locale: ' . $locale);
        }
        return '';
    }

    private static $last_time = 0;

    /**
     * @param string $name
     */
    public static function debugTime($name)
    {
        list($usec, $sec) = explode(" ", microtime());
        $time = sprintf("%14.0f", $sec * 10000 + $usec * 10000);
        if (static::$last_time) {
            echo "Zeit ($name): " . ($time - static::$last_time) . " (" . date("Y-m-d H:i:s") . ")<br>";
        }
        static::$last_time = $time;
    }

    /**
     * @param string $mailType
     * @param string $toEmail
     * @param string $subject
     * @param string $text
     * @param string $fromEmail
     * @param string $fromName
     */
    public static function sendEmailMandrill($mailType, $toEmail, $subject, $text, $fromEmail, $fromName)
    {
        /** @var \app\models\settings\AntragsgruenApp $params */
        $params = \Yii::$app->params;

        $mandrill = new \Mandrill($params->mandrillApiKey);

        $tags = array(\app\models\db\EmailLog::getTypes()[$mailType]);

        $headers                   = array();
        $headers['Auto-Submitted'] = 'auto-generated';

        $message = array(
            'html'         => null,
            'text'         => $text,
            'subject'      => $subject,
            'from_email'   => $fromEmail,
            'from_name'    => $fromName,
            'to'           => array(
                array(
                    "name"  => null,
                    "email" => $toEmail,
                    "type"  => "to",
                )
            ),
            'important'    => false,
            'tags'         => $tags,
            'track_clicks' => false,
            'track_opens'  => false,
            'inline_css'   => true,
            'headers'      => $headers,
        );
        $mandrill->messages->send($message, false);
    }

    /**
     * @param int $mailType
     * @param string $toEmail
     * @param null|int $toPersonId
     * @param string $subject
     * @param string $text
     * @param null|string $fromName
     * @param null|string $fromEmail
     * @param null|array $noLogReplaces
     */
    public static function sendMailLog(
        $mailType,
        $toEmail,
        $toPersonId,
        $subject,
        $text,
        $fromName = null,
        $fromEmail = null,
        $noLogReplaces = null
    ) {
        /** @var \app\models\settings\AntragsgruenApp $params */
        $params = \Yii::$app->params;

        $sendText = ($noLogReplaces ? str_replace(
            array_keys($noLogReplaces),
            array_values($noLogReplaces),
            $text
        ) : $text);

        $fromName     = ($fromName ? $fromName : $params->mailFromName);
        $fromEmail    = ($fromEmail ? $fromEmail : $params->mailFromEmail);
        $sendMailFrom = mb_encode_mimeheader($fromName) . ' <' . $fromEmail . '>';

        if (YII_ENV != 'test' && mb_strpos($toEmail, '@example.org') === false) {
            if ($params->mandrillApiKey) {
                static::sendEmailMandrill($mailType, $toEmail, $subject, $sendText, $fromEmail, $fromName);
            } else {
                mb_send_mail($toEmail, $subject, $sendText, "From: " . $sendMailFrom);
            }
        }

        $obj = new \app\models\db\EmailLog();
        if ($toPersonId) {
            $obj->toUserId = $toPersonId;
        }
        $obj->toEmail   = $toEmail;
        $obj->type      = $mailType;
        $obj->fromEmail = $sendMailFrom;
        $obj->subject   = $subject;
        $obj->text      = $text;
        $obj->dateSent  = date('Y-m-d H:i:s');
        $obj->save();
    }


    /**
     * @static
     * @param string $mysqldate
     * @param string|null $locale
     * @return string
     * @throws Internal
     */
    public static function formatMysqlDate($mysqldate, $locale = null)
    {
        if (strlen($mysqldate) == 0) {
            return '-';
        } elseif (substr($mysqldate, 0, 10) == date("Y-m-d")) {
            return \yii::t('base', 'Today');
        } elseif (substr($mysqldate, 0, 10) == date("Y-m-d" - 3600 * 24)) {
            return \yii::t('base', 'Yesterday');
        }

        if ($locale === null) {
            $locale = Tools::getCurrentDateLocale();
        }
        if ($locale !== 'de') {
            throw new Internal('Unsupported Locale: ' . $locale);
        }

        $date = explode("-", substr($mysqldate, 0, 10));
        return sprintf("%02d.%02d.%04d", $date[2], $date[1], $date[0]);
    }

    /**
     * @static
     * @param string $mysqlDate
     * @param string|null $locale
     * @return string
     * @throws Internal
     */
    public static function formatMysqlDateTime($mysqlDate, $locale = null)
    {
        if (strlen($mysqlDate) == 0) {
            return '-';
        }

        if ($locale === null) {
            $locale = Tools::getCurrentDateLocale();
        }
        if ($locale !== 'de') {
            throw new Internal('Unsupported Locale: ' . $locale);
        }

        return self::formatMysqlDate($mysqlDate) . ", " . substr($mysqlDate, 11, 5) . " Uhr";
    }
}
