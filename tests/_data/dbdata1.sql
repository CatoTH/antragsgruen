SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 0;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 0;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'TRADITIONAL,ALLOW_INVALID_DATES';


--
-- Dumping data for table `amendment`
--

INSERT INTO `amendment` (`id`, `motionId`, `titlePrefix`, `changeMetatext`, `changeText`, `changeExplanation`, `changeExplanationHtml`, `cache`, `dateCreation`, `dateResolution`, `status`, `statusString`, `noteInternal`, `textFixed`)
VALUES
  (1, 2, 'Ä1', '', '',
   '<p>Auf gehds beim Schichtl pfiad de Charivari Wurschtsolod Gamsbart, Kneedl gwiss. Wos dringma aweng unbandig gfreit mi imma Habedehre, sei Sauwedda dringma aweng Maßkruag Schuabladdla! Do legst di nieda hob i an Suri wia Haferl Graudwiggal Klampfn Biakriagal i bin a woschechta Bayer ebba.</p>\n',
   0, '', '2015-05-23 01:46:34', NULL, 3, '', NULL, 0),
  (2, 3, 'Ä1', '', '',
   '<p>Um das ganze mal zu testen.</p>\n<p>Links<a href="https://www.antragsgruen.de">https://www.antragsgruen.de</a></p>\n',
   0, '', '2015-07-06 04:14:41', NULL, 3, '', NULL, 0),
  (3, 2, 'Ä2', '', '', '<p>Diff-Test</p>\n', 0, '', '2015-07-07 07:49:25', NULL, 3, '', NULL, 0);

--
-- Dumping data for table `amendmentSection`
--

INSERT INTO `amendmentSection` (`amendmentId`, `sectionId`, `data`, `dataRaw`, `metadata`) VALUES
  (1, 1, 'O’zapft is!', 'O’zapft is!', NULL),
  (1, 2,
   '<p>Bavaria ipsum dolor sit amet Biazelt Auffisteign Schorsch mim Radl foahn Ohrwaschl Steckerleis wann griagd ma nacha wos z’dringa glacht Mamalad, muass? I bin a woschechta Bayer sowos oamoi und sei und glei wirds no fui lustiga: Jo mei is des schee middn ognudelt, Trachtnhuat Biawambn gscheid: Griasd eich midnand etza nix Gwiass woass ma ned owe. Dahoam gscheckate middn Spuiratz des is a gmahde Wiesn. Des is schee so Obazda san da, Haferl pfenningguat schoo griasd eich midnand.</p>\n<ul>\n<li>Auffi Gamsbart nimma de Sepp Ledahosn Ohrwaschl um Godds wujn Wiesn Deandlgwand Mongdratzal! Jo leck mi Mamalad i daad mechad?</li>\n	<li>Do nackata Wurscht i hob di narrisch gean, Diandldrahn Deandlgwand vui huift vui woaß?</li>\n	<li>Ned Mamalad auffi i bin a woschechta Bayer greaßt eich nachad, umananda gwiss nia need Weiznglasl.</li>\n	<li>Woibbadinga noch da Giasinga Heiwog Biazelt mechad mim Spuiratz, soi zwoa.</li>\n	<li>Oamoi a Maß und no a Maß des basd scho wann griagd ma nacha wos z’dringa do Meidromml, oba a fescha Bua!</li>\n</ul>\n<p>I waar soweid Blosmusi es nomoi. Broadwurschtbudn des is a gmahde Wiesn Kirwa mogsd a Bussal Guglhupf schüds nei. Luja i moan oiwei Baamwach Watschnbaam, wiavui baddscher! Biakriagal a fescha Bua Semmlkneedl iabaroi oba um Godds wujn Ledahosn wui Greichats. Geh um Godds wujn luja heid greaßt eich nachad woaß Breihaus eam! De om auf’n Gipfe auf gehds beim Schichtl mehra Baamwach a bissal wos gehd ollaweil gscheid:</p>\n<blockquote>\n<p>Scheans Schdarmbeaga See i hob di narrisch gean i jo mei is des schee! Nia eam hod vasteh i sog ja nix, i red ja bloß sammawiedaguad, umma eana obandeln! Zwoa jo mei scheans amoi, san und hoggd Milli barfuaßat gscheit. Foidweg vui huift vui singan, mehra Biakriagal om auf’n Gipfe! Ozapfa sodala Charivari greaßt eich nachad Broadwurschtbudn do middn liberalitas Bavariae sowos Leonhardifahrt:</p>\n</blockquote>\n<p>Wui helfgod Wiesn, ognudelt schaugn: Dahoam gelbe Rüam Schneid singan wo hi sauba i moan scho aa no a Maß a Maß und no a Maß nimma. Is umananda a ganze Hoiwe zwoa, Schneid. Vui huift vui Brodzeid kumm geh naa i daad vo de allerweil, gor. Woaß wia Gams, damischa. A ganze Hoiwe Ohrwaschl Greichats iabaroi Prosd Engelgwand nix Reiwadatschi.Weibaleid ognudelt Ledahosn noch da Giasinga Heiwog i daad Almrausch, Ewig und drei Dog nackata wea ko, dea ko. Meidromml Graudwiggal nois dei, nackata. No Diandldrahn nix Gwiass woass ma ned hod boarischer: Samma sammawiedaguad wos, i hoam Brodzeid. Jo mei Sepp Gaudi, is ma Wuascht do Hendl Xaver Prosd eana an a bravs. Sauwedda an Brezn, abfieseln.</p>\n',
   '<p>Bavaria ipsum dolor sit amet Biazelt Auffisteign Schorsch mim Radl foahn Ohrwaschl Steckerleis wann griagd ma nacha wos z&rsquo;dringa glacht Mamalad, muass? I bin a woschechta Bayer sowos oamoi und sei und glei wirds no fui lustiga: Jo mei is des schee middn ognudelt, Trachtnhuat Biawambn gscheid: Griasd eich midnand etza nix Gwiass woass ma ned owe. Dahoam gscheckate middn Spuiratz des is a gmahde Wiesn. Des is schee so Obazda san da, Haferl pfenningguat schoo griasd eich midnand.</p>\r\n\r\n<ul>\r\n	<li>Auffi Gamsbart nimma de Sepp Ledahosn Ohrwaschl um Godds wujn Wiesn Deandlgwand Mongdratzal! Jo leck mi Mamalad i daad mechad?</li>\r\n	<li>Do nackata Wurscht i hob di narrisch gean, Diandldrahn Deandlgwand vui huift vui woa&szlig;?</li>\r\n	<li>Ned Mamalad auffi i bin a woschechta Bayer grea&szlig;t eich nachad, umananda gwiss nia need Weiznglasl.</li>\r\n	<li>Woibbadinga noch da Giasinga Heiwog Biazelt mechad mim Spuiratz, soi zwoa.</li>\r\n	<li><span class="ice-cts ice-ins" data-changedata="" data-cid="2" data-last-change-time="1432410364839" data-time="1432410364839" data-userid="" data-username="">Oamoi a Ma&szlig; und no a Ma&szlig; des basd scho wann griagd ma nacha wos z&rsquo;dringa do Meidromml, oba a fescha Bua!</span></li>\r\n</ul>\r\n\r\n<p>I waar soweid Blosmusi es nomoi. Broadwurschtbudn des is a gmahde Wiesn Kirwa mogsd a Bussal Guglhupf sch&uuml;ds nei. Luja i moan oiwei Baamwach Watschnbaam, wiavui baddscher! Biakriagal a fescha Bua Semmlkneedl iabaroi oba um Godds wujn Ledahosn wui Greichats. Geh um Godds wujn luja heid grea&szlig;t eich nachad woa&szlig; Breihaus eam! De om auf&rsquo;n Gipfe auf gehds beim Schichtl mehra Baamwach a bissal wos gehd ollaweil gscheid:</p>\r\n\r\n<blockquote>\r\n<p>Scheans Schdarmbeaga See i hob di narrisch gean i jo mei is des schee! Nia eam hod vasteh i sog ja nix, i red ja blo&szlig; sammawiedaguad, umma eana obandeln! Zwoa jo mei scheans amoi, san und hoggd Milli barfua&szlig;at gscheit. Foidweg vui huift vui singan, mehra Biakriagal om auf&rsquo;n Gipfe! Ozapfa sodala Charivari grea&szlig;t eich nachad Broadwurschtbudn do middn liberalitas Bavariae sowos Leonhardifahrt:</p>\r\n</blockquote>\r\n\r\n<p>Wui helfgod Wiesn, ognudelt schaugn: Dahoam gelbe R&uuml;am Schneid singan wo hi sauba i moan scho aa no a Ma&szlig; a Ma&szlig; und no a Ma&szlig; nimma. Is umananda a ganze Hoiwe zwoa, Schneid. Vui huift vui Brodzeid kumm geh naa i daad vo de allerweil, gor. Woa&szlig; wia Gams, damischa. A ganze Hoiwe Ohrwaschl Greichats iabaroi Prosd Engelgwand nix Reiwadatschi.Weibaleid ognudelt Ledahosn noch da Giasinga Heiwog i daad Almrausch, Ewig und drei Dog nackata wea ko, dea ko. Meidromml Graudwiggal nois dei, nackata. No Diandldrahn nix Gwiass woass ma ned hod boarischer: Samma sammawiedaguad wos, i hoam Brodzeid. Jo mei Sepp Gaudi, is ma Wuascht do Hendl Xaver Prosd eana an a bravs. Sauwedda an Brezn, abfieseln.</p>\r\n',
   NULL),
  (1, 4,
   '<p>Woibbadinga damischa owe gwihss Sauwedda ded Charivari dei heid gfoids ma sagrisch guad. Maßkruag wo hi mim Radl foahn Landla Leonhardifahrt, Radler. Ohrwaschl und glei wirds no fui lustiga Spotzerl Fünferl, so auf gehds beim Schichtl do legst di nieda ned Biawambn Breihaus. I mechad dee Schwoanshaxn ghupft wia gsprunga measi gschmeidig hawadere midananda vui huift vui Biawambn, des wiad a Mordsgaudi is. Biaschlegl soi oans, zwoa, gsuffa Oachkatzlschwoaf hod Wiesn.</p>\n<p>Oamoi großherzig Mamalad, liberalitas Bavariae hoggd! Nimmds helfgod im Beidl des basd scho i hob di liab. A Prosit der Gmiadlichkeit midanand mim obandln do mim Radl foahn, Jodler. Ned woar Brotzeit Brotzeit gwihss eana Gidarn. Foidweg Spuiratz kimmt, um Godds wujn. Am acht’n Tag schuf Gott des Bia i sog ja nix, i red ja bloß jedza, Biakriagal a bissal wos gehd ollaweil. Ledahosn om auf’n Gipfe Servas des wiad a Mordsgaudi, griasd eich midnand Bladl Fünferl Gams.</p>\n<p>Leonhardifahrt ma da middn. Greichats an naa do. Af Schuabladdla Leonhardifahrt Marei, des um Godds wujn Biakriagal! Hallelujah sog i, luja schüds nei koa des is schee jedza hogg di hera dringma aweng Spezi nia Musi. Wurschtsolod jo mei is des schee gor Ramasuri ozapfa no gfreit mi i hob di liab auffi, Schbozal. Hogg di hera nia need Biakriagal so schee, Schdarmbeaga See.</p>\n',
   '<p>Woibbadinga damischa owe gwihss Sauwedda ded Charivari dei heid gfoids ma sagrisch guad. Ma&szlig;kruag wo hi mim Radl foahn Landla Leonhardifahrt, Radler. Ohrwaschl und glei wirds no fui lustiga Spotzerl F&uuml;nferl, so auf gehds beim Schichtl do legst di nieda ned Biawambn Breihaus. I mechad dee Schwoanshaxn ghupft wia gsprunga measi gschmeidig hawadere midananda vui huift vui Biawambn, des wiad a Mordsgaudi is. Biaschlegl soi oans, zwoa, gsuffa Oachkatzlschwoaf hod Wiesn.</p>\r\n\r\n<p>Oamoi gro&szlig;herzig Mamalad, liberalitas Bavariae hoggd! Nimmds helfgod im Beidl des basd scho i hob di liab. A Prosit der Gmiadlichkeit midanand mim obandln do mim Radl foahn, Jodler. Ned woar Brotzeit Brotzeit gwihss eana Gidarn. Foidweg Spuiratz kimmt, um Godds wujn. Am acht&rsquo;n Tag schuf Gott des Bia i sog ja nix, i red ja blo&szlig; jedza, Biakriagal a bissal wos gehd ollaweil. Ledahosn om auf&rsquo;n Gipfe Servas des wiad a Mordsgaudi, griasd eich midnand Bladl F&uuml;nferl Gams.</p>\r\n\r\n<p>Leonhardifahrt ma da middn. Greichats an naa do. Af Schuabladdla Leonhardifahrt Marei, des um Godds wujn Biakriagal! Hallelujah sog i, luja sch&uuml;ds nei koa des is schee jedza hogg di hera dringma aweng Spezi nia Musi. Wurschtsolod jo mei is des schee gor Ramasuri ozapfa no gfreit mi i hob di liab auffi, Schbozal. Hogg di hera nia need Biakriagal so schee, Schdarmbeaga See.</p>\r\n',
   NULL),
  (2, 1, 'Textformatierungen', 'Textformatierungen', NULL),
  (2, 2,
   '<p>Normaler Text, nicht mehr fett und kursiv<br>\nZeilenumbruch <span class="underline">unterstrichen<br>\nUnd noch eine neue Zeile gq Q.</span></p>\n<p><span class="strike">Durchgestrichen und <em>kursiv</em></span></p>\n<ol><li>Listenpunkt</li>\n	<li>Listenpunkt (<em>kursiv</em>)<br>\n	Zeilenumbruch</li>\n	<li>Nummer 3</li>\n	<li>Seltsame Zeichen: &amp; % $ # _ { } ~ ^ \\ \\today</li>\n</ol><p>Normaler Text wieder.</p>\n<p>Absatz 2</p>\n<ul>\n<li>Einfache Punkte</li>\n	<li>Nummer 2</li>\n</ul>\n<p>Link Bla</p>\n<blockquote>\n<p>Zitat 223<br>\nZeilenumbruch</p>\n<p>Neuer Paragraph</p>\n</blockquote>\n<p>Ende</p>\n',
   '<p>Normaler Text<ins class="ice-ins ice-cts" data-changedata="" data-cid="2" data-last-change-time="1436184812653" data-time="1436184809398" data-userid="" data-username="">, nicht mehr</ins> fett und kursiv<br />\r\nZeilenumbruch <span class="underline">unterstrich<ins class="ice-ins ice-cts" data-changedata="" data-cid="17" data-last-change-time="1436184821900" data-time="1436184821900" data-userid="" data-username=""></ins>en<br />\r\n<ins class="ice-ins ice-cts" data-changedata="" data-cid="19" data-last-change-time="1436184827341" data-time="1436184823592" data-userid="" data-username="">Und noch eine neue Zeile.</ins></span></p>\r\n\r\n<p><span class="strike">Durchgestrichen und <em>kursiv</em></span></p>\r\n\r\n<ol>\r\n	<li>Listenpunkt</li>\r\n	<li>Listenpunkt (<em>kursiv</em>)<br />\r\n	Zeilenumbruch</li>\r\n	<li>Nummer 3</li>\r\n	<li>Seltsame Zeichen: &amp; % $ # _ { } ~ ^ \\ \\today</li>\r\n</ol>\r\n\r\n<p>Normaler Text wieder.</p>\r\n\r\n<p>Absatz 2</p>\r\n\r\n<ul>\r\n	<li>Einfache Punkte</li>\r\n	<li>Nummer 2</li>\r\n</ul>\r\n\r\n<p>Link Bla</p>\r\n\r\n<blockquote>\r\n<p>Zitat 223<br />\r\nZeilenumbruch</p>\r\n\r\n<p>Neuer Paragraph</p>\r\n</blockquote>\r\n\r\n<p>Ende</p>\r\n',
   NULL),
  (2, 4, '<p>Textformatierungs-Test</p>\n', '<p>Textformatierungs-Test</p>\r\n', NULL),
  (3, 1, 'O’zapft is!', 'O’zapft is!', NULL),
  (3, 2,
   '<p>Bavaria ipsum dolor sit amet Biazelt Auffisteign Schorsch mim Radl foahn Ohrwaschl Steckerleis wann griagd ma nacha wos z’dringa glacht Mamalad, muass? I bin a woschechta Bayer sowos oamoi und sei und glei wirds no fui lustiga: Jo mei is des schee middn ognudelt, Trachtnhuat Biawambn gscheid: Griasd eich midnand etza nix Gwiass woass ma ned owe. Dahoam gscheckate middn Spuiratz des is a gmahde Wiesn. Des is schee so Obazda san da, Haferl pfenningguat schoo griasd eich midnand.</p>\n<ul>\n<li>Auffi Gamsbart nimma de Sepp Ledahosn Ohrwaschl um Godds wujn Wiesn Deandlgwand Mongdratzal! Jo leck mi Mamalad i daad mechad?</li>\n	<li>Neuer Punkt</li>\n	<li>Do nackata Wurscht i hob di narrisch gean, Diandldrahn Deandlgwand vui huift vui woaß?</li>\n	<li>Ned Mamalad auffi i bin a woschechta Bayer greaßt eich nachad, umananda gwiss nia need Weiznglasl.</li>\n	\n</ul>\n<p>I waar soweid Blosmusi es nomoi. Broadwurschtbudn des is a gmahde Wiesn Kirwa mogsd a Bussal Guglhupf schüds nei. Luja i moan oiwei Baamwach Watschnbaam, wiavui baddscher! Biakriagal a fescha Bua Semmlkneedl iabaroi oba um Godds wujn Ledahosn wui Greichats. Geh um Godds wujn luja heid greaßt eich nachad woaß Breihaus eam! De om auf’n Gipfe auf gehds beim Schichtl mehra Baamwach a bissal wos gehd ollaweil gscheid:</p>\n<blockquote>\n<p>Scheans Schdarmbeaga See i hob di narrisch gean i jo mei is des schee! Nia eam hod vasteh i sog ja nix, i red ja bloß sammawiedaguad, umma eana obandeln! Zwoa jo mei scheans amoi, san und hoggd Milli barfuaßat gscheit. Foidweg vui huift vui singan, mehra Biakriagal om auf’n Gipfe! Ozapfa sodala Charivari greaßt eich nachad Broadwurschtbudn do middn liberalitas Bavariae sowos Leonhardifahrt:</p>\n</blockquote>\n<p>Wui helfgod Wiesn, ognudelt schaugn: Dahoam gelbe Rüam Schneid singan wo hi sauba i moan scho aa no a Maß a Maß und no a Maß nimma. Is umananda a ganze Hoiwe zwoa, Schneid. Vui huift vui Brodzeid kumm geh naa i daad vo de allerweil, gor. Woaß wia Gams, damischa. A ganze Hoiwe Ohrwaschl Greichats iabaroi Prosd Engelgwand nix Reiwadatschi. Woibbadinga damischa owe gwihss Sauwedda Weibaleid ognudelt Ledahosn noch da Giasinga Heiwog i daad Almrausch, Ewig und drei Dog nackata wea ko, dea ko. Meidromml Graudwiggal nois dei, nackata. No Diandldrahn nix Gwiass woass ma ned hod boarischer: Samma sammawiedaguad wos, i hoam Brodzeid. Jo mei Sepp Gaudi, is ma Wuascht do Hendl Xaver Prosd eana an a bravs.</p>\n',
   '<p>Bavaria ipsum dolor sit amet Biazelt Auffisteign Schorsch mim Radl foahn Ohrwaschl Steckerleis wann griagd ma nacha wos z&rsquo;dringa glacht Mamalad, muass? I bin a woschechta Bayer sowos oamoi und sei und glei wirds no fui lustiga: Jo mei is des schee middn ognudelt, Trachtnhuat Biawambn gscheid: Griasd eich midnand etza nix Gwiass woass ma ned owe. Dahoam gscheckate middn Spuiratz des is a gmahde Wiesn. Des is schee so Obazda san da, Haferl pfenningguat schoo griasd eich midnand.</p>\r\n\r\n<ul>\r\n	<li>Auffi Gamsbart nimma de Sepp Ledahosn Ohrwaschl um Godds wujn Wiesn Deandlgwand Mongdratzal! Jo leck<ins class="ice-ins ice-cts" data-changedata="" data-cid="2" data-last-change-time="1436269713822" data-time="1436269713822" data-userid="" data-username="">﻿</ins> mi Mamalad i daad mechad?</li>\r\n	<li><ins class="ice-ins ice-cts" data-changedata="" data-cid="3" data-last-change-time="1436269716823" data-time="1436269715340" data-userid="" data-username="">﻿Neuer Punkt﻿</ins></li>\r\n	<li>Do nackata Wurscht i hob di narrisch gean, Diandldrahn Deandlgwand vui huift vui woa&szlig;?</li>\r\n	<li>Ned Mamalad auffi i bin a woschechta Bayer grea&szlig;t eich nachad, umananda gwiss nia need Weiznglasl.</li>\r\n	<li><ins class="ice-ins ice-cts" data-changedata="" data-cid="17" data-last-change-time="1436269718348" data-time="1436269718250" data-userid="" data-username="">﻿</ins><del class="ice-del ice-cts" data-changedata="" data-cid="16" data-last-change-time="1436269718102" data-time="1436269718102" data-userid="" data-username="">Woibbadinga noch da Giasinga Heiwog Biazelt mechad mim Spuiratz, soi zwoa.</del></li>\r\n</ul>\r\n\r\n<p>I waar soweid Blosmusi es nomoi. Broadwurschtbudn des is a gmahde Wiesn Kirwa mogsd a Bussal Guglhupf sch&uuml;ds nei. Luja i moan oiwei Baamwach Watschnbaam, wiavui baddscher! Biakriagal a fescha Bua Semmlkneedl iabaroi oba um Godds wujn Ledahosn wui Greichats. Geh um Godds wujn luja heid grea&szlig;t eich nachad woa&szlig; Breihaus eam! De om auf&rsquo;n Gipfe auf gehds beim Schichtl mehra Baamwach a bissal wos gehd ollaweil gscheid:</p>\r\n\r\n<blockquote>\r\n<p>Scheans Schdarmbeaga See i hob di narrisch gean i jo mei is des schee! Nia eam hod vasteh i sog ja nix, i red ja blo&szlig; sammawiedaguad, umma eana obandeln! Zwoa jo mei scheans amoi, san und hoggd Milli barfua&szlig;at gscheit. Foidweg vui huift vui singan, mehra Biakriagal om auf&rsquo;n Gipfe! Ozapfa sodala Charivari grea&szlig;t eich nachad Broadwurschtbudn do middn liberalitas Bavariae sowos Leonhardifahrt:</p>\r\n</blockquote>\r\n\r\n<p>Wui helfgod Wiesn, ognudelt schaugn: Dahoam gelbe R&uuml;am Schneid singan wo hi sauba i moan scho aa no a Ma&szlig; a Ma&szlig; und no a Ma&szlig; nimma. Is umananda a ganze Hoiwe zwoa, Schneid. Vui huift vui Brodzeid kumm geh naa i daad vo de allerweil, gor. Woa&szlig; wia Gams, damischa. <ins class="ice-ins ice-cts" data-changedata="" data-cid="20" data-last-change-time="1436269727931" data-time="1436269727931" data-userid="" data-username="">﻿</ins>A ganze Hoiwe Ohrwaschl Greichats iabaroi Prosd Engelgwand nix Reiwadatschi.<ins class="ice-ins ice-cts" data-changedata="" data-cid="21" data-last-change-time="1436269735589" data-time="1436269734806" data-userid="" data-username=""> Woibbadinga damischa owe gwihss Sauwedda ﻿</ins>Weibaleid ognudelt Ledahosn noch da Giasinga Heiwog i daad Almrausch, Ewig und drei Dog nackata wea ko, dea ko. Meidromml Graudwiggal nois dei, nackata. No Diandldrahn nix Gwiass woass ma ned hod boarischer: Samma sammawiedaguad wos, i hoam Brodzeid. Jo mei Sepp Gaudi, is ma Wuascht do Hendl Xaver Prosd eana an a bravs.<del class="ice-del ice-cts" data-changedata="" data-cid="24" data-last-change-time="1436269740482" data-time="1436269740217" data-userid="" data-username=""> </del><del class="ice-del ice-cts" data-changedata="" data-cid="26" data-last-change-time="1436269741200" data-time="1436269741200" data-userid="" data-username="">Sauwedda an Brezn, abfieseln.</del></p>\r\n',
   NULL),
  (3, 4,
   '<p>New line at beginning</p>\n<p>Woibbadinga damischa owe gwihss Sauwedda ded Charivari dei heid gfoids ma sagrisch guad. Maßkruag wo hi mim Radl foahn Landla Leonhardifahrt, Radler. Ohrwaschl und glei wirds no fui lustiga Spotzerl Fünferl, so auf gehds beim Schichtl do legst di nieda ned Biawambn Breihaus. I mechad dee Schwoanshaxn ghupft wia gsprunga measi gschmeidig hawadere midananda vui huift vui Biawambn, des wiad a Mordsgaudi is. Biaschlegl soi oans, zwoa, gsuffa Oachkatzlschwoaf hod Wiesn.</p>\n<p>Neuer Absatz</p>\n<p>Oamoi großherzig Mamalad, liberalitas Bavariae hoggd! Nimmds helfgod im Beidl des basd scho i hob di liab. A Prosit der Gmiadlichkeit midanand mim obandln do mim Radl foahn, Jodler. Ned woar Brotzeit Brotzeit gwihss eana Gidarn. Foidweg Spuiratz kimmt, um Godds wujn. Am acht’n Tag schuf Gott des Bia i sog ja nix, i red ja bloß jedza, Biakriagal a bissal wos gehd ollaweil. Ledahosn om auf’n Gipfe Servas des wiad a Mordsgaudi, griasd eich midnand Bladl Fünferl Gams.</p>\n<p> </p>\n',
   '<p>New line at beginning</p>\n<p>Woibbadinga damischa owe gwihss Sauwedda<ins class="ice-ins ice-cts" data-changedata="" data-cid="2" data-last-change-time="1436269732235" data-time="1436269731932" data-userid="" data-username="">﻿</ins> ded Charivari dei heid gfoids ma sagrisch guad. Ma&szlig;kruag wo hi mim Radl foahn Landla Leonhardifahrt, Radler. Ohrwaschl und glei wirds no fui lustiga Spotzerl F&uuml;nferl, so auf gehds beim Schichtl do legst di nieda ned Biawambn Breihaus. I mechad dee Schwoanshaxn ghupft wia gsprunga measi gschmeidig hawadere midananda vui huift vui Biawambn, des wiad a Mordsgaudi is. Biaschlegl soi oans, zwoa, gsuffa Oachkatzlschwoaf hod Wiesn.</p>\r\n\r\n<p><ins class="ice-ins ice-cts" data-changedata="" data-cid="4" data-last-change-time="1436269753356" data-time="1436269751954" data-userid="" data-username="">﻿Neuer Absatz﻿</ins></p>\r\n\r\n<p>Oamoi gro&szlig;herzig Mamalad, liberalitas Bavariae hoggd! Nimmds helfgod im Beidl des basd scho i hob di liab. A Prosit der Gmiadlichkeit midanand mim obandln do mim Radl foahn, Jodler. Ned woar Brotzeit Brotzeit gwihss eana Gidarn. Foidweg Spuiratz kimmt, um Godds wujn. Am acht&rsquo;n Tag schuf Gott des Bia i sog ja nix, i red ja blo&szlig; jedza, Biakriagal a bissal wos gehd ollaweil. Ledahosn om auf&rsquo;n Gipfe Servas des wiad a Mordsgaudi, griasd eich midnand Bladl F&uuml;nferl Gams.</p>\r\n\r\n<p><del class="ice-del ice-cts" data-changedata="" data-cid="18" data-last-change-time="1436269755978" data-time="1436269755978" data-userid="" data-username="">Leonhardifahrt ma da middn. Greichats an naa do. Af Schuabladdla Leonhardifahrt Marei, des um Godds wujn Biakriagal! Hallelujah sog i, luja sch&uuml;ds nei koa des is schee jedza hogg di hera dringma aweng Spezi nia Musi. Wurschtsolod jo mei is des schee gor Ramasuri ozapfa no gfreit mi i hob di liab auffi, Schbozal. Hogg di hera nia need Biakriagal so schee, Schdarmbeaga See.</del></p>\r\n',
   NULL);

--
-- Dumping data for table `amendmentSupporter`
--

INSERT INTO `amendmentSupporter` (`id`, `amendmentId`, `position`, `userId`, `role`, `comment`, `personType`, `name`, `organization`, `resolutionDate`, `contactEmail`, `contactPhone`)
VALUES
  (1, 1, 0, NULL, 'initiates', NULL, 0, 'Tester', '', NULL, 'tester@example.org', NULL),
  (2, 2, 0, 1, 'initiates', NULL, 1, 'Testadmin', 'Antragsgrün', '2015-07-17', 'testadmin@example.org', ''),
  (3, 3, 0, 1, 'initiates', NULL, 0, 'Testadmin', '', NULL, 'testadmin@example.org', '');

--
-- Dumping data for table `consultation`
--

INSERT INTO `consultation` (`id`, `siteId`, `urlPath`, `type`, `wordingBase`, `title`, `titleShort`, `eventDateFrom`, `eventDateTo`, `amendmentNumbering`, `adminEmail`, `settings`)
VALUES
  (1, 1, 'std-parteitag', 0, 'de-parteitag', 'Test2', 'Test2', NULL, NULL, 0, 'tobias@hoessl.eu', NULL),
  (2, 2, 'vorstandswahlen', 1, 'de-bewerbung', 'Vorstandswahlen', 'Vorstandswahlen', NULL, NULL, 0,
   'testadmin@example.org',
   '{"maintainanceMode":false,"motionNeedsEmail":false,"motionNeedsPhone":false,"motionHasPhone":false,"commentNeedsEmail":false,"iniatorsMayEdit":false,"adminsMayEdit":true,"confirmEmails":false,"lineNumberingGlobal":false,"hideRevision":false,"minimalisticUI":false,"showFeeds":true,"commentsSupportable":false,"screeningMotions":false,"screeningMotionsShown":false,"screeningAmendments":false,"screeningComments":false,"initiatorsMayReject":false,"hasPDF":true,"commentWholeMotions":false,"allowMultipleTags":false,"allowStrikeFormat":false,"lineLength":80,"startLayoutType":0,"logoUrl":null,"logoUrlFB":null,"motionIntro":null}'),
  (3, 3, 'parteitag', 2, 'de-parteitag', 'Parteitag', 'Parteitag', NULL, NULL, 0, 'testadmin@example.org',
   '{"maintainanceMode":false,"screeningMotions":true,"lineNumberingGlobal":false,"motionNeedsEmail":false,"motionNeedsPhone":false,"motionHasPhone":false,"commentNeedsEmail":false,"iniatorsMayEdit":false,"adminsMayEdit":true,"confirmEmails":false,"hideRevision":false,"minimalisticUI":false,"showFeeds":true,"commentsSupportable":false,"screeningMotionsShown":false,"screeningAmendments":true,"screeningComments":false,"initiatorsMayReject":false,"hasPDF":true,"commentWholeMotions":false,"allowMultipleTags":false,"allowStrikeFormat":false,"lineLength":80,"startLayoutType":3,"logoUrl":null,"logoUrlFB":null,"motionIntro":null}'),
  (4, 4, 'bdk', 3, 'de-parteitag', 'BDK', 'BDK', NULL, NULL, 0, 'testadmin@example.org',
   '{"maintainanceMode":false,"screeningMotions":true,"lineNumberingGlobal":false,"commentNeedsEmail":false,"iniatorsMayEdit":false,"adminsMayEdit":true,"confirmEmails":false,"hideRevision":false,"minimalisticUI":false,"showFeeds":true,"commentsSupportable":false,"screeningMotionsShown":false,"screeningAmendments":true,"screeningComments":false,"initiatorsMayReject":false,"commentWholeMotions":false,"allowMultipleTags":false,"allowStrikeFormat":false,"lineLength":95,"startLayoutType":0,"logoUrl":"","logoUrlFB":"","motionIntro":null,"pdfIntroduction":""}');

--
-- Dumping data for table `consultationAgendaItem`
--

INSERT INTO `consultationAgendaItem` (`id`, `consultationId`, `parentItemId`, `position`, `code`, `codeExplicit`, `title`, `description`, `motionTypeId`, `deadline`)
VALUES
  (1, 3, NULL, 0, '', '0.', 'Tagesordnung', '', NULL, NULL),
  (2, 3, NULL, 1, '', '', 'Wahlen', '', NULL, NULL),
  (3, 3, 2, 0, '', '', '1. Vorsitzende(r)', '', 6, NULL),
  (4, 3, 2, 1, '', '', '2. Vorsitzende(r)', '', 6, NULL),
  (5, 3, 2, 2, '', '', 'Schatzmeister(in)', '', 6, NULL),
  (6, 3, NULL, 2, '', '0.', 'Anträge', '', 5, NULL),
  (7, 3, NULL, 3, '', '0.', 'Sonstiges', '', NULL, NULL);

--
-- Dumping data for table `consultationMotionType`
--

INSERT INTO `consultationMotionType` (`id`, `consultationId`, `titleSingular`, `titlePlural`, `createTitle`, `motionPrefix`, `position`, `cssIcon`, `pdfLayout`, `texTemplateId`, `deadlineMotions`, `deadlineAmendments`, `policyMotions`, `policyAmendments`, `policyComments`, `policySupport`, `contactEmail`, `contactPhone`, `initiatorForm`, `initiatorFormSettings`)
VALUES
  (1, 1, 'Antrag', 'Anträge', 'Antrag stellen', 'A', 0, NULL, 0, 1, NULL, NULL, 1, 1, 1, 0, 2, 1, 0, NULL),
  (3, 2, 'Antrag', 'Anträge', 'Antrag stellen', 'A', 2, NULL, 0, 1, NULL, NULL, 1, 1, 1, 0, 2, 1, 0, NULL),
  (4, 2, 'Bewerbung', 'Bewerbungen', 'Bewerben', 'B', 0, NULL, 0, 1, NULL, NULL, 1, 1, 1, 0, 2, 1, 0, NULL),
  (5, 3, 'Antrag', 'Anträge', 'Antrag stellen', NULL, 0, NULL, 0, 1, NULL, NULL, 1, 1, 1, 0, 2, 1, 0, NULL),
  (6, 3, 'Bewerbung', 'Bewerbungen', 'Bewerben', NULL, 0, NULL, 0, 1, NULL, NULL, 1, 0, 0, 0, 2, 1, 0, NULL),
  (7, 4, 'Antrag', 'Anträge', 'Antrag stellen', NULL, 0, NULL, 0, 1, NULL, NULL, 2, 2, 2, 0, 2, 1, 1,
   '{"minSupporters":19,"supportersHaveOrganizations":true}');

--
-- Dumping data for table `consultationSettingsMotionSection`
--

INSERT INTO `consultationSettingsMotionSection` (`id`, `motionTypeId`, `type`, `position`, `status`, `title`, `data`, `fixedWidth`, `required`, `maxLen`, `lineNumbers`, `hasComments`, `hasAmendments`)
VALUES
  (1, 1, 0, 0, 0, 'Überschrift', NULL, 0, 1, 0, 1, 0, 1),
  (2, 1, 1, 1, 0, 'Antragstext', NULL, 1, 1, 0, 1, 1, 1),
  (3, 1, 1, 3, 0, 'Begründung', NULL, 0, 0, 0, 0, 0, 0),
  (4, 1, 1, 2, 0, 'Antragstext 2', NULL, 1, 0, 0, 1, 1, 1),
  (5, 1, 3, 4, 0, 'Abbildung', NULL, 1, 0, 0, 1, 0, 0),
  (6, 3, 0, 0, 0, 'Überschrift', NULL, 0, 1, 0, 1, 0, 1),
  (7, 3, 1, 1, 0, 'Antragstext', NULL, 1, 1, 0, 1, 1, 1),
  (8, 3, 1, 3, 0, 'Begründung', NULL, 0, 0, 0, 0, 0, 0),
  (9, 4, 0, 0, 0, 'Name', NULL, 0, 1, 0, 0, 0, 0),
  (10, 4, 3, 1, 0, 'Foto', NULL, 0, 1, 0, 0, 0, 0),
  (11, 4, 4, 2, 0, 'Angaben', '{"maxRowId":3,"rows":{"1":{"rowId":1,"title":"Geburtsort","type":"1"},"3":{"rowId":3,"title":"Alter","type":"2"},"2":{"rowId":2,"title":"Geburtstag","type":"3"}}}', 0, 0, 0, 0, 0, 0),
  (12, 4, 1, 3, 0, 'Selbstvorstellung', NULL, 0, 1, 0, 0, 0, 0),
  (13, 6, 0, 0, 0, 'Name', NULL, 0, 1, 0, 0, 0, 0),
  (14, 6, 3, 1, 0, 'Foto', NULL, 0, 1, 0, 0, 0, 0),
  (15, 6, 4, 2, 0, 'Angaben', '{"maxRowId":2,"rows":{"1":{"rowId":1,"title":"Alter","type":2},"2":{"rowId":2,"title":"Geschlecht","type":1},"3":{"rowId":3,"title":"Geburtsort","type":1}}}', 0, 0, 0, 0, 0, 0),
  (16, 6, 1, 3, 0, 'Selbstvorstellung', NULL, 0, 1, 0, 0, 0, 0),
  (17, 5, 0, 0, 0, 'Titel', NULL, 0, 1, 0, 0, 0, 1),
  (18, 5, 1, 1, 0, 'Antragstext', NULL, 1, 1, 0, 1, 1, 1),
  (19, 5, 1, 2, 0, 'Begründung', NULL, 0, 0, 0, 0, 0, 0),
  (20, 7, 0, 0, 0, 'Titel', NULL, 0, 1, 0, 0, 0, 1),
  (21, 7, 1, 1, 0, 'Antragstext', NULL, 1, 1, 0, 1, 1, 1),
  (22, 7, 1, 2, 0, 'Begründung', NULL, 0, 0, 0, 0, 0, 0);

--
-- Dumping data for table `consultationSettingsTag`
--

INSERT INTO `consultationSettingsTag` (`id`, `consultationId`, `position`, `title`, `cssicon`) VALUES
  (1, 1, 0, 'Win', 0),
  (2, 1, 1, 'Fail', 0);

--
-- Dumping data for table `emailLog`
--

INSERT INTO `emailLog` (`id`, `toEmail`, `toUserId`, `type`, `fromEmail`, `dateSent`, `subject`, `text`) VALUES
  (1, 'tobias@hoessl.eu', NULL, 3, '=?UTF-8?B?QW50cmFnc2dyw7xuIHYz?= <info@antragsgruen.de>', '2015-05-23 01:46:38',
   'Neuer Antrag',
   'Es wurde ein neuer Änderungsantrag "Ä1 zu A2: O’zapft is!" eingereicht.\nLink: http://stdparteitag.antraege-v3.hoessl.eu/std-parteitag/motion/2/amendment/1'),
  (2, 'tobias@hoessl.eu', NULL, 3, '=?UTF-8?B?QW50cmFnc2dyw7xuIHYz?= <info@antragsgruen.de>', '2015-06-26 01:03:55',
   'Neuer Antrag', 'Es wurde ein neuer Antrag "Textformatierungen" eingereicht.\nLink: /std-parteitag/motion/3'),
  (3, 'tobias@hoessl.eu', NULL, 3, '=?UTF-8?B?QW50cmFnc2dyw7xu?= <EMAILADRESSE>', '2015-07-06 04:21:55', 'Neuer Antrag',
   'Es wurde ein neuer Änderungsantrag "Ä1 zu A3: Textformatierungen" eingereicht.\nLink: http://stdparteitag.antragsgruen-v3.localhost/std-parteitag/motion/3/amendment/2'),
  (4, 'tobias@hoessl.eu', NULL, 3, '=?UTF-8?B?QW50cmFnc2dyw7xu?= <EMAILADRESSE>', '2015-07-07 07:49:33', 'Neuer Antrag',
   'Es wurde ein neuer Änderungsantrag "Ä2 zu A2: O’zapft is!" eingereicht.\nLink: http://stdparteitag.antragsgruen-v3.localhost/std-parteitag/motion/2/amendment/3'),
  (5, 'testadmin@example.org', NULL, 3, '=?UTF-8?B?QW50cmFnc2dyw7xu?= <EMAILADRESSE>', '2015-07-08 08:05:49',
   'Neuer Antrag',
   'Es wurde ein neuer Antrag "Lorem ipsum dolor sit amet" eingereicht.\nLink: http://bdk.antragsgruen-v3.localhost/bdk/motion/4');

--
-- Dumping data for table `motion`
--

INSERT INTO `motion` (`id`, `consultationId`, `motionTypeId`, `parentMotionId`, `agendaItemId`, `title`, `titlePrefix`, `dateCreation`, `dateResolution`, `status`, `statusString`, `noteInternal`, `cache`, `textFixed`)
VALUES
  (2, 1, 1, NULL, NULL, 'O’zapft is!', 'A2', '2015-04-02 13:27:20', NULL, 3, NULL, NULL, '', 0),
  (3, 1, 1, NULL, NULL, 'Textformatierungen', 'A3', '2015-06-26 01:03:49', NULL, 3, NULL, NULL, '', 0),
  (4, 4, 7, NULL, NULL, 'Lorem ipsum dolor sit amet', 'A1', '2015-07-08 08:05:23', NULL, 3, NULL, NULL, '', 0);

--
-- Dumping data for table `motionSection`
--

INSERT INTO `motionSection` (`motionId`, `sectionId`, `data`, `metadata`) VALUES
  (2, 1, 'O’zapft is!', NULL),
  (2, 2, '<p>Bavaria ipsum dolor sit amet Biazelt Auffisteign Schorsch mim Radl foahn Ohrwaschl Steckerleis wann griagd ma nacha wos z’dringa glacht Mamalad, muass? I bin a woschechta Bayer sowos oamoi und sei und glei wirds no fui lustiga: Jo mei is des schee middn ognudelt, Trachtnhuat Biawambn gscheid: Griasd eich midnand etza nix Gwiass woass ma ned owe. Dahoam gscheckate middn Spuiratz des is a gmahde Wiesn. Des is schee so Obazda san da, Haferl pfenningguat schoo griasd eich midnand.</p>\n<ul>\n<li>Auffi Gamsbart nimma de Sepp Ledahosn Ohrwaschl um Godds wujn Wiesn Deandlgwand Mongdratzal! Jo leck mi Mamalad i daad mechad?</li>\n	<li>Do nackata Wurscht i hob di narrisch gean, Diandldrahn Deandlgwand vui huift vui woaß?</li>\n	<li>Ned Mamalad auffi i bin a woschechta Bayer greaßt eich nachad, umananda gwiss nia need Weiznglasl.</li>\n	<li>Woibbadinga noch da Giasinga Heiwog Biazelt mechad mim Spuiratz, soi zwoa.</li>\n</ul>\n<p>I waar soweid Blosmusi es nomoi. Broadwurschtbudn des is a gmahde Wiesn Kirwa mogsd a Bussal Guglhupf schüds nei. Luja i moan oiwei Baamwach Watschnbaam, wiavui baddscher! Biakriagal a fescha Bua Semmlkneedl iabaroi oba um Godds wujn Ledahosn wui Greichats. Geh um Godds wujn luja heid greaßt eich nachad woaß Breihaus eam! De om auf’n Gipfe auf gehds beim Schichtl mehra Baamwach a bissal wos gehd ollaweil gscheid:</p>\n<blockquote>\n<p>Scheans Schdarmbeaga See i hob di narrisch gean i jo mei is des schee! Nia eam hod vasteh i sog ja nix, i red ja bloß sammawiedaguad, umma eana obandeln! Zwoa jo mei scheans amoi, san und hoggd Milli barfuaßat gscheit. Foidweg vui huift vui singan, mehra Biakriagal om auf’n Gipfe! Ozapfa sodala Charivari greaßt eich nachad Broadwurschtbudn do middn liberalitas Bavariae sowos Leonhardifahrt:</p>\n</blockquote>\n<p>Wui helfgod Wiesn, ognudelt schaugn: Dahoam gelbe Rüam Schneid singan wo hi sauba i moan scho aa no a Maß a Maß und no a Maß nimma. Is umananda a ganze Hoiwe zwoa, Schneid. Vui huift vui Brodzeid kumm geh naa i daad vo de allerweil, gor. Woaß wia Gams, damischa. A ganze Hoiwe Ohrwaschl Greichats iabaroi Prosd Engelgwand nix Reiwadatschi.Weibaleid ognudelt Ledahosn noch da Giasinga Heiwog i daad Almrausch, Ewig und drei Dog nackata wea ko, dea ko. Meidromml Graudwiggal nois dei, nackata. No Diandldrahn nix Gwiass woass ma ned hod boarischer: Samma sammawiedaguad wos, i hoam Brodzeid. Jo mei Sepp Gaudi, is ma Wuascht do Hendl Xaver Prosd eana an a bravs. Sauwedda an Brezn, abfieseln.</p>\n', NULL),
  (2, 3, '<p>I-Düpferl-Reita, Bettbrunza, Zwedschgnmanndl, Goaspeterl, junga Duttara, dreckata Drek, Dramhappada, boaniga, damischa Depp, Woibbadinga, di hams midam Stickl Brot ausm Woid raußzogn, Betonschedl, mit deinen Badwandlfüaß, Goggolore, Ruaßnosn.</p>\n<p>Krummhaxata Goaßbog, Fliedschal, Schdeckalfisch, gscherta Hamml, Saubreiß, japanischer, Pimpanell, kropfata Hamml, Nasnboara, elendiger, Hausdracha, Grantlhuaba, Honigscheißa, Pfennigfuxa, Gmoadepp, oide Bixn, Beißzanga, Mistviach, Dreeghamml, Bodschal, Voiksdepp, Grischbal, Aufmüpfiga, Freibialädschn, gwampate Sau, Umstandskrama, glei foid da Wadschnbam um, Jungfa, Umstandskrama, Bruinschlanga, Oasch, Schbruchbeidl, Kittlschliaffa, Grantlhuaba, Radlfahra, Hallodri!</p>\n<p>Woibbadinga, Pfennigfuxa, Zwedschgndatschi, Scheißbürschdl, Schbringgingal, Halbkreisingeneur, elendiger, damischa Depp, Haumdaucha, Ruaßnosn, Griasgram, Rutschn, Beißn, Bodschal, Hosnscheissa, Dreegsau, oida Daggl, Dreegschleida, Schwobnsäckle, Beißn.</p>\n<p>Asphaltwanzn, Zwedschgnmanndl, Hopfastanga, gscherte Nuss, Saufbeitl, oida Daddara, Vieh mit Haxn, Bruinschlanga, Daamaluudscha, Bierdimpfl, Hundsbua, oida Daggl, Kirchalicht, Doafdrottl, gscheate Ruam, schiache Goaß, Schuibuamtratza, Zwedschgarl, oide Schäwan.</p>\n<p>Herrgoddsacklzementfixlujja, Voiksdepp, Hopfastanga, Hundsgribbe, Schdeckalfisch, Chaotngschwerl, ja, wo samma denn, Hoibschaariga, Hundsbua, Frichdal, glei fangst a boa!</p>\n<p>Du ogsoachte, aus’gschammta, Auftaklta, kropfata Hamml, klebrigs Biaschal, Beißn, Ruaßnosn, Honigscheißa, eigschnabbda, Ecknsteha, Freibialädschn, du saudamischa, Hockableiba, Aufschneida, Saubreiß, japanischer, hoit’s Mei, Saubreiß, Badwaschl, Kasberlkopf.</p>\n<p>Saubreiß, Geizgroogn, Erzdepp, Rotzgloggn, Radlfahra, glei fangst a boa, Eisackla, Aff, Grawurgl, Haumdaucha, Schachtlhuba, Bauantrampl, Schlawina, schiache Goaß, depperta Doafdebb, Asphaltwanzn, hoid dei Babbn, Schdeckalfisch, Hemmadbiesla, halbseidener, Aufmüpfiga, Voiksdepp, Gibskobf, Kasberlkopf.<br>\nFlegel, Kamejtreiba, glei foid da Wadschnbam um, schdaubiga Bruada, Oaschgsicht, greißlicha Uhu, oida Daddara!</p>\n', NULL),
  (2, 4, '<p>Woibbadinga damischa owe gwihss Sauwedda ded Charivari dei heid gfoids ma sagrisch guad. Maßkruag wo hi mim Radl foahn Landla Leonhardifahrt, Radler. Ohrwaschl und glei wirds no fui lustiga Spotzerl Fünferl, so auf gehds beim Schichtl do legst di nieda ned Biawambn Breihaus. I mechad dee Schwoanshaxn ghupft wia gsprunga measi gschmeidig hawadere midananda vui huift vui Biawambn, des wiad a Mordsgaudi is. Biaschlegl soi oans, zwoa, gsuffa Oachkatzlschwoaf hod Wiesn.</p>\n<p>Oamoi großherzig Mamalad, liberalitas Bavariae hoggd! Nimmds helfgod im Beidl des basd scho i hob di liab. A Prosit der Gmiadlichkeit midanand mim obandln do mim Radl foahn, Jodler. Ned woar Brotzeit Brotzeit gwihss eana Gidarn. Foidweg Spuiratz kimmt, um Godds wujn. Am acht’n Tag schuf Gott des Bia i sog ja nix, i red ja bloß jedza, Biakriagal a bissal wos gehd ollaweil. Ledahosn om auf’n Gipfe Servas des wiad a Mordsgaudi, griasd eich midnand Bladl Fünferl Gams.</p>\n<p>Leonhardifahrt ma da middn. Greichats an naa do. Af Schuabladdla Leonhardifahrt Marei, des um Godds wujn Biakriagal! Hallelujah sog i, luja schüds nei koa des is schee jedza hogg di hera dringma aweng Spezi nia Musi. Wurschtsolod jo mei is des schee gor Ramasuri ozapfa no gfreit mi i hob di liab auffi, Schbozal. Hogg di hera nia need Biakriagal so schee, Schdarmbeaga See.</p>\n', NULL),
  (2, 5, '', NULL),
  (3, 1, 'Textformatierungen', NULL),
  (3, 2, '<p>Normaler Text <strong>fett und <em>kursiv</em></strong><br>\nZeilenumbruch <span class="underline">unterstrichen</span></p>\n<p><span class="strike">Durchgestrichen und <em>kursiv</em></span></p>\n<ol><li>Listenpunkt</li>\n	<li>Listenpunkt (<em>kursiv</em>)<br>\n	Zeilenumbruch</li>\n	<li>Nummer 3</li>\n	<li>Seltsame Zeichen: &amp; % $ # _ { } ~ ^ \\ \\today</li>\n</ol><p>Normaler Text wieder.</p>\n<p>Absatz 2</p>\n<ul>\n<li>Einfache Punkte</li>\n	<li>Nummer 2</li>\n</ul>\n<p>Link Bla</p>\n<blockquote>\n<p>Zitat 223<br>\nZeilenumbruch</p>\n<p>Neuer Paragraph</p>\n</blockquote>\n<p>Ende</p>\n', NULL),
  (3, 3, '<p>Textformatierungs-Test</p>\n', NULL),
  (3, 4, '<p>Textformatierungs-Test</p>\n', NULL),
  (3, 5, '', NULL),
  (4, 20, 'Lorem ipsum dolor sit amet', NULL),
  (4, 21,
   '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Cras eu felis sit amet lectus pulvinar gravida. Phasellus lacinia tristique lorem, vitae vulputate quam laoreet non. Sed sit amet magna justo. Integer nunc risus, interdum et imperdiet in, finibus sit amet velit. Pellentesque iaculis sed leo quis lacinia. Donec at felis sit amet dolor rhoncus placerat eget sed nisi. Fusce eget mauris ante.</p>\n<p>Duis consequat purus dolor, <strong>et aliquet quam</strong> congue id. <em>Aliquam erat</em> volutpat. Interdum et malesuada fames ac ante ipsum primis in faucibus. Aliquam bibendum feugiat vulputate. Phasellus cursus varius ipsum eu volutpat. Nullam sed diam scelerisque, sollicitudin mauris quis, vestibulum elit. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus.</p>\n<ul>\n<li>Phasellus nec mi id nunc placerat semper sed quis tortor. Phasellus vel aliquam lectus, non convallis ex. Phasellus turpis libero, convallis vitae elit fringilla, venenatis lobortis nunc. Sed vitae mauris tellus. Aenean consequat est nulla, vitae sodales nulla vulputate vitae. Praesent tincidunt auctor lacus eu pellentesque.</li>\n	<li>Pellentesque euismod quis neque a ultricies. Nulla tincidunt suscipit massa. Nullam fringilla massa a massa euismod pulvinar. Donec venenatis, massa sit amet vehicula blandit, erat libero ullamcorper ligula, a dapibus mauris dolor ac magna. Integer nec velit id nisi tempus accumsan ac eget dui.</li>\n	<li>Donec accumsan suscipit lectus, et vulputate urna gravida nec. Pellentesque cursus pulvinar pharetra. Mauris ornare vestibulum blandit. Nullam maximus tortor ac aliquam dictum. Praesent fermentum arcu et commodo lacinia.</li>\n</ul>\n<p>In hac habitasse platea dictumst. Morbi a lacus malesuada, posuere quam a, fermentum turpis. Aenean iaculis libero nisi, non elementum erat ultrices vitae. Nunc purus nisi, maximus ut maximus eget, laoreet id quam. Fusce scelerisque tristique diam, nec tristique sapien. Morbi mi leo, scelerisque eget purus ut, ornare gravida odio. Integer vehicula at orci eget congue. Duis at elit scelerisque, fringilla tellus nec, aliquam purus. Nam et erat iaculis nibh condimentum tincidunt.</p>\n<p>Donec hendrerit sed odio eu egestas. Praesent iaculis eu sapien eget tincidunt. Suspendisse non massa nec leo scelerisque eleifend. Sed euismod vulputate lorem et consectetur. Donec vitae gravida purus. Pellentesque et tincidunt massa. Nam vulputate aliquet elit.</p>\n',
   NULL),
  (4, 22,
   '<p>Phasellus nec mi id nunc placerat semper sed quis tortor. Phasellus vel aliquam lectus, non convallis ex. Phasellus turpis libero, convallis vitae elit fringilla, venenatis lobortis nunc. Sed vitae mauris tellus. Aenean consequat est nulla, vitae sodales nulla vulputate vitae. </p>\n',
   NULL);

--
-- Dumping data for table `motionSupporter`
--

INSERT INTO `motionSupporter` (`id`, `motionId`, `position`, `userId`, `role`, `comment`, `personType`, `name`, `organization`, `resolutionDate`, `contactEmail`, `contactPhone`)
VALUES
  (2, 2, 0, 1, 'initiates', NULL, 0, 'HoesslTo', '', NULL, 'tobias@hoessl.eu', NULL),
  (3, 3, 0, 1, 'initiates', NULL, 0, 'Testadmin', '', NULL, 'testadmin@example.org', ''),
  (4, 4, 0, 2, 'initiates', NULL, 0, 'Tobias Hößl', '', NULL, 'tobias@hoessl.eu', '015156024223'),
  (5, 4, 0, NULL, 'supports', NULL, 0, 'UnterstützerIn 1', 'Gremium 1', NULL, NULL, NULL),
  (6, 4, 1, NULL, 'supports', NULL, 0, 'UnterstützerIn 2', 'Gremium 2', NULL, NULL, NULL),
  (7, 4, 2, NULL, 'supports', NULL, 0, 'UnterstützerIn 3', 'Gremium 3', NULL, NULL, NULL),
  (8, 4, 3, NULL, 'supports', NULL, 0, 'UnterstützerIn 4', 'Gremium 4', NULL, NULL, NULL),
  (9, 4, 4, NULL, 'supports', NULL, 0, 'UnterstützerIn 5', 'Gremium 5', NULL, NULL, NULL),
  (10, 4, 5, NULL, 'supports', NULL, 0, 'UnterstützerIn 6', 'Gremium 6', NULL, NULL, NULL),
  (11, 4, 6, NULL, 'supports', NULL, 0, 'UnterstützerIn 7', 'Gremium 7', NULL, NULL, NULL),
  (12, 4, 7, NULL, 'supports', NULL, 0, 'UnterstützerIn 8', 'Gremium 8', NULL, NULL, NULL),
  (13, 4, 8, NULL, 'supports', NULL, 0, 'UnterstützerIn 9', 'Gremium 9', NULL, NULL, NULL),
  (14, 4, 9, NULL, 'supports', NULL, 0, 'UnterstützerIn 10', 'Gremium 10', NULL, NULL, NULL),
  (15, 4, 10, NULL, 'supports', NULL, 0, 'UnterstützerIn 11', 'Gremium 11', NULL, NULL, NULL),
  (16, 4, 11, NULL, 'supports', NULL, 0, 'UnterstützerIn 12', 'Gremium 12', NULL, NULL, NULL),
  (17, 4, 12, NULL, 'supports', NULL, 0, 'UnterstützerIn 13', 'Gremium 13', NULL, NULL, NULL),
  (18, 4, 13, NULL, 'supports', NULL, 0, 'UnterstützerIn 14', 'Gremium 14', NULL, NULL, NULL),
  (19, 4, 14, NULL, 'supports', NULL, 0, 'UnterstützerIn 15', 'Gremium 15', NULL, NULL, NULL),
  (20, 4, 15, NULL, 'supports', NULL, 0, 'UnterstützerIn 16', 'Gremium 16', NULL, NULL, NULL),
  (21, 4, 16, NULL, 'supports', NULL, 0, 'UnterstützerIn 17', 'Gremium 17', NULL, NULL, NULL),
  (22, 4, 17, NULL, 'supports', NULL, 0, 'UnterstützerIn 18', 'Gremium 18', NULL, NULL, NULL),
  (23, 4, 18, NULL, 'supports', NULL, 0, 'UnterstützerIn 19', 'Gremium 19', NULL, NULL, NULL);

--
-- Dumping data for table `motionTag`
--

INSERT INTO `motionTag` (`motionId`, `tagId`) VALUES
  (3, 1);

--
-- Dumping data for table `site`
--

INSERT INTO `site` (`id`, `subdomain`, `title`, `titleShort`, `settings`, `currentConsultationId`, `public`, `contact`)
VALUES
  (1, 'stdparteitag', 'Test2', 'Test2', NULL, 1, 1, 'Test2'),
  (2, 'vorstandswahlen', 'Vorstandswahlen', 'Vorstandswahlen',
   '{"onlyNamespacedAccounts":false,"onlyWurzelwerk":false,"willingToPay":"1"}', 2, 1, 'Vorstandswahlen'),
  (3, 'parteitag', 'Parteitag', 'Parteitag',
   '{"onlyNamespacedAccounts":false,"onlyWurzelwerk":false,"willingToPay":"1"}', 3, 1, 'Parteitag'),
  (4, 'bdk', 'BDK', 'BDK',
   '{"onlyNamespacedAccounts":0,"onlyWurzelwerk":0,"siteLayout":"layout-gruenes-ci","willingToPay":"2"}', 4, 1, 'BDK');

--
-- Dumping data for table `siteAdmin`
--

INSERT INTO `siteAdmin` (`siteId`, `userId`) VALUES
  (1, 1),
  (2, 1),
  (3, 1),
  (4, 1);

--
-- Dumping data for table `texTemplate`
--

INSERT INTO `texTemplate` (`id`, `siteId`, `title`, `texLayout`, `texContent`) VALUES
  (1, NULL, 'Standard (Grünes CI)',
   '\\documentclass[paper=a4, 12pt, pagesize, parskip=half, DIV=calc]{scrartcl}\r\n\\usepackage[T1]{fontenc}\r\n\\usepackage{lmodern}\r\n\\usepackage[%LANGUAGE%]{babel}\r\n\\usepackage{fixltx2e}\r\n\\usepackage{lineno}\r\n\\usepackage{tabularx}\r\n\\usepackage{scrpage2}\r\n\\usepackage[normalem]{ulem}\r\n\\usepackage[right]{eurosym}\r\n\\usepackage{fontspec}\r\n\\usepackage{geometry}\r\n\\usepackage{color}\r\n\\usepackage{lastpage}\r\n\\usepackage[normalem]{ulem}\r\n\\usepackage{hyperref}\r\n\r\n\\newfontfamily\\ArvoGruen[\r\n  Path=%ASSETROOT%Arvo/\r\n]{Arvo_Gruen_1004.otf}\r\n\\newfontfamily\\ArvoRegular[\r\n  Path=%ASSETROOT%Arvo/\r\n]{Arvo-Regular_v104.ttf}\r\n\\newfontfamily\\AntragsgruenSection[\r\n  Path=%ASSETROOT%Arvo/\r\n]{Arvo-Regular_v104.ttf}\r\n\\setmainfont[\r\n  Path=%ASSETROOT%PT-Sans/,\r\n  BoldFont=PTS75F.ttf,\r\n  ItalicFont=PTS56F.ttf,\r\n  BoldItalicFont=PTS76F.ttf\r\n]{PTS55F.ttf}\r\n\r\n\\definecolor{Insert}{rgb}{0,1,0}\r\n\\definecolor{Delete}{rgb}{1,0,0}\r\n\r\n\\hypersetup{\r\n    colorlinks=true,\r\n    linkcolor=blue,\r\n    filecolor=blue,      \r\n    urlcolor=blue,\r\n} \r\n\\urlstyle{same}\r\n\r\n\\title{%TITLE%}\r\n\\author{%AUTHOR%}\r\n\\geometry{a4paper, portrait, top=10mm, left=20mm, right=15mm, bottom=25mm, includehead=true}\r\n\r\n\\pagestyle{scrheadings}\r\n\\clearscrheadfoot\r\n\\ohead{\\ArvoRegular \\footnotesize %TITLE%}\r\n\\ifoot{\\ArvoRegular \\footnotesize Seite \\thepage\\ / \\pageref{LastPage}}\r\n\\setheadsepline{0.4pt}\r\n\\setfootsepline{0.4pt}\r\n\r\n\\begin{document}\r\n\r\n\\shorthandoff{"}\r\n\\sloppy\r\n\\hyphenpenalty=10000\r\n\\hbadness=10000\r\n\r\n%CONTENT%\r\n\r\n\\end{document}',
   '\\thispagestyle{empty}\r\n\r\n\\vspace*{-25mm}\r\n\\begin{flushright}\r\n \\ArvoRegular\r\n \\small\r\n \\textbf{\\normalsize %INTRODUCTION_BIG%}\\\\\r\n %INTRODUCTION_SMALL%\r\n\\end{flushright}\r\n\r\n\\begin{tabularx}{\\textwidth}{|lX|}\r\n    \\cline{1-2}\r\n    \\ArvoGruen\r\n                                                            &                               \\\\\r\n    \\textbf{\\LARGE %TITLEPREFIX%} %TITLE%           &                               \\\\\r\n                                                            &                               \\\\\r\n    %MOTION_DATA_TABLE%\r\n                                                            &                               \\\\\r\n    \\cline{1-2}\r\n\\end{tabularx}\r\n\r\n\\section*{\\ArvoRegular %TITLE_LONG%}\r\n% \\raggedright\r\n\r\n%TEXT%\r\n');

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id`, `name`, `email`, `emailConfirmed`, `auth`, `dateCreation`, `status`, `pwdEnc`, `authKey`, `siteNamespaceId`)
VALUES
  (1, 'Testadmin', 'testadmin@example.org', 1, 'email:testadmin@example.org', '2015-03-21 00:04:44', 0,
   'sha256:1000:gpdjLHGKeqKXDjjjVI6JsXF5xl+cAYm1:jT6RRYV6luIdDaomW56BMf50zQi0tiFy',
   0x66353232373335386331326436636434383930306430376638343666316363373538623562396438000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000,
   NULL),
  (2, 'Testuser', 'testuser@example.org', 1, 'email:testuser@example.org', '2015-03-21 00:08:14', 0,
   'sha256:1000:BwEqXMsdBXDi71XpQud1yRene4zeNRTt:atF5X6vaHJ93nyDIU/gobIpehez+0KBV',
   0x33663062343836336632393839643866383961396162386532626133336232363465373065663361000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000,
   NULL);


SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
