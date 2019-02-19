<?php

namespace app\components\yii;

use app\components\UrlHelper;
use app\models\exceptions\Internal;
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
                $fp     = fopen($params->getTmpDir() . 'missing-translations.log', 'a');
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
            $categories = [
                'base'      => 'Basis-Layout',
                'structure' => 'Interne Bezeichnungen',
                'con'       => 'Veranstaltung',
                'pages'     => 'Redaktionelle Seiten',
                'motion'    => 'Anträge',
                'amend'     => 'Änderungsanträge',
                'diff'      => 'Diff',
                'export'    => 'Exports',
                'initiator' => 'Initiator*innen',
                'manager'   => 'Seiten-Konfiguration',
                'comment'   => 'Kommentare',
                'admin'     => 'Administration',
                'user'      => 'Account-Einstellungen',
                'wizard'    => 'Wizard',
            ];
        } else {
            $categories = [
                'base'      => 'Basic layout',
                'structure' => 'Internal labels',
                'con'       => 'Consultation',
                'pages'     => 'Content pages',
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

        foreach (AntragsgruenApp::getActivePluginIds() as $pluginId) {
            $categories[$pluginId] = $pluginId;
        }

        return $categories;
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
        if ($language === 'de') {
            return array_merge([
                'de-parteitag'       => 'Konferenz / Parteitag',
                'de-bewerbung'       => 'Bewerbungsverfahren',
                'de-programm'        => 'Programmdiskussion',
                'de-bdk'             => 'BDK',
                'de-neos'            => 'NEOS',
            ], $localMessages);
        };
        if ($language === 'en') {
            return array_merge([
                'en-uk'       => 'English (UK)',
                'en-congress' => 'Convention',
            ], $localMessages);
        }
        if ($language === 'fr') {
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

        foreach (AntragsgruenApp::getActivePluginIds() as $pluginId) {
            if ($category === $pluginId) {
                $messageFile = '@app/plugins/' . $pluginId . '/messages/' . $language . '/';
                $messageFile = Yii::getAlias($messageFile);
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
            return [];
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
     * @return array
     */
    public function getBaseMessagesWithHints($category, $language)
    {
        $messages = $this->loadMessagesRaw($category, $language, false);
        return array_map(function ($textId, $entry) {
            if (is_array($entry)) {
                $entry['id'] = $textId;
                return $entry;
            } else {
                return [
                    'id'          => $textId,
                    'text'        => $entry,
                    'description' => '',
                ];
            }
        }, array_keys($messages), $messages);
    }

    /**
     * @param string $category
     * @param string $language
     * @param bool $withConsultationStrings
     * @return array
     */
    private function loadMessagesRaw($category, $language, $withConsultationStrings)
    {
        $categories = static::getTranslatableCategories();
        if (!isset($categories[$category])) {
            throw new Internal('Unknown language category: ' . $category);
        }

        $categoryFilename = $category;
        if ($category === 'con') {
            $categoryFilename = 'consultation'; // 'con' is a restricted filename at Windows, see #254
        }

        $baseFile     = $this->getMessageFilePath($categoryFilename, 'en');
        $origMessages = $this->loadMessagesFromFile($baseFile);

        $consultation = UrlHelper::getCurrentConsultation();
        if (!$consultation) {
            if ($language === 'en') {
                return $origMessages;
            } else {
                $baseFile      = $this->getMessageFilePath($categoryFilename, $language);
                $transMessages = $this->loadMessagesFromFile($baseFile);
                return array_merge($origMessages, $transMessages);
            }
        };

        $languages    = explode(',', $consultation->wordingBase);
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
                if ($text->text !== '' && $text->category === $category) {
                    $conSpecific[$text->textId] = $text->text;
                }
            }
        }

        return array_merge($origMessages, $baseMessages, $extMessages, $conSpecific);
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
        return array_map(function ($entry) {
            if (is_array($entry)) {
                return $entry['text'];
            } else {
                return $entry;
            }
        }, $this->loadMessagesRaw($category, $language, $withConsultationStrings));
    }
}
