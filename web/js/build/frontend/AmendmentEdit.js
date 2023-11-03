define(["require","exports","../shared/AntragsgruenEditor","../shared/DraftSavingEngine"],(function(t,e,i,a){"use strict";Object.defineProperty(e,"__esModule",{value:!0}),e.AmendmentEdit=void 0;class n{constructor(t){this.$form=t,this.hasChanged=!1,this.isSingleLocationMode=!1;let e=parseInt(t.data("multi-paragraph-mode"),10);if(void 0===e)throw"data-multi-paragraph-mode needs to be set";this.lang=$("html").attr("lang"),this.$form.find(".editorialChange input").on("change",this.editorialOpenerClicked.bind(this)).trigger("change"),this.initGlobalAlternative(),$(".input-group.date").datetimepicker({locale:this.lang,format:"L"}),1===e?this.initMultiParagraphMode():(this.isSingleLocationMode=-1===e,this.spmInit());let i=$("#draftHint"),r=i.data("motion-id"),d=i.data("amendment-id");new a.DraftSavingEngine(t,i,"motion_"+r+"_"+d),t.on("submit",(()=>{$(window).off("beforeunload",n.onLeavePage)}))}initGlobalAlternative(){}editorialOpenerClicked(){let t=this.$form.find("#sectionHolderEditorial"),e=t.find(".texteditor");if(this.$form.find(".editorialChange input").prop("checked")){if(t.removeClass("hidden"),void 0===CKEDITOR.instances.amendmentEditorial_wysiwyg){let t=new i.AntragsgruenEditor("amendmentEditorial_wysiwyg");e.parents("form").on("submit",(()=>{this.$form.find(".editorialChange input").prop("checked")?e.parent().find("textarea.raw").val(t.getEditor().getData()):e.parent().find("textarea.raw").val("")})),$("#"+e.attr("id")).on("keypress",this.onContentChanged.bind(this))}}else t.addClass("hidden")}initMultiParagraphMode(){$(".wysiwyg-textarea:not(#sectionHolderEditorial)").each(((t,e)=>{let a=$(e).find(".texteditor"),n=new i.AntragsgruenEditor(a.attr("id")).getEditor();a.parents("form").on("submit",(()=>{a.parent().find("textarea.raw").val(n.getData()),void 0!==n.plugins.lite&&(n.plugins.lite.findPlugin(n).acceptAll(),a.parent().find("textarea.consolidated").val(n.getData()))})),$("#"+a.attr("id")).on("keypress",this.onContentChanged.bind(this))})),this.$form.find(".resetText").on("click",(t=>{let e=$(t.currentTarget).parents(".wysiwyg-textarea").find(".texteditor");window.CKEDITOR.instances[e.attr("id")].setData(e.data("original-html")),$(t.currentTarget).parents(".modifiedActions").addClass("hidden")}))}spmSetModifyable(){let t=this.$spmParagraphs.filter(".modified");0==t.length?this.$spmParagraphs.addClass("modifyable"):(this.$spmParagraphs.removeClass("modifyable"),$("input[name=modifiedParagraphNo]").val(t.data("paragraph-no")),$("input[name=modifiedSectionId]").val(t.parents(".texteditorBox").data("section-id")))}spmOnParaClick(t){let e=$(t.currentTarget);if(!e.hasClass("modifyable"))return;e.addClass("modified"),this.spmSetModifyable();let a,n=e.find(".texteditor");a=void 0!==CKEDITOR.instances[n.attr("id")]?CKEDITOR.instances[n.attr("id")]:new i.AntragsgruenEditor(n.attr("id")).getEditor(),n.attr("contenteditable","true"),n.parents("form").on("submit",(t=>{if(this.isSingleLocationMode&&r())return t.preventDefault(),t.stopPropagation(),e.find(".oneChangeHint").removeClass("hidden"),void e.scrollintoview({top_offset:-100});n.parent().find("textarea.raw").val(a.getData()),void 0!==a.plugins.lite&&(a.plugins.lite.findPlugin(a).acceptAll(),n.parent().find("textarea.consolidated").val(a.getData()))}));const r=()=>{let t=0,e=0;return n.find(".ice-ins").each((function(){$(this)[0].innerText.length>0&&"\ufeff"!==$(this)[0].innerText&&t++})),n.find(".ice-del").each((function(){$(this)[0].innerText.length>0&&"\ufeff"!==$(this)[0].innerText&&e++})),t>1||e>1};$("#"+n.attr("id")).on("keypress",this.onContentChanged.bind(this)).on("keyup",(()=>{this.isSingleLocationMode&&(r()?e.find(".oneChangeHint").removeClass("hidden"):e.find(".oneChangeHint").addClass("hidden"))}).bind(this)),n.trigger("focus")}spmRevert(t){t.preventDefault(),t.stopPropagation();let e=$(t.target).parents(".wysiwyg-textarea"),a=e.find(".texteditor");void 0!==CKEDITOR.instances[a.attr("id")]&&i.AntragsgruenEditor.destroyInstanceById(a.attr("id")),a.html(e.data("original")),e.removeClass("modified"),e.find(".oneChangeHint").addClass("hidden"),this.spmSetModifyable()}spmInitNonSingleParas(t,e){let a=$(e),n=a.find(".texteditor");if(a.hasClass("hidden"))return;let r=new i.AntragsgruenEditor(n.attr("id")).getEditor();n.parents("form").on("submit",(()=>{n.parent().find("textarea.raw").val(r.getData()),void 0!==r.plugins.lite&&(r.plugins.lite.findPlugin(r).acceptAll(),n.parent().find("textarea.consolidated").val(r.getData()))})),$("#"+n.attr("id")).on("keypress",this.onContentChanged.bind(this))}spmInit(){if(console.log("spm"),this.$spmParagraphs=$(".wysiwyg-textarea.single-paragraph"),this.$spmParagraphs.on("click",this.spmOnParaClick.bind(this)),this.$spmParagraphs.find(".modifiedActions .revert").on("click",this.spmRevert.bind(this)),this.spmSetModifyable(),$(".wysiwyg-textarea").filter(":not(.single-paragraph)").each(this.spmInitNonSingleParas.bind(this)),$(".texteditorBox").each(((t,e)=>{let i=$(e),a=i.data("section-id"),n=i.data("changed-para-no");n>-1&&$("#section_holder_"+a+"_"+n).trigger("click")})),this.$form.data("init-section-id")){const t=$("#section_holder_"+this.$form.data("init-section-id")+"_"+this.$form.data("init-paragraph-no"));t.trigger("click"),t.scrollintoview({top_offset:-100})}}static onLeavePage(){return __t("std","leave_changed_page")}onContentChanged(){this.hasChanged||(this.hasChanged=!0,$("body").hasClass("testing")||$(window).on("beforeunload",n.onLeavePage))}}e.AmendmentEdit=n}));
//# sourceMappingURL=AmendmentEdit.js.map
