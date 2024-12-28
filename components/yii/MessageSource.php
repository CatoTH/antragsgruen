<?php

namespace app\components\yii;

use app\components\UrlHelper;
use app\models\exceptions\Internal;
use app\models\settings\AntragsgruenApp;

class MessageSource extends \yii\i18n\MessageSource
{
    public string $basePath = '@app/messages';

    public function init(): void
    {
        parent::init();
        if (YII_DEBUG) {
            $this->on(self::EVENT_MISSING_TRANSLATION, function ($event) {
                $params = AntragsgruenApp::getInstance();
                /** @var resource $fp */
                $fp     = fopen($params->getTmpDir() . 'missing-translations.log', 'a');
                fwrite($fp, $event->language . ' - ' . $event->category . ' - ' . $event->message . "\n");
                fclose($fp);
            });
        }
    }

    public static function clearTranslationCache(): void
    {
        $i18n = \Yii::$app->getI18n();
        $i18n->translations['*'] = \Yii::createObject([
            'class' => self::class,
            'basePath' => '@app/messages',
        ]);
    }

    /**
     * @return array<string, string>
     */
    public static function getTranslatableCategories(): array
    {
        if (\Yii::$app->language === 'de') {
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
                'speech'    => 'Redelisten',
                'voting'    => 'Abstimmungen',
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
                'speech'    => 'Speaking list',
                'voting'    => 'Votings',
                'wizard'    => 'Wizard',
            ];
        }

        foreach (AntragsgruenApp::getActivePluginIds() as $pluginId) {
            $categories[$pluginId] = $pluginId;
        }

        return $categories;
    }

    public static function getLanguageVariants(string $language): array
    {
        $localMessages = AntragsgruenApp::getInstance()->localMessages[$language] ?? [];

        foreach (AntragsgruenApp::getInstance()->getPluginClasses() as $pluginId => $pluginClass) {
            if (in_array($language, $pluginClass::getProvidedTranslations())) {
                $localMessages[$language . '-' . $pluginId] = 'Plugin: ' . $pluginId;
            }
        }

        if ($language === 'de') {
            return array_merge([
                'de-parteitag'       => 'Konferenz / Parteitag',
                'de-bewerbung'       => 'Bewerbungsverfahren',
                'de-programm'        => 'Programmdiskussion',
                'de-aevorschlaege'   => 'Änderungsvorschläge',
                'de-bdk'             => 'BDK',
            ], $localMessages);
        }
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
        if ($language === 'nl') {
            return array_merge([
                'nl' => 'Nederlands',
            ], $localMessages);
        }
        if ($language === 'ca') {
            return array_merge([
                'ca' => 'Català',
            ], $localMessages);
        }
        if ($language === 'me') {
            return array_merge([
                'me' => 'Montenegrin',
            ], $localMessages);
        }
        return [];
    }

    public static function getBaseLanguages(): array
    {
        return [
            'de' => 'Deutsch',
            'en' => 'English',
            'fr' => 'Français',
            'nl' => 'Nederlands',
            'ca' => 'Català',
            'me' => 'Montenegrin',
        ];
    }

    public static function getMotionTypeChangableTexts(): array
    {
        return [
            'motion' => [
                'create_explanation',
                'support_collection_hint',
                'support_collection_reached_hint',
                'support_collect_explanation',
                'support_collect_explanation_title',
                'confirmed_support_phase',
                'confirmed_support_phase_addfemale',
                'confirmed_support_phase_ww',
                'submitted_screening_email',
                'submitted_screening_email_subject',
                'submitted_supp_phase_email',
                'submitted_supp_phase_email_subject',
                'submitted_adminnoti_title',
                'submitted_adminnoti_body',
                'support_reached_email_subject',
                'support_reached_email_body',
                'back_to_motion',
            ],
            'amend' => [
                'create_explanation',
                'create_explanation_statutes',
                'create_explanation_amendtoamend',
                'support_collection_hint',
                'support_collection_reached_hint',
                'support_collect_explanation',
                'support_collect_explanation_title',
                'submitted_screening_email',
                'submitted_screening_email_subject',
                'submitted_supp_phase_email',
                'submitted_supp_phase_email_subject',
                'submitted_adminnoti_title',
                'submitted_adminnoti_body',
                'support_reached_email_subject',
                'support_reached_email_body',
            ],
        ];
    }

    /**
     * Returns message file path for the specified language and category.
     */
    protected function getMessageFilePath(string $category, string $language): string
    {
        $messageFile = \Yii::getAlias($this->basePath) . "/$language/";

        foreach (AntragsgruenApp::getInstance()->getPluginClasses() as $pluginId => $pluginClass) {
            foreach ($pluginClass::getProvidedTranslations() as $pluginLang) {
                if ($language === $pluginLang .'-' . $pluginId) {
                    $messageFile = '@app/plugins/' . $pluginId . '/messages/' . $pluginLang . '/';
                    $messageFile = \Yii::getAlias($messageFile);
                }
            }
            if ($category === $pluginId) {
                $messageFile = '@app/plugins/' . $pluginId . '/messages/' . $language . '/';
                $messageFile = \Yii::getAlias($messageFile);
            }
        }

        $messageFile .= str_replace('\\', '/', $category) . '.php';

        return $messageFile;
    }


    /**
     * Loads the message translation for the specified language and category or returns null if file doesn't exist.
     *
     * @param string $messageFile path to message file
     * @return array|null array of messages or null if file not found
     */
    protected function loadMessagesFromFile(string $messageFile): ?array
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
     * @throws Internal
     */
    public function getBaseMessages(string $category, string $language): array
    {
        return $this->loadMessages($category, $language, false);
    }

    public function getBaseMessagesWithHints(string $category, string $language): array
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

    private function loadMessagesRaw(string $category, string $language, bool $withConsultationStrings): array
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
        }

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
    protected function loadMessages($category, $language, bool $withConsultationStrings = true): array
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
