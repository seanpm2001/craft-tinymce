(()=>{"use strict";var e={144:(e,t,n)=>{Object.defineProperty(t,"__esModule",{value:!0}),t.TinyMCEField=void 0;const i=n(311),s=(e,t={})=>{let n;return()=>{void 0===n?n=Craft.createElementSelectorModal(e,Object.assign({resizable:!0,multiSelect:!1,disableOnSelect:!0},t)):n.show()}};t.TinyMCEField=class{constructor(e,t={}){this._settings=e,console.log(this._settings);const n=this._init.bind(this),i=this._setup.bind(this),s=Object.assign({plugins:"autoresize lists link image code",content_css:!1,menubar:!1,statusbar:!1,toolbar:"undo redo | blocks | bold italic strikethrough | bullist numlist | link craftElementsEntryLink craftElementsAssetLink | image craftElementsAssetMedia | hr | code",allow_conditional_comments:!1,element_format:"html",fix_list_elements:!0,relative_urls:!1,remove_script_host:!1,anchor_top:!1,anchor_bottom:!1,autoresize_bottom_margin:0},t,{selector:`#${this._settings.id}`,language:this._settings.language,directionality:this._settings.direction,setup:i,init_instance_callback(e){n(e);const i=t.init_instance_callback;"function"==typeof i&&i.apply(this,arguments)}});tinymce.init(s).then((()=>{}),(()=>{}))}_commandHandleFromElementType(e){return e.split("\\").map(((e,t)=>(0===t?e[0]:e[0].toUpperCase())+e.slice(1).toLowerCase())).join("")}_setup(e){for(const{elementType:t,optionTitle:n,sources:i}of this._settings.linkOptions){const o=this._commandHandleFromElementType(t),l=`${o}Link`,a=s(t,{sources:i,criteria:{locale:this._settings.locale},onSelect([t]){const n=e.selection.getContent(),i=`${t.url}#${o}:${t.id}`,s=t.label,l=null!=n?n:t.label,a=n.length>0?"mceReplaceContent":"mceInsertContent";console.log(a),e.execCommand(a,!1,`<a href="${i}" title="${s}">${l}</a>`)}});e.ui.registry.addButton(l,{icon:"link",tooltip:n,onAction:()=>a()})}for(const{elementType:t,optionTitle:n,sources:i}of this._settings.mediaOptions){const o=this._commandHandleFromElementType(t),l=`${o}Media`,a=s(t,{sources:i,transforms:this._settings.transforms,storageKey:"RichTextFieldType.ChooseImage",criteria:{locale:this._settings.locale,kind:"image"},onSelect:([t],n=null)=>{const i=e.selection.getContent(),s=`${t.url}#${o}:${t.id}`+(null!==n?`:${n}`:""),l=t.label,a=i.length>0?"mceReplaceContent":"mceInsertContent";e.execCommand(a,!1,`<img src="${s}" alt="${l}" width="" height="">`)}});e.ui.registry.addButton(l,{icon:"image",tooltip:n,onAction:()=>a()})}}_init(e){const t=i(e.container),n=i(e.formElement);e.on("focus",(e=>t.addClass("mce-focused"))),e.on("blur",(e=>t.removeClass("mce-focused")));const s=n.data("elementEditor");new window.MutationObserver((()=>{i(e.targetElm).val(e.getContent());(s.isFullPage?Garnish.$bod:n).trigger("change")})).observe(e.getBody(),{characterData:!0,childList:!0,subtree:!0})}}},311:e=>{e.exports=jQuery}},t={};function n(i){var s=t[i];if(void 0!==s)return s.exports;var o=t[i]={exports:{}};return e[i](o,o.exports,n),o.exports}(()=>{const e=n(144);window.initTinyMCE=t=>new e.TinyMCEField(t)})()})();
//# sourceMappingURL=input.js.map