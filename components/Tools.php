<?php

declare(strict_types=1);

namespace app\components;

use app\models\db\Consultation;
use app\models\settings\Consultation as ConsultationSettings;
use app\models\exceptions\Internal;
use app\views\pdfLayouts\IPdfWriter;
use Doctrine\Common\Annotations\AnnotationReader;
use setasign\Fpdi\FpdiException;
use setasign\Fpdi\PdfParser\StreamReader;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;
use Symfony\Component\Serializer\NameConverter\MetadataAwareNameConverter;
use Symfony\Component\Serializer\Normalizer\{ArrayDenormalizer, DateTimeNormalizer, ObjectNormalizer};
use Symfony\Component\Serializer\{Serializer, SerializerInterface};

class Tools
{
    private static SerializerInterface $serializer;

    public static function getSerializer(): SerializerInterface
    {
        if (!isset(self::$serializer)) {
            $classMetadataFactory = new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader()));
            $metadataAwareNameConverter = new MetadataAwareNameConverter($classMetadataFactory);
            $encoders = [new JsonEncoder()];
            $normalizers = [
                new ArrayDenormalizer(),
                new DateTimeNormalizer(),
                new ObjectNormalizer($classMetadataFactory, $metadataAwareNameConverter, null, new ReflectionExtractor()),
            ];
            self::$serializer = new Serializer($normalizers, $encoders);
        }
        return self::$serializer;
    }

    public static function dateSql2timestamp(string $input): int
    {
        $parts = explode(' ', $input);
        $date  = array_map('intval', explode('-', $parts[0]));

        if (count($parts) === 2) {
            $time = array_map('intval', explode(':', $parts[1]));
        } else {
            $time = [0, 0, 0];
        }

        $ts = mktime($time[0], $time[1], $time[2], $date[1], $date[2], $date[0]);
        return ($ts === false ? 0 : $ts);
    }

    public static function dateSql2Datetime(string $input): ?\DateTime
    {
        $datetime = \DateTime::createFromFormat('Y-m-d H:i:s', $input . ' 00:00:00');
        return ($datetime === false ? null : $datetime);
    }

    public static function getCurrentDateLocale(): string
    {
        $consultation = Consultation::getCurrent();
        if ($consultation && $consultation->wordingBase) {
            return explode('-', $consultation->wordingBase)[0];
        }
        return explode('-', RequestContext::getWebApplication()->language)[0];
    }

    public static function getCurrentDateFormat(): string
    {
        $consultation = Consultation::getCurrent();
        if ($consultation && $consultation->getSettings()->dateFormat && $consultation->getSettings()->dateFormat !== ConsultationSettings::DATE_FORMAT_DEFAULT) {
            return $consultation->getSettings()->dateFormat;
        }

        return match (self::getCurrentDateLocale()) {
            'de', 'me' => ConsultationSettings::DATE_FORMAT_DMY_DOT,
            'fr', 'ca' => ConsultationSettings::DATE_FORMAT_DMY_SLASH,
            'nl' => ConsultationSettings::DATE_FORMAT_DMY_DASH,
            default => ConsultationSettings::DATE_FORMAT_MDY_SLASH,
        };
    }

    public static function dateBootstraptime2sql(string $time, ?string $locale = null): string
    {
        if ($locale === null) {
            $locale = Tools::getCurrentDateLocale();
        }

        if ($locale === 'de' || $locale === 'me') {
            $pattern = '/^(?<day>\\d{1,2})\.(?<month>\\d{1,2})\.(?<year>\\d{4}) ' .
                       '(?<hour>\\d{1,2})\:(?<minute>\\d{1,2})$/';
            if (preg_match($pattern, $time, $matches) && $matches['year'] > 1970) {
                return sprintf(
                    '%1$04d-%2$02d-%3$02d %4$02d:%5$02d:00',
                    $matches['year'],
                    $matches['month'],
                    $matches['day'],
                    $matches['hour'],
                    $matches['minute']
                );
            }
        } elseif ($locale === 'nl') {
            $pattern = '/^(?<day>\\d{1,2})\-(?<month>\\d{1,2})\-(?<year>\\d{4}) ' .
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
        } elseif ($locale === 'fr' || $locale === 'ca') {
            $pattern = '/^(?<day>\\d{1,2})\/(?<month>\\d{1,2})\/(?<year>\\d{4}) ' .
                       '(?<hour>\\d{1,2})\:(?<minute>\\d{1,2})$/';
            if (preg_match($pattern, $time, $matches) && $matches['year'] > 1970) {
                return sprintf(
                    '%1$04d-%2$02d-%3$02d %4$02d:%5$02d:00',
                    $matches['year'],
                    $matches['month'],
                    $matches['day'],
                    $matches['hour'],
                    $matches['minute']
                );
            }
        } elseif ($locale === 'en') {
            $pattern = '/^(?<month>\\d{1,2})\/(?<day>\\d{1,2})\/(?<year>\\d{4}) ' .
                       '(?<hour>\\d{1,2})\:(?<minute>\\d{1,2}) (?<ampm>am|pm)$/i';
            if (preg_match($pattern, $time, $matches) && $matches['year'] > 1970) {
                if (intval($matches['hour']) === 12) {
                    $hours = (strtolower($matches['ampm']) === 'pm' ? 12 : 0);
                } else {
                    $hours = (strtolower($matches['ampm']) === 'pm' ? intval($matches['hour']) + 12 : $matches['hour']);
                }

                return sprintf(
                    '%1$04d-%2$02d-%3$02d %4$02d:%5$02d:00',
                    $matches['year'],
                    $matches['month'],
                    $matches['day'],
                    (string)$hours,
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

    public static function dateSql2bootstrapdate(?string $date, ?string $locale = null): string
    {
        if ($date === null) {
            return '';
        }
        if ($locale === null) {
            $locale = Tools::getCurrentDateLocale();
        }

        if (!preg_match('/^(?<year>\\d{4})-(?<month>\\d{1,2})-(?<day>\\d{1,2})$/', $date, $matches)) {
            return '';
        }

        if ($locale === 'de' || $locale === 'me') {
            return $matches['day'] . '.' . $matches['month'] . '.' . $matches['year'];
        } elseif ($locale === 'fr' || $locale === 'ca') {
            return $matches['day'] . '/' . $matches['month'] . '/' . $matches['year'];
        } elseif ($locale === 'en') {
            return $matches['month'] . '/' . $matches['day'] . '/' . $matches['year'];
        } elseif ($locale === 'nl') {
            return $matches['day'] . '-' . $matches['month'] . '-' . $matches['year'];
        } else {
            throw new Internal('Unsupported Locale: ' . $locale);
        }
    }

    public static function dateBootstrapdate2sql(?string $date, ?string $locale = null): string
    {
        if ($date === null) {
            return '';
        }
        if ($locale === null) {
            $locale = Tools::getCurrentDateLocale();
        }

        if ($locale === 'de' || $locale === 'me') {
            $pattern = '/^(?<day>\\d{1,2})\.(?<month>\\d{1,2})\.(?<year>\\d{4})$/';
            if (preg_match($pattern, $date, $matches)) {
                return sprintf('%1$04d-%2$02d-%3$02d', $matches['year'], $matches['month'], $matches['day']);
            }
        } elseif ($locale === 'fr' || $locale === 'ca') {
            $pattern = '/^(?<day>\\d{1,2})\/(?<month>\\d{1,2})\/(?<year>\\d{4})$/';
            if (preg_match($pattern, $date, $matches)) {
                return sprintf('%1$04d-%2$02d-%3$02d', $matches['year'], $matches['month'], $matches['day']);
            }
        } elseif ($locale === 'en') {
            $pattern = '/^(?<month>\\d{1,2})\/(?<day>\\d{1,2})\/(?<year>\\d{4})$/';
            if (preg_match($pattern, $date, $matches)) {
                return sprintf('%1$04d-%2$02d-%3$02d', $matches['year'], $matches['month'], $matches['day']);
            }
        } elseif ($locale === 'nl') {
            $pattern = '/^(?<day>\\d{1,2})\-(?<month>\\d{1,2})\-(?<year>\\d{4})$/';
            if (preg_match($pattern, $date, $matches)) {
                return sprintf('%1$04d-%2$02d-%3$02d', $matches['year'], $matches['month'], $matches['day']);
            }
        } else {
            throw new Internal('Unsupported Locale: ' . $locale);
        }

        return '';
    }

    public static function dateSql2bootstraptime(?string $time, ?string $locale = null): string
    {
        if ($time === null) {
            return '';
        }
        if ($locale === null) {
            $locale = Tools::getCurrentDateLocale();
        }

        $pattern = '/^(?<year>\\d{4})\-(?<month>\\d{2})\-(?<day>\\d{2}) ' .
                   '(?<hour>\\d{2})\:(?<minute>\\d{2})\:(?<second>\\d{2})$/';
        if (!preg_match($pattern, $time, $matches)) {
            return '';
        }

        if ($locale === 'de' || $locale === 'me') {
            $date = $matches['day'] . '.' . $matches['month'] . '.' . $matches['year'] . ' ';
            $date .= $matches['hour'] . ':' . $matches['minute'];

            return $date;
        } elseif ($locale === 'fr' || $locale === 'ca') {
            $date = $matches['day'] . '/' . $matches['month'] . '/' . $matches['year'] . ' ';
            $date .= $matches['hour'] . ':' . $matches['minute'];

            return $date;
        } elseif ($locale === 'en') {
            $date = $matches['month'] . '/' . $matches['day'] . '/' . $matches['year'] . ' ';
            $date .= $matches['hour'] . ':' . $matches['minute'];

            return $date;
        } elseif ($locale === 'nl') {
            $date = $matches['day'] . '-' . $matches['month'] . '-' . $matches['year'] . ' ';
            $date .= $matches['hour'] . ':' . $matches['minute'];

            return $date;

        } else {
            throw new Internal('Unsupported Locale: ' . $locale);
        }
    }

    public static function date2bootstraptime(?\DateTime $time, ?string $locale = null): string
    {
        if ($time === null) {
            return '';
        }
        if ($locale === null) {
            $locale = Tools::getCurrentDateLocale();
        }

        return match ($locale) {
            'de', 'me' => $time->format('d.m.Y H:i'),
            'fr', 'ca' => $time->format('d/m/Y H:i'),
            'en' => $time->format('m/d/Y H:i'),
            'nl' => $time->format('d-m-Y H:i'),
            default => throw new Internal('Unsupported Locale: ' . $locale)
        };
    }

    private static int $last_time = 0;

    public static function debugTime(string $name): void
    {
        list($usec, $sec) = explode(' ', microtime());
        $time = sprintf('%14.0f', intval($sec) * 10000 + floatval($usec) * 10000);
        if (self::$last_time) {
            echo 'Time (' . $name . '): ' . ($time - self::$last_time) . ' (' . date('Y-m-d H:i:s') . ')<br>';
        }
        self::$last_time = (int)$time;
    }

    public static function formatMysqlDateWithAria(?string $mysqldate, bool $allowRelativeDates = true): string
    {
        $currentTs = DateTools::getCurrentTimestamp();

        if ($mysqldate === null || strlen($mysqldate) === 0) {
            return '-';
        } elseif (substr($mysqldate, 0, 10) === date('Y-m-d', $currentTs) && $allowRelativeDates) {
            return \Yii::t('base', 'Today');
        } elseif (substr($mysqldate, 0, 10) === date('Y-m-d', $currentTs - 3600 * 24) && $allowRelativeDates) {
            return \Yii::t('base', 'Yesterday');
        }

        $date = explode('-', substr($mysqldate, 0, 10));
        if (count($date) !== 3) {
            return '-';
        }

        $replaces = [
            '%DAY%'       => sprintf('%02d', $date[2]),
            '%MONTH%'     => sprintf('%02d', $date[1]),
            '%YEAR%'      => sprintf('%04d', $date[0]),
            '%MONTHNAME%' => \Yii::t('structure', 'months_' . intval($date[1])),
        ];

        $pattern = match (self::getCurrentDateFormat()) {
            ConsultationSettings::DATE_FORMAT_DMY_DOT => '<span aria-label="%DAY%. %MONTHNAME% %YEAR%">%DAY%.%MONTH%.%YEAR%</span>',
            ConsultationSettings::DATE_FORMAT_DMY_SLASH => '<span aria-label="%DAY%. %MONTHNAME% %YEAR%">%DAY%/%MONTH%/%YEAR%</span>',
            ConsultationSettings::DATE_FORMAT_MDY_SLASH => '<span aria-label="%DAY%. %MONTHNAME% %YEAR%">%MONTH%/%DAY%/%YEAR%</span>',
            ConsultationSettings::DATE_FORMAT_YMD_DASH => '<span aria-label="%DAY%. %MONTHNAME% %YEAR%">%YEAR%-%MONTH%-%DAY%</span>',
            ConsultationSettings::DATE_FORMAT_DMY_DASH => '<span aria-label="%DAY%. %MONTHNAME% %YEAR%">%DAY%-%MONTH%-%YEAR%</span>',
            default => throw new Internal('Unsupported date format: ' . self::getCurrentDateFormat()),
        };

        return str_replace(array_keys($replaces), array_values($replaces), $pattern);
    }

    public static function formatMysqlDate(?string $mysqldate, bool $allowRelativeDates = true): string
    {
        $currentTs = DateTools::getCurrentTimestamp();

        if ($mysqldate === null || strlen($mysqldate) === 0) {
            return '-';
        } elseif (substr($mysqldate, 0, 10) === date('Y-m-d', $currentTs) && $allowRelativeDates) {
            return \Yii::t('base', 'Today');
        } elseif (substr($mysqldate, 0, 10) === date('Y-m-d', $currentTs - 3600 * 24) && $allowRelativeDates) {
            return \Yii::t('base', 'Yesterday');
        }

        $date = explode('-', substr($mysqldate, 0, 10));
        if (count($date) !== 3) {
            return '-';
        }

        return match (self::getCurrentDateFormat()) {
            ConsultationSettings::DATE_FORMAT_DMY_DOT => sprintf('%02d.%02d.%04d', $date[2], $date[1], $date[0]),
            ConsultationSettings::DATE_FORMAT_DMY_SLASH => sprintf('%02d/%02d/%04d', $date[2], $date[1], $date[0]),
            ConsultationSettings::DATE_FORMAT_MDY_SLASH => sprintf('%02d/%02d/%04d', $date[1], $date[2], $date[0]),
            ConsultationSettings::DATE_FORMAT_YMD_DASH => sprintf('%04d-%02d-%02d', $date[0], $date[1], $date[2]),
            ConsultationSettings::DATE_FORMAT_DMY_DASH => sprintf('%02d-%02d-%04d', $date[2], $date[1], $date[0]),
            default => throw new Internal('Unsupported date format: ' . self::getCurrentDateFormat()),
        };
    }

    public static function formatMysqlDateTime(string $mysqlDate, bool $allowRelativeDates = true): string
    {
        if (strlen($mysqlDate) === 0) {
            return '-';
        }

        return self::formatMysqlDate($mysqlDate, $allowRelativeDates) . ", " . substr($mysqlDate, 11, 5);
    }

    public static function formatRemainingTime(?\DateTime $deadline): string
    {
        if (!$deadline) {
            return '?';
        }
        $seconds = $deadline->getTimestamp() - DateTools::getCurrentTimestamp();
        if ($seconds < 0) {
            return \Yii::t('structure', 'remaining_over');
        }
        if ($seconds >= 3600 * 24) {
            $days = (int)floor($seconds / (3600 * 24));

            return $days . ' ' . \Yii::t('structure', $days === 1 ? 'remaining_day' : 'remaining_days');
        } elseif ($seconds >= 3600) {
            $hours = (int)floor($seconds / 3600);

            return $hours . ' ' . \Yii::t('structure', $hours === 1 ? 'remaining_hour' : 'remaining_hours');
        } elseif ($seconds >= 60) {
            $minutes = (int)floor($seconds / 60);

            return $minutes . ' ' . \Yii::t('structure', $minutes === 1 ? 'remaining_minute' : 'remaining_minutes');
        } else {
            return $seconds . ' ' . \Yii::t('structure', $seconds === 1 ? 'remaining_second' : 'remaining_seconds');
        }
    }

    public static function compareSqlTimes(string $dateTime1, string $dateTime2): int
    {
        $ts1 = ($dateTime1 ? self::dateSql2timestamp($dateTime1) : 0);
        $ts2 = ($dateTime2 ? self::dateSql2timestamp($dateTime2) : 0);
        if ($ts1 < $ts2) {
            return -1;
        } elseif ($ts1 > $ts2) {
            return 1;
        } else {
            return 0;
        }
    }

    public static function sanitizeFilename(string $filename, bool $noUmlaut): string
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

    public static function formatModelValidationErrors(array $errors): string
    {
        $errorStrs = [];
        foreach ($errors as $field => $error) {
            foreach ($error as $err) {
                $errorStrs[] = $field . ': ' . $err;
            }
        }

        return implode("\n", $errorStrs);
    }

    private static function parsePhpSize(string $size): int
    {
        if (is_numeric($size)) {
            return intval($size);
        } else {
            $value_length = strlen($size);
            $qty          = floatval(substr($size, 0, $value_length - 1));
            $unit         = strtolower(substr($size, $value_length - 1));
            switch ($unit) {
                case 'k':
                    $qty *= 1024;
                    break;
                case 'm':
                    $qty *= 1048576;
                    break;
                case 'g':
                    $qty *= 1073741824;
                    break;
            }

            return intval($qty);
        }
    }

    public static function getMaxUploadSize(): int
    {
        $post_max_size = self::parsePhpSize((string)ini_get('post_max_size'));
        $upload_size   = self::parsePhpSize((string)ini_get('upload_max_filesize'));
        if ($upload_size < $post_max_size) {
            return $upload_size;
        } else {
            return $post_max_size;
        }
    }

    /**
     * @throws FpdiException
     */
    public static function appendPdfToPdf(IPdfWriter $pdf, string $toAppendData, ?string $bookmarkId = null, ?string $bookmarkName = null): void
    {
        $pageCount = $pdf->setSourceFile(StreamReader::createByString($toAppendData));

        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $page = $pdf->ImportPage($pageNo);
            $dim  = $pdf->getTemplatesize($page);
            if (is_array($dim)) {
                $pdf->AddPage($dim['width'] > $dim['height'] ? 'L' : 'P', [$dim['width'], $dim['height']], false);
            } else {
                $pdf->AddPage();
            }

            if ($pageNo === 1 && $bookmarkId !== null) {
                $pdf->setDestination($bookmarkId, 0, '');
                $pdf->Bookmark($bookmarkName, 0, 0, '', '', [128,0,0], -1, '#' . $bookmarkId);
            }

            $pdf->useTemplate($page);
        }
    }
}
