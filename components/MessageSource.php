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
        return [
            'base'      => 'Basis-Layout',
            'structure' => 'Interne Bezeichnungen',
            'con'       => 'Consultation',
            'motion'    => 'Motion',
            'amend'     => 'Amendment',
            'diff'      => 'Diff',
            'export'    => 'Exports',
            'initiator' => 'InitiatorInnen',
            'manager'   => 'Seiten-Konfiguration',
            'comment'   => 'Kommentare',
            'admin'     => 'Administration',
            'user'      => 'Account-Einstellungen',
        ];
    }

    /**
     * @param string $language
     * @return array
     */
    public static function getLanguageVariants($language)
    {
        if ($language == 'de') {
            return [
                'de-parteitag' => 'Konferenz / Parteitag',
                'de-bewerbung' => 'Bewerbungsverfahren',
                'de-programm'  => 'Programmdiskussion',
            ];
        };
        return [];
    }

    /**
     * @return array
     */
    public static function getBaseLanguages()
    {
        return [
            'de' => 'Deutsch',
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
        $parts = explode('-', $consultation->wordingBase);

        $baseFile     = $this->getMessageFilePath($category, $parts[0]);
        $baseMessages = $this->loadMessagesFromFile($baseFile);
        if (!is_array($baseMessages)) {
            $baseMessages = [];
        }

        $extFile     = $this->getMessageFilePath($category, $consultation->wordingBase);
        $extMessages = $this->loadMessagesFromFile($extFile);
        if (!is_array($extMessages)) {
            $extMessages = [];
        }

        $conSpecific = [];
        if ($withConsultationStrings) {
            foreach ($consultation->texts as $text) {
                if ($text->text != '' && $text->category == $category) {
                    $conSpecific[$text->textId] = $text->text;
                }
            }
        }

        return array_merge($baseMessages, $extMessages, $conSpecific);
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
            case 'maintainance':
                $data                  = new PageData();
                $data->pageTitle       = \Yii::t('base', 'content_maint_title');
                $data->breadcrumbTitle = \Yii::t('base', 'content_maint_bread');
                $data->text            = \Yii::t('base', 'content_maint_text');
                break;
            case 'help':
                $data                  = new PageData();
                $data->pageTitle       = \Yii::t('base', 'content_help_title');
                $data->breadcrumbTitle = \Yii::t('base', 'content_help_bread');
                $data->text            = '<p>Hilfe...</p>';
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
