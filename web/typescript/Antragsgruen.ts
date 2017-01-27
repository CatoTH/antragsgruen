declare let requirejs: any;

(function ($: JQueryStatic) {
    $("[data-antragsgruen-load-class]").each(function() {
        let loadModule = $(this).data("antragsgruen-load-class");
        requirejs([loadModule]);
    });

    $("[data-antragsgruen-widget]").each(function() {
        let $element = $(this),
            loadModule = $element.data("data-antragsgruen-widget");
        requirejs([loadModule], function(util) {
            let className = loadModule.split('/');
            new window[className[className.length - 1]]($element);
        });
    });

    $(".jsProtectionHint").each(function () {
        let $hint = $(this);
        $('<input type="hidden" name="jsprotection">').attr("value", $hint.data("value")).appendTo($hint.parent());
        $hint.remove();
    });

    $(document).on('click', '.amendmentAjaxTooltip', function (ev) {
        let $el = $(ev.currentTarget);
        if ($el.data('initialized') == '0') {
            $el.data('initialized', '1');
            $el.popover({
                html: true,
                trigger: 'manual',
                container: 'body',
                template: '<div class="popover popover-amendment-ajax" role="tooltip"><div class="arrow"></div><h3 class="popover-title"></h3><div class="popover-content"></div></div>',
                content: function () {
                    let id = 'pop_' + (new Date()).getTime(),
                        content = '<div id="' + id + '">Loading...</div>',
                        url = $el.data('url');
                    $.get(url, function (ret) {
                        $('#' + id).html(ret);
                    });
                    return content;
                }
            });
        }
        $('.amendmentAjaxTooltip').not($el).popover('hide');
        $el.popover('toggle');
    });
    $(document).on('click', function (ev) {
        let $target = $(ev.target);
        if (
            !$target.hasClass('amendmentAjaxTooltip') && !$target.hasClass('popover') &&
            $target.parents('.amendmentAjaxTooltip').length == 0 && $target.parents('.popover').length == 0
        ) {
            $('.amendmentAjaxTooltip').popover('hide');
        }
    });
}(jQuery));
