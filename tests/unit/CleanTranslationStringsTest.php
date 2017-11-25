<?php

namespace unit;

use app\components\HTMLTools;
use app\components\MessageSource;
use Codeception\Specify;
use Yii;
use yii\i18n\I18N;

class CleanTranslationStringsTest extends DBTestBase
{
    use Specify;

    /**
     * @param string $str1
     * @return string
     */
    protected function normalizeStr($str1)
    {
        $str1 = preg_replace('/\s*/', '', $str1);
        $str1 = str_replace('&quot;', '"', $str1);
        $str1 = str_replace('&shy;', '­', $str1);
        return $str1;
    }

    /**
     * @throws \yii\base\InvalidConfigException
     */
    public function testUnchangingTranslationStrings()
    {
        $changedStrings = [];
        foreach (array_keys(MessageSource::getTranslatableCategories()) as $catId) {
            /** @var I18N $i18n */
            $i18n = Yii::$app->get('i18n');
            /** @var MessageSource $messagesource */
            $messagesource = $i18n->getMessageSource($catId);
            $strings       = $messagesource->getBaseMessages($catId, 'de');
            foreach ($strings as $strId => $strContent) {
                $cleaned = HTMLTools::cleanHtmlTranslationString($strContent);
                if (static::normalizeStr($cleaned) != static::normalizeStr($strContent)) {
                    $changedStrings[] = $strId . ': ' . $strContent . ' => ' . $cleaned;
                }
            }
        }
        if (count($changedStrings) > 0) {
            $this->fail(implode("\n", $changedStrings));
        }
    }
}
