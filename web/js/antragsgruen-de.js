/*global Intl */

ANTRAGSGRUEN_STRINGS = {
    "std": {
        "del_confirm": "Wirklich löschen?",
        "draft_del": "Entwurf löschen",
        "draft_del_confirm": "Entwurf wirklich löschen?",
        "draft_date": "Entwurf vom",
        "draft_restore_confirm": "Diesen Entwurf wiederherstellen?",
        "min_x_supporter": "Es müssen mindestens %NUM% Unterstützer*innen angegeben werden.",
        "missing_resolution_date": "Es muss ein Beschlussdatum angegeben werden.",
        "pw_x_chars": "Das Passwort muss mindestens %NUM% Zeichen lang sein.",
        "pw_min_x_chars": "Min. %NUM% Zeichen",
        "pw_no_match": "Die beiden Passwörter stimmen nicht überein.",
        "leave_changed_page": "Es gibt noch ungespeicherte Änderungen. Diese Seite wirklich verlassen?",
        "moved_paragraph_from": "Verschoben von Absatz ##PARA##",
        "moved_paragraph_to": "Verschoben zu Absatz ##PARA##",
        "moved_paragraph_from_line": "Verschoben von Absatz ##PARA## (Zeile ##LINE##)",
        "moved_paragraph_to_line": "Verschoben zu Absatz ##PARA## (Zeile ##LINE##)"
    },
    "merge": {
        "initiated_by": "Gestellt von",
        "title_open_in_blank": "Den Änderungsantrag in einem neuen Fenster öffnen",
        "title_del_title": "Die Überschrift \"Kollidierender Änderungsantrag: ...\" entfernen",
        "title_del_colliding": "Den kompletten kollidierenden Block entfernen",
        "title": "Überschrift",
        "change_accept": "Übernehmen",
        "change_reject": "Verwerfen",
        "colliding_title": "Kollidierender ÄA",
        "colliding_start": "Ab hier Kollissionen",
        "colliding_end": "Bis hier Kollissionen"
    },
    "admin": {
        "adminMayEditConfirm": "Wenn dies deaktiviert wird, wirkt sich das auch auf alle bisherigen Anträge aus und kann für bisherige Anträge nicht rückgängig gemacht werden. Wirklich setzen?",
        "deleteDataConfirm": "Diese Angabe wirklich löschen?",
        "agendaAddEntry": "Eintrag hinzufügen",
        "agendaDelEntryConfirm": "Diesen Tagesordnungspunkt mitsamit Unterpunkten löschen?",
        "removeAdminConfirm": "Diesem Zugang wirklich die Admin-Rechte entziehen?",
        "emailMissingCode": "Im E-Mail-Text muss der Code %ACCOUNT% vorkommen.",
        "emailMissingLink": "Im E-Mail-Text muss der Code %LINK% vorkommen.",
        "emailMissingTo": "Es wurden keine E-Mail-Adressen angegeben.",
        "emailNumberMismatch": "Es wurden nicht genauso viele Namen wie E-Mail-Adressen angegeben. Bitte achte darauf, dass für jede Zeile bei den E-Mail-Adressen exakt ein Name angegeben wird!",
        "delMotionConfirm": "Diesen Antrag wirklich löschen?",
        "delAmendmentConfirm": "Diesen Änderungsantrag wirklich löschen?",
        "deleteMotionSectionConfirm": "Wenn dieser Abschnitt gelöscht wird, wird er auch unwiderruflich in allen Anträgen dieses Types gelöscht. Wirklich löschen?",
        "consDeleteConfirm": "Soll diese Veranstaltung mitsamt aller Anträge und Änderungsanträge wirklich gelöscht werden?"
    }
};


if (typeof(Intl.__addLocaleData) != 'undefined') {
    Intl.__addLocaleData({
        "locale": "de",
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
                "full": "{1} 'um' {0}",
                "long": "{1} 'um' {0}",
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
                            "S",
                            "M",
                            "D",
                            "M",
                            "D",
                            "F",
                            "S"
                        ],
                        "short": [
                            "So.",
                            "Mo.",
                            "Di.",
                            "Mi.",
                            "Do.",
                            "Fr.",
                            "Sa."
                        ],
                        "long": [
                            "Sonntag",
                            "Montag",
                            "Dienstag",
                            "Mittwoch",
                            "Donnerstag",
                            "Freitag",
                            "Samstag"
                        ]
                    },
                    "eras": {
                        "narrow": [
                            "BE"
                        ],
                        "short": [
                            "BE"
                        ],
                        "long": [
                            "BE"
                        ]
                    },
                    "dayPeriods": {
                        "am": "vorm.",
                        "pm": "nachm."
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
                            "S",
                            "M",
                            "D",
                            "M",
                            "D",
                            "F",
                            "S"
                        ],
                        "short": [
                            "So.",
                            "Mo.",
                            "Di.",
                            "Mi.",
                            "Do.",
                            "Fr.",
                            "Sa."
                        ],
                        "long": [
                            "Sonntag",
                            "Montag",
                            "Dienstag",
                            "Mittwoch",
                            "Donnerstag",
                            "Freitag",
                            "Samstag"
                        ]
                    },
                    "dayPeriods": {
                        "am": "vorm.",
                        "pm": "nachm."
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
                            "S",
                            "M",
                            "D",
                            "M",
                            "D",
                            "F",
                            "S"
                        ],
                        "short": [
                            "So.",
                            "Mo.",
                            "Di.",
                            "Mi.",
                            "Do.",
                            "Fr.",
                            "Sa."
                        ],
                        "long": [
                            "Sonntag",
                            "Montag",
                            "Dienstag",
                            "Mittwoch",
                            "Donnerstag",
                            "Freitag",
                            "Samstag"
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
                        "am": "vorm.",
                        "pm": "nachm."
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
                            "S",
                            "M",
                            "D",
                            "M",
                            "D",
                            "F",
                            "S"
                        ],
                        "short": [
                            "So.",
                            "Mo.",
                            "Di.",
                            "Mi.",
                            "Do.",
                            "Fr.",
                            "Sa."
                        ],
                        "long": [
                            "Sonntag",
                            "Montag",
                            "Dienstag",
                            "Mittwoch",
                            "Donnerstag",
                            "Freitag",
                            "Samstag"
                        ]
                    },
                    "dayPeriods": {
                        "am": "vorm.",
                        "pm": "nachm."
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
                            "S",
                            "M",
                            "D",
                            "M",
                            "D",
                            "F",
                            "S"
                        ],
                        "short": [
                            "So.",
                            "Mo.",
                            "Di.",
                            "Mi.",
                            "Do.",
                            "Fr.",
                            "Sa."
                        ],
                        "long": [
                            "Sonntag",
                            "Montag",
                            "Dienstag",
                            "Mittwoch",
                            "Donnerstag",
                            "Freitag",
                            "Samstag"
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
                        "am": "vorm.",
                        "pm": "nachm."
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
                            "S",
                            "M",
                            "D",
                            "M",
                            "D",
                            "F",
                            "S"
                        ],
                        "short": [
                            "So.",
                            "Mo.",
                            "Di.",
                            "Mi.",
                            "Do.",
                            "Fr.",
                            "Sa."
                        ],
                        "long": [
                            "Sonntag",
                            "Montag",
                            "Dienstag",
                            "Mittwoch",
                            "Donnerstag",
                            "Freitag",
                            "Samstag"
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
                        "am": "vorm.",
                        "pm": "nachm."
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
                            "S",
                            "M",
                            "D",
                            "M",
                            "D",
                            "F",
                            "S"
                        ],
                        "short": [
                            "So.",
                            "Mo.",
                            "Di.",
                            "Mi.",
                            "Do.",
                            "Fr.",
                            "Sa."
                        ],
                        "long": [
                            "Sonntag",
                            "Montag",
                            "Dienstag",
                            "Mittwoch",
                            "Donnerstag",
                            "Freitag",
                            "Samstag"
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
                        "am": "vorm.",
                        "pm": "nachm."
                    }
                },
                "gregory": {
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
                            "S",
                            "M",
                            "D",
                            "M",
                            "D",
                            "F",
                            "S"
                        ],
                        "short": [
                            "So.",
                            "Mo.",
                            "Di.",
                            "Mi.",
                            "Do.",
                            "Fr.",
                            "Sa."
                        ],
                        "long": [
                            "Sonntag",
                            "Montag",
                            "Dienstag",
                            "Mittwoch",
                            "Donnerstag",
                            "Freitag",
                            "Samstag"
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
                            "vor unserer Zeitrechnung",
                            "unserer Zeitrechnung"
                        ]
                    },
                    "dayPeriods": {
                        "am": "vorm.",
                        "pm": "nachm."
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
                            "S",
                            "M",
                            "D",
                            "M",
                            "D",
                            "F",
                            "S"
                        ],
                        "short": [
                            "So.",
                            "Mo.",
                            "Di.",
                            "Mi.",
                            "Do.",
                            "Fr.",
                            "Sa."
                        ],
                        "long": [
                            "Sonntag",
                            "Montag",
                            "Dienstag",
                            "Mittwoch",
                            "Donnerstag",
                            "Freitag",
                            "Samstag"
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
                        "am": "vorm.",
                        "pm": "nachm."
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
                            "S",
                            "M",
                            "D",
                            "M",
                            "D",
                            "F",
                            "S"
                        ],
                        "short": [
                            "So.",
                            "Mo.",
                            "Di.",
                            "Mi.",
                            "Do.",
                            "Fr.",
                            "Sa."
                        ],
                        "long": [
                            "Sonntag",
                            "Montag",
                            "Dienstag",
                            "Mittwoch",
                            "Donnerstag",
                            "Freitag",
                            "Samstag"
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
                        "am": "vorm.",
                        "pm": "nachm."
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
                            "S",
                            "M",
                            "D",
                            "M",
                            "D",
                            "F",
                            "S"
                        ],
                        "short": [
                            "So.",
                            "Mo.",
                            "Di.",
                            "Mi.",
                            "Do.",
                            "Fr.",
                            "Sa."
                        ],
                        "long": [
                            "Sonntag",
                            "Montag",
                            "Dienstag",
                            "Mittwoch",
                            "Donnerstag",
                            "Freitag",
                            "Samstag"
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
                        "am": "vorm.",
                        "pm": "nachm."
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
                            "S",
                            "M",
                            "D",
                            "M",
                            "D",
                            "F",
                            "S"
                        ],
                        "short": [
                            "So.",
                            "Mo.",
                            "Di.",
                            "Mi.",
                            "Do.",
                            "Fr.",
                            "Sa."
                        ],
                        "long": [
                            "Sonntag",
                            "Montag",
                            "Dienstag",
                            "Mittwoch",
                            "Donnerstag",
                            "Freitag",
                            "Samstag"
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
                        "am": "vorm.",
                        "pm": "nachm."
                    }
                },
                "japanese": {
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
                            "S",
                            "M",
                            "D",
                            "M",
                            "D",
                            "F",
                            "S"
                        ],
                        "short": [
                            "So.",
                            "Mo.",
                            "Di.",
                            "Mi.",
                            "Do.",
                            "Fr.",
                            "Sa."
                        ],
                        "long": [
                            "Sonntag",
                            "Montag",
                            "Dienstag",
                            "Mittwoch",
                            "Donnerstag",
                            "Freitag",
                            "Samstag"
                        ]
                    },
                    "dayPeriods": {
                        "am": "vorm.",
                        "pm": "nachm."
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
                            "S",
                            "M",
                            "D",
                            "M",
                            "D",
                            "F",
                            "S"
                        ],
                        "short": [
                            "So.",
                            "Mo.",
                            "Di.",
                            "Mi.",
                            "Do.",
                            "Fr.",
                            "Sa."
                        ],
                        "long": [
                            "Sonntag",
                            "Montag",
                            "Dienstag",
                            "Mittwoch",
                            "Donnerstag",
                            "Freitag",
                            "Samstag"
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
                        "am": "vorm.",
                        "pm": "nachm."
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
                            "S",
                            "M",
                            "D",
                            "M",
                            "D",
                            "F",
                            "S"
                        ],
                        "short": [
                            "So.",
                            "Mo.",
                            "Di.",
                            "Mi.",
                            "Do.",
                            "Fr.",
                            "Sa."
                        ],
                        "long": [
                            "Sonntag",
                            "Montag",
                            "Dienstag",
                            "Mittwoch",
                            "Donnerstag",
                            "Freitag",
                            "Samstag"
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
                    "positivePattern": "{number} {currency}",
                    "negativePattern": "{minusSign}{number} {currency}"
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
