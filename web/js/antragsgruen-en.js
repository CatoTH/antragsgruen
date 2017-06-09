/*global Intl */


ANTRAGSGRUEN_STRINGS = {
    "std": {
        "del_confirm": "Really delete it?",
        "draft_del": "Delete draft",
        "draft_del_confirm": "Really delete this draft?",
        "draft_date": "Draft date",
        "draft_restore_confirm": "Really restore this draft?",
        "min_x_supporter": "You have to enter at least %NUM% supporters.",
        "missing_resolution_date": "A resolution date has to be entered.",
        "pw_x_chars": "The password needs to be at least %NUM% characters long.",
        "pw_min_x_chars": "Min. %NUM% characters",
        "pw_no_match": "The passwords do not match.",
        "leave_changed_page": "There are unsaved changes. Do you really want to leave this page and discard those changes?",
        "moved_paragraph_from": "Moved from paragraph ##PARA##",
        "moved_paragraph_to": "Moved to paragraph ##PARA##",
        "moved_paragraph_from_line": "Moved from paragraph ##PARA## (line ##LINE##)",
        "moved_paragraph_to_line": "Moved to paragraph ##PARA##(line ##LINE##)"
    },
    "merge": {
        "initiated_by": "Proposed by",
        "title_open_in_blank": "Open the amendment in a new window",
        "title_del_title": "Remove the headline \"Colliding Amendment: ...\"",
        "title_del_colliding": "Rmove the whole colliding paragraph",
        "title": "Headline",
        "change_accept": "Accept",
        "change_reject": "Reject",
        "colliding_title": "Colliding amendm.",
        "colliding_start": "Collissions start here",
        "colliding_end": "Collissions end here"
    },
    "admin": {
        "adminMayEditConfirm": "If this is deactivated, this cannot be undone for all motions created up to now.",
        "deleteDataConfirm": "Really delete this?",
        "agendaAddEntry": "Add entry",
        "agendaDelEntryConfirm": "Delete this agenda item and all sub-items?",
        "removeAdminConfirm": "Do you really want to remove admin right from this user?",
        "emailMissingCode": "The text needs to contain the code %ACCOUNT%.",
        "emailMissingLink": "The text needs to contain the code %LINK%.",
        "emailMissingTo": "No e-mail-address was entered.",
        "emailNumberMismatch": "The number of names and e-mail-addresses does not match.",
        "delMotionConfirm": "Do you really want to delete this motion?",
        "delAmendmentConfirm": "Do you really want to delete this amendment?",
        "deleteMotionSectionConfirm": "Do you really want to delete this section? It will be deleted from all motions of this motion type.",
        "consDeleteConfirm": "Do you really want to delete this consultation, including all motions and amendments?"
    }
};


if (typeof(Intl.__addLocaleData) != 'undefined') {
    Intl.__addLocaleData({
    "locale": "fr",
    "date": {
        "ca": [
            "gregory",
            "generic"
        ],
        "hourNo0": true,
        "hour12": false,
        "formats": {
            "short": "{1} {0}",
            "medium": "{1} 'à' {0}",
            "full": "{1} 'à' {0}",
            "long": "{1} 'à' {0}",
            "availableFormats": {
                "d": "d",
                "E": "E",
                "Ed": "E d",
                "Ehm": "E h:mm a",
                "EHm": "E HH:mm",
                "Ehms": "E h:mm:ss a",
                "EHms": "E HH:mm:ss",
                "Gy": "y G",
                "GyMMM": "MMM y G",
                "GyMMMd": "d MMM y G",
                "GyMMMEd": "E d MMM y G",
                "h": "h a",
                "H": "HH 'h'",
                "hm": "h:mm a",
                "Hm": "HH:mm",
                "hms": "h:mm:ss a",
                "Hms": "HH:mm:ss",
                "hmsv": "h:mm:ss a v",
                "Hmsv": "HH:mm:ss v",
                "hmv": "h:mm a v",
                "Hmv": "HH:mm v",
                "M": "L",
                "Md": "dd/MM",
                "MEd": "E dd/MM",
                "MMM": "LLL",
                "MMMd": "d MMM",
                "MMMEd": "E d MMM",
                "MMMMd": "d MMMM",
                "ms": "mm:ss",
                "y": "y",
                "yM": "MM/y",
                "yMd": "dd/MM/y",
                "yMEd": "E dd/MM/y",
                "yMMM": "MMM y",
                "yMMMd": "d MMM y",
                "yMMMEd": "E d MMM y",
                "yMMMM": "MMMM y",
                "yQQQ": "QQQ y",
                "yQQQQ": "QQQQ y"
            },
            "dateFormats": {
                "yMMMMEEEEd": "EEEE d MMMM y",
                "yMMMMd": "d MMMM y",
                "yMMMd": "d MMM y",
                "yMd": "dd/MM/y"
            },
            "timeFormats": {
                "hmmsszzzz": "HH:mm:ss zzzz",
                "hmsz": "HH:mm:ss z",
                "hms": "HH:mm:ss",
                "hm": "HH:mm"
            }
        },
        "calendars": {
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
                        "D",
                        "L",
                        "M",
                        "M",
                        "J",
                        "V",
                        "S"
                    ],
                    "short": [
                        "dim.",
                        "lun.",
                        "mar.",
                        "mer.",
                        "jeu.",
                        "ven.",
                        "sam."
                    ],
                    "long": [
                        "dimanche",
                        "lundi",
                        "mardi",
                        "mercredi",
                        "jeudi",
                        "vendredi",
                        "samedi"
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
                    "am": "AM",
                    "pm": "PM"
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
                        "janv.",
                        "févr.",
                        "mars",
                        "avr.",
                        "mai",
                        "juin",
                        "juil.",
                        "août",
                        "sept.",
                        "oct.",
                        "nov.",
                        "déc."
                    ],
                    "long": [
                        "janvier",
                        "février",
                        "mars",
                        "avril",
                        "mai",
                        "juin",
                        "juillet",
                        "août",
                        "septembre",
                        "octobre",
                        "novembre",
                        "décembre"
                    ]
                },
                "days": {
                    "narrow": [
                        "D",
                        "L",
                        "M",
                        "M",
                        "J",
                        "V",
                        "S"
                    ],
                    "short": [
                        "dim.",
                        "lun.",
                        "mar.",
                        "mer.",
                        "jeu.",
                        "ven.",
                        "sam."
                    ],
                    "long": [
                        "dimanche",
                        "lundi",
                        "mardi",
                        "mercredi",
                        "jeudi",
                        "vendredi",
                        "samedi"
                    ]
                },
                "eras": {
                    "narrow": [
                        "av. J.-C.",
                        "ap. J.-C.",
                        "AEC",
                        "EC"
                    ],
                    "short": [
                        "av. J.-C.",
                        "ap. J.-C.",
                        "AEC",
                        "EC"
                    ],
                    "long": [
                        "avant Jésus-Christ",
                        "après Jésus-Christ",
                        "avant l’ère commune",
                        "de l’ère commune"
                    ]
                },
                "dayPeriods": {
                    "am": "AM",
                    "pm": "PM"
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
                "positivePattern": "{number} {currency}",
                "negativePattern": "{minusSign}{number} {currency}"
            },
            "percent": {
                "positivePattern": "{number} {percentSign}",
                "negativePattern": "{minusSign}{number} {percentSign}"
            }
        },
        "symbols": {
            "latn": {
                "decimal": ",",
                "group": " ",
                "nan": "NaN",
                "plusSign": "+",
                "minusSign": "-",
                "percentSign": "%",
                "infinity": "∞"
            }
        },
        "currencies": {
            "ARS": "$AR",
            "AUD": "$AU",
            "BEF": "FB",
            "BMD": "$BM",
            "BND": "$BN",
            "BRL": "R$",
            "BSD": "$BS",
            "BZD": "$BZ",
            "CAD": "$CA",
            "CLP": "$CL",
            "COP": "$CO",
            "CYP": "£CY",
            "EUR": "€",
            "FJD": "$FJ",
            "FKP": "£FK",
            "FRF": "F",
            "GBP": "£GB",
            "GIP": "£GI",
            "IEP": "£IE",
            "ILP": "£IL",
            "ILS": "₪",
            "INR": "₹",
            "ITL": "₤IT",
            "KRW": "₩",
            "LBP": "£LB",
            "MTP": "£MT",
            "MXN": "$MX",
            "NAD": "$NA",
            "NZD": "$NZ",
            "RHD": "$RH",
            "SBD": "$SB",
            "SGD": "$SG",
            "SRD": "$SR",
            "TTD": "$TT",
            "USD": "$US",
            "UYU": "$UY",
            "VND": "₫",
            "WST": "WS$",
            "XAF": "FCFA",
            "XOF": "CFA",
            "XPF": "FCFP"
        }
    }
});
}
