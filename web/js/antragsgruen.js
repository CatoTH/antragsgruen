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
        var normalizedText = text.
        replace(/(\r\n|\n|\r)/gm, "").
        replace(/^\s+|\s+$/g, "").
        replace("&nbsp;", "");
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
            removePlugins: 'stylescombo,format,save,newpage,print,templates,showblocks,specialchar,about,preview,pastetext,pastefromword,bbcode',
            extraPlugins: 'autogrow,wordcount,tabletools',
            scayt_sLang: 'de_DE',
            autoGrow_bottomSpace: 20,
            // Whether or not you want to show the Word Count
            wordcount: {
                showWordCount: false,
                showCharCount: false,
                showParagraphs: false,
                countHTML: false,
                countSpacesAsChars: true
            },
            title: $el.attr("title")
        };

        if ($el.data('track-changed') == '1' || $el.data('allow-diff-formattings') == '1') {
            allowedContent = 'strong s em u sub sup;' +
                'ul ol li [data-*](ice-ins,ice-del,ice-cts,appendHint){list-style-type};' +
                    //'table tr td th tbody thead caption [border] {margin,padding,width,height,border,border-spacing,border-collapse,align,cellspacing,cellpadding};' +
                'p blockquote [data-*](ice-ins,ice-del,ice-cts,appendHint,collidingParagraphHead){border,margin,padding};' +
                'span[data-*](ice-ins,ice-del,ice-cts,appendHint,underline,strike,subscript,superscript);' +
                'a[href,data-*](ice-ins,ice-del,ice-cts,appendHint);' +
                'br ins del[data-*](ice-ins,ice-del,ice-cts,appendHint);' +
                'section(collidingParagraph)';
        } else {
            allowedContent = 'strong s em u sub sup;' +
                'ul ol li {list-style-type};' +
                    //'table tr td th tbody thead caption [border] {margin,padding,width,height,border,border-spacing,border-collapse,align,cellspacing,cellpadding};' +
                'p blockquote {border,margin,padding};' +
                'span(underline,strike,subscript,superscript);' +
                'a[href];';
        }

        if ($el.data('track-changed') == '1') {
            ckeditorConfig.extraPlugins += ',lite';
            if ($el.data('track-changed-tooltips') == '1') {
                ckeditorConfig.lite = {tooltips: true};
            } else {
                ckeditorConfig.lite = {tooltips: false};
            }
        } else {
            ckeditorConfig.removePlugins += ',lite';
        }
        ckeditorConfig.allowedContent = allowedContent;
        ckeditorConfig.pasteFilter = allowedContent;

        var editor = CKEDITOR.inline(id, ckeditorConfig);

        /*
         editor.on('paste', function (data) {
         if (data.data.type != 'html') {
         return;
         }
         var html = data.data.dataValue;
         console.log(data, CKEDITOR.instances.sections_2_wysiwyg.focusManager.currentActive, CKEDITOR.instances.sections_2_wysiwyg.focusManager);
         });
         */

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

    var draftSavingEngine = function (keyBase) {
        if (!$html.hasClass("localstorage")) {
            return;
        }

        var $draftHint = $("#draftHint"),
            $form = $("form.draftForm"),
            localKey = keyBase + "_" + Math.floor(Math.random() * 1000000),
            key;

        $form.append('<input type="hidden" name="draftId" value="' + localKey + '">');

        var doDelete = function ($li) {
            localStorage.removeItem($li.data("key"));
            $li.remove();
            if ($draftHint.find("ul").children().length == 0) {
                $draftHint.addClass("hidden");
            }
        };
        var doRestore = function ($li) {
            var inst,
                restoreKey = $li.data("key"),
                data = JSON.parse(localStorage.getItem(restoreKey));
            for (inst in CKEDITOR.instances) {
                if (CKEDITOR.instances.hasOwnProperty(inst)) {
                    if (typeof(data[inst]) != "undefined") {
                        CKEDITOR.instances[inst].setData(data[inst]);
                    }
                }
            }
            if (data.hasOwnProperty("amendmentEditorial_wysiwyg") && data['amendmentEditorial_wysiwyg'] != '') {
                if (!CKEDITOR.hasOwnProperty('amendmentEditorial_wysiwyg')) {
                    $(".editorialChange .opener").click();
                    window.setTimeout(function () {
                        CKEDITOR.instances['amendmentEditorial_wysiwyg'].setData(data['amendmentEditorial_wysiwyg']);
                    }, 100);
                }
            }

            $(".form-group.plain-text").each(function () {
                var $input = $(this).find("input[type=text]");
                if (typeof(data[$input.attr("id")]) != "undefined") {
                    $input.val(data[$input.attr("id")]);
                }
            });
            $(".form-group.amendmentStatus").each(function () {
                var $input = $(this).find("input[type=text].hidden"),
                    id = $(this).find(".selectlist").attr("id");
                if (typeof(data[id]) != "undefined") {
                    $('#' + id).selectlist('selectByValue', data[id]);
                }
            });

            $form.find("input[name=draftId]").remove();
            $form.append('<input type="hidden" name="draftId" value="' + restoreKey + '">');

            localKey = restoreKey;
            $li.remove();
            if ($draftHint.find("ul").children().length == 0) {
                $draftHint.addClass("hidden");
            }
        };

        for (key in localStorage) if (localStorage.hasOwnProperty(key)) {
            if (key.indexOf(keyBase + "_") == 0) {
                var data = JSON.parse(localStorage.getItem(key)),
                    lastEdit = new Date(data['lastEdit']),
                    $link = $("<li><a href='#' class='restore'></a> " +
                        "<a href='#' class='delete glyphicon glyphicon-trash' title='" + __t("std", "draft_del") +
                        "'></a></li>");


                $link.data("key", key);
                var dateStr = new Intl.DateTimeFormat($html.attr("lang"), {
                    weekday: 'long', year: 'numeric', month: 'long', day: 'numeric',
                    hour: 'numeric', minute: 'numeric'
                }).format(lastEdit);
                $link.find('.restore').text('Entwurf vom: ' + dateStr).click(function (ev) {
                    ev.preventDefault();
                    var $li = $(this).parents("li").first();
                    bootbox.confirm(__t("std", "draft_restore_confirm"), function (result) {
                        if (result) {
                            doRestore($li);
                        }
                    });
                });
                $link.find('.delete').click(function (ev) {
                    ev.preventDefault();
                    var $li = $(this).parents("li").first();
                    bootbox.confirm(__t("std", "draft_del_confirm"), function (result) {
                        if (result) {
                            doDelete($li);
                        }
                    });
                });
                $draftHint.find("ul").append($link);
                $draftHint.removeClass("hidden");
            }
        }

        window.setTimeout(function () {
            for (var inst in CKEDITOR.instances) {
                if (CKEDITOR.instances.hasOwnProperty(inst)) {
                    $("#" + inst).data("original", CKEDITOR.instances[inst].getData());
                }
            }
            $(".form-group.plain-text").each(function () {
                var $input = $(this).find("input[type=text]");
                $input.data("original", $input.val());
            });
            $(".form-group.amendmentStatus").each(function () {
                var $input = $(this).find("input[type=text].hidden");
                $input.data("original", $input.val());
            });
        }, 2000);

        window.setInterval(function () {
            var data = {},
                foundChanged = false,
                inst;

            for (inst in CKEDITOR.instances) {
                if (CKEDITOR.instances.hasOwnProperty(inst)) {
                    var dat = CKEDITOR.instances[inst].getData();
                    data[inst] = dat;
                    if (dat != $("#" + inst).data("original")) {
                        foundChanged = true;
                    }
                }
            }
            $(".form-group.plain-text").each(function () {
                var $input = $(this).find("input[type=text]");
                data[$input.attr("id")] = $input.val();
                if ($input.val() != $input.data("original")) {
                    foundChanged = true;
                }
            });
            $(".form-group.amendmentStatus").each(function () {
                var $input = $(this).find("input[type=text].hidden"),
                    id = $(this).find(".selectlist").attr("id");
                data[id] = $input.val();
                if ($input.val() != $input.data("original")) {
                    foundChanged = true;
                }
            });

            if (foundChanged) {
                data['lastEdit'] = new Date().getTime();
                localStorage.setItem(localKey, JSON.stringify(data));
            } else {
                localStorage.removeItem(localKey);
            }
        }, 3000);
    };

    var motionEditForm = function () {
        var lang = $html.attr('lang');
        $(".input-group.date").datetimepicker({
            locale: lang,
            format: 'L'
        });
        $(".wysiwyg-textarea").each(function () {
            var $holder = $(this),
                $textarea = $holder.find(".texteditor"),
                editor = $.AntragsgruenCKEDITOR.init($textarea.attr("id"));

            $textarea.parents("form").submit(function () {
                $textarea.parent().find("textarea").val(editor.getData());
            });
        });
        $(".form-group.plain-text").each(function () {
            var $fieldset = $(this),
                $input = $fieldset.find("input.form-control");
            if ($fieldset.data("max-len") != 0) {
                var maxLen = $fieldset.data("max-len"),
                    maxLenSoft = false,
                    $warning = $fieldset.find('.maxLenTooLong'),
                    $submit = $fieldset.parents("form").first().find("button[type=submit]"),
                    $currCounter = $fieldset.find(".maxLenHint .counter");
                if (maxLen < 0) {
                    maxLenSoft = true;
                    maxLen = -1 * maxLen;
                }

                var onChange = function () {
                    var currLen = $input.val().length;
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
                $input.on('keyup change', onChange);
                onChange();
            }
        });

        var $draftHint = $("#draftHint"),
            draftMotionType = $draftHint.data("motion-type"),
            draftMotionId = $draftHint.data("motion-id");
        draftSavingEngine("motion_" + draftMotionType + "_" + draftMotionId);
    };

    var motionMergeAmendmentsForm = function () {
        $(".wysiwyg-textarea").each(function () {
            var $holder = $(this),
                $textarea = $holder.find(".texteditor"),
                editor = $.AntragsgruenCKEDITOR.init($textarea.attr("id")),
                currMouseX = null;

            $holder.on("mousemove", function(ev) {
                currMouseX = ev.offsetX;
            });

            $textarea.parents("form").submit(function () {
                $textarea.parent().find("textarea.raw").val(editor.getData());
                $textarea.parent().find("textarea.consolidated").val(editor.getData());
            });

            var $text = $('<div>' + editor.getData() + '</div>');
            $text.find("ul.appendHint, ol.appendHint").each(function () {
                var $this = $(this),
                    appendHint = $this.data("append-hint");
                $this.find("> li").addClass("appendHint").attr("data-append-hint", appendHint);
                $this.removeClass("appendHint").removeData("append-hint");
            });
            var newText = $text.html();
            editor.setData(newText);

            var closeTooltip = function (el) {
                    var $el = $(el);
                    if (el.nodeName.toLowerCase() == 'ul' || el.nodeName.toLowerCase() == 'ol') {
                        $el = $el.children();
                    }
                    $el.popover("hide").popover("destroy");
                    $holder.removeData("popover-shown");
                },
                insertReject = function () {
                    editor.fire( 'saveSnapshot' );
                    closeTooltip(this);
                    $(this).remove();
                },
                insertAccept = function () {
                    editor.fire( 'saveSnapshot' );
                    var $this = $(this);
                    closeTooltip(this);
                    $this.removeClass("ice-cts").removeClass("ice-ins").removeClass("appendHint");
                    if (this.nodeName.toLowerCase() == 'ul' || this.nodeName.toLowerCase() == 'ol') {
                        $this.children().removeClass("ice-cts").removeClass("ice-ins").removeClass("appendHint");
                    }
                    if (this.nodeName.toLowerCase() == 'ins') {
                        $this.replaceWith($this.html());
                    }
                },
                deleteReject = function () {
                    editor.fire( 'saveSnapshot' );
                    var $this = $(this);
                    closeTooltip(this);
                    $this.removeClass("ice-cts").removeClass("ice-del").removeClass("appendHint");
                    if (this.nodeName.toLowerCase() == 'ul' || this.nodeName.toLowerCase() == 'ol') {
                        $this.children().removeClass("ice-cts").removeClass("ice-ins").removeClass("appendHint");
                    }
                    if (this.nodeName.toLowerCase() == 'del') {
                        $this.replaceWith($this.html());
                    }
                },
                deleteAccept = function () {
                    editor.fire( 'saveSnapshot' );
                    closeTooltip(this);
                    $(this).remove();
                },
                popoverContent = function () {
                    var $this = $(this),
                        html = '<div><button type="button" class="accept btn btn-sm btn-default"></button>';
                    html += '<button type="button" class="reject btn btn-sm btn-default"></button></div>';
                    var $el = $(html);
                    if ($this.hasClass("ice-ins")) {
                        $el.find("button.accept").text("Übernehmen").click(insertAccept.bind($this[0]));
                        $el.find("button.reject").text("Verwerfen").click(insertReject.bind($this[0]));
                    } else if ($this.hasClass("ice-del")) {
                        $el.find("button.accept").text("Löschen").click(deleteAccept.bind($this[0]));
                        $el.find("button.reject").text("Behalten").click(deleteReject.bind($this[0]));
                    } else if ($this[0].nodeName.toLowerCase() == 'li') {
                        var $list = $this.parent();
                        if ($list.hasClass("ice-ins")) {
                            $el.find("button.accept").text("Übernehmen").click(insertAccept.bind($list[0]));
                            $el.find("button.reject").text("Verwerfen").click(insertReject.bind($list[0]));
                        } else if ($list.hasClass("ice-del")) {
                            $el.find("button.accept").text("Löschen").click(deleteAccept.bind($list[0]));
                            $el.find("button.reject").text("Behalten").click(deleteReject.bind($list[0]));
                        } else {
                            console.log("unknown", $list);
                        }
                    } else {
                        console.log("unknown", $this);
                        alert("unknown");
                    }
                    return $el;
                },
                removePopupIfInactive = function () {
                    if ($(this).is(":hover")) {
                        return window.setTimeout(removePopupIfInactive.bind(this), 500);
                    }
                    if ($holder.find(".popover").length > 0 && $holder.find(".popover").is(":hover")) {
                        return window.setTimeout(removePopupIfInactive.bind(this), 500);
                    }
                    $(this).popover("hide").popover("destroy");
                };

            $holder.on('mouseover', '.collidingParagraphHead', function () {
                $(this).parents(".collidingParagraph").addClass("hovered");
            }).on('mouseout', '.collidingParagraphHead', function () {
                $(this).parents(".collidingParagraph").removeClass("hovered");
            });

            $holder.on("mouseover", ".appendHint", function () {
                var $previous = $holder.data("popover-shown");
                if ($previous) $previous.popover("hide").popover("destroy");
                var $this = $(this);
                $this.popover({
                    'container': $holder,
                    'animation': false,
                    'trigger': 'manual',
                    'placement': 'bottom',
                    'html': true,
                    'content': popoverContent
                });
                $this.popover('show');
                $holder.data("popover-shown", $this);
                var $popover = $holder.find("> .popover"),
                    width = $popover.width();
                $popover.css("left", Math.floor(currMouseX - (width / 2) + 20) + "px");
                $popover.on("mousemove", function(ev) { ev.stopPropagation(); });
                window.setTimeout(removePopupIfInactive.bind($this[0]), 500);
            });

            var callPopoverContent = function () {
                var $this = $(this),
                    html = '<div style="white-space: nowrap;"><button type="button" class="btn btn-small btn-default delTitle" title="Die Überschrift &quot;Kollidierender Änderungsantrag: ...&quot; entfernen"><span style="text-decoration: line-through">Überschrift</span></button>';
                html += '<button type="button" class="reject btn btn-small btn-default" title="Den kompletten kollidierenden Block entfernen"><span class="glyphicon glyphicon-trash"></span></button>';
                html += '<a href="#" class="btn btn-small btn-default opener" target="_blank" title="Den Änderungsantrag in einem neuen Fenster öffnen"><span class="glyphicon glyphicon-new-window"></span></a>';
                html += '</div>';
                var $el = $(html);
                $el.find("a.opener").attr("href", $this.find("a").attr("href"));
                $el.find(".reject").click(function () {
                    editor.fire( 'saveSnapshot' );
                    $this.popover("hide").popover("destroy");
                    $this.parents(".collidingParagraph").remove();
                });
                $el.find(".delTitle").click(function () {
                    editor.fire( 'saveSnapshot' );
                    $this.popover("hide").popover("destroy");
                    $this.remove();
                });
                return $el;
            };

            $holder.on("mouseover", ".collidingParagraphHead", function () {
                var $previous = $holder.data("popover-shown");
                if ($previous) $previous.popover("hide").popover("destroy");
                var $this = $(this);
                $this.popover({
                    'container': $holder,
                    'animation': false,
                    'trigger': 'manual',
                    'placement': 'top',
                    'html': true,
                    'title': 'Kollidierender ÄA',
                    'content': callPopoverContent
                });
                $this.popover('show');
                $holder.data("popover-shown", $this);
                var $popover = $holder.find("> .popover"),
                    width = $popover.width();
                $popover.css("left", Math.floor(currMouseX - (width / 2) + 20) + "px");
                $popover.on("mousemove", function(ev) { ev.stopPropagation(); });
                window.setTimeout(removePopupIfInactive.bind($this[0]), 500);
            });
        });


        var $draftHint = $("#draftHint"),
            origMotionId = $draftHint.data("orig-motion-id"),
            newMotionId = $draftHint.data("new-motion-id");
        draftSavingEngine("motionmerge_" + origMotionId + "_" + newMotionId);
    };

    var amendmentEditFormMultiPara = function () {
        console.log("amendmentEditFormMultiPara");
        $(".wysiwyg-textarea").each(function () {
            var $holder = $(this),
                $textarea = $holder.find(".texteditor");
            if ($holder.hasClass("hidden") || $textarea.attr("id") == "amendmentEditorial_wysiwyg") {
                return;
            }
            var editor = $.AntragsgruenCKEDITOR.init($textarea.attr("id"));
            $textarea.parents("form").submit(function () {
                console.log("save", $textarea.parent().find("textarea.raw"), editor.getData());
                $textarea.parent().find("textarea.raw").val(editor.getData());
                if (typeof(editor.plugins.lite) != 'undefined') {
                    editor.plugins.lite.findPlugin(editor).acceptAll();
                    $textarea.parent().find("textarea.consolidated").val(editor.getData());
                }
            });
        });
        var $draftHint = $("#draftHint"),
            draftMotionId = $draftHint.data("motion-id"),
            draftAmendmentId = $draftHint.data("amendment-id");
        draftSavingEngine("amendment_" + draftMotionId + "_" + draftAmendmentId);
    };

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


    var contentPageEdit = function () {
        $('.contentPage').each(function () {
            var $this = $(this),
                $form = $this.find('> form'),
                $editCaller = $this.find('> .editCaller'),
                $textHolder = $form.find('> .textHolder'),
                $textSaver = $form.find('> .textSaver'),
                editor = null;

            $editCaller.click(function (ev) {
                ev.preventDefault();
                $editCaller.addClass("hidden");
                $textHolder.attr('contenteditable', true);

                editor = CKEDITOR.inline($textHolder.attr('id'), {
                    scayt_sLang: 'de_DE',
                    removePlugins: 'lite'
                });

                $textHolder.focus();
                $textSaver.removeClass("hidden");
            });
            $textSaver.addClass("hidden");
            $textSaver.find('button').click(function (ev) {
                ev.preventDefault();

                $.post($form.attr('action'), {
                    'data': editor.getData(),
                    '_csrf': $form.find('> input[name=_csrf]').val()
                }, function (ret) {
                    if (ret == '1') {
                        $textSaver.addClass("hidden");
                        editor.destroy();
                        $textHolder.attr('contenteditable', false);
                        $editCaller.removeClass("hidden");
                    } else {
                        alert('Something went wrong...');
                    }
                })
            });
        });
    };

    var motionShow = function () {
        var $paragraphs = $('.motionTextHolder .paragraph');
        $paragraphs.find('.comment .shower').click(function (ev) {
            ev.preventDefault();
            var $this = $(this),
                $commentHolder = $this.parents('.paragraph').first().find('.commentHolder');
            $this.addClass("hidden");
            $this.parent().find('.hider').removeClass("hidden");
            $commentHolder.removeClass("hidden");
            if (!$commentHolder.isOnScreen(0.1, 0.1)) {
                $commentHolder.scrollintoview({top_offset: -100});
            }
        });

        $paragraphs.find('.comment .hider').click(function (ev) {
            var $this = $(this);
            $this.addClass("hidden");
            $this.parent().find('.shower').removeClass("hidden");

            $this.parents('.paragraph').first().find('.commentHolder').addClass("hidden");
            ev.preventDefault();
        });

        $paragraphs.filter('.commentsOpened').find('.comment .shower').click();
        $paragraphs.filter(':not(.commentsOpened)').find('.comment .hider').click();

        $paragraphs.each(function () {
            var $paragraph = $(this),
                $paraFirstLine = $paragraph.find(".lineNumber").first(),
                lineHeight = $paraFirstLine.height();

            var amends = $paragraph.find(".bookmarks > .amendment");
            amends = amends.sort(function (el1, el2) {
                return $(el1).data("first-line") - $(el2).data("first-line");
            });
            $paragraph.find(".bookmarks").append(amends);

            $paragraph.find('ul.bookmarks li.amendment').each(function () {
                var $amendment = $(this),
                    firstLine = $amendment.data("first-line"),
                    targetOffset = (firstLine - $paraFirstLine.data("line-number")) * lineHeight,
                    $prevBookmark = $amendment.prevAll(),
                    delta = targetOffset;
                $prevBookmark.each(function () {
                    var $pre = $(this);
                    delta -= $pre.height();
                    delta -= parseInt($pre.css("margin-top"));
                    delta -= 7;
                });
                if (delta < 0) {
                    delta = 0;
                }
                $amendment.css('margin-top', delta + "px");

                $amendment.mouseover(function () {
                    $paragraph.find("> .textOrig").addClass("hidden");
                    $paragraph.find("> .textAmendment").addClass("hidden");
                    $paragraph.find("> .textAmendment.amendment" + $amendment.find("a").data("id")).removeClass("hidden");
                }).mouseout(function () {
                    $paragraph.find("> .textOrig").removeClass("hidden");
                    $paragraph.find("> .textAmendment").addClass("hidden");
                });
            });
        });


        $('.tagAdderHolder').click(function (ev) {
            ev.preventDefault();
            $(this).addClass("hidden");
            $('#tagAdderForm').removeClass("hidden");
        });

        var s = location.hash.split('#comm');
        if (s.length == 2) {
            $('#comment' + s[1]).scrollintoview({top_offset: -100});
        }

        $("form.delLink").submit(function (ev) {
            ev.preventDefault();
            var form = this;
            bootbox.confirm(__t("std", "del_confirm"), function (result) {
                if (result) {
                    form.submit();
                }
            });
        });
    };

    var amendmentShow = function () {
        var s = location.hash.split('#comm');
        if (s.length == 2) {
            $('#comment' + s[1]).scrollintoview({top_offset: -100});
        }

        $("form.delLink").submit(function (ev) {
            ev.preventDefault();
            var form = this;
            bootbox.confirm(__t("std", "del_confirm"), function (result) {
                if (result) {
                    form.submit();
                }
            });
        });
    };

    var defaultInitiatorForm = function () {
        var $fullTextHolder = $('#fullTextHolder'),
            $supporterData = $('.supporterData'),
            $supporterAdderRow = $supporterData.find('.adderRow'),
            $initiatorData = $('.initiatorData'),
            $initiatorAdderRow = $initiatorData.find('.adderRow');

        $('#personTypeNatural, #personTypeOrga').on('click change', function () {
            if ($('#personTypeOrga').prop('checked')) {
                $initiatorData.find('.organizationRow').addClass("hidden");
                $initiatorData.find('.contactNameRow').removeClass("hidden");
                $initiatorData.find('.resolutionRow').removeClass("hidden");
                $initiatorData.find('.adderRow').addClass("hidden");
                $('.supporterData, .supporterDataHead').addClass("hidden");
            } else {
                $initiatorData.find('.organizationRow').removeClass("hidden");
                $initiatorData.find('.contactNameRow').addClass("hidden");
                $initiatorData.find('.resolutionRow').addClass("hidden");
                $initiatorData.find('.adderRow').removeClass("hidden");
                $('.supporterData, .supporterDataHead').removeClass("hidden");
            }
        }).first().trigger('change');

        $initiatorAdderRow.find('a').click(function (ev) {
            ev.preventDefault();
            var $newEl = $($('#newInitiatorTemplate').data('html'));
            $initiatorAdderRow.before($newEl);
        });
        $initiatorData.on('click', '.initiatorRow .rowDeleter', function (ev) {
            ev.preventDefault();
            $(this).parents('.initiatorRow').remove();
        });


        $supporterAdderRow.find('a').click(function (ev) {
            ev.preventDefault();
            var $newEl = $($('#newSupporterTemplate').data('html'));
            $supporterAdderRow.before($newEl);
        });

        $('.fullTextAdder a').click(function (ev) {
            ev.preventDefault();
            $(this).parent().addClass("hidden");
            $('#fullTextHolder').removeClass("hidden");
        });
        $('.fullTextAdd').click(function () {
            var lines = $fullTextHolder.find('textarea').val().split(";"),
                template = $('#newSupporterTemplate').data('html'),
                getNewElement = function () {
                    var $rows = $supporterData.find(".supporterRow");
                    for (var i = 0; i < $rows.length; i++) {
                        var $row = $rows.eq(i);
                        if ($row.find(".name").val() == '' && $row.find(".organization").val() == '') return $row;
                    }
                    // No empty row found
                    var $newEl = $(template);
                    if ($supporterAdderRow.length > 0) {
                        $supporterAdderRow.before($newEl);
                    } else {
                        $('.fullTextAdder').before($newEl);
                    }
                    return $newEl;
                };
            var $firstAffectedRow = null;
            for (var i = 0; i < lines.length; i++) {
                if (lines[i] == '') {
                    continue;
                }
                var $newEl = getNewElement();
                if ($firstAffectedRow == null) $firstAffectedRow = $newEl;
                if ($newEl.find('input.organization').length > 0) {
                    var parts = lines[i].split(',');
                    $newEl.find('input.name').val(parts[0].trim());
                    if (parts.length > 1) {
                        $newEl.find('input.organization').val(parts[1].trim());
                    }
                } else {
                    $newEl.find('input.name').val(lines[i]);
                }
            }
            $fullTextHolder.find('textarea').select().focus();
            $firstAffectedRow.scrollintoview();
        });
        $supporterData.on('click', '.supporterRow .rowDeleter', function (ev) {
            ev.preventDefault();
            $(this).parents('.supporterRow').remove();
        });
        $supporterData.on('keydown', ' .supporterRow input[type=text]', function (ev) {
            var $row;
            if (ev.keyCode == 13) { // Enter
                ev.preventDefault();
                ev.stopPropagation();
                $row = $(this).parents('.supporterRow');
                if ($row.next().hasClass('adderRow')) {
                    var $newEl = $($('#newSupporterTemplate').data('html'));
                    $supporterAdderRow.before($newEl);
                    $newEl.find('input[type=text]').first().focus();
                } else {
                    $row.next().find('input[type=text]').first().focus();
                }
            } else if (ev.keyCode == 8) { // Backspace
                $row = $(this).parents('.supporterRow');
                if ($row.find('input.name').val() != '') {
                    return;
                }
                if ($row.find('input.organization').val() != '') {
                    return;
                }
                $row.remove();
                $supporterAdderRow.prev().find('input.name, input.organization').last().focus();
            }
        });

        var $editforms = $('#motionEditForm, #amendmentEditForm');

        if ($supporterData.length > 0 && $supporterData.data('min-supporters') > 0) {
            $editforms.submit(function (ev) {
                if ($('#personTypeOrga').prop('checked')) {
                    return;
                }
                var found = 0;
                $supporterData.find('.supporterRow').each(function () {
                    if ($(this).find('input.name').val().trim() != '') {
                        found++;
                    }
                });
                if (found < $supporterData.data('min-supporters')) {
                    ev.preventDefault();
                    bootbox.alert(__t("std", "min_x_supporter").replace(/%NUM%/, $supporterData.data('min-supporters')));
                }
            });
        }

        $editforms.submit(function (ev) {
            if ($('#personTypeOrga').prop('checked')) {
                if ($('#resolutionDate').val() == '') {
                    ev.preventDefault();
                    bootbox.alert(__t("std", "missing_resolution_date"));
                }
            }
        });
    };

    var loginForm = function () {
        var $form = $("#usernamePasswordForm"),
            pwMinLen = $("#passwordInput").data("min-len");
        $form.find("input[name=createAccount]").change(function () {
            if ($(this).prop("checked")) {
                $("#pwdConfirm").removeClass('hidden');
                $("#regName").removeClass('hidden').find("input").attr("required", "required");
                $("#passwordInput").attr("placeholder", "Min. " + pwMinLen + " Zeichen");
                $("#create_str").removeClass('hidden');
                $("#login_str").addClass('hidden');
            } else {
                $("#pwdConfirm").addClass('hidden');
                $("#regName").addClass('hidden').find("input").removeAttr("required");
                $("#passwordInput").attr("placeholder", "");
                $("#createStr").addClass('hidden');
                $("#loginStr").removeClass('hidden');
            }
        }).trigger("change");
        $form.submit(function (ev) {
            var pwd = $("#passwordInput").val();
            if (pwd.length < pwMinLen) {
                ev.preventDefault();
                bootbox.alert(__t("std", "pw_x_chars").replace(/%NUM%/, pwMinLen));
            }
            if ($form.find("input[name=createAccount]").prop("checked")) {
                if (pwd != $("#passwordConfirm").val()) {
                    ev.preventDefault();
                    bootbox.alert(__t("std", "pw_no_match"));
                }
            }
        });
    };

    var accountEdit = function () {
        var pwMinLen = $("#userPwd").data("min-len");

        $('.accountDeleteForm input[name=accountDeleteConfirm]').change(function () {
            if ($(this).prop("checked")) {
                $(".accountDeleteForm button[name=accountDelete]").prop("disabled", false);
            } else {
                $(".accountDeleteForm button[name=accountDelete]").prop("disabled", true);
            }
        }).trigger('change');

        var $emailExisting = $('.emailExistingRow');
        if ($emailExisting.length == 1) {
            var $changeRow = $('.emailChangeRow');
            $changeRow.addClass('hidden');
            $(".requestEmailChange").click(function (ev) {
                ev.preventDefault();
                $changeRow.removeClass("hidden");
                $emailExisting.addClass("hidden");
                $changeRow.find("input").focus();
            });
        }

        $('.userAccountForm').submit(function (ev) {
            var pwd = $("#userPwd").val(),
                pwd2 = $("#userPwd2").val();
            if (pwd != '' || pwd2 != '') {
                if (pwd.length < pwMinLen) {
                    ev.preventDefault();
                    bootbox.alert(__t("std", "pw_x_chars").replace(/%NUM%/, pwMinLen));
                } else if (pwd != pwd2) {
                    ev.preventDefault();
                    bootbox.alert(__t("std", "pw_no_match"));
                }
            }
        });
    };

    // Needs to be synchronized with CunsultationAgendaItem:getSortedFromConsultation
    var recalcAgendaCodes = function () {
        var recalcAgendaNode = function ($ol) {
                var currNumber = '0.',
                    $lis = $ol.find('> li.agendaItem');
                $lis.each(function () {
                    var $li = $(this),
                        code = $li.data('code'),
                        currStr = '',
                        $subitems = $li.find('> ol');
                    if (code[0] == '0' || code > 0) {
                        currStr = currNumber = code;
                    } else if (code == '#') {
                        var x = currNumber.split('.');
                        x[0]++;
                        currNumber = currStr = x.join('.');
                    } else {
                        currStr = code;
                    }
                    $li.find('> div > h3 .code').text(currStr);
                    if ($subitems.length > 0) {
                        recalcAgendaNode($subitems);
                    }
                });
            },
            $root = $('ol.motionListAgenda');
        recalcAgendaNode($root);
    };

    $.Antragsgruen = {
        'loginForm': loginForm,
        'motionShow': motionShow,
        'amendmentShow': amendmentShow,
        'motionEditForm': motionEditForm,
        'motionMergeAmendmentsForm': motionMergeAmendmentsForm,
        'amendmentEditForm': amendmentEditForm,
        'contentPageEdit': contentPageEdit,
        'defaultInitiatorForm': defaultInitiatorForm,
        'accountEdit': accountEdit,
        'recalcAgendaCodes': recalcAgendaCodes
    };

    $(".jsProtectionHint").remove();

}(jQuery));



