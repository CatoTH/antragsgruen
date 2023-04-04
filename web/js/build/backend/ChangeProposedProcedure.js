define(["require","exports"],(function(t,e){"use strict";Object.defineProperty(e,"__esModule",{value:!0}),e.ChangeProposedProcedure=void 0;e.ChangeProposedProcedure=class{constructor(t){this.$widget=t,this.savingComment=!1,this.initElements(),this.initOpener(),this.initStatusSetter(),this.initCommentForm(),this.initVotingBlock(),this.initExplanation(),this.initTags(),t.on("submit",(t=>t.preventDefault())),this.setVotingBlockSettings()}initElements(){this.$statusDetails=this.$widget.find(".statusDetails"),this.$visibilityInput=this.$widget.find("input[name=proposalVisible]"),this.$votingStatusInput=this.$widget.find("input[name=votingStatus]"),this.$votingBlockId=this.$widget.find("select[name=votingBlockId]"),this.$tagsSelect=this.$widget.find(".proposalTagsSelect"),this.$openerBtn=$(".proposedChangesOpener button"),this.context=this.$widget.data("context"),this.saveUrl=this.$widget.attr("action"),this.csrf=this.$widget.find("input[name=_csrf]").val()}initOpener(){this.$openerBtn.on("click",(()=>{this.$widget.removeClass("hidden"),this.$openerBtn.addClass("hidden"),localStorage.setItem("proposed_procedure_enabled","1")})),this.$widget.on("click",".closeBtn",(()=>{this.$widget.addClass("hidden"),this.$openerBtn.removeClass("hidden"),localStorage.setItem("proposed_procedure_enabled","0")})),"1"===localStorage.getItem("proposed_procedure_enabled")?(this.$widget.removeClass("hidden"),this.$openerBtn.addClass("hidden")):this.$widget.addClass("hidden")}initTags(){const t=this.$tagsSelect;t.selectize({create:!0,plugins:["remove_button"]}),t.on("change",(()=>{this.$widget.addClass("isChanged")}))}reinitAfterReload(){this.initElements(),this.statusChanged(),this.commentsScrollBottom(),this.initExplanation(),this.initTags(),this.$widget.find(".newBlock").addClass("hidden"),this.$widget.find(".notifyProposerSection").addClass("hidden"),this.$widget.find("#votingBlockId").trigger("change")}setGlobalProposedStr(t){$(".motionData .proposedStatusRow .str").html(t)}performCallWithReload(t){t._csrf=this.csrf,t.context=this.context,$.post(this.saveUrl,t,(t=>{if(console.log(t),t.redirectToUrl)window.location.href=t.redirectToUrl;else if(t.success){let e=$(t.html);this.$widget.children().remove(),this.$widget.append(e.children()),this.reinitAfterReload(),this.$widget.addClass("showSaved").removeClass("isChanged"),t.proposalStr&&this.setGlobalProposedStr(t.proposalStr),window.setTimeout((()=>this.$widget.removeClass("showSaved")),2e3)}else t.error?alert(t.error):alert("An error occurred")})).fail((()=>{alert("Could not save")}))}notifyProposer(){const t=this.$widget.find("textarea[name=proposalNotificationText]").val(),e=this.$widget.find("input[name=proposalNotificationFrom]").val(),i=this.$widget.find("input[name=proposalNotificationReply]").val();this.performCallWithReload({notifyProposer:"1",text:t,fromName:e,replyTo:i})}setPropserHasAccepted(){const t=this.$widget.find(".setConfirmation").data("msg");bootbox.confirm(t,(t=>{t&&this.performCallWithReload({setProposerHasAccepted:"1"})}))}sendAgain(){const t=this.$widget.find(".sendAgain").data("msg");bootbox.confirm(t,(t=>{t&&this.performCallWithReload({sendAgain:"1"})}))}saveStatus(){const t=this.$tagsSelect[0];let e=this.$widget.find(".statusForm input[type=radio]:checked").val(),i={setStatus:e,visible:this.$visibilityInput.prop("checked")?1:0,votingBlockId:this.$votingBlockId.val(),votingItemBlockName:this.$widget.find(".votingItemBlockNameRow input").val(),tags:t.selectize.items};10==e&&(i.proposalComment=this.$widget.find("input[name=referredTo]").val()),22==e&&(this.$widget.find("select[name=obsoletedByAmendment]").length>0?i.proposalComment=this.$widget.find("select[name=obsoletedByAmendment]").val():i.proposalComment=this.$widget.find("select[name=obsoletedByMotion]").val()),28==e&&this.$widget.find("select[name=movedToOtherMotion]").length>0&&(i.proposalComment=this.$widget.find("select[name=movedToOtherMotion]").val()),23==e&&(i.proposalComment=this.$widget.find("input[name=statusCustomStr]").val()),11==e&&(i.votingStatus=this.$votingStatusInput.filter(":checked").val()),"NEW"==i.votingBlockId&&(i.votingBlockTitle=this.$widget.find("input[name=newBlockTitle]").val()),i.votingItemBlockId={},this.$widget.find(".votingItemBlockInput").each((function(t,e){const s=$(e);i.votingItemBlockId[s.data("voting-block")+""]=s.val()})),this.$widget.find("input[name=setPublicExplanation]").prop("checked")&&(i.proposalExplanation=this.$widget.find("textarea[name=proposalExplanation]").val()),this.performCallWithReload(i)}statusChanged(){let t=parseInt(this.$widget.find(".statusForm input[type=radio]:checked").val(),10);this.$statusDetails.addClass("hidden"),this.$statusDetails.filter(".status_"+t.toString(10)).removeClass("hidden"),0===t?this.$widget.addClass("noStatus"):this.$widget.removeClass("noStatus")}initStatusSetter(){this.$widget.on("change",".statusForm input[type=radio]",((t,e)=>{$(t.currentTarget).prop("checked")&&(this.statusChanged(),e&&!0===e.init||this.$widget.addClass("isChanged"))})),this.$widget.find(".statusForm input[type=radio]").trigger("change",{init:!0}),this.$widget.on("change keyup","input, textarea",(t=>{$(t.currentTarget).parents(".proposalCommentForm").length>0||this.$widget.addClass("isChanged")})),this.$widget.on("change","#obsoletedByAmendment",(()=>{this.$widget.addClass("isChanged")})),this.$widget.on("change","#movedToOtherMotion",(()=>{this.$widget.addClass("isChanged")})),this.$widget.on("click",".saving button",this.saveStatus.bind(this)),this.$widget.on("click",".notifyProposer",(()=>{this.$widget.find(".notifyProposerSection").removeClass("hidden")})),this.$widget.on("click",".setConfirmation",this.setPropserHasAccepted.bind(this)),this.$widget.on("click",".sendAgain",this.sendAgain.bind(this)),this.$widget.on("click","button[name=notificationSubmit]",this.notifyProposer.bind(this))}setVotingBlockSettings(){this.$widget.find(".votingItemBlockRow select").on("change",(t=>{const e=$(t.currentTarget);if(e.val()){const t=e.find("option[value="+e.val()+"]").data("group-name");this.$widget.find(".votingItemBlockNameRow input").val(t),this.$widget.find(".votingItemBlockNameRow").removeClass("hidden")}else this.$widget.find(".votingItemBlockNameRow").addClass("hidden")})),"NEW"===this.$votingBlockId.val()?(this.$widget.find(".newBlock").removeClass("hidden"),this.$widget.find(".votingItemBlockRow").addClass("hidden"),this.$widget.find(".votingItemBlockNameRow").addClass("hidden")):(this.$widget.find(".newBlock").addClass("hidden"),this.$widget.find(".votingItemBlockRow").addClass("hidden"),this.$widget.find(".votingItemBlockRow"+this.$votingBlockId.val()).removeClass("hidden"),this.$widget.find(".votingItemBlockRow"+this.$votingBlockId.val()+" select").trigger("change"))}initVotingBlock(){this.$widget.on("change","#votingBlockId",(()=>{this.$widget.addClass("isChanged"),this.setVotingBlockSettings()})),this.$widget.on("change",".votingItemBlockRow select",(()=>{this.$widget.addClass("isChanged")})),this.$widget.find(".newBlock").addClass("hidden")}initExplanation(){this.$widget.find("input[name=setPublicExplanation]").on("change",(t=>{$(t.target).prop("checked")?this.$widget.find("section.publicExplanation").removeClass("hidden"):this.$widget.find("section.publicExplanation").addClass("hidden")})),this.$widget.find("input[name=setPublicExplanation]").prop("checked")?this.$widget.find("section.publicExplanation").removeClass("hidden"):this.$widget.find("section.publicExplanation").addClass("hidden")}commentsScrollBottom(){let t=this.$widget.find(".proposalCommentForm .commentList");t[0].scrollTop=t[0].scrollHeight}doSaveComment(){let t=this.$widget.find(".proposalCommentForm"),e=t.find(".commentList"),i=t.find("textarea").val();""==i||this.savingComment||(this.savingComment=!0,$.post(this.saveUrl,{writeComment:i,_csrf:this.csrf},(i=>{if(i.success){let s="";i.comment.delLink&&(s='<button type="button" data-url="'+i.comment.delLink+'" class="btn-link delComment">',s+='<span class="glyphicon glyphicon-trash"></span></button>');let n=$('<li class="comment"><div class="header"><div class="date"></div>'+s+'<div class="name"></div></div><div class="comment"></div></li>');n.find(".date").text(i.comment.dateFormatted),n.find(".name").text(i.comment.username),n.find(".comment").text(i.comment.text),n.data("id",i.comment.id),e.append(n),t.find("textarea").val(""),e[0].scrollTop=e[0].scrollHeight}else alert("Could not save: "+JSON.stringify(i));this.savingComment=!1})).fail((()=>{alert("Could not save"),this.savingComment=!1})))}delComment(t){$.post(t.find(".delComment").data("url"),{_csrf:this.csrf,id:t.data("id")},(e=>{e.success?t.remove():alert("Error: "+e.error)}))}initCommentForm(){this.$widget.on("click",".proposalCommentForm button",(()=>{this.doSaveComment()})),this.commentsScrollBottom(),this.$widget.on("keypress",".proposalCommentForm textarea",(t=>{t.originalEvent.metaKey&&13===t.originalEvent.keyCode&&this.doSaveComment()})),this.$widget.on("click",".delComment",(t=>{this.delComment($(t.currentTarget).parents(".comment").first())}))}}}));
//# sourceMappingURL=ChangeProposedProcedure.js.map
