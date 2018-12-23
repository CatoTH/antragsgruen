<?php

namespace app\controllers;

use app\async\models\TransferrableChannelObject;
use app\async\models\Userdata;
use app\components\UrlHelper;
use app\components\yii\MessageSource;
use app\models\db\User;
use yii\i18n\I18N;
use yii\web\Response;

class AsyncController extends Base
{
    /**
     * @return string
     * @throws \Exception
     */
    public function actionUser()
    {
        \yii::$app->response->format = Response::FORMAT_RAW;
        \yii::$app->response->headers->add('Content-Type', 'application/json');
        if (\Yii::$app->request->remoteIP !== '127.0.0.1' && \Yii::$app->request->remoteIP !== '::1') {
            \yii::$app->response->statusCode = 401;
            return json_encode(['error' => 'This IP is not whitelisted']);
        }
        $user = User::getCurrentUser();
        if (!$user) {
            \yii::$app->response->statusCode = 401;
            return json_encode(['error' => 'not logged in']);
        }
        return json_encode(Userdata::createFromDbObject($user, $this->consultation));
    }

    /**
     * @param string $channel
     * @return string
     */
    public function actionObjects($channel)
    {
        \yii::$app->response->format = Response::FORMAT_RAW;
        \yii::$app->response->headers->add('Content-Type', 'application/json');
        if (\Yii::$app->request->remoteIP !== '127.0.0.1' && \Yii::$app->request->remoteIP !== '::1') {
            \yii::$app->response->statusCode = 401;
            return json_encode(['error' => 'This IP is not whitelisted']);
        }
        $class = TransferrableChannelObject::$CHANNEL_CLASSES[$channel];
        return json_encode($class::getCollection($this->consultation));
    }

    /**
     * @return string
     * @throws \yii\base\ExitException
     */
    public function actionClient()
    {
        if (!$this->consultation) {
            return $this->redirect(UrlHelper::homeUrl());
        }
        if (!User::getCurrentUser()) {
            return $this->showErrorpage(401, 'please log in');
        }
        return $this->render('client-vue');
    }

    /**
     * @param string $category
     * @return string[]
     */
    private function getTranslations($category)
    {
        /** @var I18N $i18n */
        $i18n = \Yii::$app->get('i18n');

        $returnedStrings = [];
        /** @var MessageSource $messagesource */
        $messagesource = $i18n->getMessageSource($category);
        $strings       = $messagesource->getBaseMessagesWithHints($category, $this->consultation->wordingBase);

        $consStrings = [];
        foreach ($this->consultation->texts as $text) {
            if ($text->category === $category) {
                $consStrings[$text->textId] = $text->text;
            }
        }

        foreach ($strings as $string) {
            if (isset($consStrings[$string['id']])) {
                $returnedStrings[$string['id']] = $consStrings[$string['id']];
            } else {
                $returnedStrings[$string['id']] = $string['text'];
            }
        }

        return $returnedStrings;
    }

    /**
     * @return string[]
     */
    private function getTags()
    {
        $tags = [];
        foreach ($this->consultation->tags as $tag) {
            $tags[$tag->id] = $tag->title;
        }
        return $tags;
    }

    /**
     * @param $categories
     * @return string
     */
    public function actionTranslations($categories)
    {
        \yii::$app->response->format = Response::FORMAT_RAW;
        \yii::$app->response->headers->add('Content-Type', 'text/javascript');

        $returnedStrings = [];

        foreach (explode(',', $categories) as $category) {
            if (trim($category) === '') {
                continue;
            }
            if ($category === 'tags') {
                $returnedStrings[$category] = $this->getTags();
            } else {
                $returnedStrings[$category] = $this->getTranslations($category);
            }
        }

        $retJs = 'if (window.ANTRAGSGRUEN_TRANSLATIONS === undefined) window.ANTRAGSGRUEN_TRANSLATIONS = {};';
        $retJs .= 'window.ANTRAGSGRUEN_TRANSLATIONS = ' .
            'Object.assign(window.ANTRAGSGRUEN_TRANSLATIONS, ' . json_encode($returnedStrings) . ');';
        return $retJs;
    }
}
