define(["require","exports"],(function(t,e){"use strict";Object.defineProperty(e,"__esModule",{value:!0}),e.VotingAdmin=void 0;e.VotingAdmin=class{constructor(t){this.element=t[0];const e=this.element.getAttribute("data-voting");this.createVueWidget(e),this.initVotingCreater(),this.initVotingSorter(e),$('[data-toggle="tooltip"]').tooltip()}createVueWidget(t){const e=this.element.querySelector(".votingAdmin"),i=this.element.getAttribute("data-url-vote-settings"),o=this.element.getAttribute("data-vote-create"),n=this.element.getAttribute("data-url-vote-download"),s=JSON.parse(this.element.getAttribute("data-addable-motions")),r=this.element.getAttribute("data-url-poll"),a=JSON.parse(this.element.getAttribute("data-user-groups")),d=this.element.getAttribute("data-url-sort");this.widget=Vue.createApp({template:'<div class="adminVotings">\n                <voting-sort-widget\n                    v-if="isSorting"\n                    :votings="votings"\n                    ref="voting-sort-widget"\n                    @sorted="onSorted"></voting-sort-widget>\n                <voting-admin-widget\n                    v-if="!isSorting"\n                    v-for="voting in votings"\n                    :voting="voting"\n                    :addableMotions="addableMotions"\n                    :alreadyAddedItems="alreadyAddedItems"\n                    :userGroups="userGroups"\n                    :voteDownloadUrl="voteDownloadUrl"\n                    @set-status="setStatus"\n                    @save-settings="saveSettings"\n                    @remove-item="removeItem"\n                    @delete-voting="deleteVoting"\n                    @add-imotion="addIMotion"\n                    @add-question="addQuestion"\n                    @set-voters-to-user-group="setVotersToUserGroup"\n                    ref="voting-admin-widget"\n                ></voting-admin-widget>\n            </div>',data:()=>({isSorting:!1,votingsJson:null,votings:null,userGroups:a,voteDownloadUrl:n,addableMotions:s,csrf:document.querySelector("head meta[name=csrf-token]").getAttribute("content"),pollingId:null,onReloadedCbs:[]}),computed:{alreadyAddedItems:function(){const t=[],e=[];return this.votings.forEach((i=>{i.items.forEach((i=>{"motion"===i.type&&t.push(i.id),"amendment"===i.type&&e.push(i.id)}))})),{motions:t,amendments:e}}},methods:{_performOperation:function(t,e){let o={_csrf:this.csrf};e&&(o=Object.assign(o,e));const n=this,s=i.replace(/VOTINGBLOCKID/,t);$.post(s,o,(function(t){void 0===t.success||t.success?n.votings=t:alert(t.message)})).catch((function(t){alert(t.responseText)}))},setVotingFromJson(t){t!==this.votingsJson&&(this.votings=JSON.parse(t),this.votingsJson=t)},setVotingFromObject(t){this.votings=t,this.votingsJson=null},toggleSorting(){this.isSorting=!this.isSorting},setStatus(t,e){this._performOperation(t,{op:"update-status",status:e})},saveSettings(t,e,i,o,n,s,r,a,d,l){this._performOperation(t,{op:"save-settings",title:e,answerTemplate:i,majorityType:o,quorumType:n,votePolicy:s,resultsPublic:r,votesPublic:a,votingTime:d,assignedMotion:l})},onSorted(t){let e={_csrf:this.csrf,votingIds:t};const i=this;$.post(d,e,(function(t){void 0===t.success||t.success?(i.votings=t,i.isSorting=!1):alert(t.message)})).catch((function(t){alert(t.responseText)}))},deleteVoting(t){this._performOperation(t,{op:"delete-voting"})},createVoting:function(t,e,i,n,s,r,a,d,l,c){let u={_csrf:this.csrf,type:t,answers:e,title:i,specificQuestion:n,assignedMotion:s,majorityType:r,votePolicy:a,userGroups:d,resultsPublic:l,votesPublic:c};const g=this;$.post(o,u,(function(t){void 0===t.success||t.success?(g.votings=t.votings,g.onReloadedCbs.forEach((t=>{t(g.votings)})),window.setTimeout((()=>{$("#voting"+t.created_voting).scrollintoview({top_offset:-100})}),200)):alert(t.message)})).catch((function(t){alert(t.responseText)}))},removeItem(t,e,i){this._performOperation(t,{op:"remove-item",itemType:e,itemId:i})},addIMotion(t,e){this._performOperation(t,{op:"add-imotion",itemDefinition:e})},addQuestion(t,e){this._performOperation(t,{op:"add-question",question:e})},setVotersToUserGroup(t,e,i){this._performOperation(t,{op:"set-voters-to-user-group",userIds:e,newUserGroup:i})},addReloadedCb:function(t){this.onReloadedCbs.push(t)},reloadData:function(){const t=this;$.get(r,(function(e){t.setVotingFromJson(e),t.onReloadedCbs.forEach((e=>{e(t.votings)}))}),"text").catch((function(t){console.error("Could not load voting data from backend",t)}))},startPolling:function(){const t=this;this.pollingId=window.setInterval((function(){t.reloadData()}),3e3)}},beforeUnmount(){window.clearInterval(this.pollingId)},created(){this.setVotingFromJson(t),this.startPolling()}}),this.widget.config.compilerOptions.whitespace="condense",window.__initVueComponents(this.widget,"voting"),this.widgetComponent=this.widget.mount(e),window.votingAdminWidget=this.widgetComponent}initPolicyWidget(){const t=$(this.element),e=t.find(".userGroupSelect"),i=e.data("load-url");let o={};i&&(o=Object.assign(o,{loadThrottle:null,valueField:"id",labelField:"label",searchField:"label",load:function(t,e){return t?$.get(i,{query:t}).then((t=>{e(t)})):e()}})),e.find("select").selectize(o);const n=t.find(".policySelect");n.on("change",(()=>{6===parseInt(n.val(),10)?e.removeClass("hidden"):e.addClass("hidden")})).trigger("change")}initVotingSorter(t){const e=this.element.querySelector(".sortVotings");e.addEventListener("click",(()=>{this.widgetComponent.toggleSorting()})),JSON.parse(t).length>1&&e.classList.remove("hidden"),this.widgetComponent.addReloadedCb((t=>{t.length>1?e.classList.remove("hidden"):e.classList.add("hidden")}))}initVotingCreater(){const t=this.element.querySelector(".createVotingOpener"),e=this.element.querySelector(".createVotingHolder"),i=this.element.querySelector(".specificQuestion"),o=this.element.querySelector(".majorityTypeSettings");t.addEventListener("click",(()=>{e.classList.remove("hidden"),t.classList.add("hidden")}));const n=(t,i)=>{let o=i;return e.querySelectorAll(t).forEach((t=>{const e=t;e.checked&&(o=e.value)})),o},s=()=>{"question"===n(".votingType input","question")?i.classList.remove("hidden"):i.classList.add("hidden")};e.querySelectorAll(".votingType input").forEach((t=>{t.addEventListener("change",s)})),s();const r=()=>{"2"===n(".answerTemplate input","0")?o.classList.add("hidden"):o.classList.remove("hidden")};e.querySelectorAll(".answerTemplate input").forEach((t=>{t.addEventListener("change",r)})),r(),this.initPolicyWidget(),e.querySelector("form").addEventListener("submit",(i=>{i.stopPropagation(),i.preventDefault();const o=n(".votingType input","question"),s=parseInt(n(".answerTemplate input","0"),10),r=e.querySelector(".settingsTitle"),a=e.querySelector(".settingsQuestion"),d=e.querySelector(".settingsAssignedMotion"),l=parseInt(n(".majorityTypeSettings input","1"),10),c=parseInt(n(".resultsPublicSettings input","1"),10),u=parseInt(n(".votesPublicSettings input","0"),10),g=parseInt(e.querySelector(".policySelect").value,10);let p;p=6===g?e.querySelector(".userGroupSelectList").selectize.items.map((t=>parseInt(t,10))):[],this.widgetComponent.createVoting(o,s,r.value,a.value,d.value,l,g,p,c,u),e.classList.add("hidden"),t.classList.remove("hidden")}))}}}));
//# sourceMappingURL=VotingAdmin.js.map
