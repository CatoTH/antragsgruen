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
                'base'            => 'Basis-Layout',
                'structure'       => 'Interne Bezeichnungen',
                'con'             => 'Veranstaltung',
                'pages'           => 'Redaktionelle Seiten',
                'motion'          => 'Anträge',
                'amend'           => 'Änderungsanträge',
                'diff'            => 'Diff',
                'export'          => 'Exports',
                'initiator'       => 'InitiatorInnen',
                'manager'         => 'Seiten-Konfiguration',
                'comment'         => 'Kommentare',
                'admin'           => 'Administration',
                'user'            => 'Account-Einstellungen',
                'wizard'          => 'Wizard',
                'memberpetitions' => 'Mitgliederbegehren',
            ];
        } else {
            return [
                'base'            => 'Basic layout',
                'structure'       => 'Internal labels',
                'con'             => 'Consultation',
                'pages'           => 'Content pages',
                'motion'          => 'Motion',
                'amend'           => 'Amendment',
                'diff'            => 'Diff',
                'export'          => 'Exports',
                'initiator'       => 'Proposers',
                'manager'         => 'Site creation',
                'comment'         => 'Comments',
                'admin'           => 'Administration',
                'user'            => 'User accounts',
                'wizard'          => 'Wizard',
                'memberpetitions' => 'Member petitions',
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
                'de-parteitag'       => 'Konferenz / Parteitag',
                'de-bewerbung'       => 'Bewerbungsverfahren',
                'de-programm'        => 'Programmdiskussion',
                'de-bdk'             => 'BDK',
                'de-memberpetitions' => 'Mitgliederbegehren',
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

        /** @var AntragsgruenApp $params */
        $params = \Yii::$app->params;
        foreach ($params->getPluginClasses() as $pluginClass) {
            if ($pluginClass::getMessagePath($category)) {
                $messageFile = Yii::getAlias($pluginClass::getMessagePath($category)) . "/$language/";
            }
        }

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
     * @throws Internal
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

        $categoryFilename = $category;
        if ($category === 'con') {
            $categoryFilename = 'consultation'; // 'con' is a restricted filename at Windows, see #254
        }

        $consultation = UrlHelper::getCurrentConsultation();
        if (!$consultation) {
            $baseFile = $this->getMessageFilePath($categoryFilename, $language);
            return $this->loadMessagesFromFile($baseFile);
        };
        $languages = explode(',', $consultation->wordingBase);

        $baseFile     = $this->getMessageFilePath($categoryFilename, 'en');
        $origMessages = $this->loadMessagesFromFile($baseFile);

        $baseMessages = $extMessages = [];
        foreach ($languages as $lang) {
            $parts = explode('-', $lang);

            $baseFile = $this->getMessageFilePath($categoryFilename, $parts[0]);
            $messages = $this->loadMessagesFromFile($baseFile);
            if ($messages) {
                $baseMessages = array_merge($baseMessages, $messages);
            }

            $extFile  = $this->getMessageFilePath($categoryFilename, $lang);
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
     * @return string[]
     */
    public static function getDefaultPages()
    {
        return [
            'maintenance' => \Yii::t('pages', 'content_maint_title'),
            'help'        => \Yii::t('pages', 'content_help_title'),
            'legal'       => \Yii::t('pages', 'content_imprint_title'),
            'privacy'     => \Yii::t('pages', 'content_privacy_title'),
            'welcome'     => \Yii::t('pages', 'content_welcome'),
            'login'       => \Yii::t('pages', 'content_login'),
        ];
    }

    /**
     * @return string[]
     */
    public static function getSitewidePages()
    {
        return ['legal', 'privacy', 'login'];
    }

    /**
     * Pages that have a fallback for the whole system. Only relevant in multi-site-setups.
     *
     * @return string[]
     */
    public static function getSystemwidePages()
    {
        return ['legal', 'privacy'];
    }

    /**
     * @param Consultation|null $consultation
     * @param string $pageKey
     * @return PageData
     */
    public static function getPageData($consultation, $pageKey)
    {
        switch ($pageKey) {
            case 'maintenance':
                $data                  = new PageData();
                $data->pageTitle       = \Yii::t('pages', 'content_maint_title');
                $data->breadcrumbTitle = \Yii::t('pages', 'content_maint_bread');
                $data->text            = \Yii::t('pages', 'content_maint_text');
                break;
            case 'help':
                $data                  = new PageData();
                $data->pageTitle       = \Yii::t('pages', 'content_help_title');
                $data->breadcrumbTitle = \Yii::t('pages', 'content_help_bread');
                $data->text            = \Yii::t('pages', 'content_help_place');
                break;
            case 'legal':
                $data                  = new PageData();
                $data->pageTitle       = \Yii::t('pages', 'content_imprint_title');
                $data->breadcrumbTitle = \Yii::t('pages', 'content_imprint_bread');
                $data->text            = '<p>' . \Yii::t('pages', 'content_imprint_title') . '</p>';
                break;
            case 'privacy':
                $data                  = new PageData();
                $data->pageTitle       = \Yii::t('pages', 'content_privacy_title');
                $data->breadcrumbTitle = \Yii::t('pages', 'content_privacy_bread');
                $data->text            = '';
                break;
            case 'welcome':
                $data                  = new PageData();
                $data->pageTitle       = \Yii::t('pages', 'content_welcome');
                $data->breadcrumbTitle = \Yii::t('pages', 'content_welcome');
                $data->text            = \Yii::t('pages', 'content_welcome_text');
                break;
            case 'login':
                $data                  = new PageData();
                $data->pageTitle       = \Yii::t('pages', 'content_login');
                $data->breadcrumbTitle = \Yii::t('pages', 'content_login');
                $data->text            = \Yii::t('pages', 'content_login_text');
                break;
            default:
                $data                  = new PageData();
                $data->pageTitle       = '';
                $data->breadcrumbTitle = '';
                $data->text            = '';
                break;
        }
        $found = false;
        if (!in_array($pageKey, static::getSitewidePages())) {
            foreach ($consultation->texts as $text) {
                if ($text->category == 'pagedata' && $text->textId == $pageKey) {
                    $data->text = $text->text;
                    $data->id   = $text->id;
                    $found      = true;
                }
            }
        }
        if (!$found) {
            $site = $consultation->site;
            $text = ConsultationText::findOne([
                'siteId'         => $site->id,
                'consultationId' => null,
                'category'       => 'pagedata',
                'textId'         => $pageKey,
            ]);
            if ($text) {
                $data->text = $text->text;
                $data->id   = $text->id;
                $found      = true;
            }
        }
        if (!$found && in_array($pageKey, static::getSystemwidePages())) {
            $text = ConsultationText::findOne([
                'siteId'   => null,
                'category' => 'pagedata',
                'textId'   => $pageKey,
            ]);
            if ($text) {
                $data->text = $text->text;
                $data->id   = $text->id;
            }
        }
        return $data;
    }

    /**
     * @param Consultation|null $consultation
     */
    public function getAllPages($consultation)
    {
        /** @var ConsultationText[] $text */
        $pages = ConsultationText::findAll(['consultationId' => null]);
    }
}
