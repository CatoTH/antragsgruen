SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 0;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 0;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'TRADITIONAL,ALLOW_INVALID_DATES';

INSERT INTO `###TABLE_PREFIX###texTemplate` (`id`, `siteId`, `title`, `texLayout`, `texContent`) VALUES
  (1, NULL, 'Standard (Grünes CI)',
   '\\documentclass[paper=a4, 11pt, pagesize, parskip=half, DIV=calc]{scrartcl}\r\n\\usepackage[T1]{fontenc}\r\n\\usepackage{lmodern}\r\n\\usepackage[%LANGUAGE%]{babel}\r\n\\usepackage{fixltx2e}\r\n\\usepackage{lineno}\r\n\\usepackage{tabularx}\r\n\\usepackage{scrpage2}\r\n\\usepackage[normalem]{ulem}\r\n\\usepackage[right]{eurosym}\r\n\\usepackage{fontspec}\r\n\\usepackage{geometry}\r\n\\usepackage{color}\r\n\\usepackage{lastpage}\r\n\\usepackage[normalem]{ulem}\r\n\\usepackage{hyperref}\r\n\\usepackage{wrapfig}\r\n\\usepackage{enumitem}\r\n\r\n\\newfontfamily\\ArvoGruen[\r\n  Path=%ASSETROOT%Arvo/\r\n]{Arvo_Gruen_1004.otf}\r\n\\newfontfamily\\ArvoRegular[\r\n  Path=%ASSETROOT%Arvo/\r\n]{Arvo-Regular_v104.ttf}\r\n\\newfontfamily\\AntragsgruenSection[\r\n  Path=%ASSETROOT%Arvo/\r\n]{Arvo-Regular_v104.ttf}\r\n\\setmainfont[\r\n  Path=%ASSETROOT%PT-Sans/,\r\n  BoldFont=PTS75F.ttf,\r\n  ItalicFont=PTS56F.ttf,\r\n  BoldItalicFont=PTS76F.ttf\r\n]{PTS55F.ttf}\r\n\r\n\\definecolor{Insert}{rgb}{0,0.6,0}\r\n\\definecolor{Delete}{rgb}{1,0,0}\r\n\r\n\\hypersetup{\r\n    colorlinks=true,\r\n    linkcolor=blue,\r\n    filecolor=blue,      \r\n    urlcolor=blue,\r\n} \r\n\\urlstyle{same}\r\n\r\n\\title{%TITLE%}\r\n\\author{%AUTHOR%}\r\n\\geometry{a4paper, portrait, top=10mm, left=20mm, right=15mm, bottom=25mm, includehead=true}\r\n\r\n\\pagestyle{scrheadings}\r\n\\clearscrheadfoot\r\n\\renewcommand\\sectionmark[1]{\\markright{\\MakeMarkcase {\\hskip .5em\\relax#1}}}\r\n\\setcounter{secnumdepth}{0}\r\n\r\n\\newcommand\\invisiblesection[1]{%\r\n  \\refstepcounter{section}%\r\n  \\addcontentsline{toc}{section}{\\protect\\numberline{\\thesection}#1}%\r\n  \\sectionmark{#1}\r\n}\r\n\r\n\\ohead{\\ArvoRegular \\footnotesize \\rightmark}\r\n\\ofoot{\\ArvoRegular \\footnotesize Seite \\thepage\\\r\n% / \\pageref{LastPage}\r\n}\r\n\\setheadsepline{0.4pt}\r\n\\setfootsepline{0.4pt}\r\n\r\n\\begin{document}\r\n\r\n\\shorthandoff{"}\r\n\\sloppy\r\n\\hyphenpenalty=10000\r\n\\hbadness=10000\r\n\r\n%CONTENT%\r\n\r\n\\end{document}',
   '\\setcounter{page}{1}\r\n\\thispagestyle{empty}\r\n\r\n\\vspace*{-25mm}\r\n\\begin{flushright}\r\n \\ArvoRegular\r\n \\small\r\n \\textbf{\\normalsize %INTRODUCTION_BIG%}\\\\\r\n %INTRODUCTION_SMALL%\r\n\\end{flushright}\r\n\r\n\\begin{tabularx}{\\textwidth}{|lX|}\r\n    \\cline{1-2}\r\n    \\ArvoGruen\r\n&                               \\\\\r\n    \\multicolumn{2}{|l|}{\r\n    \\parbox{17cm}{\\raggedright\\textbf{\\LARGE %TITLEPREFIX%} %TITLE%      }} \\\\\r\n                                                            &                               \\\\\r\n    %MOTION_DATA_TABLE%\r\n                                                            &                               \\\\\r\n    \\cline{1-2}\r\n\\end{tabularx}\r\n\r\n\\invisiblesection{\\ArvoRegular %TITLE_LONG%}\r\n\r\n%TEXT%\r\n'),
  (2, NULL, 'Ohne Zeilennummern',
   '\\documentclass[paper=a4, 11pt, pagesize, parskip=half, DIV=calc]{scrartcl}\r\n\\usepackage[T1]{fontenc}\r\n\\usepackage{lmodern}\r\n\\usepackage[%LANGUAGE%]{babel}\r\n\\usepackage{fixltx2e}\r\n\\usepackage{lineno}\r\n\\usepackage{tabularx}\r\n\\usepackage{scrpage2}\r\n\\usepackage[normalem]{ulem}\r\n\\usepackage[right]{eurosym}\r\n\\usepackage{fontspec}\r\n\\usepackage{geometry}\r\n\\usepackage{color}\r\n\\usepackage{lastpage}\r\n\\usepackage[normalem]{ulem}\r\n\\usepackage{hyperref}\r\n\\usepackage{wrapfig}\r\n\\usepackage{enumitem}\r\n\r\n\\newfontfamily\\ArvoGruen[\r\n  Path=%ASSETROOT%Arvo/\r\n]{Arvo_Gruen_1004.otf}\r\n\\newfontfamily\\ArvoRegular[\r\n  Path=%ASSETROOT%Arvo/\r\n]{Arvo-Regular_v104.ttf}\r\n\\newfontfamily\\AntragsgruenSection[\r\n  Path=%ASSETROOT%Arvo/\r\n]{Arvo-Regular_v104.ttf}\r\n\\setmainfont[\r\n  Path=%ASSETROOT%PT-Sans/,\r\n  BoldFont=PTS75F.ttf,\r\n  ItalicFont=PTS56F.ttf,\r\n  BoldItalicFont=PTS76F.ttf\r\n]{PTS55F.ttf}\r\n\r\n\\definecolor{Insert}{rgb}{0,0.6,0}\r\n\\definecolor{Delete}{rgb}{1,0,0}\r\n\r\n\\hypersetup{\r\n    colorlinks=true,\r\n    linkcolor=blue,\r\n    filecolor=blue,      \r\n    urlcolor=blue,\r\n} \r\n\\urlstyle{same}\r\n\r\n\\title{%TITLE%}\r\n\\author{%AUTHOR%}\r\n\\geometry{a4paper, portrait, top=10mm, left=20mm, right=15mm, bottom=25mm, includehead=true}\r\n\r\n\\pagestyle{scrheadings}\r\n\\clearscrheadfoot\r\n\\renewcommand\\sectionmark[1]{\\markright{\\MakeMarkcase {\\hskip .5em\\relax#1}}}\r\n\\setcounter{secnumdepth}{0}\r\n\r\n\\newcommand\\invisiblesection[1]{%\r\n  \\refstepcounter{section}%\r\n  \\addcontentsline{toc}{section}{\\protect\\numberline{\\thesection}#1}%\r\n  \\sectionmark{#1}\r\n}\r\n\r\n\\ohead{\\ArvoRegular \\footnotesize \\rightmark}\r\n\\setheadsepline{0.4pt}\r\n\\setfootsepline{0.4pt}\r\n\r\n\\begin{document}\r\n\r\n\\shorthandoff{"}\r\n\\sloppy\r\n\\hyphenpenalty=10000\r\n\\hbadness=10000\r\n\r\n%CONTENT%\r\n\r\n\\end{document}',
   '\\setcounter{page}{1}\r\n\\thispagestyle{empty}\r\n\r\n\\vspace*{-25mm}\r\n\\begin{flushright}\r\n \\ArvoRegular\r\n \\small\r\n \\textbf{\\normalsize %INTRODUCTION_BIG%}\\\\\r\n %INTRODUCTION_SMALL%\r\n\\end{flushright}\r\n\r\n\\begin{tabularx}{\\textwidth}{|lX|}\r\n    \\cline{1-2}\r\n    \\ArvoGruen\r\n&                               \\\\\r\n    \\multicolumn{2}{|l|}{\r\n    \\parbox{17cm}{\\raggedright\\textbf{\\LARGE %TITLEPREFIX%} %TITLE%      }} \\\\\r\n                                                            &                               \\\\\r\n    %MOTION_DATA_TABLE%\r\n                                                            &                               \\\\\r\n    \\cline{1-2}\r\n\\end{tabularx}\r\n\r\n\\invisiblesection{\\ArvoRegular %TITLE_LONG%}\r\n\r\n%TEXT%\r\n');

INSERT INTO `###TABLE_PREFIX###migration` (`version`, `apply_time`) VALUES
  ('m000000_000000_base', 1443797618),
  ('m150930_094343_amendment_multiple_paragraphs', 1443797661),
  ('m151021_084634_supporter_organization_contact_person', 1445519132),
  ('m151025_123256_user_email_change', 1445802530),
  ('m151104_092212_motion_type_deletable', 1445802530),
  ('m151104_132242_site_consultation_date_creation', 1445802530),
  ('m151106_083636_site_properties', 1446801672),
  ('m151106_183055_motion_type_two_cols', 1446834722),
  ('m160114_200337_motion_section_is_right', 1452801905),
  ('m160228_152511_motion_type_rename_initiator_form', 1457086233),
  ('m160304_095858_motion_slug', 1457086236),
  ('m160305_201135_support_separate_to_motions_and_amendments', '1457209261'),
  ('m160305_214526_support_likes_dislikes', '1457209261'),
  ('m160605_104819_remove_consultation_type', '1457209261'),
  ('m161112_161536_add_date_delete', '1457209261'),
  ('m170111_182139_motions_non_amendable', '1457209261'),
  ('m170129_173812_typo_maintenance', '1485711868'),
  ('m170204_191243_additional_user_fields', '1486235651'),
  ('m170206_185458_supporter_contact_name', '1486410534'),
  ('m170226_134156_motionInitiatorsAmendmentMerging', '1489921851'),
  ('m170419_182728_delete_consultation_admin', '1492626507'),
  ('m170611_195343_global_alternatives', '1497211108')
;

SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
