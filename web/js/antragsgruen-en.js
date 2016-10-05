/*global Intl */


ANTRAGSGRUEN_STRINGS = {
    "std": {
        "del_confirm": "Really delete it?",
        "draft_del": "Delete draft",
        "draft_del_confirm": "Really delete this draft?",
        "draft_restore_confirm": "Really restore this draft?",
        "min_x_supporter": "You have to enter at least %NUM% supporters.",
        "missing_resolution_date": "A resolution date has to be entered.",
        "pw_x_chars": "The password needs to be at least %NUM% characters long.",
        "pw_no_match": 'The passwords do not match.'
    },
    "merge": {
        "initiated_by": "Proposed by",
        "title_open_in_blank": "Open the amendment in a new window",
        "title_del_title": "Remove the headline \"Colliding Amendment: ...\"",
        "title_del_colliding": "Rmove the whole colliding paragraph",
        "title": "Headline",
        "insert_accept": "Accept",
        "insert_reject": "Reject",
        "delete_accept": "Delete",
        "delete_reject": "Keep it",
        "colliding_title": "Colliding amendm."
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
        "deleteMotionSectionConfirm": "Do you really want to delete this section? It will be deleted from all motions of this motion type."
    }
};


if (typeof(Intl.__addLocaleData) != 'undefined') {
    Intl.__addLocaleData({
    "locale": "en",
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
        "hour12": true,
        "formats": {
            "short": "{1}, {0}",
            "medium": "{1}, {0}",
            "full": "{1} 'at' {0}",
            "long": "{1} 'at' {0}",
            "availableFormats": {
                "d": "d",
                "E": "ccc",
                "Ed": "d E",
                "Ehm": "E h:mm a",
                "EHm": "E HH:mm",
                "Ehms": "E h:mm:ss a",
                "EHms": "E HH:mm:ss",
                "Gy": "y G",
                "GyMMM": "MMM y G",
                "GyMMMd": "MMM d, y G",
                "GyMMMEd": "E, MMM d, y G",
                "h": "h a",
                "H": "HH",
                "hm": "h:mm a",
                "Hm": "HH:mm",
                "hms": "h:mm:ss a",
                "Hms": "HH:mm:ss",
                "hmsv": "h:mm:ss a v",
                "Hmsv": "HH:mm:ss v",
                "hmv": "h:mm a v",
                "Hmv": "HH:mm v",
                "M": "L",
                "Md": "M/d",
                "MEd": "E, M/d",
                "MMM": "LLL",
                "MMMd": "MMM d",
                "MMMEd": "E, MMM d",
                "MMMMd": "MMMM d",
                "ms": "mm:ss",
                "y": "y",
                "yM": "M/y",
                "yMd": "M/d/y",
                "yMEd": "E, M/d/y",
                "yMMM": "MMM y",
                "yMMMd": "MMM d, y",
                "yMMMEd": "E, MMM d, y",
                "yMMMM": "MMMM y",
                "yQQQ": "QQQ y",
                "yQQQQ": "QQQQ y"
            },
            "dateFormats": {
                "yMMMMEEEEd": "EEEE, MMMM d, y",
                "yMMMMd": "MMMM d, y",
                "yMMMd": "MMM d, y",
                "yMd": "M/d/yy"
            },
            "timeFormats": {
                "hmmsszzzz": "h:mm:ss a zzzz",
                "hmsz": "h:mm:ss a z",
                "hms": "h:mm:ss a",
                "hm": "h:mm a"
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
                        "Jan",
                        "Feb",
                        "Mar",
                        "Apr",
                        "May",
                        "Jun",
                        "Jul",
                        "Aug",
                        "Sep",
                        "Oct",
                        "Nov",
                        "Dec"
                    ],
                    "long": [
                        "January",
                        "February",
                        "March",
                        "April",
                        "May",
                        "June",
                        "July",
                        "August",
                        "September",
                        "October",
                        "November",
                        "December"
                    ]
                },
                "days": {
                    "narrow": [
                        "S",
                        "M",
                        "T",
                        "W",
                        "T",
                        "F",
                        "S"
                    ],
                    "short": [
                        "Sun",
                        "Mon",
                        "Tue",
                        "Wed",
                        "Thu",
                        "Fri",
                        "Sat"
                    ],
                    "long": [
                        "Sunday",
                        "Monday",
                        "Tuesday",
                        "Wednesday",
                        "Thursday",
                        "Friday",
                        "Saturday"
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
                    "am": "AM",
                    "pm": "PM"
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
                        "Mo1",
                        "Mo2",
                        "Mo3",
                        "Mo4",
                        "Mo5",
                        "Mo6",
                        "Mo7",
                        "Mo8",
                        "Mo9",
                        "Mo10",
                        "Mo11",
                        "Mo12"
                    ],
                    "long": [
                        "Month1",
                        "Month2",
                        "Month3",
                        "Month4",
                        "Month5",
                        "Month6",
                        "Month7",
                        "Month8",
                        "Month9",
                        "Month10",
                        "Month11",
                        "Month12"
                    ]
                },
                "days": {
                    "narrow": [
                        "S",
                        "M",
                        "T",
                        "W",
                        "T",
                        "F",
                        "S"
                    ],
                    "short": [
                        "Sun",
                        "Mon",
                        "Tue",
                        "Wed",
                        "Thu",
                        "Fri",
                        "Sat"
                    ],
                    "long": [
                        "Sunday",
                        "Monday",
                        "Tuesday",
                        "Wednesday",
                        "Thursday",
                        "Friday",
                        "Saturday"
                    ]
                },
                "dayPeriods": {
                    "am": "AM",
                    "pm": "PM"
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
                        "T",
                        "W",
                        "T",
                        "F",
                        "S"
                    ],
                    "short": [
                        "Sun",
                        "Mon",
                        "Tue",
                        "Wed",
                        "Thu",
                        "Fri",
                        "Sat"
                    ],
                    "long": [
                        "Sunday",
                        "Monday",
                        "Tuesday",
                        "Wednesday",
                        "Thursday",
                        "Friday",
                        "Saturday"
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
                        "Mo1",
                        "Mo2",
                        "Mo3",
                        "Mo4",
                        "Mo5",
                        "Mo6",
                        "Mo7",
                        "Mo8",
                        "Mo9",
                        "Mo10",
                        "Mo11",
                        "Mo12"
                    ],
                    "long": [
                        "Month1",
                        "Month2",
                        "Month3",
                        "Month4",
                        "Month5",
                        "Month6",
                        "Month7",
                        "Month8",
                        "Month9",
                        "Month10",
                        "Month11",
                        "Month12"
                    ]
                },
                "days": {
                    "narrow": [
                        "S",
                        "M",
                        "T",
                        "W",
                        "T",
                        "F",
                        "S"
                    ],
                    "short": [
                        "Sun",
                        "Mon",
                        "Tue",
                        "Wed",
                        "Thu",
                        "Fri",
                        "Sat"
                    ],
                    "long": [
                        "Sunday",
                        "Monday",
                        "Tuesday",
                        "Wednesday",
                        "Thursday",
                        "Friday",
                        "Saturday"
                    ]
                },
                "dayPeriods": {
                    "am": "AM",
                    "pm": "PM"
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
                        "T",
                        "W",
                        "T",
                        "F",
                        "S"
                    ],
                    "short": [
                        "Sun",
                        "Mon",
                        "Tue",
                        "Wed",
                        "Thu",
                        "Fri",
                        "Sat"
                    ],
                    "long": [
                        "Sunday",
                        "Monday",
                        "Tuesday",
                        "Wednesday",
                        "Thursday",
                        "Friday",
                        "Saturday"
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
                        "T",
                        "W",
                        "T",
                        "F",
                        "S"
                    ],
                    "short": [
                        "Sun",
                        "Mon",
                        "Tue",
                        "Wed",
                        "Thu",
                        "Fri",
                        "Sat"
                    ],
                    "long": [
                        "Sunday",
                        "Monday",
                        "Tuesday",
                        "Wednesday",
                        "Thursday",
                        "Friday",
                        "Saturday"
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
                    "am": "AM",
                    "pm": "PM"
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
                        "T",
                        "W",
                        "T",
                        "F",
                        "S"
                    ],
                    "short": [
                        "Sun",
                        "Mon",
                        "Tue",
                        "Wed",
                        "Thu",
                        "Fri",
                        "Sat"
                    ],
                    "long": [
                        "Sunday",
                        "Monday",
                        "Tuesday",
                        "Wednesday",
                        "Thursday",
                        "Friday",
                        "Saturday"
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
                        "Jan",
                        "Feb",
                        "Mar",
                        "Apr",
                        "May",
                        "Jun",
                        "Jul",
                        "Aug",
                        "Sep",
                        "Oct",
                        "Nov",
                        "Dec"
                    ],
                    "long": [
                        "January",
                        "February",
                        "March",
                        "April",
                        "May",
                        "June",
                        "July",
                        "August",
                        "September",
                        "October",
                        "November",
                        "December"
                    ]
                },
                "days": {
                    "narrow": [
                        "S",
                        "M",
                        "T",
                        "W",
                        "T",
                        "F",
                        "S"
                    ],
                    "short": [
                        "Sun",
                        "Mon",
                        "Tue",
                        "Wed",
                        "Thu",
                        "Fri",
                        "Sat"
                    ],
                    "long": [
                        "Sunday",
                        "Monday",
                        "Tuesday",
                        "Wednesday",
                        "Thursday",
                        "Friday",
                        "Saturday"
                    ]
                },
                "eras": {
                    "narrow": [
                        "B",
                        "A",
                        "BCE",
                        "CE"
                    ],
                    "short": [
                        "BC",
                        "AD",
                        "BCE",
                        "CE"
                    ],
                    "long": [
                        "Before Christ",
                        "Anno Domini",
                        "Before Common Era",
                        "Common Era"
                    ]
                },
                "dayPeriods": {
                    "am": "AM",
                    "pm": "PM"
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
                        "T",
                        "W",
                        "T",
                        "F",
                        "S"
                    ],
                    "short": [
                        "Sun",
                        "Mon",
                        "Tue",
                        "Wed",
                        "Thu",
                        "Fri",
                        "Sat"
                    ],
                    "long": [
                        "Sunday",
                        "Monday",
                        "Tuesday",
                        "Wednesday",
                        "Thursday",
                        "Friday",
                        "Saturday"
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
                    "am": "AM",
                    "pm": "PM"
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
                        "T",
                        "W",
                        "T",
                        "F",
                        "S"
                    ],
                    "short": [
                        "Sun",
                        "Mon",
                        "Tue",
                        "Wed",
                        "Thu",
                        "Fri",
                        "Sat"
                    ],
                    "long": [
                        "Sunday",
                        "Monday",
                        "Tuesday",
                        "Wednesday",
                        "Thursday",
                        "Friday",
                        "Saturday"
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
                    "am": "AM",
                    "pm": "PM"
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
                        "T",
                        "W",
                        "T",
                        "F",
                        "S"
                    ],
                    "short": [
                        "Sun",
                        "Mon",
                        "Tue",
                        "Wed",
                        "Thu",
                        "Fri",
                        "Sat"
                    ],
                    "long": [
                        "Sunday",
                        "Monday",
                        "Tuesday",
                        "Wednesday",
                        "Thursday",
                        "Friday",
                        "Saturday"
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
                    "am": "AM",
                    "pm": "PM"
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
                        "T",
                        "W",
                        "T",
                        "F",
                        "S"
                    ],
                    "short": [
                        "Sun",
                        "Mon",
                        "Tue",
                        "Wed",
                        "Thu",
                        "Fri",
                        "Sat"
                    ],
                    "long": [
                        "Sunday",
                        "Monday",
                        "Tuesday",
                        "Wednesday",
                        "Thursday",
                        "Friday",
                        "Saturday"
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
                    "am": "AM",
                    "pm": "PM"
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
                        "Jan",
                        "Feb",
                        "Mar",
                        "Apr",
                        "May",
                        "Jun",
                        "Jul",
                        "Aug",
                        "Sep",
                        "Oct",
                        "Nov",
                        "Dec"
                    ],
                    "long": [
                        "January",
                        "February",
                        "March",
                        "April",
                        "May",
                        "June",
                        "July",
                        "August",
                        "September",
                        "October",
                        "November",
                        "December"
                    ]
                },
                "days": {
                    "narrow": [
                        "S",
                        "M",
                        "T",
                        "W",
                        "T",
                        "F",
                        "S"
                    ],
                    "short": [
                        "Sun",
                        "Mon",
                        "Tue",
                        "Wed",
                        "Thu",
                        "Fri",
                        "Sat"
                    ],
                    "long": [
                        "Sunday",
                        "Monday",
                        "Tuesday",
                        "Wednesday",
                        "Thursday",
                        "Friday",
                        "Saturday"
                    ]
                },
                "dayPeriods": {
                    "am": "AM",
                    "pm": "PM"
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
                        "T",
                        "W",
                        "T",
                        "F",
                        "S"
                    ],
                    "short": [
                        "Sun",
                        "Mon",
                        "Tue",
                        "Wed",
                        "Thu",
                        "Fri",
                        "Sat"
                    ],
                    "long": [
                        "Sunday",
                        "Monday",
                        "Tuesday",
                        "Wednesday",
                        "Thursday",
                        "Friday",
                        "Saturday"
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
                    "am": "AM",
                    "pm": "PM"
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
                        "Jan",
                        "Feb",
                        "Mar",
                        "Apr",
                        "May",
                        "Jun",
                        "Jul",
                        "Aug",
                        "Sep",
                        "Oct",
                        "Nov",
                        "Dec"
                    ],
                    "long": [
                        "January",
                        "February",
                        "March",
                        "April",
                        "May",
                        "June",
                        "July",
                        "August",
                        "September",
                        "October",
                        "November",
                        "December"
                    ]
                },
                "days": {
                    "narrow": [
                        "S",
                        "M",
                        "T",
                        "W",
                        "T",
                        "F",
                        "S"
                    ],
                    "short": [
                        "Sun",
                        "Mon",
                        "Tue",
                        "Wed",
                        "Thu",
                        "Fri",
                        "Sat"
                    ],
                    "long": [
                        "Sunday",
                        "Monday",
                        "Tuesday",
                        "Wednesday",
                        "Thursday",
                        "Friday",
                        "Saturday"
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
                "positivePattern": "{currency}{number}",
                "negativePattern": "{minusSign}{currency}{number}"
            },
            "percent": {
                "positivePattern": "{number}{percentSign}",
                "negativePattern": "{minusSign}{number}{percentSign}"
            }
        },
        "symbols": {
            "latn": {
                "decimal": ".",
                "group": ",",
                "nan": "NaN",
                "plusSign": "+",
                "minusSign": "-",
                "percentSign": "%",
                "infinity": "∞"
            }
        },
        "currencies": {
            "AUD": "A$",
            "BRL": "R$",
            "CAD": "CA$",
            "CNY": "CN¥",
            "EUR": "€",
            "GBP": "£",
            "HKD": "HK$",
            "ILS": "₪",
            "INR": "₹",
            "JPY": "¥",
            "KRW": "₩",
            "MXN": "MX$",
            "NZD": "NZ$",
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
