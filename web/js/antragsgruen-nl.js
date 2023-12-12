/*global Intl */

ANTRAGSGRUEN_STRINGS = {
    "std": {
        "del_confirm": "Weet je zeker dat je dit wilt verwijderen?",
        "draft_del": "Concept verwijderen",
        "draft_del_confirm": "Weet je zeker dat je dit wilt verwijderen?",
        "draft_date": "Conceptdatum",
        "draft_restore_confirm": "Dit concept herstellen?",
        "min_x_supporter": "Je moet minimaal %NUM% ondersteuners hebben.",
        "missing_resolution_date": "Er moet een resolutiedatum worden opgegeven.",
        "missing_gender": "Vul iets in bij het genderveld",
        "pw_x_chars": "Het wachtwoord moet minimaal %NUM% tekens lang zijn.",
        "pw_min_x_chars": "Minimaal %NUM% tekens",
        "pw_no_match": "De twee wachtwoorden komen niet overeen.",
        "leave_changed_page": "Er zijn nog niet opgeslagen wijzigingen. Weet je zeker dat je deze pagina wilt verlaten?",
        "moved_paragraph_from": "Verplaatst van alinea ##PARA##.",
        "moved_paragraph_to": "Verplaatst naar alinea ##PARA##.",
        "moved_paragraph_from_line": "Verplaatst van alinea ##PARA## (regel ##LINE##)",
        "moved_paragraph_to_line": "Verplaatst naar alinea ##PARA## (regel ##LINE##)"
    },
    "merge": {
        "initiated_by": "Aangeleverd door",
        "title_open_in_blank": "Open de wijziging in een nieuw venster",
        "title_del_title": "Verwijder \"tegenstrijdige: ...\" titel",
        "title_del_colliding": "Verwijder het volledige tegenstrijdige blok",
        "title": "Titel",
        "change_accept": "Overnemen",
        "change_reject": "Werwerpen",
        "colliding_title": "Tegenstrijdige ÄA",
        "colliding_start": "Tegenstrijdigheid start hier",
        "colliding_end": "Tegenstrijdigheid tot hier",
        "reloadParagraph": "Als deze selectie wordt gewijzigd, gaan handmatige wijzigingen in deze paragraaf verloren. Volgende?"
    },
    "admin": {
        "adminMayEditConfirm": "Als dit wordt gedeactiveerd, heeft dit ook invloed op alle eerdere toepassingen en kan dit niet worden geannuleerd voor eerdere toepassingen. Wil je doorgaan?",
        "deleteDataConfirm": "Wil je deze informatie echt verwijderen?",
        "agendaAddEntry": "Item toevoegen",
        "agendaAddDate": "Datum toevoegen",
        "agendaShowTimes": "Exacte tijden instellen",
        "agendaDelEntryConfirm": "Dit agendapunt en diens subpunten schrappen?",
        "removeAdminConfirm": "Wil je echt de beheerdersrechten van dit account intrekken?",
        "removeUserConfirm": "Echt %NAME% van DIT evenement verwijderen? Ingediende aanvragen enz. worden bewaard en moeten expliciet worden verwijderd.",
        "deleteUserConfirm": "%NAME% echt verwijderen? Ingediende aanvragen enz. worden bewaard en moeten expliciet worden verwijderd.",
        "emailMissingCode": "De e-mailtekst moet de code %ACCOUNT% bevatten.",
        "emailMissingLink": "De e-mailtekst moet de code %LINK% bevatten.",
        "emailMissingTo": "Er werden geen e-mailadressen opgegeven.",
        "emailMissingUsername": "Er werden geen usernames genoemd.",
        "emailNumberMismatch": "Er zijn niet evenveel usernames als e-mailadressen. Zorg ervoor dat er precies één naam wordt ingevuld voor elke regel met e-mailadressen!",
        "delMotionConfirm": "Weet je zeker dat je dit verzoek wilt verwijderen?",
        "delAmendmentConfirm": "Weet je zeker dat je deze wijziging wilt verwijderen?",
        "delPageConfirm": "Weet je zeker dat je deze pagina wilt verwijderen?",
        "deleteMotionSectionConfirm": "Als dit type wordt verwijderd, worden ook alle stukken van dit type verwijderd. Wil je dit echt verwijderen?",
        "consDeleteConfirm": "Moet dit evenement, inclusief alle moties en amendementen, echt worden afgelast?",
        "gotoUpdateModeConfirm": "Moet de updatemodus worden geactiveerd? De applicatie is niet beschikbaar wanneer de updatemodus actief is."
    }
};


if (typeof(Intl.__addLocaleData) != 'undefined') {
    Intl.__addLocaleData({
        "locale": "nl",
        "date": {
            "ca": [
                "gregory",
                "buddhist",
                "chinese",
                "coptic",
                "dangi",
                "ethioaa",
                "ethiopic",
                "generic",
                "hebrew",
                "indian",
                "islamic",
                "islamicc",
                "japanese",
                "persian",
                "roc"
            ],
            "hourNo0": true,
            "hour12": false,
            "formats": {
                "short": "{1}, {0}",
                "medium": "{1}, {0}",
                "full": "{1} 'op' {0}",
                "long": "{1} 'op' {0}",
                "availableFormats": {
                    "d": "d",
                    "E": "ccc",
                    "Ed": "E, d.",
                    "Ehm": "E h:mm a",
                    "EHm": "E, HH:mm",
                    "Ehms": "E, h:mm:ss a",
                    "EHms": "E, HH:mm:ss",
                    "Gy": "y G",
                    "GyMMM": "MMM y G",
                    "GyMMMd": "d. MMM y G",
                    "GyMMMEd": "E, d. MMM y G",
                    "h": "h a",
                    "H": "HH 'Uhr'",
                    "hm": "h:mm a",
                    "Hm": "HH:mm",
                    "hms": "h:mm:ss a",
                    "Hms": "HH:mm:ss",
                    "hmsv": "h:mm:ss a v",
                    "Hmsv": "HH:mm:ss v",
                    "hmv": "h:mm a v",
                    "Hmv": "HH:mm v",
                    "M": "L",
                    "Md": "d.M.",
                    "MEd": "E, d.M.",
                    "MMd": "d.MM.",
                    "MMdd": "dd.MM.",
                    "MMM": "LLL",
                    "MMMd": "d. MMM",
                    "MMMEd": "E, d. MMM",
                    "MMMMd": "d. MMMM",
                    "MMMMEd": "E, d. MMMM",
                    "ms": "mm:ss",
                    "y": "y",
                    "yM": "M.y",
                    "yMd": "d.M.y",
                    "yMEd": "E, d.M.y",
                    "yMM": "MM.y",
                    "yMMdd": "dd.MM.y",
                    "yMMM": "MMM y",
                    "yMMMd": "d. MMM y",
                    "yMMMEd": "E, d. MMM y",
                    "yMMMM": "MMMM y",
                    "yQQQ": "QQQ y",
                    "yQQQQ": "QQQQ y"
                },
                "dateFormats": {
                    "yMMMMEEEEd": "EEEE, d. MMMM y",
                    "yMMMMd": "d. MMMM y",
                    "yMMMd": "dd.MM.y",
                    "yMd": "dd.MM.yy"
                },
                "timeFormats": {
                    "hmmsszzzz": "HH:mm:ss zzzz",
                    "hmsz": "HH:mm:ss z",
                    "hms": "HH:mm:ss",
                    "hm": "HH:mm"
                }
            },
            "calendars": {
                "buddhist": {
                    "months": {
                        "narrow": [
                            "J",
                            "F",
                            "M",
                            "A",
                            "M",
                            "J",
                            "J",
                            "A",
                            "S",
                            "O",
                            "N",
                            "D"
                        ],
                        "short": [
                            "jan.",
                            "feb.",
                            "maa.",
                            "apr.",
                            "mei",
                            "juni",
                            "juli",
                            "aug.",
                            "sep.",
                            "okt.",
                            "nov.",
                            "dec."
                        ],
                        "long": [
                            "januari",
                            "februari",
                            "maart",
                            "april",
                            "mei",
                            "juni",
                            "juli",
                            "augustus",
                            "september",
                            "oktober",
                            "november",
                            "december"
                        ]
                    },
                    "days": {
                        "narrow": [
                            "z",
                            "m",
                            "d",
                            "w",
                            "d",
                            "v",
                            "z"
                        ],
                        "short": [
                            "zo.",
                            "ma.",
                            "di.",
                            "wo.",
                            "do.",
                            "vr.",
                            "za."
                        ],
                        "long": [
                            "zondag",
                            "maandag",
                            "dinsdag",
                            "woensdag",
                            "donderdag",
                            "vrijdag",
                            "zaterdag"
                        ]
                    },
                    "eras": {
                        "narrow": [
                            "n.Chr."
                        ],
                        "short": [
                            "n.Chr."
                        ],
                        "long": [
                            "n.Chr."
                        ]
                    },
                    "dayPeriods": {
                        "am": "am",
                        "pm": "pm"
                    }
                },
                "chinese": {
                    "months": {
                        "narrow": [
                            "1",
                            "2",
                            "3",
                            "4",
                            "5",
                            "6",
                            "7",
                            "8",
                            "9",
                            "10",
                            "11",
                            "12"
                        ],
                        "short": [
                            "M01",
                            "M02",
                            "M03",
                            "M04",
                            "M05",
                            "M06",
                            "M07",
                            "M08",
                            "M09",
                            "M10",
                            "M11",
                            "M12"
                        ],
                        "long": [
                            "M01",
                            "M02",
                            "M03",
                            "M04",
                            "M05",
                            "M06",
                            "M07",
                            "M08",
                            "M09",
                            "M10",
                            "M11",
                            "M12"
                        ]
                    },
                    "days": {
                        "narrow": [
                            "z",
                            "m",
                            "d",
                            "w",
                            "d",
                            "v",
                            "z"
                        ],
                        "short": [
                            "zo.",
                            "ma.",
                            "di.",
                            "wo.",
                            "do.",
                            "vr.",
                            "za."
                        ],
                        "long": [
                            "zondag",
                            "maandag",
                            "dinsdag",
                            "woensdag",
                            "donderdag",
                            "vrijdag",
                            "zaterdag"
                        ]
                    },
                    "dayPeriods": {
                        "am": "am",
                        "pm": "pm"
                    }
                },
                "coptic": {
                    "months": {
                        "narrow": [
                            "1",
                            "2",
                            "3",
                            "4",
                            "5",
                            "6",
                            "7",
                            "8",
                            "9",
                            "10",
                            "11",
                            "12",
                            "13"
                        ],
                        "short": [
                            "Tout",
                            "Baba",
                            "Hator",
                            "Kiahk",
                            "Toba",
                            "Amshir",
                            "Baramhat",
                            "Baramouda",
                            "Bashans",
                            "Paona",
                            "Epep",
                            "Mesra",
                            "Nasie"
                        ],
                        "long": [
                            "Tout",
                            "Baba",
                            "Hator",
                            "Kiahk",
                            "Toba",
                            "Amshir",
                            "Baramhat",
                            "Baramouda",
                            "Bashans",
                            "Paona",
                            "Epep",
                            "Mesra",
                            "Nasie"
                        ]
                    },
                    "days": {
                        "narrow": [
                            "z",
                            "m",
                            "d",
                            "w",
                            "d",
                            "v",
                            "z"
                        ],
                        "short": [
                            "zo.",
                            "ma.",
                            "di.",
                            "wo.",
                            "do.",
                            "vr.",
                            "za."
                        ],
                        "long": [
                            "zondag",
                            "maandag",
                            "dinsdag",
                            "woensdag",
                            "donderdag",
                            "vrijdag",
                            "zaterdag"
                        ]
                    },
                    "eras": {
                        "narrow": [
                            "ERA0",
                            "ERA1"
                        ],
                        "short": [
                            "ERA0",
                            "ERA1"
                        ],
                        "long": [
                            "ERA0",
                            "ERA1"
                        ]
                    },
                    "dayPeriods": {
                        "am": "am",
                        "pm": "pm"
                    }
                },
                "dangi": {
                    "months": {
                        "narrow": [
                            "1",
                            "2",
                            "3",
                            "4",
                            "5",
                            "6",
                            "7",
                            "8",
                            "9",
                            "10",
                            "11",
                            "12"
                        ],
                        "short": [
                            "M01",
                            "M02",
                            "M03",
                            "M04",
                            "M05",
                            "M06",
                            "M07",
                            "M08",
                            "M09",
                            "M10",
                            "M11",
                            "M12"
                        ],
                        "long": [
                            "M01",
                            "M02",
                            "M03",
                            "M04",
                            "M05",
                            "M06",
                            "M07",
                            "M08",
                            "M09",
                            "M10",
                            "M11",
                            "M12"
                        ]
                    },
                    "days": {
                        "narrow": [
                            "z",
                            "m",
                            "d",
                            "w",
                            "d",
                            "v",
                            "z"
                        ],
                        "short": [
                            "zo.",
                            "ma.",
                            "di.",
                            "wo.",
                            "do.",
                            "vr.",
                            "za."
                        ],
                        "long": [
                            "zondag",
                            "maandag",
                            "dinsdag",
                            "woensdag",
                            "donderdag",
                            "vrijdag",
                            "zaterdag"
                        ]
                    },
                    "dayPeriods": {
                        "am": "am",
                        "pm": "pm"
                    }
                },
                "ethiopic": {
                    "months": {
                        "narrow": [
                            "1",
                            "2",
                            "3",
                            "4",
                            "5",
                            "6",
                            "7",
                            "8",
                            "9",
                            "10",
                            "11",
                            "12",
                            "13"
                        ],
                        "short": [
                            "Meskerem",
                            "Tekemt",
                            "Hedar",
                            "Tahsas",
                            "Ter",
                            "Yekatit",
                            "Megabit",
                            "Miazia",
                            "Genbot",
                            "Sene",
                            "Hamle",
                            "Nehasse",
                            "Pagumen"
                        ],
                        "long": [
                            "Meskerem",
                            "Tekemt",
                            "Hedar",
                            "Tahsas",
                            "Ter",
                            "Yekatit",
                            "Megabit",
                            "Miazia",
                            "Genbot",
                            "Sene",
                            "Hamle",
                            "Nehasse",
                            "Pagumen"
                        ]
                    },
                    "days": {
                        "narrow": [
                            "z",
                            "m",
                            "d",
                            "w",
                            "d",
                            "v",
                            "z"
                        ],
                        "short": [
                            "zo.",
                            "ma.",
                            "di.",
                            "wo.",
                            "do.",
                            "vr.",
                            "za."
                        ],
                        "long": [
                            "zondag",
                            "maandag",
                            "dinsdag",
                            "woensdag",
                            "donderdag",
                            "vrijdag",
                            "zaterdag"
                        ]
                    },
                    "eras": {
                        "narrow": [
                            "ERA0",
                            "ERA1"
                        ],
                        "short": [
                            "ERA0",
                            "ERA1"
                        ],
                        "long": [
                            "ERA0",
                            "ERA1"
                        ]
                    },
                    "dayPeriods": {
                        "am": "am",
                        "pm": "pm"
                    }
                },
                "ethioaa": {
                    "months": {
                        "narrow": [
                            "1",
                            "2",
                            "3",
                            "4",
                            "5",
                            "6",
                            "7",
                            "8",
                            "9",
                            "10",
                            "11",
                            "12",
                            "13"
                        ],
                        "short": [
                            "Meskerem",
                            "Tekemt",
                            "Hedar",
                            "Tahsas",
                            "Ter",
                            "Yekatit",
                            "Megabit",
                            "Miazia",
                            "Genbot",
                            "Sene",
                            "Hamle",
                            "Nehasse",
                            "Pagumen"
                        ],
                        "long": [
                            "Meskerem",
                            "Tekemt",
                            "Hedar",
                            "Tahsas",
                            "Ter",
                            "Yekatit",
                            "Megabit",
                            "Miazia",
                            "Genbot",
                            "Sene",
                            "Hamle",
                            "Nehasse",
                            "Pagumen"
                        ]
                    },
                    "days": {
                        "narrow": [
                            "z",
                            "m",
                            "d",
                            "w",
                            "d",
                            "v",
                            "z"
                        ],
                        "short": [
                            "zo.",
                            "ma.",
                            "di.",
                            "wo.",
                            "do.",
                            "vr.",
                            "za."
                        ],
                        "long": [
                            "zondag",
                            "maandag",
                            "dinsdag",
                            "woensdag",
                            "donderdag",
                            "vrijdag",
                            "zaterdag"
                        ]
                    },
                    "eras": {
                        "narrow": [
                            "ERA0"
                        ],
                        "short": [
                            "ERA0"
                        ],
                        "long": [
                            "ERA0"
                        ]
                    },
                    "dayPeriods": {
                        "am": "am",
                        "pm": "pm"
                    }
                },
                "generic": {
                    "months": {
                        "narrow": [
                            "1",
                            "2",
                            "3",
                            "4",
                            "5",
                            "6",
                            "7",
                            "8",
                            "9",
                            "10",
                            "11",
                            "12"
                        ],
                        "short": [
                            "M01",
                            "M02",
                            "M03",
                            "M04",
                            "M05",
                            "M06",
                            "M07",
                            "M08",
                            "M09",
                            "M10",
                            "M11",
                            "M12"
                        ],
                        "long": [
                            "M01",
                            "M02",
                            "M03",
                            "M04",
                            "M05",
                            "M06",
                            "M07",
                            "M08",
                            "M09",
                            "M10",
                            "M11",
                            "M12"
                        ]
                    },
                    "days": {
                        "narrow": [
                            "z",
                            "m",
                            "d",
                            "w",
                            "d",
                            "v",
                            "z"
                        ],
                        "short": [
                            "zo.",
                            "ma.",
                            "di.",
                            "wo.",
                            "do.",
                            "vr.",
                            "za."
                        ],
                        "long": [
                            "zondag",
                            "maandag",
                            "dinsdag",
                            "woensdag",
                            "donderdag",
                            "vrijdag",
                            "zaterdag"
                        ]
                    },
                    "eras": {
                        "narrow": [
                            "ERA0",
                            "ERA1"
                        ],
                        "short": [
                            "ERA0",
                            "ERA1"
                        ],
                        "long": [
                            "ERA0",
                            "ERA1"
                        ]
                    },
                    "dayPeriods": {
                        "am": "am",
                        "pm": "pm"
                    }
                },
                "gregory": {
                    "months": {
                        "narrow": [
                            "j",
                            "f",
                            "m",
                            "a",
                            "m",
                            "j",
                            "j",
                            "a",
                            "s",
                            "o",
                            "n",
                            "d"
                        ],
                        "short": [
                            "jan.",
                            "feb.",
                            "maa.",
                            "apr.",
                            "mei",
                            "juni",
                            "juli",
                            "aug.",
                            "sep.",
                            "okt.",
                            "nov.",
                            "dec."
                        ],
                        "long": [
                            "januari",
                            "februari",
                            "maart",
                            "april",
                            "mei",
                            "juni",
                            "juli",
                            "augustus",
                            "september",
                            "oktober",
                            "november",
                            "december"
                        ]
                    },
                    "days": {
                        "narrow": [
                            "z",
                            "m",
                            "d",
                            "w",
                            "d",
                            "v",
                            "z"
                        ],
                        "short": [
                            "zo.",
                            "ma.",
                            "di.",
                            "wo.",
                            "do.",
                            "vr.",
                            "za."
                        ],
                        "long": [
                            "zondag",
                            "maandag",
                            "dinsdag",
                            "woensdag",
                            "donderdag",
                            "vrijdag",
                            "zaterdag"
                        ]
                    },
                    "eras": {
                        "narrow": [
                            "v. Chr.",
                            "n. Chr.",
                            "v. u. Z.",
                            "u. Z."
                        ],
                        "short": [
                            "v. Chr.",
                            "n. Chr.",
                            "v. u. Z.",
                            "u. Z."
                        ],
                        "long": [
                            "v. Chr.",
                            "n. Chr.",
                            "Voor Christus",
                            "Na Christus"
                        ]
                    },
                    "dayPeriods": {
                        "am": "am",
                        "pm": "pm"
                    }
                },
                "hebrew": {
                    "months": {
                        "narrow": [
                            "1",
                            "2",
                            "3",
                            "4",
                            "5",
                            "6",
                            "7",
                            "8",
                            "9",
                            "10",
                            "11",
                            "12",
                            "13",
                            "7"
                        ],
                        "short": [
                            "Tishri",
                            "Heshvan",
                            "Kislev",
                            "Tevet",
                            "Shevat",
                            "Adar I",
                            "Adar",
                            "Nisan",
                            "Iyar",
                            "Sivan",
                            "Tamuz",
                            "Av",
                            "Elul",
                            "Adar II"
                        ],
                        "long": [
                            "Tishri",
                            "Heshvan",
                            "Kislev",
                            "Tevet",
                            "Shevat",
                            "Adar I",
                            "Adar",
                            "Nisan",
                            "Iyar",
                            "Sivan",
                            "Tamuz",
                            "Av",
                            "Elul",
                            "Adar II"
                        ]
                    },
                    "days": {
                        "narrow": [
                            "z",
                            "m",
                            "d",
                            "w",
                            "d",
                            "v",
                            "z"
                        ],
                        "short": [
                            "zo.",
                            "ma.",
                            "di.",
                            "wo.",
                            "do.",
                            "vr.",
                            "za."
                        ],
                        "long": [
                            "zondag",
                            "maandag",
                            "dinsdag",
                            "woensdag",
                            "donderdag",
                            "vrijdag",
                            "zaterdag"
                        ]
                    },
                    "eras": {
                        "narrow": [
                            "AM"
                        ],
                        "short": [
                            "AM"
                        ],
                        "long": [
                            "AM"
                        ]
                    },
                    "dayPeriods": {
                        "am": "am",
                        "pm": "pm"
                    }
                },
                "indian": {
                    "months": {
                        "narrow": [
                            "1",
                            "2",
                            "3",
                            "4",
                            "5",
                            "6",
                            "7",
                            "8",
                            "9",
                            "10",
                            "11",
                            "12"
                        ],
                        "short": [
                            "Chaitra",
                            "Vaisakha",
                            "Jyaistha",
                            "Asadha",
                            "Sravana",
                            "Bhadra",
                            "Asvina",
                            "Kartika",
                            "Agrahayana",
                            "Pausa",
                            "Magha",
                            "Phalguna"
                        ],
                        "long": [
                            "Chaitra",
                            "Vaisakha",
                            "Jyaistha",
                            "Asadha",
                            "Sravana",
                            "Bhadra",
                            "Asvina",
                            "Kartika",
                            "Agrahayana",
                            "Pausa",
                            "Magha",
                            "Phalguna"
                        ]
                    },
                    "days": {
                        "narrow": [
                            "z",
                            "m",
                            "d",
                            "w",
                            "d",
                            "v",
                            "z"
                        ],
                        "short": [
                            "zo.",
                            "ma.",
                            "di.",
                            "wo.",
                            "do.",
                            "vr.",
                            "za."
                        ],
                        "long": [
                            "zondag",
                            "maandag",
                            "dinsdag",
                            "woensdag",
                            "donderdag",
                            "vrijdag",
                            "zaterdag"
                        ]
                    },
                    "eras": {
                        "narrow": [
                            "Saka"
                        ],
                        "short": [
                            "Saka"
                        ],
                        "long": [
                            "Saka"
                        ]
                    },
                    "dayPeriods": {
                        "am": "am",
                        "pm": "pm"
                    }
                },
                "islamic": {
                    "months": {
                        "narrow": [
                            "1",
                            "2",
                            "3",
                            "4",
                            "5",
                            "6",
                            "7",
                            "8",
                            "9",
                            "10",
                            "11",
                            "12"
                        ],
                        "short": [
                            "Muh.",
                            "Saf.",
                            "Rab. I",
                            "Rab. II",
                            "Jum. I",
                            "Jum. II",
                            "Raj.",
                            "Sha.",
                            "Ram.",
                            "Shaw.",
                            "Dhuʻl-Q.",
                            "Dhuʻl-H."
                        ],
                        "long": [
                            "Muharram",
                            "Safar",
                            "Rabiʻ I",
                            "Rabiʻ II",
                            "Jumada I",
                            "Jumada II",
                            "Rajab",
                            "Shaʻban",
                            "Ramadan",
                            "Shawwal",
                            "Dhuʻl-Qiʻdah",
                            "Dhuʻl-Hijjah"
                        ]
                    },
                    "days": {
                        "narrow": [
                            "z",
                            "m",
                            "d",
                            "w",
                            "d",
                            "v",
                            "z"
                        ],
                        "short": [
                            "zo.",
                            "ma.",
                            "di.",
                            "wo.",
                            "do.",
                            "vr.",
                            "za."
                        ],
                        "long": [
                            "zondag",
                            "maandag",
                            "dinsdag",
                            "woensdag",
                            "donderdag",
                            "vrijdag",
                            "zaterdag"
                        ]
                    },
                    "eras": {
                        "narrow": [
                            "AH"
                        ],
                        "short": [
                            "AH"
                        ],
                        "long": [
                            "AH"
                        ]
                    },
                    "dayPeriods": {
                        "am": "am",
                        "pm": "pm"
                    }
                },
                "islamicc": {
                    "months": {
                        "narrow": [
                            "1",
                            "2",
                            "3",
                            "4",
                            "5",
                            "6",
                            "7",
                            "8",
                            "9",
                            "10",
                            "11",
                            "12"
                        ],
                        "short": [
                            "Muh.",
                            "Saf.",
                            "Rab. I",
                            "Rab. II",
                            "Jum. I",
                            "Jum. II",
                            "Raj.",
                            "Sha.",
                            "Ram.",
                            "Shaw.",
                            "Dhuʻl-Q.",
                            "Dhuʻl-H."
                        ],
                        "long": [
                            "Muharram",
                            "Safar",
                            "Rabiʻ I",
                            "Rabiʻ II",
                            "Jumada I",
                            "Jumada II",
                            "Rajab",
                            "Shaʻban",
                            "Ramadan",
                            "Shawwal",
                            "Dhuʻl-Qiʻdah",
                            "Dhuʻl-Hijjah"
                        ]
                    },
                    "days": {
                        "narrow": [
                            "z",
                            "m",
                            "d",
                            "w",
                            "d",
                            "v",
                            "z"
                        ],
                        "short": [
                            "zo.",
                            "ma.",
                            "di.",
                            "wo.",
                            "do.",
                            "vr.",
                            "za."
                        ],
                        "long": [
                            "zondag",
                            "maandag",
                            "dinsdag",
                            "woensdag",
                            "donderdag",
                            "vrijdag",
                            "zaterdag"
                        ]
                    },
                    "eras": {
                        "narrow": [
                            "AH"
                        ],
                        "short": [
                            "AH"
                        ],
                        "long": [
                            "AH"
                        ]
                    },
                    "dayPeriods": {
                        "am": "am",
                        "pm": "pm"
                    }
                },
                "japanese": {
                    "months": {
                        "narrow": [
                            "j",
                            "f",
                            "m",
                            "a",
                            "m",
                            "j",
                            "j",
                            "a",
                            "s",
                            "o",
                            "n",
                            "d"
                        ],
                        "short": [
                            "jan.",
                            "feb.",
                            "maa.",
                            "apr.",
                            "mei",
                            "juni",
                            "juli",
                            "aug.",
                            "sep.",
                            "okt.",
                            "nov.",
                            "dec."
                        ],
                        "long": [
                            "januari",
                            "februari",
                            "maart",
                            "april",
                            "mei",
                            "juni",
                            "juli",
                            "august",
                            "september",
                            "oktober",
                            "november",
                            "december"
                        ]
                    },
                    "days": {
                        "narrow": [
                            "z",
                            "m",
                            "d",
                            "w",
                            "d",
                            "v",
                            "z"
                        ],
                        "short": [
                            "zo.",
                            "ma.",
                            "di.",
                            "wo.",
                            "do.",
                            "vr.",
                            "za."
                        ],
                        "long": [
                            "zondag",
                            "maandag",
                            "dinsdag",
                            "woensdag",
                            "donderdag",
                            "vrijdag",
                            "zaterdag"
                        ]
                    },
                    "dayPeriods": {
                        "am": "am",
                        "pm": "pm"
                    }
                },
                "persian": {
                    "months": {
                        "narrow": [
                            "1",
                            "2",
                            "3",
                            "4",
                            "5",
                            "6",
                            "7",
                            "8",
                            "9",
                            "10",
                            "11",
                            "12"
                        ],
                        "short": [
                            "Farvardin",
                            "Ordibehesht",
                            "Khordad",
                            "Tir",
                            "Mordad",
                            "Shahrivar",
                            "Mehr",
                            "Aban",
                            "Azar",
                            "Dey",
                            "Bahman",
                            "Esfand"
                        ],
                        "long": [
                            "Farvardin",
                            "Ordibehesht",
                            "Khordad",
                            "Tir",
                            "Mordad",
                            "Shahrivar",
                            "Mehr",
                            "Aban",
                            "Azar",
                            "Dey",
                            "Bahman",
                            "Esfand"
                        ]
                    },
                    "days": {
                        "narrow": [
                            "z",
                            "m",
                            "d",
                            "w",
                            "d",
                            "v",
                            "z"
                        ],
                        "short": [
                            "zo.",
                            "ma.",
                            "di.",
                            "wo.",
                            "do.",
                            "vr.",
                            "za."
                        ],
                        "long": [
                            "zondag",
                            "maandag",
                            "dinsdag",
                            "woensdag",
                            "donderdag",
                            "vrijdag",
                            "zaterdag"
                        ]
                    },
                    "eras": {
                        "narrow": [
                            "AP"
                        ],
                        "short": [
                            "AP"
                        ],
                        "long": [
                            "AP"
                        ]
                    },
                    "dayPeriods": {
                        "am": "am",
                        "pm": "pm"
                    }
                },
                "roc": {
                    "months": {
                        "narrow": [
                            "J",
                            "F",
                            "M",
                            "A",
                            "M",
                            "J",
                            "J",
                            "A",
                            "S",
                            "O",
                            "N",
                            "D"
                        ],
                        "short": [
                            "Jan.",
                            "Feb.",
                            "März",
                            "Apr.",
                            "Mai",
                            "Juni",
                            "Juli",
                            "Aug.",
                            "Sep.",
                            "Okt.",
                            "Nov.",
                            "Dez."
                        ],
                        "long": [
                            "Januar",
                            "Februar",
                            "März",
                            "April",
                            "Mai",
                            "Juni",
                            "Juli",
                            "August",
                            "September",
                            "Oktober",
                            "November",
                            "Dezember"
                        ]
                    },
                    "days": {
                        "narrow": [
                            "z",
                            "m",
                            "d",
                            "w",
                            "d",
                            "v",
                            "z"
                        ],
                        "short": [
                            "zo.",
                            "ma.",
                            "di.",
                            "wo.",
                            "do.",
                            "vr.",
                            "za."
                        ],
                        "long": [
                            "zondag",
                            "maandag",
                            "dinsdag",
                            "woensdag",
                            "donderdag",
                            "vrijdag",
                            "zaterdag"
                        ]
                    },
                    "eras": {
                        "narrow": [
                            "Before R.O.C.",
                            "Minguo"
                        ],
                        "short": [
                            "Before R.O.C.",
                            "Minguo"
                        ],
                        "long": [
                            "Before R.O.C.",
                            "Minguo"
                        ]
                    },
                    "dayPeriods": {
                        "am": "vorm.",
                        "pm": "nachm."
                    }
                }
            }
        },
        "number": {
            "nu": [
                "latn"
            ],
            "patterns": {
                "decimal": {
                    "positivePattern": "{number}",
                    "negativePattern": "{minusSign}{number}"
                },
                "currency": {
                    "positivePattern": "{currency} {number}",
                    "negativePattern": "{currency} {minusSign}{number}"
                },
                "percent": {
                    "positivePattern": "{number} {percentSign}",
                    "negativePattern": "{minusSign}{number} {percentSign}"
                }
            },
            "symbols": {
                "latn": {
                    "decimal": ",",
                    "group": ".",
                    "nan": "NaN",
                    "plusSign": "+",
                    "minusSign": "-",
                    "percentSign": "%",
                    "infinity": "∞"
                }
            },
            "currencies": {
                "ATS": "öS",
                "AUD": "AU$",
                "BGM": "BGK",
                "BGO": "BGJ",
                "BRL": "R$",
                "CAD": "CA$",
                "CNY": "CN¥",
                "DEM": "DM",
                "EUR": "€",
                "GBP": "£",
                "HKD": "HK$",
                "ILS": "₪",
                "INR": "₹",
                "JPY": "¥",
                "KRW": "₩",
                "MXN": "MX$",
                "NZD": "NZ$",
                "THB": "฿",
                "TWD": "NT$",
                "USD": "$",
                "VND": "₫",
                "XAF": "FCFA",
                "XCD": "EC$",
                "XOF": "CFA",
                "XPF": "CFPF"
            }
        }
    });
}
