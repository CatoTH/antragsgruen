define(["require","exports","../shared/AntragsgruenEditor"],function(e,t,r){"use strict";Object.defineProperty(t,"__esModule",{value:!0});var n=function(){function a(){}return a.removeEmptyParagraphs=function(){$(".paragraphHolder").each(function(e,t){0==t.childNodes.length&&$(t).remove()})},a.accept=function(e){var t=$(e);t.hasClass("ice-ins")&&a.insertAccept(e),t.hasClass("ice-del")&&a.deleteAccept(e)},a.reject=function(e){var t=$(e);t.hasClass("ice-ins")&&a.insertReject(t),t.hasClass("ice-del")&&a.deleteReject(t)},a.insertReject=function(e){var t,n=e[0].nodeName.toLowerCase();t="li"==n?e.parent():e,"ul"==n||"ol"==n||"li"==n||"blockquote"==n||"pre"==n||"p"==n?(t.css("overflow","hidden").height(t.height()),t.animate({height:"0"},250,function(){t.remove(),$(".collidingParagraph:empty").remove(),a.removeEmptyParagraphs()})):t.remove()},a.insertAccept=function(e){var t=$(e);t.removeClass("ice-cts ice-ins appendHint moved"),t.removeAttr("data-moving-partner data-moving-partner-id data-moving-partner-paragraph data-moving-msg"),"ul"!=e.nodeName.toLowerCase()&&"ol"!=e.nodeName.toLowerCase()||t.children().removeClass("ice-cts").removeClass("ice-ins").removeClass("appendHint"),"li"==e.nodeName.toLowerCase()&&t.parent().removeClass("ice-cts").removeClass("ice-ins").removeClass("appendHint"),"ins"==e.nodeName.toLowerCase()&&t.replaceWith(t.html())},a.deleteReject=function(e){e.removeClass("ice-cts ice-del appendHint"),e.removeAttr("data-moving-partner data-moving-partner-id data-moving-partner-paragraph data-moving-msg");var t=e[0].nodeName.toLowerCase();"ul"!=t&&"ol"!=t||e.children().removeClass("ice-cts").removeClass("ice-del").removeClass("appendHint"),"li"==t&&e.parent().removeClass("ice-cts").removeClass("ice-del").removeClass("appendHint"),"del"==t&&e.replaceWith(e.html())},a.deleteAccept=function(e){var t,n=e.nodeName.toLowerCase();t="li"==n?$(e).parent():$(e),"ul"==n||"ol"==n||"li"==n||"blockquote"==n||"pre"==n||"p"==n?(t.css("overflow","hidden").height(t.height()),t.animate({height:"0"},250,function(){t.remove(),$(".collidingParagraph:empty").remove(),a.removeEmptyParagraphs()})):t.remove()},a}();t.MotionMergeChangeActions=n;var a=function(){function e(i,r,o,e){this.$element=i,this.parent=e;var s=null,d=null;i.popover({container:"body",animation:!1,trigger:"manual",placement:function(e){var a=$(e);return window.setTimeout(function(){var e=a.width(),t=i.offset().top,n=i.height();null===s&&0<e&&(s=r-e/2,(d=o+10)<t+19&&(d=t+19),t+n<d&&(d=t+n)),a.css("left",s+"px"),a.css("top",d+"px")},1),"bottom"},html:!0,content:this.getContent.bind(this)}),i.popover("show"),i.find("> .popover").on("mousemove",function(e){e.stopPropagation()}),window.setTimeout(this.removePopupIfInactive.bind(this),1e3)}return e.prototype.getContent=function(){var e=this.$element,t=e.data("cid");null==t&&(t=e.parent().data("cid")),e.parents(".texteditor").first().find("[data-cid="+t+"]").addClass("hover");var n=$('<div><button type="button" class="accept btn btn-sm btn-default"></button><button type="button" class="reject btn btn-sm btn-default"></button><a href="#" class="btn btn-small btn-default opener" target="_blank"><span class="glyphicon glyphicon-new-window"></span></a><div class="initiator" style="font-size: 0.8em;"></div></div>');if(n.find(".opener").attr("href",e.data("link")).attr("title",__t("merge","title_open_in_blank")),n.find(".initiator").text(__t("merge","initiated_by")+": "+e.data("username")),e.hasClass("ice-ins"))n.find("button.accept").text(__t("merge","change_accept")).click(this.accept.bind(this)),n.find("button.reject").text(__t("merge","change_reject")).click(this.reject.bind(this));else if(e.hasClass("ice-del"))n.find("button.accept").text(__t("merge","change_accept")).click(this.accept.bind(this)),n.find("button.reject").text(__t("merge","change_reject")).click(this.reject.bind(this));else if("li"==e[0].nodeName.toLowerCase()){var a=e.parent();a.hasClass("ice-ins")?(n.find("button.accept").text(__t("merge","change_accept")).click(this.accept.bind(this)),n.find("button.reject").text(__t("merge","change_reject")).click(this.reject.bind(this))):a.hasClass("ice-del")?(n.find("button.accept").text(__t("merge","change_accept")).click(this.accept.bind(this)),n.find("button.reject").text(__t("merge","change_reject")).click(this.reject.bind(this))):console.log("unknown",a)}else console.log("unknown",e),alert("unknown");return n},e.prototype.removePopupIfInactive=function(){return this.$element.is(":hover")?window.setTimeout(this.removePopupIfInactive.bind(this),1e3):0<$("body").find(".popover:hover").length?window.setTimeout(this.removePopupIfInactive.bind(this),1e3):void this.destroy()},e.prototype.affectedChangesets=function(){var e=this.$element.data("cid");return null==e&&(e=this.$element.parent().data("cid")),this.$element.parents(".texteditor").find("[data-cid="+e+"]")},e.prototype.performActionWithUI=function(e){var t=window.scrollX,n=window.scrollY;this.parent.saveEditorSnapshot(),this.destroy(),e.call(this),$(".collidingParagraph:empty").remove(),this.parent.focusTextarea(),window.scrollTo(t,n)},e.prototype.accept=function(){var e=this;this.performActionWithUI(function(){e.affectedChangesets().each(function(e,t){n.accept(t)})})},e.prototype.reject=function(){var e=this;this.performActionWithUI(function(){e.affectedChangesets().each(function(e,t){n.reject(t)})})},e.prototype.destroy=function(){this.$element.popover("hide").popover("destroy");var e=this.$element.data("cid");null==e&&(e=this.$element.parent().data("cid")),this.$element.parents(".texteditor").first().find("[data-cid="+e+"]").removeClass("hover")},e}(),i=function(){function e(e,t,n){this.$element=e,this.parent=n,e.popover({container:"body",animation:!1,trigger:"manual",placement:"bottom",html:!0,title:__t("merge","colliding_title"),content:this.getContent.bind(this)}),e.popover("show");var a=$("body > .popover"),i=a.width();a.css("left",Math.floor(e.offset().left+t-i/2+20)+"px"),a.on("mousemove",function(e){e.stopPropagation()}),window.setTimeout(this.removePopupIfInactive.bind(this),500)}return e.prototype.removePopupIfInactive=function(){return this.$element.is(":hover")?window.setTimeout(this.removePopupIfInactive.bind(this),1e3):0<$("body").find(".popover:hover").length?window.setTimeout(this.removePopupIfInactive.bind(this),1e3):void this.destroy()},e.prototype.performActionWithUI=function(e){this.parent.saveEditorSnapshot(),this.destroy(),e.call(this),$(".collidingParagraph:empty").remove(),this.parent.focusTextarea()},e.prototype.getContent=function(){var e=this,n=this.$element,t='<div style="white-space: nowrap;"><button type="button" class="btn btn-small btn-default delTitle"><span style="text-decoration: line-through">'+__t("merge","title")+"</span></button>";t+='<button type="button" class="reject btn btn-small btn-default"><span class="glyphicon glyphicon-trash"></span></button>',t+='<a href="#" class="btn btn-small btn-default opener" target="_blank"><span class="glyphicon glyphicon-new-window"></span></a>',t+='<div class="initiator" style="font-size: 0.8em;"></div>',t+="</div>";var a=$(t);return a.find(".delTitle").attr("title",__t("merge","title_del_title")),a.find(".reject").attr("title",__t("merge","title_del_colliding")),a.find("a.opener").attr("href",n.find("a").attr("href")).attr("title",__t("merge","title_open_in_blank")),a.find(".initiator").text(__t("merge","initiated_by")+": "+n.parents(".collidingParagraph").data("username")),a.find(".reject").click(function(){e.performActionWithUI.call(e,function(){var t=n.parents(".collidingParagraph");t.css({overflow:"hidden"}).height(t.height()),t.animate({height:"0"},250,function(){var e=t.parents(".paragraphHolder");t.remove(),0==e.find(".collidingParagraph").length&&e.removeClass("hasCollisions")})})}),a.find(".delTitle").click(function(){e.performActionWithUI.call(e,function(){var e=n.parents(".collidingParagraph");n.remove(),e.removeClass("collidingParagraph");var t=e.parents(".paragraphHolder");0==t.find(".collidingParagraph").length&&t.removeClass("hasCollisions")})}),a},e.prototype.destroy=function(){var e=this.$element.data("cid");null==e&&(e=this.$element.parent().data("cid")),this.$element.parents(".texteditor").first().find("[data-cid="+e+"]").removeClass("hover"),this.$element.popover("hide").popover("destroy")},e}(),o=function(){function e(e,t){var n=this;this.$holder=e,this.rootObject=t;var a=e.find(".texteditor"),i=new r.AntragsgruenEditor(a.attr("id"));this.texteditor=i.getEditor(),this.rootObject.addSubmitListener(function(){e.find("textarea.raw").val(n.texteditor.getData()),e.find("textarea.consolidated").val(n.texteditor.getData())}),this.prepareText(),this.initializeTooltips(),this.$holder.find(".acceptAllChanges").click(this.acceptAll.bind(this)),this.$holder.find(".rejectAllChanges").click(this.rejectAll.bind(this))}return e.prototype.prepareText=function(){var e=$("<div>"+this.texteditor.getData()+"</div>");e.find("ul.appendHint, ol.appendHint").each(function(e,t){var n=$(t),a=n.data("append-hint");n.find("> li").addClass("appendHint").attr("data-append-hint",a).attr("data-link",n.data("link")).attr("data-username",n.data("username")),n.removeClass("appendHint").removeData("append-hint")}),e.find(".moved .moved").removeClass("moved"),e.find(".moved").each(this.markupMovedParagraph.bind(this)),e.find(".hasCollisions").attr("data-collision-start-msg",__t("merge","colliding_start")).attr("data-collision-end-msg",__t("merge","colliding_end"));var t=e.html();this.texteditor.setData(t)},e.prototype.markupMovedParagraph=function(e,t){var n,a=$(t),i=a.data("moving-partner-paragraph");n=(n=a.hasClass("inserted")?__t("std","moved_paragraph_from"):__t("std","moved_paragraph_to")).replace(/##PARA##/,i+1),"LI"===a[0].nodeName&&(a=a.parent()),a.attr("data-moving-msg",n)},e.prototype.initializeTooltips=function(){var t=this;this.$holder.on("mouseover",".collidingParagraphHead",function(e){$(e.target).parents(".collidingParagraph").addClass("hovered"),s.activePopup&&s.activePopup.destroy(),s.activePopup=new i($(e.currentTarget),s.currMouseX,t)}).on("mouseout",".collidingParagraphHead",function(e){$(e.target).parents(".collidingParagraph").removeClass("hovered")}),this.$holder.on("mouseover",".appendHint",function(e){s.activePopup&&s.activePopup.destroy(),s.activePopup=new a($(e.currentTarget),e.pageX,e.pageY,t)})},e.prototype.acceptAll=function(){this.texteditor.fire("saveSnapshot"),this.$holder.find(".collidingParagraph").each(function(e,t){var n=$(t);n.find(".collidingParagraphHead").remove(),n.replaceWith(n.children())}),this.$holder.find(".ice-ins").each(function(e,t){n.insertAccept(t)}),this.$holder.find(".ice-del").each(function(e,t){n.deleteAccept(t)})},e.prototype.rejectAll=function(){this.texteditor.fire("saveSnapshot"),this.$holder.find(".collidingParagraph").each(function(e,t){$(t).remove()}),this.$holder.find(".ice-ins").each(function(e,t){n.insertReject($(t))}),this.$holder.find(".ice-del").each(function(e,t){n.deleteReject($(t))})},e.prototype.saveEditorSnapshot=function(){this.texteditor.fire("saveSnapshot")},e.prototype.focusTextarea=function(){},e.prototype.getContent=function(){return this.texteditor.getData()},e}(),s=function(){function i(e){var a=this;this.$form=e,this.textareas={},$(".wysiwyg-textarea").each(function(e,t){var n=$(t);a.textareas[n.attr("id")]=new o(n,a),n.on("mousemove",function(e){i.currMouseX=e.offsetX})}),this.$form.on("submit",function(){$(window).off("beforeunload",i.onLeavePage)}),$(window).on("beforeunload",i.onLeavePage),this.initDraftSaving()}return i.onLeavePage=function(){return __t("std","leave_changed_page")},i.prototype.addSubmitListener=function(e){this.$form.submit(e)},i.prototype.setDraftDate=function(e){this.$draftSavingPanel.find(".lastSaved .none").hide();var t=$("html").attr("lang"),n=new Intl.DateTimeFormat(t,{year:"numeric",month:"numeric",day:"numeric",hour:"numeric",minute:"numeric",hour12:!1}).format(e);this.$draftSavingPanel.find(".lastSaved .value").text(n)},i.prototype.saveDraft=function(){for(var t=this,e={},n=0,a=Object.getOwnPropertyNames(this.textareas);n<a.length;n++){var i=a[n];e[i.replace("section_holder_","")]=this.textareas[i].getContent()}var r=this.$draftSavingPanel.find("input[name=public]").prop("checked");$.ajax({type:"POST",url:this.$form.data("draftSaving"),data:{public:r?1:0,sections:e,_csrf:this.$form.find("> input[name=_csrf]").val()},success:function(e){e.success?(t.$draftSavingPanel.find(".savingError").addClass("hidden"),t.setDraftDate(new Date(e.date)),r?t.$form.find(".publicLink").removeClass("hidden"):t.$form.find(".publicLink").addClass("hidden")):(t.$draftSavingPanel.find(".savingError").removeClass("hidden"),t.$draftSavingPanel.find(".savingError .errorNetwork").addClass("hidden"),t.$draftSavingPanel.find(".savingError .errorHolder").text(e.error).removeClass("hidden"))},error:function(){t.$draftSavingPanel.find(".savingError").removeClass("hidden"),t.$draftSavingPanel.find(".savingError .errorNetwork").removeClass("hidden"),t.$draftSavingPanel.find(".savingError .errorHolder").text("").addClass("hidden")}})},i.prototype.initAutosavingDraft=function(){var e=this,t=this.$draftSavingPanel.find("input[name=autosave]");if(window.setInterval(function(){t.prop("checked")&&e.saveDraft()},5e3),localStorage){var n=localStorage.getItem("merging-draft-auto-save");null!==n&&t.prop("checked","1"==n)}t.change(function(){var e=t.prop("checked");localStorage&&localStorage.setItem("merging-draft-auto-save",e?"1":"0")}).trigger("change")},i.prototype.initDraftSaving=function(){if(this.$draftSavingPanel=this.$form.find("#draftSavingPanel"),this.$draftSavingPanel.find(".saveDraft").on("click",this.saveDraft.bind(this)),this.$draftSavingPanel.find("input[name=public]").on("change",this.saveDraft.bind(this)),this.initAutosavingDraft(),this.$draftSavingPanel.data("resumed-date")){var e=new Date(this.$draftSavingPanel.data("resumed-date"));this.setDraftDate(e)}$("#yii-debug-toolbar").remove()},i.activePopup=null,i.currMouseX=null,i}();t.MotionMergeAmendments=s});
//# sourceMappingURL=MotionMergeAmendments.js.map
