!function(a){a("[data-antragsgruen-load-class]").each(function(){var t=a(this).data("antragsgruen-load-class");requirejs([t])}),a("[data-antragsgruen-widget]").each(function(){var t=a(this),e=t.data("antragsgruen-widget");requirejs([e],function(a){var n=e.split("/");new a[n[n.length-1]](t)})}),a(".jsProtectionHint").each(function(){var t=a(this);a('<input type="hidden" name="jsprotection">').attr("value",t.data("value")).appendTo(t.parent()),t.remove()}),bootbox.setLocale(a("html").attr("lang").split("_")[0]),a(document).on("click",".amendmentAjaxTooltip",function(t){var e=a(t.currentTarget);if("0"==e.data("initialized")){e.data("initialized","1");var n="popover popover-amendment-ajax "+e.data("tooltip-extra-class"),o="right";e.data("placement")&&(o=e.data("placement")),e.popover({html:!0,trigger:"manual",container:"body",template:'<div class="'+n+'" role="tooltip"><div class="arrow"></div><h3 class="popover-title"></h3><div class="popover-content"></div></div>',placement:o,content:function(){var t="pop_"+(new Date).getTime(),n='<div id="'+t+'">Loading...</div>',o=e.data("url");return a.get(o,function(e){a("#"+t).html(e)}),n}})}a(".amendmentAjaxTooltip").not(e).popover("hide"),a(".ajaxAmendment").parents(".popover").remove(),e.popover("toggle")}),a(document).on("click",function(t){var e=a(t.target);e.hasClass("amendmentAjaxTooltip")||e.hasClass("popover")||0!=e.parents(".amendmentAjaxTooltip").length||0!=e.parents(".popover").length||(a(".amendmentAjaxTooltip").popover("hide"),a(".ajaxAmendment").parents(".popover").remove())});var t=function(e){var n="0.";e.find("> li.agendaItem").each(function(){var e=a(this),o=e.data("code"),i="",r=e.find("> ol");if("#"==o){var d=n.split(".");if(d[0].match(/^[a-y]$/i))d[0]=String.fromCharCode(d[0].charCodeAt(0)+1);else{var p=d[0].match(/^(.*[^0-9])?([0-9]*)$/),l=void 0===p[1]?"":p[1],c=parseInt(""==p[2]?"1":p[2]);d[0]=l+ ++c}n=i=d.join(".")}else i=n=o+"";e.find("> div > h3 .code").text(i),r.length>0&&t(r)})};a("ol.motionListAgenda").on("antragsgruen:agenda-change",function(){t(a(this))}).trigger("antragsgruen:agenda-change"),window.__t=function(a,t){return"undefined"==typeof ANTRAGSGRUEN_STRINGS?"@TRANSLATION STRINGS NOT LOADED":void 0===ANTRAGSGRUEN_STRINGS[a]?"@UNKNOWN CATEGORY: "+a:void 0===ANTRAGSGRUEN_STRINGS[a][t]?"@UNKNOWN STRING: "+a+" / "+t:ANTRAGSGRUEN_STRINGS[a][t]}}(jQuery);
//# sourceMappingURL=Antragsgruen.js.map
