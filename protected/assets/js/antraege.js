/*global $*/
/*global console*/

(function () {
    "use strict";

    $(".unterstuetzerInnenwidget .unterstuetzerInnenwidget_add_caller a").click(function (ev) {
        var text = $(this).parents(".unterstuetzerInnenwidget").data("neutemplate");
        $(text).insertBefore($(this).parents(".unterstuetzerInnenwidget_add_caller"));
        //$(this).parents(".unterstuetzerInnenwidget_add_caller").insertBefore(text);
        ev.preventDefault();
    });
    $(".unterstuetzerInnenwidget_adder").hide();
    if ($(".unterstuetzerInnenwidget").length > 0) $(function () {
        $(".unterstuetzerInnenwidget").sortable({
            handle: ".sort_handle",
            tolerance: "pointer",
            containment: "parent",
            axis: "y"
        });
    });

    $(document).on("change", ".person_selector",function () {
        var $t = $(this);
        if ($t.val() === "neu") {
            $t.parents(".unterstuetzerInnenwidget_adder").find(".unterstuetzerIn_neu_holder").show();
        } else {
            $t.parents(".unterstuetzerInnenwidget_adder").find(".unterstuetzerIn_neu_holder").hide();
        }
    }).trigger("change");

    var $antragabsatz_holder = $(".antragabsatz_holder"),
        $lesezeichen = $(".lesezeichen"),
        $kommentare = $(".kommentare");

    $lesezeichen.find(".kommentare .hider").click(function (ev) {
        $(this).hide();
        $(this).parents(".kommentare").find(".shower").css("display", "block");
        $(this).parents(".kommentare").find(".text").show();

        $(this).parents(".row-absatz").find(".kommentarform").hide();
        $(this).hide();
        $(this).parents(".row-absatz").find(".kommentare .shower").css("display", "block");

        ev.preventDefault();
    });

    $lesezeichen.find(".kommentare .shower").click(function (ev) {
        $(this).hide();
        $(this).parents(".kommentare").find(".hider").css("display", "block");
        $(this).parents(".kommentare").find(".text").show();

        $(this).parents(".row-absatz").find(".kommentarform").show();
        $(this).hide();
        $(this).parents(".row-absatz").find(".kommentare .hider").show();

        ev.preventDefault();
    });

    $(".kommentare_closed_absatz .kommentare .hider").click();

    $(".kommentarform .del_link a").click(function (ev) {
        if (!confirm("Diesen Kommentar wirklich löschen?")) {
            ev.preventDefault();
        }
    });

    $lesezeichen.find(".aenderungsantrag a").mouseover(function () {
        var ae = $(this).data("id"),
            $par = $(this).parents(".row-absatz");
        $par.find(".ae_" + ae).show();
        $par.find(".orig").hide();
    }).mouseout(function () {
        var ae = $(this).data("id"),
            $par = $(this).parents(".row-absatz");
        $par.find(".ae_" + ae).hide();
        $par.find(".orig").show();
    });

    $(".js_protection_hint").remove();
    $("input[name=form_token]").each(function () {
        $(this).parents("form").append("<input name='" + $(this).val() + "' value='1' type='hidden'>");
        $(this).remove();
    });

    $(".kommentarform").submit(function (ev) {
        $(this).find(".row").each(function () {
            var $row = $(this);
            if ($row.find("label.required").length > 0 && $row.find("input, textarea").val() == "") {
                ev.preventDefault();
                alert("Bitte fülle alle Felder aus");
                $row.find("input, textarea").focus();
            }
        });

    });

}());


function ckeditor_bbcode(id, height) {

    var $el = $("#" + id),
        initialized = $el.data("ckeditor_initialized");
    if (typeof(initialized) != "undefined" && initialized) return;
    $el.data("ckeditor_initialized", "1");
    var opts = {
        allowedContent: 'b s i u p blockquote ul ol li;',
        removePlugins: 'stylescombo,format,save,newpage,print,templates,showblocks,specialchar,about,preview,pastetext,pastefromword,magicline' + ',sourcearea',
        extraPlugins: 'autogrow,wordcount,bbcode',
        scayt_sLang: 'de_DE',
        autoGrow_bottomSpace: 20,
        // Width and height are not supported in the BBCode format, so object resizing is disabled.
        disableObjectResizing: true,
        // Whether or not you want to show the Word Count
        showWordCount: true,
        // Whether or not you want to show the Char Count
        showCharCount: true,
        toolbar: [
            { name: 'basicstyles', items: [ 'Bold', 'Italic', 'Underline', '-', 'RemoveFormat' ] },
            { name: 'paragraph', items: [ 'NumberedList', 'BulletedList', '-', 'Blockquote' ] },
            { name: 'links', items: [ 'Link', 'Unlink' ] },
            { name: 'clipboard', items: [ 'Cut', 'Copy', 'Paste', 'PasteText', 'PasteFromWord', '-', 'Undo', 'Redo' ] },
            { name: 'editing', items: [ 'Find', 'Replace', '-', 'SelectAll', '-', 'SpellChecker', 'Scayt' ] },
            { name: 'tools', items: [ 'Maximize' ] }
        ]

    };
    if (typeof(height) != "undefined" && height > 0) opts["height"] = height;
    CKEDITOR.replace(id, opts);

}


function ckeditor_simplehtml(id) {

    var $el = $("#" + id),
        initialized = $el.data("ckeditor_initialized");
    if (typeof(initialized) != "undefined" && initialized) return;
    $el.data("ckeditor_initialized", "1");

    CKEDITOR.replace(id, {
        allowedContent: 'b s i u;' +
            'ul ol li {list-style-type};' +
            'table tr td th tbody thead caption [border] {margin,padding,width,height,border,border-spacing,border-collapse,align,cellspacing,cellpadding};' +
            'p blockquote {border,margin,padding,text-align};' +
            'a[href];',
        toolbarGroups: [
            { name: 'tools' },
            { name: 'document', groups: [ 'mode', 'document', 'doctools' ] },
            { name: 'clipboard', groups: [ 'clipboard', 'undo' ] },
            { name: 'editing', groups: [ 'find', 'selection', 'spellchecker' ] },
            { name: 'forms' },
            '/',
            { name: 'basicstyles', groups: [ 'basicstyles', 'cleanup' ] },
            { name: 'paragraph', groups: [ 'list', 'indent', 'blocks', 'align', 'bidi' ] },
            { name: 'links' },
            { name: 'insert' },
            '/',
            { name: 'styles' },
            { name: 'colors' },
            { name: 'others' },
            { name: 'about' }
        ],
        removePlugins: 'stylescombo,format,save,newpage,print,templates,showblocks,specialchar,about,preview,pastetext,pastefromword,bbcode',
        extraPlugins: 'autogrow,wordcount,tabletools',
        scayt_sLang: 'de_DE',
        autoGrow_bottomSpace: 20,
        // Whether or not you want to show the Word Count
        showWordCount: true,
        // Whether or not you want to show the Char Count
        showCharCount: true
    });

}


function instanz_neu_anlegen_init() {
    var $steps = $("#AnlegenWizard").find("li"),
        $step2 = $("#step2"),
        $step3 = $("#step3");
    $step2.hide();
    $step3.hide();
    $("#weiter-1").click(function (ev) {
        ev.preventDefault();
        $("#step1").hide();
        $step2.show();
        $steps.eq(0).removeClass("active");
        $steps.eq(1).addClass("active");
    });
    $("#weiter-2").click(function (ev) {
        ev.preventDefault();
        if ($step2.find(".name input").val() == "") {
            $step2.find(".name .alert").show();
            $step2.find(".name input").focus();
            return;
        }
        if ($step2.find(".url input").val() == "") {
            $step2.find(".url .alert").show();
            $step2.find(".url input").focus();
            return;
        }
        $step2.hide();
        $step3.show();
        $steps.eq(1).removeClass("active");
        $steps.eq(2).addClass("active");
    });
    $("#CInstanzAnlegenForm_subdomain").on("blur", function() {
        if ($(this).val().match(/[^a-zA-Z0-9_-]/)) {
            alert("Bei der Subdomain sind nur Zahlen, Buchstaben, Unter- und Mittelstrich möglich.");
            $(this).focus();
        }
    });
    $step3.find("button[type=submit]").click(function (ev) {

    });
}



/*!
 * jQuery scrollintoview() plugin and :scrollable selector filter
 *
 * Version 1.8 (14 Jul 2011)
 * Requires jQuery 1.4 or newer
 *
 * Copyright (c) 2011 Robert Koritnik
 * Licensed under the terms of the MIT license
 * http://www.opensource.org/licenses/mit-license.php
 */

(function ($) {
    var converter = {
        vertical: { x: false, y: true },
        horizontal: { x: true, y: false },
        both: { x: true, y: true },
        x: { x: true, y: false },
        y: { x: false, y: true }
    };

    var settings = {
        duration: "fast",
        direction: "both"
    };

    var rootrx = /^(?:html)$/i;

    // gets border dimensions
    var borders = function (domElement, styles) {
        styles = styles || (document.defaultView && document.defaultView.getComputedStyle ? document.defaultView.getComputedStyle(domElement, null) : domElement.currentStyle);
        var px = document.defaultView && document.defaultView.getComputedStyle ? true : false;
        var b = {
            top: (parseFloat(px ? styles.borderTopWidth : $.css(domElement, "borderTopWidth")) || 0),
            left: (parseFloat(px ? styles.borderLeftWidth : $.css(domElement, "borderLeftWidth")) || 0),
            bottom: (parseFloat(px ? styles.borderBottomWidth : $.css(domElement, "borderBottomWidth")) || 0),
            right: (parseFloat(px ? styles.borderRightWidth : $.css(domElement, "borderRightWidth")) || 0)
        };
        return {
            top: b.top,
            left: b.left,
            bottom: b.bottom,
            right: b.right,
            vertical: b.top + b.bottom,
            horizontal: b.left + b.right
        };
    };

    var dimensions = function ($element) {
        var win = $(window);
        var isRoot = rootrx.test($element[0].nodeName);
        return {
            border: isRoot ? { top: 0, left: 0, bottom: 0, right: 0} : borders($element[0]),
            scroll: {
                top: (isRoot ? win : $element).scrollTop(),
                left: (isRoot ? win : $element).scrollLeft()
            },
            scrollbar: {
                right: isRoot ? 0 : $element.innerWidth() - $element[0].clientWidth,
                bottom: isRoot ? 0 : $element.innerHeight() - $element[0].clientHeight
            },
            rect: (function () {
                var r = $element[0].getBoundingClientRect();
                return {
                    top: isRoot ? 0 : r.top,
                    left: isRoot ? 0 : r.left,
                    bottom: isRoot ? $element[0].clientHeight : r.bottom,
                    right: isRoot ? $element[0].clientWidth : r.right
                };
            })()
        };
    };

    $.fn.extend({
        scrollintoview: function (options) {
            /// <summary>Scrolls the first element in the set into view by scrolling its closest scrollable parent.</summary>
            /// <param name="options" type="Object">Additional options that can configure scrolling:
            ///        duration (default: "fast") - jQuery animation speed (can be a duration string or number of milliseconds)
            ///        direction (default: "both") - select possible scrollings ("vertical" or "y", "horizontal" or "x", "both")
            ///        complete (default: none) - a function to call when scrolling completes (called in context of the DOM element being scrolled)
            /// </param>
            /// <return type="jQuery">Returns the same jQuery set that this function was run on.</return>

            options = $.extend({}, settings, options);
            options.direction = converter[typeof (options.direction) === "string" && options.direction.toLowerCase()] || converter.both;

            var dirStr = "";
            if (options.direction.x === true) dirStr = "horizontal";
            if (options.direction.y === true) dirStr = dirStr ? "both" : "vertical";

            var el = this.eq(0);
            var scroller = el.closest(":scrollable(" + dirStr + ")");

            // check if there's anything to scroll in the first place
            if (scroller.length > 0) {
                scroller = scroller.eq(0);

                var dim = {
                    e: dimensions(el),
                    s: dimensions(scroller)
                };

                var rel = {
                    top: dim.e.rect.top - (dim.s.rect.top + dim.s.border.top),
                    bottom: dim.s.rect.bottom - dim.s.border.bottom - dim.s.scrollbar.bottom - dim.e.rect.bottom,
                    left: dim.e.rect.left - (dim.s.rect.left + dim.s.border.left),
                    right: dim.s.rect.right - dim.s.border.right - dim.s.scrollbar.right - dim.e.rect.right
                };

                var animOptions = {};

                // vertical scroll
                if (options.direction.y === true) {
                    if (typeof(options["top_offset"]) != "undefined") {
                        animOptions.scrollTop = dim.s.scroll.top + rel.top + options["top_offset"];
                    } else {
                        if (rel.top < 0) {
                            animOptions.scrollTop = dim.s.scroll.top + rel.top;
                        }
                        else if (rel.top > 0 && rel.bottom < 0) {
                            animOptions.scrollTop = dim.s.scroll.top + Math.min(rel.top, -rel.bottom);
                        }
                    }
                }

                // horizontal scroll
                if (options.direction.x === true) {
                    if (rel.left < 0) {
                        animOptions.scrollLeft = dim.s.scroll.left + rel.left;
                    }
                    else if (rel.left > 0 && rel.right < 0) {
                        animOptions.scrollLeft = dim.s.scroll.left + Math.min(rel.left, -rel.right);
                    }
                }

                // scroll if needed
                if (!$.isEmptyObject(animOptions)) {
                    if (rootrx.test(scroller[0].nodeName)) {
                        scroller = $("html,body");
                    }
                    scroller
                        .animate(animOptions, options.duration)
                        .eq(0) // we want function to be called just once (ref. "html,body")
                        .queue(function (next) {
                            $.isFunction(options.complete) && options.complete.call(scroller[0]);
                            next();
                        });
                }
                else {
                    // when there's nothing to scroll, just call the "complete" function
                    $.isFunction(options.complete) && options.complete.call(scroller[0]);
                }
            }

            // return set back
            return this;
        }
    });

    var scrollValue = {
        auto: true,
        scroll: true,
        visible: false,
        hidden: false
    };

    $.extend($.expr[":"], {
        scrollable: function (element, index, meta, stack) {
            var direction = converter[typeof (meta[3]) === "string" && meta[3].toLowerCase()] || converter.both;
            var styles = (document.defaultView && document.defaultView.getComputedStyle ? document.defaultView.getComputedStyle(element, null) : element.currentStyle);
            var overflow = {
                x: scrollValue[styles.overflowX.toLowerCase()] || false,
                y: scrollValue[styles.overflowY.toLowerCase()] || false,
                isRoot: rootrx.test(element.nodeName)
            };

            // check if completely unscrollable (exclude HTML element because it's special)
            if (!overflow.x && !overflow.y && !overflow.isRoot) {
                return false;
            }

            var size = {
                height: {
                    scroll: element.scrollHeight,
                    client: element.clientHeight
                },
                width: {
                    scroll: element.scrollWidth,
                    client: element.clientWidth
                },
                // check overflow.x/y because iPad (and possibly other tablets) don't dislay scrollbars
                scrollableX: function () {
                    return (overflow.x || overflow.isRoot) && this.width.scroll > this.width.client;
                },
                scrollableY: function () {
                    return (overflow.y || overflow.isRoot) && this.height.scroll > this.height.client;
                }
            };
            return direction.y && size.scrollableY() || direction.x && size.scrollableX();
        }
    });
})(jQuery);

