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
   0, '', '2015-05-22 21:46:34', NULL, 3, '', NULL, 0),
  (2, 3, 'Ä1', '', '', '<p>Um das ganze mal zu testen.</p>\n<p>Links<a href="https://www.antragsgruen.de">https://www.antragsgruen.de</a></p>\n', 0, '', '2015-07-06 00:14:41', NULL, 3, '', NULL, 0),
  (3, 2, 'Ä2', '', '', '<p>Diff-Test</p>\n', 0, '', '2015-07-07 03:49:25', NULL, 3, '', NULL, 0),
  (4, 5, 'F-01-004', '', '', '', 0, '', '2015-03-12 14:43:00', NULL, -2, '', '', 0),
  (5, 10, NULL, '', '', '', 0, '', '2015-03-21 12:58:52', NULL, -2, '', NULL, 0),
  (6, 10, NULL, '', '', '', 0, '', '2015-04-10 06:17:00', NULL, -2, '', '', 0),
  (7, 10, NULL, '', '', '', 0, '', '2015-04-10 06:17:47', NULL, -2, '', NULL, 0),
  (8, 10, NULL, '', '', '', 0, '', '2015-04-11 07:12:55', NULL, -2, '', NULL, 0),
  (9, 10, NULL, '', '', '', 0, '', '2015-04-11 07:13:15', NULL, -2, '', NULL, 0),
  (10, 10, 'Z-01-115-1', '', '', '', 0, '', '2015-04-21 07:26:00', NULL, 3, '', '', 0),
  (11, 10, NULL, '', '', '', 0, '', '2015-04-21 07:29:00', NULL, -2, '', '', 0),
  (12, 10, NULL, '', '', '', 0, '', '2015-04-21 07:32:00', NULL, -2, '', '', 0),
  (13, 10, 'Z-01-119-1', '', '', '', 0, '', '2015-04-21 07:39:00', NULL, 3, '', '', 0),
  (14, 10, 'Z-01-033-1', '', '', '', 0, '', '2015-04-22 14:19:44', NULL, 3, '', NULL, 0),
  (15, 10, 'Z-01-274-1', '', '', '', 0, '', '2015-04-22 14:20:00', NULL, 3, '', '', 0),
  (16, 10, 'Z-01-040-1', '', '', '', 0, '', '2015-04-22 14:22:00', NULL, 3, '', '', 0),
  (17, 10, 'Z-01-274-1', '', '', '', 0, '', '2015-04-22 14:23:31', NULL, -2, '', NULL, 0),
  (18, 10, 'Z-01-262-1', '', '', '', 0, '', '2015-04-22 14:25:48', NULL, 3, '', NULL, 0),
  (19, 10, 'Z-01-041-1', '', '', '', 0, '', '2015-04-22 14:26:26', NULL, 3, '', NULL, 0),
  (20, 10, 'Z-01-255-1', '', '', '', 0, '', '2015-04-22 14:29:00', NULL, -2, '', '', 0),
  (21, 10, NULL, '', '', '', 0, '', '2015-04-22 14:30:20', NULL, -2, '', NULL, 0),
  (22, 10, 'Z-01-233-1', '', '', '', 0, '', '2015-04-22 14:31:45', NULL, 3, '', NULL, 0),
  (23, 10, 'Z-01-076-1', '', '', '', 0, '', '2015-04-22 14:33:33', NULL, 3, '', NULL, 0),
  (24, 10, 'Z-01-097-1', '', '', '', 0, '', '2015-04-22 14:35:47', NULL, 3, '', NULL, 0),
  (25, 10, 'Z-01-146-1', '', '', '', 0, '', '2015-04-22 14:37:42', NULL, 3, '', NULL, 0),
  (26, 10, 'Z-01-255-1', '', '', '', 0, '', '2015-04-22 14:39:00', NULL, 3, '', '', 0),
  (27, 10, 'Z-01-159-1', '', '', '', 0, '', '2015-04-22 14:39:42', NULL, 3, '', NULL, 0),
  (28, 10, 'Z-01-015-1', '', '', '', 0, '', '2015-04-22 14:39:49', NULL, 3, '', NULL, 0),
  (29, 10, 'Z-01-097-2', '', '', '', 0, '', '2015-04-22 14:42:49', NULL, 3, '', NULL, 0),
  (30, 10, 'Z-01-171-1', '', '', '', 0, '', '2015-04-22 14:45:00', NULL, -2, '', '', 0),
  (31, 10, 'Z-01-171-1', '', '', '', 0, '', '2015-04-22 14:47:15', NULL, 3, '', NULL, 0),
  (32, 10, 'Z-01-180-1', '', '', '', 0, '', '2015-04-22 14:49:49', NULL, 3, '', NULL, 0),
  (33, 10, 'Z-01-194-1', '', '', '', 0, '', '2015-04-22 14:51:58', NULL, 3, '', NULL, 0),
  (34, 10, 'Z-01-194-2', '', '', '', 0, '', '2015-04-22 19:54:05', NULL, 3, '', NULL, 0),
  (35, 10, 'Z-01-009-1', '', '', '', 0, '', '2015-04-22 20:11:00', NULL, 3, '', '', 0),
  (36, 10, NULL, '', '', '', 0, '', '2015-04-22 20:13:07', NULL, -2, '', NULL, 0),
  (37, 10, 'Z-01-016-1', '', '', '', 0, '', '2015-04-22 20:15:58', NULL, 3, '', NULL, 0),
  (38, 10, 'Z-01-023-1', '', '', '', 0, '', '2015-04-22 20:17:57', NULL, 3, '', NULL, 0),
  (39, 10, 'Z-01-041-2', '', '', '', 0, '', '2015-04-22 20:19:26', NULL, 3, '', NULL, 0),
  (40, 10, 'Z-01-064-1', '', '', '', 0, '', '2015-04-22 20:20:56', NULL, 3, '', NULL, 0),
  (41, 10, 'Z-01-069-1', '', '', '', 0, '', '2015-04-22 20:22:58', NULL, 3, '', NULL, 0),
  (42, 10, 'Z-01-252-1', '', '', '', 0, '', '2015-04-22 20:25:43', NULL, 3, '', NULL, 0),
  (43, 10, 'Z-01-110-1', '', '', '', 0, '', '2015-04-22 20:29:19', NULL, 3, '', NULL, 0),
  (44, 10, 'Z-01-121-1', '', '', '', 0, '', '2015-04-22 20:30:30', NULL, 3, '', NULL, 0),
  (45, 10, 'Z-01-134-1', '', '', '', 0, '', '2015-04-22 20:32:29', NULL, 3, '', NULL, 0),
  (46, 10, 'Z-01-201-1', '', '', '', 0, '', '2015-04-22 20:35:30', NULL, 3, '', NULL, 0),
  (47, 10, 'Z-01-208-1', '', '', '', 0, '', '2015-04-22 20:58:00', NULL, 3, '', '', 0),
  (48, 10, 'Z-01-214-1', '', '', '', 0, '', '2015-04-22 21:02:00', NULL, 3, '', '', 0),
  (49, 10, 'Z-01-224-1', '', '', '', 0, '', '2015-04-22 21:06:00', NULL, 3, '', '', 0),
  (50, 10, 'Z-01-274-2', '', '', '', 0, '', '2015-04-22 21:07:00', NULL, 3, '', '', 0),
  (51, 10, 'Z-01-252-2', '', '', '', 0, '', '2015-04-22 21:26:00', NULL, 3, '', '', 0),
  (52, 10, 'Z-01-087-1', '', '', '', 0, '', '2015-04-22 21:32:00', NULL, 3, '', '', 0),
  (53, 10, 'Z-01-148-1', '', '', '', 0, '', '2015-04-22 21:37:00', NULL, 3, '', '', 0),
  (54, 10, 'Z-01-208-2', '', '', '', 0, '', '2015-04-22 21:46:00', NULL, 3, '', '', 0),
  (55, 10, 'Z-01-131-1', '', '', '', 0, '', '2015-04-23 05:56:00', NULL, 3, '', '', 0),
  (56, 10, 'Z-01-136-1', '', '', '', 0, '', '2015-04-23 06:01:00', NULL, 3, '', '', 0),
  (57, 10, NULL, '', '', '', 0, '', '2015-05-07 06:33:27', NULL, -2, '', NULL, 0),
  (58, 10, NULL, '', '', '', 0, '', '2015-07-09 13:57:00', NULL, 2, '', '', 0),
  (59, 38, 'W-01-128-1', '', '', '', 0, '', '2015-04-22 14:14:30', NULL, 3, '', NULL, 0),
  (60, 38, 'W-01-428-1', '', '', '', 0, '', '2015-04-22 14:19:12', NULL, 3, '', NULL, 0),
  (61, 38, 'W-01-443-1', '', '', '', 0, '', '2015-04-22 14:22:10', NULL, 3, '', NULL, 0),
  (62, 38, 'W-01-224-1', '', '', '', 0, '', '2015-04-22 14:30:59', NULL, -2, '', NULL, 0),
  (63, 38, 'W-01-302-1', '', '', '', 0, '', '2015-04-22 14:33:26', NULL, 3, '', NULL, 0),
  (64, 38, 'W-01-312-1', '', '', '', 0, '', '2015-04-22 14:38:53', NULL, 3, '', NULL, 0),
  (65, 38, 'W-01-093-1', '', '', '', 0, '', '2015-04-22 14:43:03', NULL, 3, '', NULL, 0),
  (66, 38, 'W-01-099-1', '', '', '', 0, '', '2015-04-22 14:46:19', NULL, 3, '', NULL, 0),
  (67, 38, 'W-01-041-1', '', '', '', 0, '', '2015-04-22 15:10:00', NULL, 3, '', '', 0),
  (68, 38, 'W-01-047-1', '', '', '', 0, '', '2015-04-22 15:12:56', NULL, 3, '', NULL, 0),
  (69, 38, 'W-01-056-1', '', '', '', 0, '', '2015-04-22 15:16:00', NULL, 3, '', '', 0),
  (70, 38, 'W-01-083-1', '', '', '', 0, '', '2015-04-22 15:20:00', NULL, 3, '', '', 0),
  (71, 38, 'W-01-122-1', '', '', '', 0, '', '2015-04-22 15:24:22', NULL, 3, '', NULL, 0),
  (72, 38, 'W-01-206-1', '', '', '', 0, '', '2015-04-22 15:26:59', NULL, 3, '', NULL, 0),
  (73, 38, 'W-01-355-1', '', '', '', 0, '', '2015-04-22 15:33:00', NULL, 3, '', '', 0),
  (74, 38, 'W-01-437-1', '', '', '', 0, '', '2015-04-22 15:38:19', NULL, 3, '', NULL, 0),
  (75, 38, 'W-01-449-1', '', '', '', 0, '', '2015-04-22 18:29:00', NULL, 3, '', '', 0),
  (76, 38, 'W-01-002-1', '', '', '', 0, '', '2015-04-22 19:11:00', NULL, 3, '', 'Theresa ist alleine nicht antragsberechtigt', 0),
  (77, 38, 'W-01-008-1', '', '', '', 0, '', '2015-04-22 19:14:00', NULL, 3, '', '', 0),
  (78, 38, 'W-01-007-1', '', '', '', 0, '', '2015-04-22 19:15:00', NULL, 3, '', '', 0),
  (79, 38, 'W-01-014-2', '', '', '', 0, '', '2015-04-22 19:16:00', NULL, 3, '', '', 0),
  (80, 38, 'W-01-020-1', '', '', '', 0, '', '2015-04-22 19:18:00', NULL, 3, '', '', 0),
  (81, 38, 'W-01-048-1', '', '', '', 0, '', '2015-04-22 19:22:00', NULL, 3, '', '', 0),
  (82, 38, 'W-01-059-1', '', '', '', 0, '', '2015-04-22 19:24:00', NULL, 3, '', '', 0),
  (83, 38, 'W-01-095-2', '', '', '', 0, '', '2015-04-22 19:24:00', NULL, 3, '', '', 0),
  (84, 38, 'W-01-060-2', '', '', '', 0, '', '2015-04-22 19:28:00', NULL, 3, '', '', 0),
  (85, 38, 'W-01-076-1', '', '', '', 0, '', '2015-04-22 19:31:00', NULL, 3, '', '', 0),
  (86, 38, 'W-01-082-1', '', '', '', 0, '', '2015-04-22 19:33:00', NULL, 3, '', '', 0),
  (87, 38, 'W-01-103-1', '', '', '', 0, '', '2015-04-22 19:34:00', NULL, 3, '', '', 0),
  (88, 38, 'W-01-104-1', '', '', '', 0, '', '2015-04-22 19:38:00', NULL, 3, '', '', 0),
  (89, 38, NULL, '', '', '', 0, '', '2015-04-22 19:39:00', NULL, -2, '', '', 0),
  (90, 38, 'W-01-111-1', '', '', '', 0, '', '2015-04-22 19:41:00', NULL, 3, '', '', 0),
  (91, 38, 'W-01-121-1', '', '', '', 0, '', '2015-04-22 19:42:00', NULL, 3, '', '', 0),
  (92, 38, 'W-01-130-1', '', '', '', 0, '', '2015-04-22 19:44:00', NULL, 3, '', '', 0),
  (93, 38, 'W-01-141-1', '', '', '', 0, '', '2015-04-22 19:46:00', NULL, 3, '', '', 0),
  (94, 38, 'W-01-209-1', '', '', '', 0, '', '2015-04-22 19:48:00', NULL, 3, '', '', 0),
  (95, 38, 'W-01-247-1', '', '', '', 0, '', '2015-04-22 19:49:00', NULL, 3, '', '', 0),
  (96, 38, 'W-01-249-1', '', '', '', 0, '', '2015-04-22 19:50:00', NULL, 3, '', '', 0),
  (97, 38, 'W-01-253-1', '', '', '', 0, '', '2015-04-22 19:52:00', NULL, 3, '', '', 0),
  (98, 38, 'W-01-280-1', '', '', '', 0, '', '2015-04-22 19:54:00', NULL, 3, '', '', 0),
  (99, 38, 'W-01-296-1', '', '', '', 0, '', '2015-04-22 19:57:00', NULL, 3, '', '', 0),
  (100, 38, 'W-01-378-1', '', '', '', 0, '', '2015-04-22 19:59:00', NULL, 3, '', '', 0),
  (101, 38, 'W-01-420-1', '', '', '', 0, '', '2015-04-22 20:00:00', NULL, 3, '', '', 0),
  (102, 38, NULL, '', '', '', 0, '', '2015-04-22 20:01:00', NULL, -2, '', '', 0),
  (103, 38, 'W-01-427-1', '', '', '', 0, '', '2015-04-22 20:02:00', NULL, 3, '', '', 0),
  (104, 38, 'W-01-453-1', '', '', '', 0, '', '2015-04-22 20:04:00', NULL, 3, '', '', 0),
  (105, 38, 'W-01-471-1', '', '', '', 0, '', '2015-04-22 20:05:00', NULL, 3, '', '', 0),
  (106, 38, 'W-01-031-1', '', '', '', 0, '', '2015-04-22 20:18:00', NULL, 3, '', '', 0),
  (107, 38, 'W-01-060-1', '', '', '', 0, '', '2015-04-22 20:54:01', NULL, 3, '', NULL, 0),
  (108, 38, 'W-01-034-1', '', '', '', 0, '', '2015-04-22 21:56:00', NULL, 3, '', '', 0),
  (109, 38, 'W-01-036-2', '', '', '', 0, '', '2015-04-22 21:58:00', NULL, 3, '', '', 0),
  (110, 38, 'W-01-014-1', '', '', '', 0, '', '2015-04-23 05:20:00', NULL, 3, '', '', 0),
  (111, 38, 'W-01-030-1', '', '', '', 0, '', '2015-04-23 05:30:03', NULL, 3, '', NULL, 0),
  (112, 38, 'W-01-036-1', '', '', '', 0, '', '2015-04-23 05:32:20', NULL, 3, '', NULL, 0),
  (113, 38, 'W-01-047-2', '', '', '', 0, '', '2015-04-23 05:34:10', NULL, 3, '', NULL, 0),
  (114, 38, 'W-01-053-1', '', '', '', 0, '', '2015-04-23 05:43:53', NULL, 3, '', NULL, 0),
  (115, 38, 'W-01-095-1', '', '', '', 0, '', '2015-04-23 05:46:53', NULL, 3, '', NULL, 0),
  (116, 38, 'W-01-142-1', '', '', '', 0, '', '2015-04-23 05:48:31', NULL, 3, '', NULL, 0),
  (117, 38, 'W-01-426-1', '', '', '', 0, '', '2015-04-23 05:51:00', NULL, 3, '', '', 0),
  (118, 38, 'W-01-142-2', '', '', '', 0, '', '2015-04-23 07:33:00', NULL, 3, '', '', 0),
  (119, 38, 'W-01-224-1', '', '', '', 0, '', '2015-04-23 09:11:00', NULL, 3, '', '', 0),
  (120, 44, NULL, '', '', '', 0, '', '2015-04-20 12:39:00', NULL, 1, '', '', 0),
  (121, 44, 'V-03-NEU', '', '', '', 0, '', '2015-04-22 21:45:00', NULL, 3, '', '', 0),
  (122, 45, NULL, '', '', '', 0, '', '2015-04-22 13:07:00', NULL, -2, '', '', 0),
  (123, 45, 'V-01-048-1', '', '', '', 0, '', '2015-04-22 13:37:00', NULL, 3, '', '', 0),
  (124, 45, 'V-01-007-1', '', '', '', 0, '', '2015-04-22 13:42:00', NULL, 3, '', '', 0),
  (125, 45, 'V-01-037-1', '', '', '', 0, '', '2015-04-22 13:43:00', NULL, 3, '', '', 0),
  (126, 45, NULL, '', '', '', 0, '', '2015-04-22 13:45:14', NULL, -2, '', NULL, 0),
  (127, 45, NULL, '', '', '', 0, '', '2015-04-22 13:53:09', NULL, -2, '', NULL, 0),
  (128, 45, NULL, '', '', '', 0, '', '2015-04-22 13:57:36', NULL, -2, '', NULL, 0),
  (129, 46, NULL, '', '', '', 0, '', '2015-04-20 12:35:00', NULL, 1, '', '', 0),
  (130, 46, 'V-02-034-1', '', '', '', 0, '', '2015-04-22 21:33:00', NULL, 3, '', '', 0),
  (131, 46, 'V-02-045-2', '', '', '', 0, '', '2015-04-22 21:51:00', NULL, 3, '', '', 0),
  (132, 46, 'V-02-045-2', '', '', '', 0, '', '2015-04-22 21:52:00', NULL, -2, '', '', 0),
  (133, 46, 'V-02-032-1', '', '', '', 0, '', '2015-04-23 06:12:00', NULL, 3, '', '', 0),
  (134, 46, 'V-02-045-1', '', '', '', 0, '', '2015-04-23 06:15:00', NULL, 3, '', '', 0),
  (135, 48, 'V-05-061-1', '', '', '', 0, '', '2015-04-22 21:36:00', NULL, 3, '', '', 0),
  (136, 52, NULL, '', '', '', 0, '', '2015-04-20 07:08:11', NULL, -2, '', NULL, 0);

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
  (3, 3, 0, 1, 'initiates', NULL, 0, 'Testadmin', '', NULL, 'testadmin@example.org', ''),
  (4, 23, 0, NULL, 'initiates', NULL, NULL, 'Tobias Hößl', '', NULL, NULL, NULL),
  (5, 24, 0, NULL, 'initiates', NULL, NULL, 'Tobias Hößl', '', NULL, NULL, NULL),
  (6, 35, 0, NULL, 'initiates', NULL, NULL, 'LAK Wirtschaft und Finanzen', NULL, NULL, NULL, NULL),
  (7, 36, 0, NULL, 'initiates', NULL, NULL, 'klaus', NULL, NULL, NULL, NULL),
  (8, 37, 0, NULL, 'initiates', NULL, NULL, 'Stefan', NULL, NULL, NULL, NULL),
  (9, 38, 0, NULL, 'initiates', NULL, NULL, 'Cosima', NULL, NULL, NULL, NULL),
  (10, 39, 0, NULL, 'initiates', NULL, NULL, 'Paula', NULL, NULL, NULL, NULL),
  (11, 40, 0, NULL, 'initiates', NULL, NULL, 'Landesvorstand', '', NULL, NULL, NULL),
  (12, 41, 0, NULL, 'initiates', NULL, NULL, 'Landesvorstand', '', NULL, NULL, NULL),
  (13, 42, 0, NULL, 'initiates', NULL, NULL, 'Katha', NULL, NULL, NULL, NULL),
  (14, 43, 0, NULL, 'initiates', NULL, NULL, 'Katha', NULL, NULL, NULL, NULL),
  (15, 44, 0, NULL, 'initiates', NULL, NULL, 'Tobias Hößl', '', NULL, NULL, NULL),
  (16, 45, 0, NULL, 'initiates', NULL, NULL, 'Tobias Hößl', '', NULL, NULL, NULL),
  (17, 46, 0, NULL, 'initiates', NULL, NULL, 'Tobias Hößl', '', NULL, NULL, NULL),
  (18, 47, 0, NULL, 'initiates', NULL, NULL, 'Tobias Hößl', '', NULL, NULL, NULL),
  (19, 48, 0, NULL, 'initiates', NULL, NULL, 'Tobias Hößl', '', NULL, NULL, NULL),
  (20, 49, 0, NULL, 'initiates', NULL, NULL, 'Tobias Hößl', '', NULL, NULL, NULL),
  (21, 50, 0, NULL, 'initiates', NULL, NULL, 'Tobias Hößl', '', NULL, NULL, NULL),
  (22, 51, 0, NULL, 'initiates', NULL, NULL, 'Frieda', NULL, NULL, NULL, NULL),
  (23, 52, 0, NULL, 'initiates', NULL, NULL, 'xxx', NULL, NULL, NULL, NULL),
  (24, 53, 0, NULL, 'initiates', NULL, NULL, 'Max', NULL, NULL, NULL, NULL),
  (25, 54, 0, NULL, 'initiates', NULL, NULL, 'Tobias Hößl', '', NULL, NULL, NULL),
  (26, 55, 0, NULL, 'initiates', NULL, NULL, 'Tobias Hößl', '', NULL, NULL, NULL),
  (27, 56, 0, NULL, 'initiates', NULL, NULL, 'Horst', NULL, NULL, NULL, NULL),
  (28, 58, 0, NULL, 'initiates', NULL, NULL, 'Wolfgang Leitner', NULL, NULL, NULL, NULL),
  (29, 58, 0, NULL, 'likes', NULL, NULL, 'ml', NULL, NULL, NULL, NULL),
  (30, 58, 0, NULL, 'likes', NULL, NULL, 'Heidi Terpoorten', NULL, NULL, NULL, NULL),
  (31, 58, 0, NULL, 'likes', NULL, NULL, 'Jörg Wunsch', NULL, NULL, NULL, NULL),
  (32, 59, 0, NULL, 'initiates', NULL, NULL, 'Christian Schneider', NULL, NULL, NULL, NULL),
  (33, 60, 0, NULL, 'initiates', NULL, NULL, 'RappBene', NULL, NULL, NULL, NULL),
  (34, 60, 0, NULL, 'likes', NULL, NULL, 'Heidi Terpoorten', NULL, NULL, NULL, NULL),
  (35, 62, 0, NULL, 'initiates', NULL, NULL, 'Christian Schneider', NULL, NULL, NULL, NULL),
  (36, 63, 0, NULL, 'initiates', NULL, NULL, 'Christian Schneider', NULL, NULL, NULL, NULL),
  (37, 64, 0, NULL, 'initiates', NULL, NULL, 'Irmgard Lichtinger ', NULL, NULL, NULL, NULL),
  (38, 64, 0, NULL, 'likes', NULL, NULL, 'Heidi Terpoorten', NULL, NULL, NULL, NULL),
  (39, 65, 0, NULL, 'initiates', NULL, NULL, 'Tobias', NULL, NULL, NULL, NULL),
  (40, 66, 0, NULL, 'initiates', NULL, NULL, 'Tobias Eichelbrönner', NULL, NULL, NULL, NULL),
  (41, 68, 0, NULL, 'initiates', NULL, NULL, 'Gerald Hofmann', NULL, NULL, NULL, NULL),
  (42, 69, 0, NULL, 'initiates', NULL, NULL, 'Gerald Hofmann', NULL, NULL, NULL, NULL),
  (43, 70, 0, NULL, 'initiates', NULL, NULL, 'Gerald Hofmann', NULL, NULL, NULL, NULL),
  (44, 71, 0, NULL, 'initiates', NULL, NULL, 'Maximilian Deisenhofer', NULL, NULL, NULL, NULL),
  (45, 71, 0, NULL, 'likes', NULL, NULL, 'Ducks', NULL, NULL, NULL, NULL),
  (46, 72, 0, NULL, 'initiates', NULL, NULL, 'Christian Schneider', NULL, NULL, NULL, NULL),
  (47, 73, 0, NULL, 'initiates', NULL, NULL, 'Christian Schneider', NULL, NULL, NULL, NULL),
  (48, 74, 0, NULL, 'initiates', NULL, NULL, 'Christian Schneider', NULL, NULL, NULL, NULL),
  (49, 75, 0, NULL, 'initiates', NULL, NULL, 'Christian Schneider', NULL, NULL, NULL, NULL),
  (50, 76, 0, NULL, 'initiates', NULL, NULL, 'Christian Schneider', NULL, NULL, NULL, NULL),
  (51, 76, 0, NULL, 'likes', NULL, NULL, 'Michael Mittag', NULL, NULL, NULL, NULL),
  (52, 77, 0, NULL, 'initiates', NULL, NULL, 'Katha', NULL, NULL, NULL, NULL),
  (53, 77, 0, NULL, 'likes', NULL, NULL, 'Heidi Terpoorten', NULL, NULL, NULL, NULL),
  (54, 78, 0, NULL, 'initiates', NULL, NULL, 'Dennis Lassiter', NULL, NULL, NULL, NULL),
  (55, 78, 0, NULL, 'likes', NULL, NULL, 'DennisLassiter', NULL, NULL, NULL, NULL),
  (56, 78, 0, NULL, 'likes', NULL, NULL, 'Andreas', NULL, NULL, NULL, NULL),
  (57, 78, 0, NULL, 'likes', NULL, NULL, 'gruenpohl', NULL, NULL, NULL, NULL),
  (58, 79, 0, NULL, 'initiates', NULL, NULL, 'DennisLassiter', NULL, NULL, NULL, NULL),
  (59, 79, 0, NULL, 'likes', NULL, NULL, 'gruenpohl', NULL, NULL, NULL, NULL),
  (60, 80, 0, NULL, 'initiates', NULL, NULL, 'Heidi Terpoorten', NULL, NULL, NULL, NULL),
  (61, 81, 0, NULL, 'likes', NULL, NULL, 'DennisLassiter', NULL, NULL, NULL, NULL),
  (62, 81, 0, NULL, 'initiates', NULL, NULL, 'DennisLassiter', NULL, NULL, NULL, NULL),
  (63, 81, 0, NULL, 'dislikes', NULL, NULL, 'HuMa', NULL, NULL, NULL, NULL),
  (64, 81, 0, NULL, 'likes', NULL, NULL, 'Andreas', NULL, NULL, NULL, NULL),
  (65, 82, 0, NULL, 'initiates', NULL, NULL, 'Marc Daniel Heintz', NULL, NULL, NULL, NULL),
  (66, 83, 0, NULL, 'initiates', NULL, NULL, 'Marc Daniel Heintz', NULL, NULL, NULL, NULL),
  (67, 84, 0, NULL, 'initiates', NULL, NULL, 'Wolfgang Schwimmer', NULL, NULL, NULL, NULL),
  (68, 85, 0, NULL, 'initiates', NULL, NULL, 'Jens Eichler', NULL, NULL, NULL, NULL),
  (69, 86, 0, NULL, 'initiates', NULL, NULL, 'Jens Eichler', NULL, NULL, NULL, NULL),
  (70, 87, 0, NULL, 'initiates', NULL, NULL, 'LAK HFT', NULL, NULL, NULL, NULL),
  (71, 88, 0, NULL, 'initiates', NULL, NULL, 'Maximilian Rumler', NULL, NULL, NULL, NULL),
  (72, 89, 0, NULL, 'initiates', NULL, NULL, 'Maximilian Rumler', NULL, NULL, NULL, NULL),
  (73, 89, 0, NULL, 'likes', NULL, NULL, 'HuMa', NULL, NULL, NULL, NULL),
  (74, 90, 0, NULL, 'initiates', NULL, NULL, 'Philipp Steffen', NULL, NULL, NULL, NULL),
  (75, 91, 0, NULL, 'initiates', NULL, NULL, 'Philipp Steffen', NULL, NULL, NULL, NULL),
  (76, 92, 0, NULL, 'initiates', NULL, NULL, 'Philipp Steffen', NULL, NULL, NULL, NULL),
  (77, 93, 0, NULL, 'initiates', NULL, NULL, 'Philipp Steffen', NULL, NULL, NULL, NULL),
  (78, 93, 0, NULL, 'likes', NULL, NULL, 'SonjaSchuhmacher', NULL, NULL, NULL, NULL),
  (79, 94, 0, NULL, 'initiates', NULL, NULL, 'p.stuerzenberger@googlemail.com', NULL, NULL, NULL, NULL),
  (80, 95, 0, NULL, 'initiates', NULL, NULL, 'Christian Hierneis', NULL, NULL, NULL, NULL),
  (81, 95, 0, NULL, 'likes', NULL, NULL, 'Heidi Terpoorten', NULL, NULL, NULL, NULL),
  (82, 96, 0, NULL, 'initiates', NULL, NULL, 'Michael', NULL, NULL, NULL, NULL),
  (83, 97, 0, NULL, 'initiates', NULL, NULL, 'Irmgard Lichtinger ', NULL, NULL, NULL, NULL),
  (84, 98, 0, NULL, 'initiates', NULL, NULL, 'Bernhard Zimmer', NULL, NULL, NULL, NULL),
  (85, 99, 0, NULL, 'initiates', NULL, NULL, 'Bernhard Zimmer', NULL, NULL, NULL, NULL),
  (86, 100, 0, NULL, 'initiates', NULL, NULL, 'Bernhard Zimmer', NULL, NULL, NULL, NULL),
  (87, 101, 0, NULL, 'initiates', NULL, NULL, 'Marie-Luise Thierauf', NULL, NULL, NULL, NULL),
  (88, 101, 0, NULL, 'likes', NULL, NULL, 'Heidi Terpoorten', NULL, NULL, NULL, NULL),
  (89, 102, 0, NULL, 'initiates', NULL, NULL, 'Bernhard Zimmer', NULL, NULL, NULL, NULL),
  (90, 102, 0, NULL, 'likes', NULL, NULL, 'Heidi Terpoorten', NULL, NULL, NULL, NULL),
  (91, 102, 0, NULL, 'likes', NULL, NULL, 'Chris', NULL, NULL, NULL, NULL),
  (92, 103, 0, NULL, 'initiates', NULL, NULL, 'Bernhard Zimmer', NULL, NULL, NULL, NULL),
  (93, 103, 0, NULL, 'likes', NULL, NULL, 'Heidi Terpoorten', NULL, NULL, NULL, NULL),
  (94, 104, 0, NULL, 'initiates', NULL, NULL, 'Bernhard Zimmer', NULL, NULL, NULL, NULL),
  (95, 104, 0, NULL, 'likes', NULL, NULL, 'Heidi Terpoorten', NULL, NULL, NULL, NULL),
  (96, 105, 0, NULL, 'initiates', NULL, NULL, 'Bernhard Zimmer', NULL, NULL, NULL, NULL),
  (97, 105, 0, NULL, 'likes', NULL, NULL, 'Stephan', NULL, NULL, NULL, NULL),
  (98, 106, 0, NULL, 'initiates', NULL, NULL, 'Bernhard Zimmer', NULL, NULL, NULL, NULL),
  (99, 107, 0, NULL, 'initiates', NULL, NULL, 'Werner', NULL, NULL, NULL, NULL),
  (100, 107, 0, NULL, 'likes', NULL, NULL, 'Ducks', NULL, NULL, NULL, NULL),
  (101, 108, 0, NULL, 'initiates', NULL, NULL, 'Werner', NULL, NULL, NULL, NULL),
  (102, 109, 0, NULL, 'initiates', NULL, NULL, 'Simon Pflanz', NULL, NULL, NULL, NULL),
  (103, 110, 0, NULL, 'initiates', NULL, NULL, 'Jörg Wunsch', NULL, NULL, NULL, NULL),
  (104, 111, 0, NULL, 'initiates', NULL, NULL, 'Jörg Wunsch', NULL, NULL, NULL, NULL),
  (105, 112, 0, NULL, 'initiates', NULL, NULL, 'Jörg Wunsch', NULL, NULL, NULL, NULL),
  (106, 113, 0, NULL, 'initiates', NULL, NULL, 'Jörg Wunsch', NULL, NULL, NULL, NULL),
  (107, 114, 0, NULL, 'initiates', NULL, NULL, 'Jörg Wunsch', NULL, NULL, NULL, NULL),
  (108, 114, 0, NULL, 'likes', NULL, NULL, 'Michael Mittag', NULL, NULL, NULL, NULL),
  (109, 115, 0, NULL, 'initiates', NULL, NULL, 'Hoebusch', NULL, NULL, NULL, NULL),
  (110, 116, 0, NULL, 'initiates', NULL, NULL, 'Hoebusch', NULL, NULL, NULL, NULL),
  (111, 117, 0, NULL, 'initiates', NULL, NULL, 'Hoebusch', NULL, NULL, NULL, NULL),
  (112, 117, 0, NULL, 'likes', NULL, NULL, 'HuMa', NULL, NULL, NULL, NULL),
  (113, 118, 0, NULL, 'initiates', NULL, NULL, 'Hoebusch', NULL, NULL, NULL, NULL),
  (114, 119, 0, NULL, 'initiates', NULL, NULL, 'Hoebusch', NULL, NULL, NULL, NULL),
  (115, 120, 0, NULL, 'initiates', NULL, NULL, 'Hoebusch', NULL, NULL, NULL, NULL),
  (116, 122, 0, NULL, 'initiates', NULL, NULL, 'Andreas Lösche', NULL, NULL, NULL, NULL),
  (117, 123, 0, NULL, 'initiates', NULL, NULL, 'Erich Hinderer', NULL, NULL, NULL, NULL),
  (118, 123, 0, NULL, 'dislikes', NULL, NULL, 'Andreas', NULL, NULL, NULL, NULL),
  (119, 124, 0, NULL, 'initiates', NULL, NULL, 'Kerstin Täubner-Benicke', NULL, NULL, NULL, NULL),
  (120, 124, 0, NULL, 'likes', NULL, NULL, 'Andreas', NULL, NULL, NULL, NULL),
  (121, 124, 0, NULL, 'likes', NULL, NULL, 'gruenpohl', NULL, NULL, NULL, NULL),
  (122, 125, 0, NULL, 'initiates', NULL, NULL, 'Bernhard Jehle', NULL, NULL, NULL, NULL),
  (123, 126, 0, NULL, 'initiates', NULL, NULL, 'Othmar Heise', NULL, NULL, NULL, NULL),
  (124, 127, 0, NULL, 'initiates', NULL, NULL, 'Othmar Heise', NULL, NULL, NULL, NULL),
  (125, 127, 0, NULL, 'likes', NULL, NULL, 'Ducks', NULL, NULL, NULL, NULL),
  (126, 128, 0, NULL, 'initiates', NULL, NULL, 'Othmar Heise', NULL, NULL, NULL, NULL),
  (127, 129, 0, NULL, 'initiates', NULL, NULL, 'Othmar Heise', NULL, NULL, NULL, NULL),
  (128, 130, 0, NULL, 'initiates', NULL, NULL, 'Othmar Heise', NULL, NULL, NULL, NULL),
  (129, 131, 0, NULL, 'initiates', NULL, NULL, 'AndrejNovak', NULL, NULL, NULL, NULL),
  (130, 132, 0, NULL, 'initiates', NULL, NULL, 'Sebastian Priller', NULL, NULL, NULL, NULL),
  (131, 133, 0, NULL, 'initiates', NULL, NULL, 'Othmar Heise', NULL, NULL, NULL, NULL),
  (132, 134, 0, NULL, 'initiates', NULL, NULL, 'Othmar Heise', NULL, NULL, NULL, NULL),
  (133, 135, 0, NULL, 'initiates', NULL, NULL, 'Othmar Heise', NULL, NULL, NULL, NULL),
  (134, 136, 0, NULL, 'initiates', NULL, NULL, 'Othmar Heise', NULL, NULL, NULL, NULL);

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
   '{"maintainanceMode":false,"screeningMotions":true,"lineNumberingGlobal":false,"commentNeedsEmail":false,"iniatorsMayEdit":false,"adminsMayEdit":true,"confirmEmails":false,"hideRevision":false,"minimalisticUI":false,"showFeeds":true,"commentsSupportable":false,"screeningMotionsShown":false,"screeningAmendments":true,"screeningComments":false,"initiatorsMayReject":false,"commentWholeMotions":false,"allowMultipleTags":false,"allowStrikeFormat":false,"lineLength":95,"startLayoutType":0,"logoUrl":"","logoUrlFB":"","motionIntro":null,"pdfIntroduction":""}'),
  (5, 5, '1laenderrat2015', 3, 'de-parteitag', 'Länderrat', 'Länderrat', NULL, NULL, 0, 'testadmin@example.org',
   '{"maintainanceMode":false,"screeningMotions":true,"lineNumberingGlobal":false,"commentNeedsEmail":false,"iniatorsMayEdit":false,"adminsMayEdit":true,"confirmEmails":false,"hideRevision":false,"minimalisticUI":false,"showFeeds":true,"commentsSupportable":false,"screeningMotionsShown":false,"screeningAmendments":true,"screeningComments":false,"initiatorsMayReject":false,"commentWholeMotions":false,"allowMultipleTags":false,"allowStrikeFormat":false,"lineLength":95,"startLayoutType":0,"logoUrl":null,"logoUrlFB":null,"motionIntro":null,"pdfIntroduction":""}');

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
   '{"minSupporters":19,"supportersHaveOrganizations":true}'),
  (8, 5, 'Antrag', 'Anträge', 'Antrag stellen', '', 0, NULL, 1, 1, NULL, NULL, 4, 4, 4, 0, 2, 1, 1,
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
  (21, 7, 1, 1, 0, 'Antragstext', NULL, 1, 1, 0, 1, 2, 1),
  (22, 7, 1, 2, 0, 'Begründung', NULL, 0, 0, 0, 0, 0, 0),
  (23, 8, 0, 0, 0, 'Titel', NULL, 0, 1, 0, 0, 0, 1),
  (24, 8, 1, 1, 0, 'Antragstext', NULL, 1, 1, 0, 1, 2, 1),
  (25, 8, 1, 2, 0, 'Begründung', NULL, 0, 0, 0, 0, 0, 0);

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
  (1, 'tobias@hoessl.eu', NULL, 3, '=?UTF-8?B?QW50cmFnc2dyw7xuIHYz?= <info@antragsgruen.de>', '2015-05-22 21:46:38',
   'Neuer Antrag',
   'Es wurde ein neuer Änderungsantrag "Ä1 zu A2: O’zapft is!" eingereicht.\nLink: http://stdparteitag.antraege-v3.hoessl.eu/std-parteitag/motion/2/amendment/1'),
  (2, 'tobias@hoessl.eu', NULL, 3, '=?UTF-8?B?QW50cmFnc2dyw7xuIHYz?= <info@antragsgruen.de>', '2015-06-25 21:03:55',
   'Neuer Antrag', 'Es wurde ein neuer Antrag "Textformatierungen" eingereicht.\nLink: /std-parteitag/motion/3'),
  (3, 'tobias@hoessl.eu', NULL, 3, '=?UTF-8?B?QW50cmFnc2dyw7xu?= <EMAILADRESSE>', '2015-07-06 00:21:55', 'Neuer Antrag',
   'Es wurde ein neuer Änderungsantrag "Ä1 zu A3: Textformatierungen" eingereicht.\nLink: http://stdparteitag.antragsgruen-v3.localhost/std-parteitag/motion/3/amendment/2'),
  (4, 'tobias@hoessl.eu', NULL, 3, '=?UTF-8?B?QW50cmFnc2dyw7xu?= <EMAILADRESSE>', '2015-07-07 03:49:33', 'Neuer Antrag',
   'Es wurde ein neuer Änderungsantrag "Ä2 zu A2: O’zapft is!" eingereicht.\nLink: http://stdparteitag.antragsgruen-v3.localhost/std-parteitag/motion/2/amendment/3'),
  (5, 'testadmin@example.org', NULL, 3, '=?UTF-8?B?QW50cmFnc2dyw7xu?= <EMAILADRESSE>', '2015-07-08 04:05:49',
   'Neuer Antrag',
   'Es wurde ein neuer Antrag "Lorem ipsum dolor sit amet" eingereicht.\nLink: http://bdk.antragsgruen-v3.localhost/bdk/motion/4');

--
-- Dumping data for table `motion`
--

INSERT INTO `motion` (`id`, `consultationId`, `motionTypeId`, `parentMotionId`, `agendaItemId`, `title`, `titlePrefix`, `dateCreation`, `dateResolution`, `status`, `statusString`, `noteInternal`, `cache`, `textFixed`)
VALUES
  (2, 1, 1, NULL, NULL, 'O’zapft is!', 'A2', '2015-04-02 09:27:20', NULL, 3, NULL, NULL, '', 0),
  (3, 1, 1, NULL, NULL, 'Textformatierungen', 'A3', '2015-06-25 21:03:49', NULL, 3, NULL, NULL, '', 0),
  (4, 4, 7, NULL, NULL, 'Lorem ipsum dolor sit amet', 'A1', '2015-07-08 04:05:23', NULL, 3, NULL, NULL, '', 0),
  (5, 5, 8, NULL, NULL, 'Vorschlag zur Tagesordnung', 'F-01', '2015-02-19 10:05:00', '2015-02-16 12:00:00', -2, NULL, NULL, '', 0),
  (6, 5, 8, NULL, NULL, 'Änderungsantrag zur Geschäftsordnung des Länderrates (zuletzt geändert am 31.5.2014)', 'F-02', '2015-02-20 09:16:00', NULL, -2, NULL, NULL, '', 0),
  (7, 5, 8, NULL, NULL, 'Änderungsantrag zur Geschäftsordnung des Länderrates (zuletzt geändert am 31.5.2014)', 'F-02', '2015-02-20 09:20:00', NULL, -2, NULL, NULL, '', 0),
  (8, 5, 8, NULL, NULL, 'Antrag zur Änderung der Geschäftsordnung des Länderrates (zuletzt geändert am 31.5.2014)', 'F-01', '2015-03-09 07:34:00', NULL, 3, NULL, NULL, '', 0),
  (9, 5, 8, NULL, NULL, 'Vorschlag zur Tagesordnung', 'T-01', '2015-03-09 07:57:00', NULL, -2, NULL, NULL, '', 0),
  (10, 5, 8, NULL, NULL, 'Grüne Zeitpolitik für ein selbstbestimmtes und solidarisches Leben', 'Z-01', '2015-03-11 09:59:00', NULL, 3, NULL, NULL, '', 0),
  (11, 5, 8, NULL, NULL, 'Mit dem Green New Deal Europas Zukunft gestalten', 'W-01', '2015-03-11 10:01:00', NULL, -2, NULL, NULL, '', 0),
  (12, 5, 8, NULL, NULL, 'Verbesserung der Palliativ/Hospizversorgung und zur Suizidprävention als gemeinsame grüne Positionierung', 'S-01', '2015-03-11 10:03:00', NULL, -2, NULL, NULL, '', 0),
  (13, 5, 8, NULL, NULL, 'sy1', '', '2015-03-11 10:37:15', NULL, -2, NULL, NULL, '', 0),
  (14, 5, 8, NULL, NULL, 'Vorschlag zur Tagesordnung', 'T-01', '2015-03-16 08:25:00', NULL, 3, NULL, NULL, '', 0),
  (15, 5, 8, NULL, NULL, 'Palliativ-Offensive jetzt! ', 'S-01', '2015-03-17 09:44:00', NULL, 4, NULL, NULL, '', 0),
  (16, 5, 8, NULL, NULL, 'Testantrag', 'Test-01', '2015-03-17 14:14:00', NULL, -2, NULL, NULL, '', 0),
  (17, 5, 8, NULL, NULL, 'Antrag zur Änderung der Urabstimmungsordnung - Doppelungen in Satzung und Urabstimmungsordnung streichen', 'U-01', '2015-03-18 07:55:00', NULL, 3, NULL, NULL, '', 0),
  (18, 5, 8, NULL, NULL, 'Antrag zur Änderung der Urabstimmungsordnung - Aussendung der Reader', 'U-02', '2015-03-18 07:56:00', NULL, 3, NULL, NULL, '', 0),
  (19, 5, 8, NULL, NULL, 'Antrag zur Änderung der Urabstimmungsordnung - Ortsverbände streichen', 'U-03', '2015-03-18 07:58:00', NULL, 3, NULL, NULL, '', 0),
  (20, 5, 8, NULL, NULL, 'Antrag zur Änderung der Urabstimmungsordnung - Form der Stellungnahme und der Bewerbungen', 'U-04', '2015-03-18 07:59:00', NULL, 3, NULL, NULL, '', 0),
  (21, 5, 8, NULL, NULL, 'Antrag zur Änderung der Urabstimmungsordnung - Bewerbungsvoraussetzung Urwahl ', 'U-05', '2015-03-18 08:00:00', NULL, 3, NULL, NULL, '', 0),
  (22, 5, 8, NULL, NULL, 'Änderungsanträge zur Urabstimmungsordnung - Eigener Paragraph Urwahl ', 'U-06', '2015-03-18 08:03:00', NULL, -2, NULL, NULL, '', 0),
  (23, 5, 8, NULL, NULL, 'Antrag zur Änderung der Urabstimmungsordnung - Fristen Diskussionsphase und Abstimmungszeit', 'U-07', '2015-03-18 08:04:00', NULL, 3, NULL, NULL, '', 0),
  (24, 5, 8, NULL, NULL, 'Antrag zur Änderung der Urabstimmungsordnung - Ermöglichung der elektronischen Versendung der Urabstimmungsunterlagen', 'U-08', '2015-03-18 08:06:00', NULL, 3, NULL, NULL, '', 0),
  (25, 5, 8, NULL, NULL, 'Antrag zur Änderung der Urabstimmungsordnung - Durchführungsregelung vereinfachen', 'U-09', '2015-03-18 08:07:00', NULL, 3, NULL, NULL, '', 0),
  (26, 5, 8, NULL, NULL, 'Antrag zur Änderung der Urabstimmungsordnung - Einsendeschluss neu definieren und Auszählungszeiten anpassen', 'U-10', '2015-03-18 08:09:00', NULL, 3, NULL, NULL, '', 0),
  (27, 5, 8, NULL, NULL, 'Antrag zur Änderung der Urabstimmungsordnung - Abstimmungszeit verlängern', 'U-11', '2015-03-18 08:10:00', NULL, -2, NULL, NULL, '', 0),
  (28, 5, 8, NULL, NULL, 'Antrag zur Änderung der Urabstimmungsordnung - Auszählungszeiten anpassen', 'U-12', '2015-03-18 08:11:00', NULL, -2, NULL, NULL, '', 0),
  (29, 5, 8, NULL, NULL, 'Antrag zur Änderung der Urabstimmungsordnung - Frist zur Unterlagen Aufbewahrung ', 'U-11', '2015-03-18 08:12:00', NULL, 3, NULL, NULL, '', 0),
  (30, 5, 8, NULL, NULL, 'Antrag zur Änderung der Urabstimmungsordnung - Eigener Paragraph Urwahl', 'U-06', '2015-03-18 13:28:00', NULL, 3, NULL, NULL, '', 0),
  (31, 5, 8, NULL, NULL, 'Testantrag', 'A1', '2015-03-20 14:38:00', NULL, -2, NULL, NULL, '', 0),
  (32, 5, 8, NULL, NULL, 'Test', '', '2015-03-20 15:36:35', NULL, -2, NULL, NULL, '', 0),
  (33, 5, 8, NULL, NULL, 'Test 2', '', '2015-03-20 15:39:15', NULL, -2, NULL, NULL, '', 0),
  (34, 5, 8, NULL, NULL, 'Test Wirtschaftspolitik', 'W-02', '2015-03-20 17:00:00', NULL, -2, NULL, NULL, '', 0),
  (35, 5, 8, NULL, NULL, 'Diskussionspapier zur Debatte über organisierte Sterbehilfe', 'S-ohne Nummer', '2015-03-25 09:51:00', NULL, 3, NULL, NULL, '', 0),
  (36, 5, 8, NULL, NULL, 'Wahl der Delegierten zum Rat der EGP', 'EGP-01', '2015-04-13 10:57:00', NULL, 3, NULL, NULL, '', 0),
  (37, 5, 8, NULL, NULL, '.', 'W-01', '2015-04-13 11:12:00', NULL, -2, NULL, NULL, '', 0),
  (38, 5, 8, NULL, NULL, 'Für Europas Zukunft: Unser Green New Deal', 'W-01', '2015-04-14 13:29:00', NULL, 3, NULL, NULL, '', 0),
  (39, 5, 8, NULL, NULL, 'TESTANTRAG', '', '2015-04-15 13:33:57', NULL, -2, NULL, NULL, '', 0),
  (40, 5, 8, NULL, NULL, 'G7-Gipfel: Kritisch begleiten, verantwortlich handeln', 'V-01', '2015-04-15 15:11:00', '2015-04-13 12:00:00', -2, NULL, NULL, '', 0),
  (41, 5, 8, NULL, NULL, 'Gemeinsam gegen die Klimakrise', 'V-02', '2015-04-15 15:15:00', NULL, -2, NULL, NULL, '', 0),
  (42, 5, 8, NULL, NULL, 'Umbenennung der Bundesarbeitsgemeinschaft (BAG) Nord/Süd in BAG Globale Entwicklung.', 'V-03', '2015-04-15 15:20:00', NULL, -2, NULL, NULL, '', 0),
  (43, 5, 8, NULL, NULL, 'G7-Gipfel: Kritisch begleiten, verantwortlich handeln', 'V-01', '2015-04-15 15:39:00', NULL, -2, NULL, NULL, '', 0),
  (44, 5, 8, NULL, NULL, 'Völkermord an den Armenier_innen anerkennen!', 'V-03', '2015-04-15 19:31:00', NULL, 3, NULL, NULL, '', 0),
  (45, 5, 8, NULL, NULL, 'G7-Gipfel: Kritisch begleiten, verantwortlich handeln', 'V-01', '2015-04-16 05:58:00', NULL, 3, NULL, NULL, '', 0),
  (46, 5, 8, NULL, NULL, 'Gemeinsam gegen die Klimakrise', 'V-02', '2015-04-16 06:02:00', NULL, 3, NULL, NULL, '', 0),
  (47, 5, 8, NULL, NULL, 'Umbenennung der Bundesarbeitsgemeinschaft (BAG) Nord/Süd ', 'V-04', '2015-04-16 06:07:00', NULL, 3, NULL, NULL, '', 0),
  (48, 5, 8, NULL, NULL, 'Jemen: Bombardements stoppen – Friedensgespräche wieder aufnehmen', 'V-05', '2015-04-16 06:16:00', NULL, 3, NULL, NULL, '', 0),
  (49, 5, 8, NULL, NULL, 'Bundesregierung muss nationaler Verantwortung für Flüchtlinge endlich gerecht werden - Länder und Kommunen müssen finanziell entlastet werden!', 'V-06', '2015-04-16 07:02:00', NULL, 3, NULL, NULL, '', 0),
  (50, 5, 8, NULL, NULL, 'Test', '', '2015-04-16 14:17:54', NULL, -2, NULL, NULL, '', 0),
  (51, 5, 8, NULL, NULL, 'Test', '', '2015-04-16 14:18:07', NULL, -2, NULL, NULL, '', 0),
  (52, 5, 8, NULL, NULL, 'Testantrag', '', '2015-04-18 19:31:00', NULL, 1, NULL, NULL, '', 0),
  (53, 5, 8, NULL, NULL, 'Nein zur Wiedereinführung der Vorratsdatenspeicherung', 'V-07-EIL', '2015-04-21 12:15:00',
   NULL, 3, NULL, NULL, '', 0),
  (54, 5, 8, NULL, NULL, 'Jemen: Bombardements Militärische Intervention stoppen – Friedensgespräche wieder aufnehmen',
   'V-03-NEU', '2015-04-23 06:07:00', NULL, -2, NULL, NULL, '', 0),
  (55, 5, 8, NULL, NULL, 'Jemen: Militärische Intervention stoppen – Friedensgespräche aufnehmen', 'V-05-NEU',
   '2015-04-23 07:18:00', NULL, 3, NULL, NULL, '', 0),
  (56, 5, 8, NULL, NULL, 'Seenotrettung jetzt', 'V-08-EIL', '2015-04-23 08:47:00', NULL, 3, NULL, NULL, '', 0),
  (57, 5, 8, NULL, NULL, 'Seenotrettung jetzt', 'V-08-EIL-NEU', '2015-04-24 19:43:00', NULL, 3, NULL, NULL, '', 0);

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
  (23, 4, 18, NULL, 'supports', NULL, 0, 'UnterstützerIn 19', 'Gremium 19', NULL, NULL, NULL),
  (24, 5, 0, NULL, 'initiates', NULL, NULL, 'Tobias Hößl', NULL, NULL, NULL, NULL),
  (25, 5, 0, NULL, 'supports', NULL, NULL, 'LAK Medien-/Netzpolitik', NULL, NULL, NULL, NULL),
  (26, 5, 0, NULL, 'supports', NULL, NULL, 'Tim Osten', NULL, NULL, NULL, NULL),
  (27, 7, 0, NULL, 'initiates', NULL, NULL, 'Tobias Hößl', '', NULL, NULL, NULL),
  (28, 8, 0, NULL, 'initiates', NULL, NULL, 'Tobias Hößl', '', NULL, NULL, NULL),
  (29, 9, 0, NULL, 'initiates', NULL, NULL, 'Tobias Hößl', '', NULL, NULL, NULL),
  (30, 10, 0, NULL, 'initiates', NULL, NULL, 'Tobias Hößl', '', NULL, NULL, NULL),
  (31, 11, 0, NULL, 'initiates', NULL, NULL, 'Nico', NULL, NULL, NULL, NULL),
  (32, 12, 0, NULL, 'initiates', NULL, NULL, 'Fabian Hamák', NULL, NULL, NULL, NULL),
  (33, 12, 0, NULL, 'supports', NULL, NULL, 'LAK Demokratie und Recht', NULL, NULL, NULL, NULL),
  (34, 13, 0, NULL, 'initiates', NULL, NULL, 'Cato', NULL, NULL, NULL, NULL),
  (35, 14, 0, NULL, 'initiates', NULL, NULL, 'Cato-Orga', NULL, NULL, NULL, NULL),
  (36, 15, 0, NULL, 'initiates', NULL, NULL, 'Cato Nr. 3', NULL, NULL, NULL, NULL),
  (37, 16, 0, NULL, 'initiates', NULL, NULL, 'cobii', NULL, NULL, NULL, NULL),
  (38, 17, 0, NULL, 'initiates', NULL, NULL, 'Tobias Hößl', '', NULL, NULL, NULL),
  (39, 18, 0, NULL, 'initiates', NULL, NULL, 'Tobias Hößl', '', NULL, NULL, NULL),
  (40, 19, 0, NULL, 'initiates', NULL, NULL, 'Tobias Hößl', '', NULL, NULL, NULL),
  (41, 20, 0, NULL, 'initiates', NULL, NULL, 'Tobias Hößl', '', NULL, NULL, NULL),
  (42, 20, 0, NULL, 'supports', NULL, NULL, 'Nat 1', NULL, NULL, NULL, NULL),
  (43, 20, 0, NULL, 'supports', NULL, NULL, 'Orga23', NULL, NULL, NULL, NULL),
  (44, 22, 0, NULL, 'initiates', NULL, NULL, 'Cato Corp.', NULL, NULL, NULL, NULL),
  (45, 23, 0, NULL, 'initiates', NULL, NULL, 'Cato Nr. 4', NULL, NULL, NULL, NULL),
  (46, 24, 0, NULL, 'initiates', NULL, NULL, 'Tobias Hößl', '', NULL, NULL, NULL),
  (47, 24, 0, NULL, 'supports', NULL, NULL, 'Test', NULL, NULL, NULL, NULL),
  (48, 25, 0, NULL, 'initiates', NULL, NULL, 'Tobias Hößl', '', NULL, NULL, NULL),
  (49, 25, 0, NULL, 'supports', NULL, NULL, 'Test', NULL, NULL, NULL, NULL),
  (50, 26, 0, NULL, 'initiates', NULL, NULL, 'Tobias Hößl', '', NULL, NULL, NULL),
  (51, 26, 0, NULL, 'supports', NULL, NULL, 'Test', NULL, NULL, NULL, NULL),
  (52, 27, 0, NULL, 'likes', NULL, NULL, 'Tobias Hößl', '', NULL, NULL, NULL),
  (53, 27, 0, NULL, 'initiates', NULL, NULL, 'tobias.hoessl@googlemail.com', NULL, NULL, NULL, NULL),
  (54, 27, 0, NULL, 'likes', NULL, NULL, 'Nico', NULL, NULL, NULL, NULL),
  (55, 27, 0, NULL, 'supports', NULL, NULL, 'Test', NULL, NULL, NULL, NULL),
  (56, 29, 0, NULL, 'initiates', NULL, NULL, 'Tobias Hößl', '', NULL, NULL, NULL),
  (57, 32, 0, NULL, 'initiates', NULL, NULL, 'Tobias Hößl', '', NULL, NULL, NULL),
  (58, 32, 0, NULL, 'likes', NULL, NULL, 'Programmkommission', NULL, NULL, NULL, NULL),
  (59, 35, 0, NULL, 'initiates', NULL, NULL, 'Programmkommission', NULL, NULL, NULL, NULL),
  (60, 36, 0, NULL, 'initiates', NULL, NULL, 'Programmkommission', NULL, NULL, NULL, NULL),
  (61, 37, 0, NULL, 'initiates', NULL, NULL, 'Programmkommission', NULL, NULL, NULL, NULL),
  (62, 38, 0, NULL, 'initiates', NULL, NULL, 'Programmkommission', NULL, NULL, NULL, NULL),
  (63, 38, 0, NULL, 'likes', NULL, NULL, 'p.stuerzenberger@googlemail.com', NULL, NULL, NULL, NULL),
  (64, 39, 0, NULL, 'initiates', NULL, NULL, 'Programmkommission', NULL, NULL, NULL, NULL),
  (65, 40, 0, NULL, 'initiates', NULL, NULL, 'Programmkommission', NULL, NULL, NULL, NULL),
  (66, 41, 0, NULL, 'initiates', NULL, NULL, 'Programmkommission', NULL, NULL, NULL, NULL),
  (67, 42, 0, NULL, 'initiates', NULL, NULL, 'Programmkommission', NULL, NULL, NULL, NULL),
  (68, 43, 0, NULL, 'initiates', NULL, NULL, 'Programmkommission', NULL, NULL, NULL, NULL),
  (69, 44, 0, NULL, 'initiates', NULL, NULL, 'Programmkommission', NULL, NULL, NULL, NULL),
  (70, 45, 0, NULL, 'initiates', NULL, NULL, 'Programmkommission', NULL, NULL, NULL, NULL),
  (71, 46, 0, NULL, 'initiates', NULL, NULL, 'Programmkommission', NULL, NULL, NULL, NULL),
  (72, 47, 0, NULL, 'initiates', NULL, NULL, 'Programmkommission', NULL, NULL, NULL, NULL),
  (73, 48, 0, NULL, 'initiates', NULL, NULL, 'Programmkommission', NULL, NULL, NULL, NULL),
  (74, 49, 0, NULL, 'initiates', NULL, NULL, 'Programmkommission', NULL, NULL, NULL, NULL),
  (75, 50, 0, NULL, 'initiates', NULL, NULL, 'Programmkommission', NULL, NULL, NULL, NULL),
  (76, 50, 0, NULL, 'likes', NULL, NULL, 'diesunnalacht', NULL, NULL, NULL, NULL),
  (77, 51, 0, NULL, 'initiates', NULL, NULL, 'Programmkommission', NULL, NULL, NULL, NULL),
  (78, 52, 0, NULL, 'initiates', NULL, NULL, 'Programmkommission', NULL, NULL, NULL, NULL),
  (79, 53, 0, NULL, 'initiates', NULL, NULL, 'Programmkommission', NULL, NULL, NULL, NULL),
  (80, 54, 0, NULL, 'initiates', NULL, NULL, 'Programmkommission', NULL, NULL, NULL, NULL),
  (81, 55, 0, NULL, 'initiates', NULL, NULL, 'Programmkommission', NULL, NULL, NULL, NULL),
  (82, 55, 0, NULL, 'likes', NULL, NULL, 'Flodur Eldnem', NULL, NULL, NULL, NULL),
  (83, 56, 0, NULL, 'initiates', NULL, NULL, 'Programmkommission', NULL, NULL, NULL, NULL),
  (84, 57, 0, NULL, 'initiates', NULL, NULL, 'Programmkommission', NULL, NULL, NULL, NULL);

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
   '{"onlyNamespacedAccounts":0,"onlyWurzelwerk":0,"siteLayout":"layout-gruenes-ci","willingToPay":"2"}', 4, 1, 'BDK'),
  (5, '1laenderrat2015', 'Länderrat', 'Länderrat',
   '{"onlyNamespacedAccounts":false,"onlyWurzelwerk":false,"siteLayout":"layout-gruenes-ci","showAntragsgruenAd":true,"willingToPay":"2"}',
   5, 1, 'Länderrat');

--
-- Dumping data for table `siteAdmin`
--

INSERT INTO `siteAdmin` (`siteId`, `userId`) VALUES
  (1, 1),
  (2, 1),
  (3, 1),
  (4, 1),
  (5, 1);

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
  (1, 'Testadmin', 'testadmin@example.org', 1, 'email:testadmin@example.org', '2015-03-20 22:04:44', 0,
   'sha256:1000:gpdjLHGKeqKXDjjjVI6JsXF5xl+cAYm1:jT6RRYV6luIdDaomW56BMf50zQi0tiFy',
   0x66353232373335386331326436636434383930306430376638343666316363373538623562396438000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000,
   NULL),
  (2, 'Testuser', 'testuser@example.org', 1, 'email:testuser@example.org', '2015-03-20 22:08:14', 0,
   'sha256:1000:BwEqXMsdBXDi71XpQud1yRene4zeNRTt:atF5X6vaHJ93nyDIU/gobIpehez+0KBV',
   0x33663062343836336632393839643866383961396162386532626133336232363465373065663361000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000,
   NULL);


SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
