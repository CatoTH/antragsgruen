<?php
define('ANTRAGSGRUEN_VERSION', '3.0.0');


class AntragsgruenAppParams
{
    public $multisiteMode = true;
    public $domainPlain = "http://antragsgruen-v3.localhost/";
    public $domainSubdomain = "http://<site_id:[\w_-]+>.antragsgruen-v3.localhost/";
    public $standardVeranstaltungsreihe = "default";
    public $pdfLogo = 'LOGO_PFAD';
    public $kontactemail = 'EMAILADRESSE';
    public $mailFromName = 'Antragsgrün';
    public $mailFromEmail = 'EMAILADRESSE';
    public $adminUserId = null;
    public $odtDefaultTemplate = null;
}

/**
 * @var AntragsgruenAppParams $params
 */
$params = require(__DIR__ . DIRECTORY_SEPARATOR . 'params.php');

$dom_plain = $params->domainPlain;
$dom       = $params->domainSubdomain;
$domv      = $dom . "<consultation_id:[\w_-]+>/";

$url_rules = array(
    $domv . 'admin/'                                                                       => 'admin/index',
    $domv . 'admin/veranstaltungen/'                                                       => 'admin/veranstaltungen/update',
    $domv . 'admin/veranstaltungen/<_a:(index|update|update_extended|delete|view)>'        => 'admin/veranstaltungen/<_a>',
    $domv . 'admin/antraege'                                                               => 'admin/antraege',
    $domv . 'admin/antraege/<_a:(index|update|delete|view)>'                               => 'admin/antraege/<_a>',
    $domv . 'admin/aenderungsantraege'                                                     => 'admin/aenderungsantraege',
    $domv . 'admin/aenderungsantraege/<_a:(index|update|delete|view)>'                     => 'admin/aenderungsantraege/<_a>',
    $domv . 'admin/antraegeKommentare'                                                     => 'admin/antraegeKommentare',
    $domv . 'admin/antraegeKommentare/<_a:(index|update|delete|view)>'                     => 'admin/antraegeKommentare/<_a>',
    $domv . 'admin/aenderungsantraegeKommentare'                                           => 'admin/aenderungsantraegeKommentare',
    $domv . 'admin/aenderungsantraegeKommentare/<_a:(index|update|delete|view)>'           => 'admin/aenderungsantraegeKommentare/<_a>',
    $domv . 'admin/texte'                                                                  => 'admin/texte',
    $domv . 'admin/texte/<_a:(index|update|delete|view)>'                                  => 'admin/texte/<_a>',
    $domv . 'admin/kommentare_excel'                                                       => 'admin/index/kommentareexcel',
    $domv . 'admin/namespacedAccounts'                                                     => 'admin/index/namespacedAccounts',
    $domv . 'admin/ae_pdf_list'                                                            => 'admin/index/aePDFList',
    $dom . 'admin/reihe_admins'                                                            => 'admin/index/reiheAdmins',
    $dom . 'admin/reihe_veranstaltungen'                                                   => 'admin/index/reiheVeranstaltungen',
    $domv . 'hilfe'                                                                        => 'consultation/hilfe',
    $domv . 'suche'                                                                        => 'consultation/suche',
    $dom . 'impressum'                                                                     => 'consultation/impressum',
    $domv . 'pdfs'                                                                         => 'consultation/pdfs',
    $domv . 'aenderungsantrags_pdfs'                                                       => 'consultation/aenderungsantragsPdfs',
    $domv . 'feedAlles'                                                                    => 'consultation/feedAlles',
    $domv . 'feedAntraege'                                                                 => 'consultation/feedAntraege',
    $domv . 'feedAenderungsantraege'                                                       => 'consultation/feedAenderungsantraege',
    $domv . 'feedKommentare'                                                               => 'consultation/feedKommentare',
    $domv . 'wartungsmodus'                                                                => 'consultation/wartungsmodus',
    $dom . 'login'                                                                         => 'consultation/login',
    $dom . 'logout'                                                                        => 'consultation/logout',
    $dom . 'anmelden_bestaetigen'                                                          => 'consultation/anmeldungBestaetigen',
    $dom . 'benachrichtigungen'                                                            => 'consultation/benachrichtigungen',
    $dom . 'benachrichtigungen/checkemail'                                                 => 'consultation/ajaxEmailIstRegistriert',
    $dom . 'benachrichtigungen/abmelden'                                                   => 'consultation/benachrichtigungenAbmelden',
    $domv . 'motion/neu'                                                                   => 'motion/neu',
    $domv . 'motion/<antrag_id:\d+>?kommentar_id=<kommentar_id:\d+>'                       => 'motion/anzeige',
    $domv . 'motion/<antrag_id:\d+>'                                                       => 'motion/anzeige',
    $domv . 'motion/<antrag_id:\d+>/pdf'                                                   => 'motion/pdf',
    $domv . 'motion/<antrag_id:\d+>/plain_html'                                            => 'motion/plainHtml',
    $domv . 'motion/<antrag_id:\d+>/odt'                                                   => 'motion/odt',
    $domv . 'motion/<antrag_id:\d+>/neuConfirm'                                            => 'motion/neuConfirm',
    $domv . 'motion/<antrag_id:\d+>/aendern'                                               => 'motion/aendern',
    $domv . 'motion/<antrag_id:\d+>/aes_einpflegen'                                        => 'motion/aes_einpflegen',
    $domv . 'motion/<antrag_id:\d+>/aenderungsantrag/<aenderungsantrag_id:\d+>'            => 'amendment/anzeige',
    $domv . 'motion/<antrag_id:\d+>/aenderungsantrag/<aenderungsantrag_id:\d+>/neuConfirm' => 'amendment/neuConfirm',
    $domv . 'motion/<antrag_id:\d+>/aenderungsantrag/<aenderungsantrag_id:\d+>/pdf'        => 'amendment/pdf',
    $domv . 'motion/<antrag_id:\d+>/aenderungsantrag/<aenderungsantrag_id:\d+>/pdf_diff'   => 'amendment/pdfDiff',
    $domv . 'motion/<antrag_id:\d+>/aenderungsantrag/neu'                                  => 'amendment/neu',
    $domv . 'motion/<antrag_id:\d+>/aenderungsantrag/ajaxCalcDiff'                         => 'amendment/ajaxCalcDiff',
    $domv                                                                                  => 'consultation/index',
    $dom                                                                                   => 'consultation/index',
);


if ($params->multisiteMode) {
    $url_rules = array_merge(array(
        $dom_plain                         => 'manager/index',
        $dom_plain . 'selbst-einsetzen'    => 'manager/selbstEinsetzen',
        $dom_plain . 'neu-anlegen'         => 'manager/neuAnlegen',
        $dom_plain . 'impressum'           => 'manager/impressum',
        $dom_plain . 'passwort'            => 'manager/passwort',
        $dom_plain . 'rechnungsverwaltung' => 'manager/rechnungsverwaltung',
        $dom_plain . 'login'               => 'manager/login',
        $dom_plain . 'logout'              => 'manager/logout',
    ), $url_rules);

    foreach ($url_rules as $key => $val) $url_rules[str_replace("http://", "http://www.", $key)] = $val;
}

return [
    'bootstrap'    => ['log'],
    'basePath'     => dirname(__DIR__),
    'components'   => [
        'cache'      => [
            'class' => 'yii\caching\FileCache',
        ],
        'mailer'     => [
            'class' => 'yii\swiftmailer\Mailer',
        ],
        'log'        => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets'    => [
                [
                    'class'  => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'db'         => require(__DIR__ . DIRECTORY_SEPARATOR . 'db.php'),
        'urlManager' => array(
            'showScriptName' => false,
            'rules'          => $url_rules
        ),

    ],
    'defaultRoute' => 'manager/index',
    'params'       => $params,
];

