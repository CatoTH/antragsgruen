define(["require","exports"],(function(t,e){"use strict";Object.defineProperty(e,"__esModule",{value:!0}),e.VotingAdmin=void 0;e.VotingAdmin=class{constructor(t){this.element=t[0],this.createVueWidget(),this.initVotingCreater(),$('[data-toggle="tooltip"]').tooltip()}createVueWidget(){const t=this.element.querySelector(".votingAdmin"),e=this.element.getAttribute("data-url-vote-settings"),i=this.element.getAttribute("data-vote-create"),s=JSON.parse(this.element.getAttribute("data-addable-motions")),n=this.element.getAttribute("data-url-poll"),o=this.element.getAttribute("data-voting"),r=JSON.parse(this.element.getAttribute("data-user-groups"));this.widget=new Vue({el:t,template:'<div class="adminVotings">\n                <voting-admin-widget v-for="voting in votings"\n                                     :voting="voting"\n                                     :addableMotions="addableMotions"\n                                     :alreadyAddedItems="alreadyAddedItems"\n                                     :userGroups="userGroups"\n                                     @set-status="setStatus"\n                                     @save-settings="saveSettings"\n                                     @remove-item="removeItem"\n                                     @delete-voting="deleteVoting"\n                                     @add-imotion="addIMotion"\n                                     @add-question="addQuestion"\n                ></voting-admin-widget>\n            </div>',data:()=>({votingsJson:null,votings:null,userGroups:r,addableMotions:s,csrf:document.querySelector("head meta[name=csrf-token]").getAttribute("content"),pollingId:null}),computed:{alreadyAddedItems:function(){const t=[],e=[];return this.votings.forEach((i=>{i.items.forEach((i=>{"motion"===i.type&&t.push(i.id),"amendment"===i.type&&e.push(i.id)}))})),{motions:t,amendments:e}}},methods:{_performOperation:function(t,i){let s={_csrf:this.csrf};i&&(s=Object.assign(s,i));const n=this,o=e.replace(/VOTINGBLOCKID/,t);$.post(o,s,(function(t){void 0===t.success||t.success?n.votings=t:alert(t.message)})).catch((function(t){alert(t.responseText)}))},setVotingFromJson(t){t!==this.votingsJson&&(this.votings=JSON.parse(t),this.votingsJson=t)},setVotingFromObject(t){this.votings=t,this.votingsJson=null},setStatus(t,e,i){this._performOperation(t,{op:"update-status",status:e,organizations:i.map((t=>({id:t.id,members_present:t.members_present})))})},saveSettings(t,e,i,s,n,o,r,a){this._performOperation(t,{op:"save-settings",title:e,answerTemplate:i,majorityType:s,votePolicy:n,resultsPublic:o,votesPublic:r,assignedMotion:a})},deleteVoting(t){this._performOperation(t,{op:"delete-voting"})},createVoting:function(t,e,s,n,o,r,a,d){let l={_csrf:this.csrf,type:t,answers:e,title:s,specificQuestion:n,assignedMotion:o,majorityType:r,resultsPublic:a,votesPublic:d};const c=this;$.post(i,l,(function(t){void 0===t.success||t.success?(c.votings=t.votings,window.setTimeout((()=>{$("#voting"+t.created_voting).scrollintoview({top_offset:-100})}),200)):alert(t.message)})).catch((function(t){alert(t.responseText)}))},removeItem(t,e,i){this._performOperation(t,{op:"remove-item",itemType:e,itemId:i})},addIMotion(t,e){this._performOperation(t,{op:"add-imotion",itemDefinition:e})},addQuestion(t,e){this._performOperation(t,{op:"add-question",question:e})},reloadData:function(){const t=this;$.get(n,(function(e){t.setVotingFromJson(e)}),"text").catch((function(t){console.error("Could not load voting data from backend",t)}))},startPolling:function(){const t=this;this.pollingId=window.setInterval((function(){t.reloadData()}),3e3)}},beforeDestroy(){window.clearInterval(this.pollingId)},created(){this.setVotingFromJson(o),this.startPolling()}})}initVotingCreater(){const t=this.element.querySelector(".createVotingOpener"),e=this.element.querySelector(".createVotingHolder"),i=this.element.querySelector(".specificQuestion"),s=this.element.querySelector(".majorityTypeSettings");t.addEventListener("click",(()=>{e.classList.remove("hidden"),t.classList.add("hidden")}));const n=(t,i)=>{let s=i;return e.querySelectorAll(t).forEach((t=>{const e=t;e.checked&&(s=e.value)})),s},o=()=>{"question"===n(".votingType input","question")?i.classList.remove("hidden"):i.classList.add("hidden")};e.querySelectorAll(".votingType input").forEach((t=>{t.addEventListener("change",o)})),o();const r=()=>{"2"===n(".answerTemplate input","0")?s.classList.add("hidden"):s.classList.remove("hidden")};e.querySelectorAll(".answerTemplate input").forEach((t=>{t.addEventListener("change",r)})),r(),e.querySelector("form").addEventListener("submit",(i=>{i.stopPropagation(),i.preventDefault();const s=n(".votingType input","question"),o=parseInt(n(".answerTemplate input","0"),10),r=e.querySelector(".settingsTitle"),a=e.querySelector(".settingsQuestion"),d=e.querySelector(".settingsAssignedMotion"),l=parseInt(n(".majorityTypeSettings input","1"),10),c=parseInt(n(".resultsPublicSettings input","1"),10),u=parseInt(n(".votesPublicSettings input","0"),10);this.widget.createVoting(s,o,r.value,a.value,d.value,l,c,u),e.classList.add("hidden"),t.classList.remove("hidden")}))}}}));
//# sourceMappingURL=VotingAdmin.js.map
