<?php

namespace app\components;

use app\controllers\Base;
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
        /** @var Base $controller */
        $controller = \Yii::$app->controller;
        if (is_a($controller, Base::class) && $controller->consultation) {
            $locale = explode('-', $controller->consultation->wordingBase);
            return $locale[0];
        }
        return \Yii::$app->language;
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
            $pattern = '/^(?<day>\\d{1,2})\.(?<month>\\d{1,2})\.(?<year>\\d{4}) ' .
                '(?<hour>\\d{1,2})\:(?<minute>\\d{1,2})$/';
            if (preg_match($pattern, $time, $matches)) {
                return sprintf(
                    '%1$04d-%2$02d-%3$02d %4$02d:%5$02d:00',
                    $matches['year'],
                    $matches['month'],
                    $matches['day'],
                    $matches['hour'],
                    $matches['minute']
                );
            }
        } elseif ($locale == 'fr') {
            $pattern = '/^(?<day>\\d{1,2})\/(?<month>\\d{1,2})\/(?<year>\\d{4}) ' .
                '(?<hour>\\d{1,2})\:(?<minute>\\d{1,2})$/';
            if (preg_match($pattern, $time, $matches)) {
                return sprintf(
                    '%1$04d-%2$02d-%3$02d %4$02d:%5$02d:00',
                    $matches['year'],
                    $matches['month'],
                    $matches['day'],
                    $matches['hour'],
                    $matches['minute']
                );
            }
        } elseif ($locale == 'en') {
            $pattern = '/^(?<month>\\d{1,2})\/(?<day>\\d{1,2})\/(?<year>\\d{4}) ' .
                '(?<hour>\\d{1,2})\:(?<minute>\\d{1,2}) (?<ampm>am|pm)$/i';
            if (preg_match($pattern, $time, $matches)) {
                $hours = (strtolower($matches['ampm']) == 'pm' ? $matches['hour'] + 12 : $matches['hour']);
                return sprintf(
                    '%1$04d-%2$02d-%3$02d %4$02d:%5$02d:00',
                    $matches['year'],
                    $matches['month'],
                    $matches['day'],
                    $hours,
                    $matches['minute']
                );
            }

            $pattern = '/^(?<month>\\d{1,2})\/(?<day>\\d{1,2})\/(?<year>\\d{4}) ' .
                '(?<hour>\\d{1,2})\:(?<minute>\\d{1,2})$/';
            if (preg_match($pattern, $time, $matches)) {
                return sprintf(
                    '%1$04d-%2$02d-%3$02d %4$02d:%5$02d:00',
                    $matches['year'],
                    $matches['month'],
                    $matches['day'],
                    $matches['hour'],
                    $matches['minute']
                );
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

        if (!preg_match('/^(?<year>\\d{4})\-(?<month>\\d{1,2})\-(?<day>\\d{1,2})$/', $date, $matches)) {
            return '';
        }

        if ($locale == 'de') {
            return $matches['day'] . '.' . $matches['month'] . '.' . $matches['year'];
        } elseif ($locale == 'fr') {
            return $matches['day'] . '/' . $matches['month'] . '/' . $matches['year'];
        } elseif ($locale == 'en') {
            return $matches['month'] . '/' . $matches['day'] . '/' . $matches['year'];
        } else {
            throw new Internal('Unsupported Locale: ' . $locale);
        }
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
            $pattern = '/^(?<day>\\d{1,2})\.(?<month>\\d{1,2})\.(?<year>\\d{4})$/';
            if (preg_match($pattern, $date, $matches)) {
                return sprintf('%1$04d-%2$02d-%3$02d', $matches['year'], $matches['month'], $matches['day']);
            }
        } elseif ($locale == 'fr') {
            $pattern = '/^(?<day>\\d{1,2})\/(?<month>\\d{1,2})\/(?<year>\\d{4})$/';
            if (preg_match($pattern, $date, $matches)) {
                return sprintf('%1$04d-%2$02d-%3$02d', $matches['year'], $matches['month'], $matches['day']);
            }
        } elseif ($locale == 'en') {
            $pattern = '/^(?<month>\\d{1,2})\/(?<day>\\d{1,2})\/(?<year>\\d{4})$/';
            if (preg_match($pattern, $date, $matches)) {
                return sprintf('%1$04d-%2$02d-%3$02d', $matches['year'], $matches['month'], $matches['day']);
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

        $pattern = '/^(?<year>\\d{4})\-(?<month>\\d{2})\-(?<day>\\d{2}) ' .
            '(?<hour>\\d{2})\:(?<minute>\\d{2})\:(?<second>\\d{2})$/';
        if (!preg_match($pattern, $time, $matches)) {
            return '';
        }

        if ($locale == 'de') {
            $date = $matches['day'] . '.' . $matches['month'] . '.' . $matches['year'] . ' ';
            $date .= $matches['hour'] . ':' . $matches['minute'];
            return $date;
        } elseif ($locale == 'fr') {
            $date = $matches['day'] . '/' . $matches['month'] . '/' . $matches['year'] . ' ';
            $date .= $matches['hour'] . ':' . $matches['minute'];
            return $date;
        } elseif ($locale == 'en') {
            $date = $matches['month'] . '/' . $matches['day'] . '/' . $matches['year'] . ' ';
            $date .= $matches['hour'] . ':' . $matches['minute'];
            return $date;
        } else {
            throw new Internal('Unsupported Locale: ' . $locale);
        }
    }

    /**
     * @param \DateTime|null $time
     * @param string|null $locale
     * @return string
     * @throws Internal
     */
    public static function date2bootstraptime($time, $locale = null)
    {
        if ($time === null) {
            return '';
        }
        if ($locale === null) {
            $locale = Tools::getCurrentDateLocale();
        }

        if ($locale == 'de') {
            return $time->format('d.m.Y H:i');
        } elseif ($locale == 'fr') {
            return $time->format('d/m/Y H:i');
        } elseif ($locale == 'en') {
            return $time->format('m/d/Y H:i');
        } else {
            throw new Internal('Unsupported Locale: ' . $locale);
        }
    }

    private static $last_time = 0;

    /**
     * @param string $name
     */
    public static function debugTime($name)
    {
        list($usec, $sec) = explode(' ', microtime());
        $time = sprintf('%14.0f', $sec * 10000 + $usec * 10000);
        if (static::$last_time) {
            echo 'Time (' . $name . '): ' . ($time - static::$last_time) . ' (' . date('Y-m-d H:i:s') . ')<br>';
        }
        static::$last_time = $time;
    }


    /**
     * @static
     * @param string $mysqldate
     * @param string|null $locale
     * @param bool $allowRelativeDates
     * @return string
     * @throws Internal
     */
    public static function formatMysqlDate($mysqldate, $locale = null, $allowRelativeDates = true)
    {
        if (strlen($mysqldate) == 0) {
            return '-';
        } elseif (substr($mysqldate, 0, 10) == date("Y-m-d") && $allowRelativeDates) {
            return \yii::t('base', 'Today');
        } elseif (substr($mysqldate, 0, 10) == date("Y-m-d", time() - 3600 * 24) && $allowRelativeDates) {
            return \yii::t('base', 'Yesterday');
        }

        if ($locale === null) {
            $locale = Tools::getCurrentDateLocale();
        }

        if ($locale == 'de') {
            $date = explode('-', substr($mysqldate, 0, 10));
            return sprintf('%02d.%02d.%04d', $date[2], $date[1], $date[0]);
        } elseif ($locale == 'fr') {
            $date = explode('-', substr($mysqldate, 0, 10));
            return sprintf('%02d/%02d/%04d', $date[2], $date[1], $date[0]);
        } elseif ($locale == 'en') {
            $date = explode('-', substr($mysqldate, 0, 10));
            return sprintf('%02d/%02d/%04d', $date[1], $date[2], $date[0]);
        } else {
            throw new Internal('Unsupported Locale: ' . $locale);
        }
    }

    /**
     * @static
     * @param string $mysqlDate
     * @param string|null $locale
     * @param bool $allowRelativeDates
     * @return string
     * @throws Internal
     */
    public static function formatMysqlDateTime($mysqlDate, $locale = null, $allowRelativeDates = true)
    {
        if (strlen($mysqlDate) == 0) {
            return '-';
        }

        if ($locale === null) {
            $locale = Tools::getCurrentDateLocale();
        }
        if ($locale !== 'de' && $locale !== 'en' && $locale != 'fr') {
            throw new Internal('Unsupported Locale: ' . $locale);
        }

        return self::formatMysqlDate($mysqlDate, $locale, $allowRelativeDates) . ", " . substr($mysqlDate, 11, 5);
    }

    /**
     * @param string $filename
     * @param bool $noUmlaut
     * @return string
     */
    public static function sanitizeFilename($filename, $noUmlaut)
    {
        $filename = str_replace(' ', '_', $filename);
        $filename = str_replace('/', '-', $filename);
        $filename = str_replace('.', '_', $filename);
        $filename = preg_replace('/[^\w0-9_-]/siu', '', $filename);
        if ($noUmlaut) {
            $filename = str_replace(
                ['ä', 'Ä', 'ö', 'Ö', 'ü', 'Ü', 'ß'],
                ['ae', 'Ae', 'oe', 'Oe', 'ue', 'Ue', 'ss'],
                $filename
            );
        }
        return $filename;
    }
}
