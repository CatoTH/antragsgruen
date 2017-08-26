<?php

namespace app\components;

use app\models\db\ConsultationText;
use app\models\exceptions\Internal;
use app\models\PageData;
use app\models\db\Consultation;
use app\models\settings\AntragsgruenApp;
use Yii;

class MessageSource extends \yii\i18n\MessageSource
{
    public $basePath = '@app/messages';
    public $fileMap;

    /**
     * Initializes this component.
     */
    public function init()
    {
        parent::init();
        if (YII_DEBUG) {
            $this->on(self::EVENT_MISSING_TRANSLATION, function ($event) {
                /** \yii\i18n\MissingTranslationEvent $event */
                /** @var AntragsgruenApp $params */
                $params = \Yii::$app->params;
                $fp     = fopen($params->tmpDir . 'missing-translations.log', 'a');
                fwrite($fp, $event->language . ' - ' . $event->category . ' - ' . $event->message . "\n");
                fclose($fp);
            });
        }
    }

    /**
     * @return array
     */
    public static function getTranslatableCategories()
    {
        if (\Yii::$app->language == 'de') {
            return [
                'base'      => 'Basis-Layout',
                'structure' => 'Interne Bezeichnungen',
                'con'       => 'Veranstaltung',
                'motion'    => 'Anträge',
                'amend'     => 'Änderungsanträge',
                'diff'      => 'Diff',
                'export'    => 'Exports',
                'initiator' => 'InitiatorInnen',
                'manager'   => 'Seiten-Konfiguration',
                'comment'   => 'Kommentare',
                'admin'     => 'Administration',
                'user'      => 'Account-Einstellungen',
                'wizard'    => 'Wizard',
            ];
        } else {
            return [
                'base'      => 'Basic layout',
                'structure' => 'Internal labels',
                'con'       => 'Consultation',
                'motion'    => 'Motion',
                'amend'     => 'Amendment',
                'diff'      => 'Diff',
                'export'    => 'Exports',
                'initiator' => 'Proposers',
                'manager'   => 'Site creation',
                'comment'   => 'Comments',
                'admin'     => 'Administration',
                'user'      => 'User accounts',
                'wizard'    => 'Wizard',
            ];
        }
    }

    /**
     * @param string $language
     * @return array
     */
    public static function getLanguageVariants($language)
    {
        /** @var AntragsgruenApp $params */
        $params        = \Yii::$app->params;
        $localMessages = (isset($params->localMessages[$language]) ? $params->localMessages[$language] : []);
        if ($language == 'de') {
            return array_merge([
                'de-parteitag' => 'Konferenz / Parteitag',
                'de-bewerbung' => 'Bewerbungsverfahren',
                'de-programm'  => 'Programmdiskussion',
                'de-bdk'       => 'BDK',
            ], $localMessages);
        };
        if ($language == 'en') {
            return array_merge([
                'en-uk'       => 'English (UK)',
                'en-congress' => 'Convention',
            ], $localMessages);
        }
        if ($language == 'fr') {
            return array_merge([
                'fr' => 'Français',
            ], $localMessages);
        }
        return [];
    }

    /**
     * @return array
     */
    public static function getBaseLanguages()
    {
        return [
            'de' => 'Deutsch',
            'en' => 'English',
            'fr' => 'Français',
        ];
    }

    /**
     * Returns message file path for the specified language and category.
     *
     * @param string $category the message category
     * @param string $language the target language
     * @return string path to message file
     */
    protected function getMessageFilePath($category, $language)
    {
        $messageFile = Yii::getAlias($this->basePath) . "/$language/";
        if (isset($this->fileMap[$category])) {
            $messageFile .= $this->fileMap[$category];
        } else {
            $messageFile .= str_replace('\\', '/', $category) . '.php';
        }

        return $messageFile;
    }


    /**
     * Loads the message translation for the specified language and category or returns null if file doesn't exist.
     *
     * @param $messageFile string path to message file
     * @return array|null array of messages or null if file not found
     */
    protected function loadMessagesFromFile($messageFile)
    {
        if (is_file($messageFile)) {
            $messages = include($messageFile);
            if (!is_array($messages)) {
                $messages = [];
            }

            return $messages;
        } else {
            return null;
        }
    }

    /**
     * @param string $category
     * @param string $language
     * @return array
     */
    public function getBaseMessages($category, $language)
    {
        return $this->loadMessages($category, $language, false);
    }


    /**
     * @param string $category
     * @param string $language
     * @param bool $withConsultationStrings
     * @return array
     * @throws Internal
     */
    protected function loadMessages($category, $language, $withConsultationStrings = true)
    {
        $categories = static::getTranslatableCategories();
        if (!isset($categories[$category])) {
            throw new Internal('Unknown language category: ' . $category);
        }

        $consultation = UrlHelper::getCurrentConsultation();
        if (!$consultation) {
            $baseFile = $this->getMessageFilePath($category, $language);
            return $this->loadMessagesFromFile($baseFile);
        };
        $languages = explode(',', $consultation->wordingBase);

        $baseFile = $this->getMessageFilePath($category, 'en');
        $origMessages = $this->loadMessagesFromFile($baseFile);

        $baseMessages = $extMessages = [];
        foreach ($languages as $lang) {
            $parts = explode('-', $lang);

            $baseFile = $this->getMessageFilePath($category, $parts[0]);
            $messages = $this->loadMessagesFromFile($baseFile);
            if ($messages) {
                $baseMessages = array_merge($baseMessages, $messages);
            }

            $extFile  = $this->getMessageFilePath($category, $lang);
            $messages = $this->loadMessagesFromFile($extFile);
            if ($messages) {
                $extMessages = array_merge($extMessages, $messages);
            }
        }

        $conSpecific = [];
        if ($withConsultationStrings) {
            foreach ($consultation->texts as $text) {
                if ($text->text != '' && $text->category == $category) {
                    $conSpecific[$text->textId] = $text->text;
                }
            }
        }

        return array_merge($origMessages, $baseMessages, $extMessages, $conSpecific);
    }


    /**
     * @param Consultation|null $consultation
     * @param string $pageKey
     * @param string $html
     * @return bool
     */
    public static function savePageData($consultation, $pageKey, $html)
    {
        $consultationId = ($consultation ? $consultation->id : null);
        /** @var ConsultationText $text */
        $text = ConsultationText::findOne(
            ['consultationId' => $consultationId, 'textId' => $pageKey, 'category' => 'pagedata']
        );
        if (!$text) {
            $text                 = new ConsultationText();
            $text->category       = 'pagedata';
            $text->consultationId = $consultationId;
            $text->textId         = $pageKey;
        }
        $text->text     = HTMLTools::correctHtmlErrors($html);
        $text->editDate = date('Y-m-d H:i:s');
        return $text->save();
    }

    /**
     * @param Consultation|null $consultation
     * @param string $pageKey
     * @return PageData
     * @throws Internal
     */
    public static function getPageData($consultation, $pageKey)
    {
        switch ($pageKey) {
            case 'maintenance':
                $data                  = new PageData();
                $data->pageTitle       = \Yii::t('base', 'content_maint_title');
                $data->breadcrumbTitle = \Yii::t('base', 'content_maint_bread');
                $data->text            = \Yii::t('base', 'content_maint_text');
                break;
            case 'help':
                $data                  = new PageData();
                $data->pageTitle       = \Yii::t('base', 'content_help_title');
                $data->breadcrumbTitle = \Yii::t('base', 'content_help_bread');
                $data->text            = \Yii::t('base', 'content_help_place');
                break;
            case 'legal':
                $data                  = new PageData();
                $data->pageTitle       = \Yii::t('base', 'content_imprint_title');
                $data->breadcrumbTitle = \Yii::t('base', 'content_imprint_bread');
                $data->text            = '<p>Impressum</p>';
                break;
            case 'privacy':
                $data                  = new PageData();
                $data->pageTitle       = \Yii::t('base', 'content_privacy_title');
                $data->breadcrumbTitle = \Yii::t('base', 'content_privacy_bread');
                $data->text            = '';
                break;
            case 'welcome':
                $data                  = new PageData();
                $data->pageTitle       = \Yii::t('base', 'content_welcome');
                $data->breadcrumbTitle = \Yii::t('base', 'content_welcome');
                $data->text            = \Yii::t('base', 'content_welcome_text');
                break;
            default:
                throw new Internal('Unknown page Key: ' . $pageKey);
        }
        if ($consultation) {
            foreach ($consultation->texts as $text) {
                if ($text->category == 'pagedata' && $text->textId == $pageKey) {
                    $data->text = $text->text;
                }
            }
            if ($pageKey == 'privacy' && $data->text == '') {
                /** @var ConsultationText $text */
                $text = ConsultationText::findOne(['consultationId' => null, 'textId' => $pageKey]);
                if ($text) {
                    $data->text = $text->text;
                }
            }
        } else {
            /** @var ConsultationText $text */
            $text = ConsultationText::findOne(['consultationId' => null, 'textId' => $pageKey]);
            if ($text) {
                $data->text = $text->text;
            }
        }
        return $data;
    }
}
