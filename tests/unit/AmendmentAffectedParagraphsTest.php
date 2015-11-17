<?php

namespace unit;

use app\components\HTMLTools;
use app\models\db\AmendmentSection;
use Codeception\Specify;

class AmendmentAffectedParagraphsTest extends DBTestBase
{
    /**
     * @param int $amendmentId
     * @param int $sectionId
     * @return \string[]
     * @throws \app\models\exceptions\Internal
     */
    private function getAffected($amendmentId, $sectionId)
    {
        /** @var AmendmentSection $section */
        $section   = AmendmentSection::findOne(['amendmentId' => $amendmentId, 'sectionId' => $sectionId]);
        $orig      = $section->getOriginalMotionSection();
        $origParas = HTMLTools::sectionSimpleHTML($orig->data);
        return $section->getAffectedParagraphs($origParas);
    }


    /**
     *
     */
    public function testAffectedParagraphs()
    {
        $diff = $this->getAffected(1, 2);
        $this->assertEquals([
            4 => '<ul><li>Woibbadinga noch da Giasinga Heiwog Biazelt mechad mim Spuiratz, soi zwoa.</li></ul><ul><li>Oamoi a Maß und no a Maß des basd scho wann griagd ma nacha wos z’dringa do Meidromml, oba a fescha Bua!</li></ul>',
        ], $diff);

        $diff = $this->getAffected(3, 2);
        $this->assertEquals([
            1 => '<ul><li>Auffi Gamsbart nimma de Sepp Ledahosn Ohrwaschl um Godds wujn Wiesn Deandlgwand Mongdratzal! Jo leck mi Mamalad i daad mechad?</li></ul><ul><li>Neuer Punkt</li></ul>',
            4 => '',
            7 => '<p>Wui helfgod Wiesn, ognudelt schaugn: Dahoam gelbe Rüam Schneid singan wo hi sauba i moan scho aa no a Maß a Maß und no a Maß nimma. Is umananda a ganze Hoiwe zwoa, Schneid. Vui huift vui Brodzeid kumm geh naa i daad vo de allerweil, gor. Woaß wia Gams, damischa. A ganze Hoiwe Ohrwaschl Greichats iabaroi Prosd Engelgwand nix Reiwadatschi. Woibbadinga damischa owe gwihss Sauwedda Weibaleid ognudelt Ledahosn noch da Giasinga Heiwog i daad Almrausch, Ewig und drei Dog nackata wea ko, dea ko. Meidromml Graudwiggal nois dei, nackata. No Diandldrahn nix Gwiass woass ma ned hod boarischer: Samma sammawiedaguad wos, i hoam Brodzeid. Jo mei Sepp Gaudi, is ma Wuascht do Hendl Xaver Prosd eana an a bravs.</p>',
        ], $diff);

        $diff = $this->getAffected(270, 2);
        $this->assertEquals([
            1 => '<ul><li>Auffi Gamsbart nimma de Sepp Ledahosn Ohrwaschl um Godds wujn Wiesn Deandlgwand Mongdratzal! Jo leck mi Mamalad i daad mechad?Abcdsfd#</li></ul><ul><li>Neue Zeile</li></ul>',
        ], $diff);

        $diff = $this->getAffected(272, 2);
        $this->assertEquals([
            7 => '<p>Wui helfgod Wiesn, ognudelt schaugn: Something Dahoam gelbe Rüam Schneid singan wo hi sauba i moan scho aa no a Maß a Maß und no a Maß nimma. Is umananda a ganze Hoiwe zwoa, Schneid. Vui huift vui Brodzeid kumm geh naa i daad vo de allerweil, gor. Woaß wia Gams, damischa. A ganze Hoiwe Ohrwaschl Greichats iabaroi Prosd Engelgwand nix Reiwadatschi.Weibaleid ognudelt Ledahosn noch da Giasinga Heiwog i daad Almrausch, Ewig und drei Dog nackata wea ko, dea ko. Meidromml Graudwiggal nois dei, nackata. No Diandldrahn nix Gwiass woass ma ned hod boarischer: Samma sammawiedaguad wos, i hoam Brodzeid. Jo mei Sepp Gaudi, is ma Wuascht do Hendl Xaver Prosd eana an a bravs. Sauwedda an Brezn, abfieseln.</p>',
        ], $diff);

        $diff = $this->getAffected(273, 2);
        $this->assertEquals([
            7 => '<p>Wui helfgod Wiesn, ognudelt schaugn: Dahoam und irgendwo wo hi sauba i moan scho aa no a Maß a Maß und no a Maß nimma. Is umananda a ganze Hoiwe zwoa, Schneid. Vui huift vui Brodzeid kumm geh naa i daad vo de allerweil, gor. Woaß wia Gams, damischa. A ganze Hoiwe Ohrwaschl Greichats iabaroi Prosd Reiwadatschi.Weibaleid ognudelt Ledahosn noch da abcdefgh Giasinga Heiwog i daad Almrausch, Ewig und drei Dog nackata wea ko, dea ko. Meidromml Graudwiggal nois dei, nackata. No Diandldrahn nix Gwiass woass ma ned hod boarischer: Samma sammawiedaguad wos, i hoam Brodzeid. Jo mei Sepp Gaudi, is ma Wuascht do Hendl Xaver Prosd eana an a bravs. Sauwedda an Brezn, abfieseln.</p>',
        ], $diff);

        $diff = $this->getAffected(1, 4);
        $this->assertEquals([], $diff);

        $diff = $this->getAffected(270, 4);
        $this->assertEquals([], $diff);
    }
}
