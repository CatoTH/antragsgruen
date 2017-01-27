/*global browser: true, regexp: true, localStorage */
/*global $, jQuery, alert, console, CKEDITOR, document, Intl, JSON, ANTRAGSGRUEN_STRINGS */
/*jslint regexp: true*/

function __t(category, str) {
    if (typeof(ANTRAGSGRUEN_STRINGS) == "undefined") {
        return '@TRANSLATION STRINGS NOT LOADED';
    }
    if (typeof(ANTRAGSGRUEN_STRINGS[category]) == "undefined") {
        return "@UNKNOWN CATEGORY: " + category
    }
    if (typeof(ANTRAGSGRUEN_STRINGS[category][str]) == "undefined") {
        return "@UNKNOWN STRING: " + category + " / " + str;
    }
    return ANTRAGSGRUEN_STRINGS[category][str];
}

(function ($) {
    "use strict";


// From ckeditor/plugins/wordcount/plugin.js
    function ckeditor_strip(html) {
        var tmp = document.createElement("div");
        tmp.innerHTML = html;

        if (tmp.textContent == '' && typeof tmp.innerText == 'undefined') {
            return '';
        }

        return tmp.textContent || tmp.innerText;
    }

    function ckeditor_charcount(text) {
        var normalizedText = text.replace(/(\r\n|\n|\r)/gm, "").replace(/^\s+|\s+$/g, "").replace("&nbsp;", "");
        normalizedText = ckeditor_strip(normalizedText).replace(/^([\s\t\r\n]*)$/, "");

        return normalizedText.length;
    }

    function ckeditorInit(id) {
        var $el = $("#" + id),
            initialized = $el.data("ckeditor_initialized"),
            allowedContent;
        if (typeof (initialized) != "undefined" && initialized) {
            return;
        }
        $el.data("ckeditor_initialized", "1");
        $el.attr("contenteditable", true);

        var ckeditorConfig = {
            coreStyles_strike: {
                element: 'span',
                attributes: {'class': 'strike'},
                overrides: 'strike'
            },
            coreStyles_underline: {
                element: 'span',
                attributes: {'class': 'underline'}
            },
            toolbarGroups: [
                {name: 'tools'},
                {name: 'document', groups: ['mode', 'document', 'doctools']},
                //{name: 'clipboard', groups: ['clipboard', 'undo']},
                //{name: 'editing', groups: ['find', 'selection', 'spellchecker']},
                //{name: 'forms'},
                {name: 'basicstyles', groups: ['basicstyles', 'cleanup']},
                {name: 'paragraph', groups: ['list', 'indent', 'blocks', 'align', 'bidi']},
                {name: 'links'},
                {name: 'insert'},
                {name: 'styles'},
                {name: 'colors'},
                {name: 'others'}
            ],
            removePlugins: 'stylescombo,save,showblocks,specialchar,about,preview,pastetext',
            extraPlugins: 'tabletools',
            scayt_sLang: 'de_DE',
            title: $el.attr("title")
        };

        var strikeEl = ($el.data("no-strike") == '1' ? '' : ' s'),
            strikeClass = ($el.data("no-strike") == '1' ? '' : ',strike');

        if ($el.data('track-changed') == '1' || $el.data('allow-diff-formattings') == '1') {
            allowedContent = 'strong' + strikeEl + ' em u sub sup;' +
                'h2 h3 h4;' +
                'ul ol li [data-*](ice-ins,ice-del,ice-cts,appendHint){list-style-type};' +
                //'table tr td th tbody thead caption [border] {margin,padding,width,height,border,border-spacing,border-collapse,align,cellspacing,cellpadding};' +
                'div [data-*](collidingParagraph,paragraphHolder,hasCollissions);' +
                'p blockquote [data-*](ice-ins,ice-del,ice-cts,appendHint,collidingParagraphHead){border,margin,padding};' +
                'span[data-*](ice-ins,ice-del,ice-cts,appendHint,underline' + strikeClass + ',subscript,superscript);' +
                'a[href,data-*](ice-ins,ice-del,ice-cts,appendHint);' +
                'br ins del[data-*](ice-ins,ice-del,ice-cts,appendHint);';
        } else {
            allowedContent = 'strong' + strikeEl + ' em u sub sup;' +
                'ul ol li {list-style-type};' +
                'h2 h3 h4;' +
                //'table tr td th tbody thead caption [border] {margin,padding,width,height,border,border-spacing,border-collapse,align,cellspacing,cellpadding};' +
                'p blockquote {border,margin,padding};' +
                'span(underline' + strikeClass + ',subscript,superscript);' +
                'a[href];';
        }

        if ($el.data('track-changed') == '1') {
            ckeditorConfig.extraPlugins += ',lite';
            ckeditorConfig.lite = {tooltips: false};
        } else {
            ckeditorConfig.removePlugins += ',lite';
        }
        if ($el.data('enter-mode') == 'br') {
            ckeditorConfig.enterMode = CKEDITOR.ENTER_BR;
        } else {
            ckeditorConfig.enterMode = CKEDITOR.ENTER_P;
        }
        ckeditorConfig.allowedContent = allowedContent;
        // ckeditorConfig.pasteFilter = allowedContent; // Seems to break copy/pasting some <strong> formatting in 4.5.11

        var editor = CKEDITOR.inline(id, ckeditorConfig);

        var $fieldset = $el.parents(".wysiwyg-textarea").first();
        if ($fieldset.data("max-len") != 0) {
            var maxLen = $fieldset.data("max-len"),
                maxLenSoft = false,
                $warning = $fieldset.find('.maxLenTooLong'),
                $submit = $el.parents("form").first().find("button[type=submit]"),
                $currCounter = $fieldset.find(".maxLenHint .counter");
            if (maxLen < 0) {
                maxLenSoft = true;
                maxLen = -1 * maxLen;
            }

            var onChange = function () {
                var currLen = ckeditor_charcount(editor.getData());
                $currCounter.text(currLen);
                if (currLen > maxLen) {
                    $warning.removeClass('hidden');
                    if (!maxLenSoft) {
                        $submit.prop("disabled", true);
                    }
                } else {
                    $warning.addClass('hidden');
                    if (!maxLenSoft) {
                        $submit.prop("disabled", false);
                    }
                }
            };
            editor.on('change', onChange);
            onChange();
        }

        return editor;
    }


    $.AntragsgruenCKEDITOR = {
        "init": ckeditorInit
    };
}(jQuery));

(function ($) {
    "use strict";

    var $html = $('html');

    var amendmentEditFormSinglePara = function () {
        var $paragraphs = $(".wysiwyg-textarea.single-paragraph");

        var setModifyable = function () {
            var $modified = $paragraphs.filter(".modified");
            if ($modified.length == 0) {
                $paragraphs.addClass('modifyable');
            } else {
                $paragraphs.removeClass('modifyable');
                $('input[name=modifiedParagraphNo]').val($modified.data("paragraph-no"));
                $('input[name=modifiedSectionId]').val($modified.parents(".texteditorBox").data("section-id"));
            }
        };
        $paragraphs.click(function () {
            var $para = $(this);
            if ($para.hasClass('modifyable')) {
                $para.addClass('modified');
                setModifyable();

                var $textarea = $para.find(".texteditor"),
                    editor;
                if (typeof(CKEDITOR.instances[$textarea.attr("id")]) !== "undefined") {
                    editor = CKEDITOR.instances[$textarea.attr("id")];
                } else {
                    editor = $.AntragsgruenCKEDITOR.init($textarea.attr("id"));
                }
                $textarea.attr("contenteditable", "true");
                $textarea.parents("form").submit(function () {
                    $textarea.parent().find("textarea.raw").val(editor.getData());
                    if (typeof(editor.plugins.lite) != 'undefined') {
                        editor.plugins.lite.findPlugin(editor).acceptAll();
                        $textarea.parent().find("textarea.consolidated").val(editor.getData());
                    }
                });
                $textarea.focus();
            }
        });
        $paragraphs.find(".modifiedActions .revert").click(function (ev) {
            ev.preventDefault();
            ev.stopPropagation();
            var $para = $(this).parents(".wysiwyg-textarea"),
                $textarea = $para.find(".texteditor"),
                id = $textarea.attr("id");
            $("#" + id).attr("contenteditable", "false");
            $textarea.html($para.data("original"));
            $para.removeClass("modified");
            setModifyable();
        });
        setModifyable();

        // Amendment Reason
        $(".wysiwyg-textarea").filter(":not(.single-paragraph)").each(function () {
            var $holder = $(this),
                $textarea = $holder.find(".texteditor");
            if ($holder.hasClass("hidden")) {
                return;
            }
            var editor = $.AntragsgruenCKEDITOR.init($textarea.attr("id"));
            $textarea.parents("form").submit(function () {
                $textarea.parent().find("textarea.raw").val(editor.getData());
                if (typeof(editor.plugins.lite) != 'undefined') {
                    editor.plugins.lite.findPlugin(editor).acceptAll();
                    $textarea.parent().find("textarea.consolidated").val(editor.getData());
                }
            });
        });

        $(".texteditorBox").each(function () {
            var $this = $(this),
                sectionId = $this.data("section-id"),
                paraNo = $this.data("changed-para-no");
            if (paraNo > -1) {
                $("#section_holder_" + sectionId + "_" + paraNo).click();
            }
        });
    };

    var amendmentEditForm = function (multipleParagraphs) {
        var lang = $html.attr('lang'),
            $opener = $(".editorialChange .opener");

        $(".input-group.date").datetimepicker({
            locale: lang,
            format: 'L'
        });
        $opener.click(function (ev) {
            ev.preventDefault();
            var $holder = $(".editorialChange"),
                $textarea = $holder.find(".texteditor");
            $(this).addClass("hidden");
            $("#section_holder_editorial").removeClass("hidden");
            var editor = $.AntragsgruenCKEDITOR.init("amendmentEditorial_wysiwyg");
            $textarea.parents("form").submit(function () {
                $textarea.parent().find("textarea.raw").val(editor.getData());
            });
        });

        if ($("#amendmentEditorial").val() != '') {
            $opener.click();
        }
        if (multipleParagraphs) {
            amendmentEditFormMultiPara();
        } else {
            amendmentEditFormSinglePara();
        }
    };

    $.Antragsgruen = {
        'amendmentEditForm': amendmentEditForm,
        'amendmentEditFormSinglePara': amendmentEditFormSinglePara
    };
}(jQuery));
