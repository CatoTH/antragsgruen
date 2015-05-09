SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 0;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 0;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'TRADITIONAL,ALLOW_INVALID_DATES';


INSERT INTO `consultation` (`id`, `siteId`, `urlPath`, `type`, `wordingBase`, `title`, `titleShort`, `eventDateFrom`, `eventDateTo`, `amendmentNumbering`, `adminEmail`, `settings`)
VALUES
  (1, 1, 'std-parteitag', 0, 'de-parteitag', 'Test2', 'Test2', NULL, NULL,
   0, 'tobias@hoessl.eu', NULL),
  (2, 2, 'vorstandswahlen', 1, 'de-bewerbung', 'Vorstandswahlen', 'Vorstandswahlen', NULL, NULL, 0, 'testadmin@example.org',
   '{"maintainanceMode":false,"motionNeedsEmail":false,"motionNeedsPhone":false,"motionHasPhone":false,"commentNeedsEmail":false,"iniatorsMayEdit":false,"adminsMayEdit":true,"confirmEmails":false,"lineNumberingGlobal":false,"hideRevision":false,"minimalisticUI":false,"showFeeds":true,"commentsSupportable":false,"screeningMotions":false,"screeningMotionsShown":false,"screeningAmendments":false,"screeningComments":false,"initiatorsMayReject":false,"hasPDF":true,"commentWholeMotions":false,"allowMultipleTags":false,"allowStrikeFormat":false,"lineLength":80,"startLayoutType":0,"logoUrl":null,"logoUrlFB":null,"motionIntro":null}');

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
  (11, 4, 4, 2, 0, 'Angaben', '{"maxRowId":3,"rows":{"1":{"rowId":1,"title":"Geburtsort","type":"1"},"3":{"rowId":3,"title":"Alter","type":"2"},"2":{"rowId":2,"title":"Geburtstag","type":"3"}}}', 0, 0, 0, 0,
   0, 0),
  (12, 4, 1, 3, 0, 'Selbstvorstellung', NULL, 0, 1, 0, 0, 0, 0);


INSERT INTO `consultationMotionType` (`id`, `consultationId`, `title`, `motionPrefix`, `position`, `cssicon`, `deadlineMotions`, `deadlineAmendments`, `policyMotions`, `policyAmendments`, `policyComments`, `policySupport`)
VALUES
  (1, 1, 'Antrag', 'A', 0, NULL, NULL, NULL, 1, 1, 1, 2),
  (3, 2, 'Antrag', 'A', 2, NULL, NULL, NULL, 1, 1, 1, 2),
  (4, 2, 'Bewerbung', 'B', 0, NULL, NULL, NULL, 1, 1, 1, 2);

INSERT INTO `consultationSettingsTag` (`id`, `consultationId`, `position`, `title`, `cssicon`) VALUES
  (1, 1, 0, 'Win', 0),
  (2, 1, 1, 'Fail', 0);

INSERT INTO `site` (`id`, `subdomain`, `title`, `titleShort`, `settings`, `currentConsultationId`, `public`, `contact`)
VALUES
  (1, 'stdparteitag', 'Test2', 'Test2', NULL, 1, 1, 'Test2'),
  (2, 'vorstandswahlen', 'Vorstandswahlen', 'Vorstandswahlen',
   '{"onlyNamespacedAccounts":false,"onlyWurzelwerk":false,"willingToPay":"1"}', 2, 1, 'Vorstandswahlen');

INSERT INTO `siteAdmin` (`siteId`, `userId`) VALUES
  (1, 1),
  (2, 1);

INSERT INTO `user` (`id`, `name`, `email`, `emailConfirmed`, `auth`, `dateCreation`, `status`, `pwdEnc`, `authKey`, `siteNamespaceId`)
VALUES
  (1, 'Testadmin', 'testadmin@example.org', 1, 'email:testadmin@example.org', '2015-03-21 11:04:44', 0,
   'sha256:1000:gpdjLHGKeqKXDjjjVI6JsXF5xl+cAYm1:jT6RRYV6luIdDaomW56BMf50zQi0tiFy', NULL, NULL),
  (2, 'Testuser', 'testuser@example.org', 1, 'email:testuser@example.org', '2015-03-21 11:08:14', 0,
   'sha256:1000:BwEqXMsdBXDi71XpQud1yRene4zeNRTt:atF5X6vaHJ93nyDIU/gobIpehez+0KBV', NULL, NULL);

INSERT INTO `motion` (`id`, `consultationId`, `motionTypeId`, `parentMotionId`, `title`, `titlePrefix`, `dateCreation`, `dateResolution`, `status`, `statusString`, `noteInternal`, `cache`, `textFixed`)
VALUES
  (2, 1, 1, NULL, 'O’zapft is!', 'A2', '2015-04-03 11:27:20', NULL, 3, NULL, NULL, '', 0);

INSERT INTO `motionSection` (`motionId`, `sectionId`, `data`, `metadata`) VALUES
  (2, 1, 'O’zapft is!', NULL),
  (2, 2,
   '<p>Bavaria ipsum dolor sit amet Biazelt Auffisteign Schorsch mim Radl foahn Ohrwaschl Steckerleis wann griagd ma nacha wos z’dringa glacht Mamalad, muass? I bin a woschechta Bayer sowos oamoi und sei und glei wirds no fui lustiga: Jo mei is des schee middn ognudelt, Trachtnhuat Biawambn gscheid: Griasd eich midnand etza nix Gwiass woass ma ned owe. Dahoam gscheckate middn Spuiratz des is a gmahde Wiesn. Des is schee so Obazda san da, Haferl pfenningguat schoo griasd eich midnand.</p>\n<ul>\n<li>Auffi Gamsbart nimma de Sepp Ledahosn Ohrwaschl um Godds wujn Wiesn Deandlgwand Mongdratzal! Jo leck mi Mamalad i daad mechad?</li>\n	<li>Do nackata Wurscht i hob di narrisch gean, Diandldrahn Deandlgwand vui huift vui woaß?</li>\n	<li>Ned Mamalad auffi i bin a woschechta Bayer greaßt eich nachad, umananda gwiss nia need Weiznglasl.</li>\n	<li>Woibbadinga noch da Giasinga Heiwog Biazelt mechad mim Spuiratz, soi zwoa.</li>\n</ul>\n<p>I waar soweid Blosmusi es nomoi. Broadwurschtbudn des is a gmahde Wiesn Kirwa mogsd a Bussal Guglhupf schüds nei. Luja i moan oiwei Baamwach Watschnbaam, wiavui baddscher! Biakriagal a fescha Bua Semmlkneedl iabaroi oba um Godds wujn Ledahosn wui Greichats. Geh um Godds wujn luja heid greaßt eich nachad woaß Breihaus eam! De om auf’n Gipfe auf gehds beim Schichtl mehra Baamwach a bissal wos gehd ollaweil gscheid:</p>\n<blockquote>\n<p>Scheans Schdarmbeaga See i hob di narrisch gean i jo mei is des schee! Nia eam hod vasteh i sog ja nix, i red ja bloß sammawiedaguad, umma eana obandeln! Zwoa jo mei scheans amoi, san und hoggd Milli barfuaßat gscheit. Foidweg vui huift vui singan, mehra Biakriagal om auf’n Gipfe! Ozapfa sodala Charivari greaßt eich nachad Broadwurschtbudn do middn liberalitas Bavariae sowos Leonhardifahrt:</p>\n</blockquote>\n<p>Wui helfgod Wiesn, ognudelt schaugn: Dahoam gelbe Rüam Schneid singan wo hi sauba i moan scho aa no a Maß a Maß und no a Maß nimma. Is umananda a ganze Hoiwe zwoa, Schneid. Vui huift vui Brodzeid kumm geh naa i daad vo de allerweil, gor. Woaß wia Gams, damischa. A ganze Hoiwe Ohrwaschl Greichats iabaroi Prosd Engelgwand nix Reiwadatschi.Weibaleid ognudelt Ledahosn noch da Giasinga Heiwog i daad Almrausch, Ewig und drei Dog nackata wea ko, dea ko. Meidromml Graudwiggal nois dei, nackata. No Diandldrahn nix Gwiass woass ma ned hod boarischer: Samma sammawiedaguad wos, i hoam Brodzeid. Jo mei Sepp Gaudi, is ma Wuascht do Hendl Xaver Prosd eana an a bravs. Sauwedda an Brezn, abfieseln.</p>\n',
   NULL),
  (2, 3,
   '<p>I-Düpferl-Reita, Bettbrunza, Zwedschgnmanndl, Goaspeterl, junga Duttara, dreckata Drek, Dramhappada, boaniga, damischa Depp, Woibbadinga, di hams midam Stickl Brot ausm Woid raußzogn, Betonschedl, mit deinen Badwandlfüaß, Goggolore, Ruaßnosn.</p>\n<p>Krummhaxata Goaßbog, Fliedschal, Schdeckalfisch, gscherta Hamml, Saubreiß, japanischer, Pimpanell, kropfata Hamml, Nasnboara, elendiger, Hausdracha, Grantlhuaba, Honigscheißa, Pfennigfuxa, Gmoadepp, oide Bixn, Beißzanga, Mistviach, Dreeghamml, Bodschal, Voiksdepp, Grischbal, Aufmüpfiga, Freibialädschn, gwampate Sau, Umstandskrama, glei foid da Wadschnbam um, Jungfa, Umstandskrama, Bruinschlanga, Oasch, Schbruchbeidl, Kittlschliaffa, Grantlhuaba, Radlfahra, Hallodri!</p>\n<p>Woibbadinga, Pfennigfuxa, Zwedschgndatschi, Scheißbürschdl, Schbringgingal, Halbkreisingeneur, elendiger, damischa Depp, Haumdaucha, Ruaßnosn, Griasgram, Rutschn, Beißn, Bodschal, Hosnscheissa, Dreegsau, oida Daggl, Dreegschleida, Schwobnsäckle, Beißn.</p>\n<p>Asphaltwanzn, Zwedschgnmanndl, Hopfastanga, gscherte Nuss, Saufbeitl, oida Daddara, Vieh mit Haxn, Bruinschlanga, Daamaluudscha, Bierdimpfl, Hundsbua, oida Daggl, Kirchalicht, Doafdrottl, gscheate Ruam, schiache Goaß, Schuibuamtratza, Zwedschgarl, oide Schäwan.</p>\n<p>Herrgoddsacklzementfixlujja, Voiksdepp, Hopfastanga, Hundsgribbe, Schdeckalfisch, Chaotngschwerl, ja, wo samma denn, Hoibschaariga, Hundsbua, Frichdal, glei fangst a boa!</p>\n<p>Du ogsoachte, aus’gschammta, Auftaklta, kropfata Hamml, klebrigs Biaschal, Beißn, Ruaßnosn, Honigscheißa, eigschnabbda, Ecknsteha, Freibialädschn, du saudamischa, Hockableiba, Aufschneida, Saubreiß, japanischer, hoit’s Mei, Saubreiß, Badwaschl, Kasberlkopf.</p>\n<p>Saubreiß, Geizgroogn, Erzdepp, Rotzgloggn, Radlfahra, glei fangst a boa, Eisackla, Aff, Grawurgl, Haumdaucha, Schachtlhuba, Bauantrampl, Schlawina, schiache Goaß, depperta Doafdebb, Asphaltwanzn, hoid dei Babbn, Schdeckalfisch, Hemmadbiesla, halbseidener, Aufmüpfiga, Voiksdepp, Gibskobf, Kasberlkopf.<br>\nFlegel, Kamejtreiba, glei foid da Wadschnbam um, schdaubiga Bruada, Oaschgsicht, greißlicha Uhu, oida Daddara!</p>\n',
   NULL),
  (2, 4,
   '<p>Woibbadinga damischa owe gwihss Sauwedda ded Charivari dei heid gfoids ma sagrisch guad. Maßkruag wo hi mim Radl foahn Landla Leonhardifahrt, Radler. Ohrwaschl und glei wirds no fui lustiga Spotzerl Fünferl, so auf gehds beim Schichtl do legst di nieda ned Biawambn Breihaus. I mechad dee Schwoanshaxn ghupft wia gsprunga measi gschmeidig hawadere midananda vui huift vui Biawambn, des wiad a Mordsgaudi is. Biaschlegl soi oans, zwoa, gsuffa Oachkatzlschwoaf hod Wiesn.</p>\n<p>Oamoi großherzig Mamalad, liberalitas Bavariae hoggd! Nimmds helfgod im Beidl des basd scho i hob di liab. A Prosit der Gmiadlichkeit midanand mim obandln do mim Radl foahn, Jodler. Ned woar Brotzeit Brotzeit gwihss eana Gidarn. Foidweg Spuiratz kimmt, um Godds wujn. Am acht’n Tag schuf Gott des Bia i sog ja nix, i red ja bloß jedza, Biakriagal a bissal wos gehd ollaweil. Ledahosn om auf’n Gipfe Servas des wiad a Mordsgaudi, griasd eich midnand Bladl Fünferl Gams.</p>\n<p>Leonhardifahrt ma da middn. Greichats an naa do. Af Schuabladdla Leonhardifahrt Marei, des um Godds wujn Biakriagal! Hallelujah sog i, luja schüds nei koa des is schee jedza hogg di hera dringma aweng Spezi nia Musi. Wurschtsolod jo mei is des schee gor Ramasuri ozapfa no gfreit mi i hob di liab auffi, Schbozal. Hogg di hera nia need Biakriagal so schee, Schdarmbeaga See.</p>\n',
   NULL),
  (2, 5, '', NULL);

INSERT INTO `motionSupporter` (`id`, `motionId`, `position`, `userId`, `role`, `comment`, `personType`, `name`, `organization`, `resolutionDate`, `contactEmail`, `contactPhone`)
VALUES
  (2, 2, 0, 1, 'initiates', NULL, 0, 'HoesslTo', '', NULL, 'tobias@hoessl.eu', NULL);


SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
