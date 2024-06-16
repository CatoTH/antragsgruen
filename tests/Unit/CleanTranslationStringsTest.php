<?php

namespace Tests\Unit;

use app\components\HTMLTools;
use app\components\yii\MessageSource;
use Codeception\Attribute\Group;
use Tests\Support\Helper\DBTestBase;
use Yii;
use yii\i18n\I18N;

#[Group('database')]
class CleanTranslationStringsTest extends DBTestBase
{
    protected function normalizeStr(string $str1): string
    {
        $str1 = preg_replace('/\s*/', '', $str1);
        $str1 = str_replace('&quot;', '"', $str1);
        $str1 = str_replace('&shy;', '­', $str1);
        return $str1;
    }

    /**
     * @throws \yii\base\InvalidConfigException|\app\models\exceptions\Internal
     */
    public function testUnchangingTranslationStrings(): void
    {
        $skipIds = [
            'motion_type_templ_progressh' // aria-hidden
        ];

        $changedStrings = [];
        foreach (array_keys(MessageSource::getTranslatableCategories()) as $catId) {
            /** @var I18N $i18n */
            $i18n = Yii::$app->get('i18n');
            /** @var MessageSource $messageSource */
            $messageSource = $i18n->getMessageSource($catId);
            $strings       = $messageSource->getBaseMessages($catId, 'de');
            foreach ($strings as $strId => $strContent) {
                if (in_array($strId, $skipIds)) {
                    continue;
                }

                $cleaned = HTMLTools::cleanHtmlTranslationString($strContent);
                if ($this->normalizeStr($cleaned)!==$this->normalizeStr($strContent)) {
                    $changedStrings[] = $strId . ': ' . $strContent . ' => ' . $cleaned;
                }
            }
        }
        if (count($changedStrings) > 0) {
            $this->fail(implode("\n", $changedStrings));
        }
    }
}
