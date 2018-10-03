define(["require","exports"],function(t,e){"use strict";Object.defineProperty(e,"__esModule",{value:!0});var i=function(){function t(t){this.$widget=t,this.savingComment=!1,this.initElements(),this.initOpener(),this.initStatusSetter(),this.initCommentForm(),this.initVotingBlock(),this.initExplanation(),t.submit(function(t){return t.preventDefault()})}return t.prototype.initElements=function(){this.$statusDetails=this.$widget.find(".statusDetails"),this.$visibilityInput=this.$widget.find("input[name=proposalVisible]"),this.$votingStatusInput=this.$widget.find("input[name=votingStatus]"),this.$votingBlockId=this.$widget.find("input[name=votingBlockId]"),this.$openerBtn=$(".proposedChangesOpener button"),this.context=this.$widget.data("context"),this.saveUrl=this.$widget.attr("action"),this.csrf=this.$widget.find("input[name=_csrf]").val()},t.prototype.initOpener=function(){var t=this;this.$openerBtn.click(function(){t.$widget.removeClass("hidden"),t.$openerBtn.addClass("hidden"),localStorage.setItem("proposed_procedure_enabled","1")}),this.$widget.on("click",".closeBtn",function(){t.$widget.addClass("hidden"),t.$openerBtn.removeClass("hidden"),localStorage.setItem("proposed_procedure_enabled","0")}),"1"===localStorage.getItem("proposed_procedure_enabled")?(this.$widget.removeClass("hidden"),this.$openerBtn.addClass("hidden")):this.$widget.addClass("hidden")},t.prototype.reinitAfterReload=function(){this.initElements(),this.statusChanged(),this.commentsScrollBottom(),this.initExplanation(),this.$widget.find(".newBlock").addClass("hidden"),this.$widget.find(".selectlist").selectlist()},t.prototype.setGlobalProposedStr=function(t){$(".motionData .proposedStatusRow .str").html(t)},t.prototype.performCallWithReload=function(t){var i=this;t._csrf=this.csrf,$.post(this.saveUrl,t,function(t){if(t.success){var e=$(t.html);i.$widget.children().remove(),i.$widget.append(e.children()),i.reinitAfterReload(),i.$widget.addClass("showSaved").removeClass("isChanged"),t.proposalStr&&i.setGlobalProposedStr(t.proposalStr),window.setTimeout(function(){return i.$widget.removeClass("showSaved")},2e3)}else t.error?alert(t.error):alert("An error occurred")}).fail(function(){alert("Could not save")})},t.prototype.notifyProposer=function(){this.performCallWithReload({notifyProposer:"1"})},t.prototype.saveStatus=function(){var t=this.$widget.find(".statusForm input[type=radio]:checked").val(),e={setStatus:t,visible:this.$visibilityInput.prop("checked")?1:0,votingBlockId:this.$votingBlockId.val()};10==t&&(e.proposalComment=this.$widget.find("input[name=referredTo]").val()),22==t&&(e.proposalComment=this.$widget.find("input[name=obsoletedByAmendment]").val()),23==t&&(e.proposalComment=this.$widget.find("input[name=statusCustomStr]").val()),11==t&&(e.votingStatus=this.$votingStatusInput.filter(":checked").val()),"NEW"==e.votingBlockId&&(e.votingBlockTitle=this.$widget.find("input[name=newBlockTitle]").val()),this.$widget.find("input[name=setPublicExplanation]").prop("checked")&&(e.proposalExplanation=this.$widget.find("textarea[name=proposalExplanation]").val()),this.performCallWithReload(e)},t.prototype.statusChanged=function(){var t=this.$widget.find(".statusForm input[type=radio]:checked").val();this.$statusDetails.addClass("hidden"),this.$statusDetails.filter(".status_"+t).removeClass("hidden"),0==t?this.$widget.addClass("noStatus"):this.$widget.removeClass("noStatus")},t.prototype.initStatusSetter=function(){var i=this;this.$widget.on("change",".statusForm input[type=radio]",function(t,e){$(t.currentTarget).prop("checked")&&(i.statusChanged(),e&&!0===e.init||i.$widget.addClass("isChanged"))}),this.$widget.find(".statusForm input[type=radio]").trigger("change",{init:!0}),this.$widget.on("change keyup","input, textarea",function(t){0<$(t.currentTarget).parents(".proposalCommentForm").length||i.$widget.addClass("isChanged")}),this.$widget.on("changed.fu.selectlist","#obsoletedByAmendment",function(){i.$widget.addClass("isChanged")}),this.$widget.on("click",".saving button",this.saveStatus.bind(this)),this.$widget.on("click",".notifyProposer",this.notifyProposer.bind(this))},t.prototype.initVotingBlock=function(){var t=this;this.$widget.on("changed.fu.selectlist","#votingBlockId",function(){t.$widget.addClass("isChanged"),"NEW"==t.$votingBlockId.val()?t.$widget.find(".newBlock").removeClass("hidden"):t.$widget.find(".newBlock").addClass("hidden")}),this.$widget.find(".newBlock").addClass("hidden")},t.prototype.initExplanation=function(){var e=this;this.$widget.find("input[name=setPublicExplanation]").change(function(t){$(t.target).prop("checked")?e.$widget.find("section.publicExplanation").removeClass("hidden"):e.$widget.find("section.publicExplanation").addClass("hidden")}),this.$widget.find("input[name=setPublicExplanation]").prop("checked")?this.$widget.find("section.publicExplanation").removeClass("hidden"):this.$widget.find("section.publicExplanation").addClass("hidden")},t.prototype.commentsScrollBottom=function(){var t=this.$widget.find(".proposalCommentForm .commentList");t[0].scrollTop=t[0].scrollHeight},t.prototype.doSaveComment=function(){var n=this,o=this.$widget.find(".proposalCommentForm"),s=o.find(".commentList"),t=o.find("textarea").val();""==t||this.savingComment||(this.savingComment=!0,$.post(this.saveUrl,{writeComment:t,_csrf:this.csrf},function(t){if(t.success){var e="";t.comment.delLink&&(e='<button type="button" data-url="'+t.comment.delLink+'" class="btn-link delComment">',e+='<span class="glyphicon glyphicon-trash"></span></button>');var i=$('<li class="comment"><div class="header"><div class="date"></div>'+e+'<div class="name"></div></div><div class="comment"></div></li>');i.find(".date").text(t.comment.dateFormatted),i.find(".name").text(t.comment.username),i.find(".comment").text(t.comment.text),i.data("id",t.comment.id),s.append(i),o.find("textarea").val(""),s[0].scrollTop=s[0].scrollHeight}else alert("Could not save: "+JSON.stringify(t));n.savingComment=!1}).fail(function(){alert("Could not save"),n.savingComment=!1}))},t.prototype.delComment=function(e){$.post(e.find(".delComment").data("url"),{_csrf:this.csrf,id:e.data("id")},function(t){t.success?e.remove():alert("Error: "+t.error)}).catch(function(){alert("An error occurred")})},t.prototype.initCommentForm=function(){var e=this;this.$widget.on("click",".proposalCommentForm button",function(){e.doSaveComment()}),this.commentsScrollBottom(),this.$widget.on("keypress",".proposalCommentForm textarea",function(t){t.originalEvent.metaKey&&13===t.originalEvent.keyCode&&e.doSaveComment()}),this.$widget.on("click",".delComment",function(t){e.delComment($(t.currentTarget).parents(".comment").first())})},t}();e.ChangeProposedProcedure=i});
//# sourceMappingURL=ChangeProposedProcedure.js.map
