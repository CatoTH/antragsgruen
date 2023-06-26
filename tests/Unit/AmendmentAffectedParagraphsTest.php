<?php

namespace Tests\Unit;

use app\components\diff\Diff;
use app\components\diff\DiffRenderer;
use app\components\HTMLTools;
use app\models\db\AmendmentSection;
use app\models\db\MotionSection;
use Codeception\Attribute\Group;
use Tests\Support\Helper\DBTestBase;

#[Group('database')]
class AmendmentAffectedParagraphsTest extends DBTestBase
{
    /**
     * @return string[]
     * @throws \app\models\exceptions\Internal
     */
    private function getAffected(int $amendmentId, int $sectionId): array
    {
        /** @var AmendmentSection $section */
        $section   = AmendmentSection::findOne(['amendmentId' => $amendmentId, 'sectionId' => $sectionId]);
        $orig      = $section->getOriginalMotionSection();
        $origParas = HTMLTools::sectionSimpleHTML($orig->getData());
        $newParas  = HTMLTools::sectionSimpleHTML($section->data);

        return Diff::computeAffectedParagraphs($origParas, $newParas, DiffRenderer::FORMATTING_CLASSES);
    }


    public function testAffectedParagraphs(): void
    {
        $diff = $this->getAffected(3, 2);
        $this->assertEquals([
            1 => '<ul><li>Auffi Gamsbart nimma de Sepp Ledahosn Ohrwaschl um Godds wujn Wiesn Deandlgwand Mongdratzal! Jo leck mi Mamalad i daad mechad?</li></ul><ul class="inserted"><li>Neuer Punkt</li></ul>',
            4 => '<ul class="deleted"><li>Woibbadinga noch da Giasinga Heiwog Biazelt mechad mim Spuiratz, soi zwoa.</li></ul>',
            7 => '<p>Wui helfgod Wiesn, ognudelt schaugn: Dahoam gelbe Rüam Schneid singan wo hi sauba i moan scho aa no a Maß a Maß und no a Maß nimma. Is umananda a ganze Hoiwe zwoa, Schneid. Vui huift vui Brodzeid kumm geh naa i daad vo de allerweil, gor. Woaß wia Gams, damischa. A ganze Hoiwe Ohrwaschl Greichats iabaroi Prosd Engelgwand nix Reiwadatschi.<ins> Woibbadinga damischa owe gwihss Sauwedda </ins>Weibaleid ognudelt Ledahosn noch da Giasinga Heiwog i daad Almrausch, Ewig und drei Dog nackata wea ko, dea ko. Meidromml Graudwiggal nois dei, nackata. No Diandldrahn nix Gwiass woass ma ned hod boarischer: Samma sammawiedaguad wos, i hoam Brodzeid. Jo mei Sepp Gaudi, is ma Wuascht do Hendl Xaver Prosd eana an a bravs<del>. Sauwedda an Brezn, abfieseln</del>.</p>',
        ], $diff);

        $diff = $this->getAffected(1, 2);
        $this->assertEquals([
            4 => '<ul><li>Woibbadinga noch da Giasinga Heiwog Biazelt mechad mim Spuiratz, soi zwoa.</li></ul><ul class="inserted"><li>Oamoi a Maß und no a Maß des basd scho wann griagd ma nacha wos z’dringa do Meidromml, oba a fescha Bua!</li></ul>',
        ], $diff);


        $diff = $this->getAffected(270, 2);
        $this->assertEquals([
            1 => '<ul><li>Auffi Gamsbart nimma de Sepp Ledahosn Ohrwaschl um Godds wujn Wiesn Deandlgwand Mongdratzal! Jo leck mi Mamalad i daad mechad?<ins>Abcdsfd#</ins></li></ul><ul class="inserted"><li>Neue Zeile</li></ul>',
            4 => '<ul><li><del>Woibbadinga </del><ins><strong>Woibbadinga</strong> </ins>noch da Giasinga Heiwog Biazelt mechad mim Spuiratz, soi zwoa.</li></ul>',
        ], $diff);

        $diff = $this->getAffected(272, 2);
        $this->assertEquals([
            7 => '<p>Wui helfgod Wiesn, ognudelt schaugn: <ins>Something </ins>Dahoam gelbe Rüam Schneid singan wo hi sauba i moan scho aa no a Maß a Maß und no a Maß nimma. Is umananda a ganze Hoiwe zwoa, Schneid. Vui huift vui Brodzeid kumm geh naa i daad vo de allerweil, gor. Woaß wia Gams, damischa. A ganze Hoiwe Ohrwaschl Greichats iabaroi Prosd Engelgwand nix Reiwadatschi.Weibaleid ognudelt Ledahosn noch da Giasinga Heiwog i daad Almrausch, Ewig und drei Dog nackata wea ko, dea ko. Meidromml Graudwiggal nois dei, nackata. No Diandldrahn nix Gwiass woass ma ned hod boarischer: Samma sammawiedaguad wos, i hoam Brodzeid. Jo mei Sepp Gaudi, is ma Wuascht do Hendl Xaver Prosd eana an a bravs. Sauwedda an Brezn, abfieseln.</p>',
        ], $diff);

        $diff = $this->getAffected(273, 2);
        $this->assertEquals([
            7 => '<p>Wui helfgod Wiesn, ognudelt schaugn: Dahoam <del>gelbe Rüam Schneid singan</del><ins>und irgendwo</ins> wo hi sauba i moan scho aa no a Maß a Maß und no a Maß nimma. Is umananda a ganze Hoiwe zwoa, Schneid. Vui huift vui Brodzeid kumm geh naa i daad vo de allerweil, gor. Woaß wia Gams, damischa. A ganze Hoiwe Ohrwaschl Greichats iabaroi Prosd <del>Engelgwand nix </del>Reiwadatschi.Weibaleid ognudelt Ledahosn noch da <ins>abcdefgh </ins>Giasinga Heiwog i daad Almrausch, Ewig und drei Dog nackata wea ko, dea ko. Meidromml Graudwiggal nois dei, nackata. No Diandldrahn nix Gwiass woass ma ned hod boarischer: Samma sammawiedaguad wos, i hoam Brodzeid. Jo mei Sepp Gaudi, is ma Wuascht do Hendl Xaver Prosd eana an a bravs. Sauwedda an Brezn, abfieseln.</p>',
        ], $diff);

        $diff = $this->getAffected(1, 4);
        $this->assertEquals([], $diff);

        $diff = $this->getAffected(270, 4);
        $this->assertEquals([], $diff);
    }

    /**
     */
    public function testLineNumbers(): void
    {
        /** @var MotionSection $section */
        $section   = MotionSection::findOne(['motionId' => 2, 'sectionId' => 2]);
        $paragraphs = $section->getTextParagraphObjects(true, true, true);
        $this->assertEquals(4, $paragraphs[0]->amendmentSections[0]->firstAffectedLine);
        $this->assertEquals(31, $paragraphs[7]->amendmentSections[0]->firstAffectedLine);
        $this->assertEquals(27, $paragraphs[7]->amendmentSections[1]->firstAffectedLine);
    }
}
