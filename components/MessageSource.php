<?php

namespace app\components;

use app\models\db\ConsultationText;
use app\models\exceptions\Internal;
use app\models\PageData;
use app\models\db\Consultation;
use Yii;

class MessageSource extends \yii\i18n\MessageSource
{
    public $basePath = '@app/messages';
    public $fileMap;

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
    protected function loadMessages($category, $language)
    {
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
        foreach ($consultation->texts as $text) {
            if ($text->text != '' && $text->category == $category) {
                $conSpecific[$text->textId] = $text->text;
            }
        }

        return array_merge($baseMessages, $extMessages, $conSpecific);
    }


    /**
     * @param Consultation $consultation
     * @param string $pageKey
     * @param string $html
     * @return bool
     */
    public static function savePageData(Consultation $consultation, $pageKey, $html)
    {
        /** @var ConsultationText $text */
        $text = ConsultationText::findOne(
            ['consultationId' => $consultation->id, 'textId' => $pageKey, 'category' => 'pagedata']
        );
        if (!$text) {
            $text                 = new ConsultationText();
            $text->category       = 'pagedata';
            $text->consultationId = $consultation->id;
            $text->textId         = $pageKey;
        }
        $text->text     = HTMLTools::cleanTrustedHtml($html);
        $text->editDate = date('Y-m-d H:i:s');
        return $text->save();
    }

    /**
     * @param Consultation $consultation
     * @param string $pageKey
     * @return PageData
     * @throws Internal
     */
    public static function getPageData(Consultation $consultation, $pageKey)
    {
        switch ($pageKey) {
            case 'maintainance':
                $data                  = new PageData();
                $data->pageTitle       = 'Wartungsmodus';
                $data->breadcrumbTitle = 'Wartungsmodus';
                $data->text            = '<p>Diese Veranstaltung wurde vom Admin noch nicht freigeschaltet.</p>';
                break;
            case 'help':
                $data                  = new PageData();
                $data->pageTitle       = 'Hilfe';
                $data->breadcrumbTitle = 'Hilfe';
                $data->text            = '<p>Hilfe...</p>';
                break;
            case 'legal':
                $data                  = new PageData();
                $data->pageTitle       = 'Impressum';
                $data->breadcrumbTitle = 'Impressum';
                $data->text            = '<p>Impressum</p>';
                break;
            case 'welcome':
                $data                  = new PageData();
                $data->pageTitle       = 'Willkommen';
                $data->breadcrumbTitle = 'Willkommen';
                $data->text            = '<p>Hallo auf Antragsgrün</p>';
                break;
            default:
                throw new Internal('Unknown page Key: ' . $pageKey);
        }
        foreach ($consultation->texts as $text) {
            if ($text->category == 'pagedata' && $text->textId == $pageKey) {
                $data->text = $text->text;
            }
        }
        return $data;
    }
}
