(function (root, factory) {
    //amd
    if (typeof define === "function" && define.amd) {
        define(['sprintf-js'], function (sprintf) {
            return factory(sprintf.vsprintf);
        });

        //commonjs
    } else if (typeof module === "object" && module.exports) {
        module.exports = factory(require('sprintf-js').vsprintf);

        //global
    } else {
        root.Translator = factory(window.vsprintf);
    }

    var i18n = new Translator(TRANSLATIONS);
    window['__'] = function (sMsg, sGroupID) {
        return i18n.p__(sGroupID, sMsg);
    };

    window['__pn'] = function (sMsgID, sGroupID, iValue) {
        if (iValue === undefined || I18N_FN.isNumber(iValue) === false) {
            iValue = 0;
        }
        return i18n.np__(sGroupID, sMsgID, sMsgID + '.PLURAL', iValue);
    };
}(this, function (vsprintf) {
    "use strict";
    function Translator (translations) {
        this.dictionary = {};
        this.plurals = {};
        this.domain = null;

        if (translations) {
            this.loadTranslations(translations);
        }
    }

    Translator.prototype = {
        loadTranslations: function (translations) {
            var domain = translations.domain || '';

            if (this.domain === null) {
                this.domain = domain;
            }

            if (this.dictionary[domain]) {
                mergeTranslations(this.dictionary[domain], translations.messages);
                return this;
            }

            if (translations.fn) {
                this.plurals[domain] = { fn: translations.fn };
            } else if (translations['plural-forms']) {
                var plural = translations['plural-forms'].split(';', 2);

                this.plurals[domain] = {
                    count: parseInt(plural[0].replace('nplurals=', '')),
                    code: plural[1].replace('plural=', 'return ') + ';'
                };
            }

            this.dictionary[domain] = translations.messages;

            return this;
        },

        defaultDomain: function (domain) {
            this.domain = domain;

            return this;
        },

        gettext: function (original) {
            return this.dpgettext(this.domain, null, original);
        },

        ngettext: function (original, plural, value) {
            return this.dnpgettext(this.domain, null, original, plural, value);
        },

        dngettext: function (domain, original, plural, value) {
            return this.dnpgettext(domain, null, original, plural, value);
        },

        npgettext: function (context, original, plural, value) {
            return this.dnpgettext(this.domain, context, original, plural, value);
        },

        pgettext: function (context, original) {
            return this.dpgettext(this.domain, context, original);
        },

        dgettext: function (domain, original) {
            return this.dpgettext(domain, null, original);
        },

        dpgettext: function (domain, context, original) {
            var translation = getTranslation(this.dictionary, domain, context, original);

            if (translation !== false && translation[0] !== '') {
                return translation[0];
            }

            return original;
        },

        dnpgettext: function (domain, context, original, plural, value) {
            var index = getPluralIndex(this.plurals, domain, value);
            var translation = getTranslation(this.dictionary, domain, context, original);

            if (translation[index] && translation[index] !== '') {
                return translation[index];
            }

            return (index === 0) ? original : plural;
        },

        __: function (original) {
            return format(
                this.gettext(original),
                Array.prototype.slice.call(arguments, 1)
            );
        },

        n__: function (original, plural, value) {
            return format(
                this.ngettext(original, plural, value),
                Array.prototype.slice.call(arguments, 3)
            );
        },

        p__: function (context, original) {
            return format(
                this.pgettext(context, original),
                Array.prototype.slice.call(arguments, 2)
            );
        },

        d__: function (domain, original) {
            return format(
                this.dgettext(domain, original),
                Array.prototype.slice.call(arguments, 2)
            );
        },

        dp__: function (domain, context, original) {
            return format(
                this.dgettext(domain, context, original),
                Array.prototype.slice.call(arguments, 3)
            );
        },

        np__: function (context, original, plural, value) {
            return format(
                this.npgettext(context, original, plural, value),
                Array.prototype.slice.call(arguments, 4)
            );
        },

        dnp__: function (domain, context, original, plural, value) {
            return format(
                this.dnpgettext(domain, context, original, plural, value),
                Array.prototype.slice.call(arguments, 5)
            );
        }
    };

    function getTranslation(dictionary, domain, context, original) {
        context = context || '';

        if (!dictionary[domain] || !dictionary[domain][context] || !dictionary[domain][context][original]) {
            return false;
        }

        try {
            I18N_LOG_COLLECT.set(original, context);
        }catch (e) {}

        return dictionary[domain][context][original];
    }

    function getPluralIndex(plurals, domain, value) {
        if (!plurals[domain]) {
            return value == 1 ? 0 : 1;
        }

        if (!plurals[domain].fn) {
            plurals[domain].fn = new Function('n', plurals[domain].code);
        }

        return plurals[domain].fn.call(this, value) + 0;
    }

    function mergeTranslations(translations, newTranslations) {
        for (var context in newTranslations) {
            if (!translations[context]) {
                translations[context] = newTranslations[context];
                continue;
            }

            for (var original in newTranslations[context]) {
                translations[context][original] = newTranslations[context][original];
            }
        }
    }

    function format (text, args) {
        if (!args.length) {
            return text;
        }

        if (args[0] instanceof Array) {
            return vsprintf(text, args[0]);
        }

        return vsprintf(text, args);
    }

    return Translator;
}));

/**
 * i18n 관련 함수 모음
 * @type {{ordinalSuffixes: string[], ordinalNumber: I18N_FN.ordinalNumber}}
 */
var I18N_FN = {
    ordinalSuffixes: ['th', 'st', 'nd', 'rd', 'th', 'th', 'th', 'th', 'th', 'th'],

    ordinalNumber: function (iValue) {
        if (iValue === undefined){
            return '';
        }

        var iNum = String(iValue).replace(/,/g, "");
        if (this.isNumber(iNum) === false) {
            return iValue;
        }
        if (__('__LANGUAGE.CODE__') !== 'en_US') {
            return iValue;
        }
        iNum = Math.abs(iNum);
        iNum = parseFloat(iNum);
        if (((iNum % 100) >= 11 && ((iNum % 100) <= 13)) || iNum % 1 != 0) {
            return iValue + 'th';
        }

        return iValue + this.ordinalSuffixes[iNum % 10];
    },
    isNumber: function (v) {
        return /^[+-]?\d*(\.?\d*)$/.test(v);
    }
}

var I18N_LOG_COLLECT = {
    aTranslationCodes : [],
    bIsCallApiOnLoaded: false,
    request_url        : window.location.pathname,

    call        : function () {
        var data = I18N_LOG_COLLECT.aTranslationCodes;
        if (data.length === 0) {
            return false;
        }
        I18N_LOG_COLLECT.aTranslationCodes = [];
        $.ajax({
            url     : '/exec/common/translate/logging',
            data    : {"data": data},
            type    : 'POST',
            dataType: 'json',
            success : function (aData) {}
        });
    },
    set         : function (sMsg_id, sGroup_id) {
        if (typeof EC_TRANSLATE_LOG_STATUS == 'undefined' || EC_TRANSLATE_LOG_STATUS !== 'T') {
            return;
        }

        var item = {
            'request_url': I18N_LOG_COLLECT.request_url,
            'msg_id'     : sMsg_id,
            'group_id'   : sGroup_id
        };

        if (I18N_LOG_COLLECT.bIsCallApiOnLoaded) {
            I18N_LOG_COLLECT.aTranslationCodes.push(item);
            I18N_LOG_COLLECT.call();
            return true;
        }
        I18N_LOG_COLLECT.aTranslationCodes.push(item);
    },
    loadComplete: function () {
        I18N_LOG_COLLECT.bIsCallApiOnLoaded = true;
        I18N_LOG_COLLECT.call();
    }
};

if (typeof EC_TRANSLATE_LOG_STATUS != 'undefined' && EC_TRANSLATE_LOG_STATUS === 'T') {
    if (document.addEventListener) {
        document.addEventListener("DOMContentLoaded", function () {
            I18N_LOG_COLLECT.loadComplete();
        }, false);
    } else if (document.attachEvent) {
        document.attachEvent("onreadystatechange", function () {
            if (document.readyState === "complete") {
                document.detachEvent("onreadystatechange", arguments.callee);
                I18N_LOG_COLLECT.loadComplete();
            }
        });
    }
}
/*!
 * jQuery JavaScript Library v1.4.4
 * http://jquery.com/
 *
 * Copyright 2010, John Resig
 * Dual licensed under the MIT or GPL Version 2 licenses.
 * http://jquery.org/license
 *
 * Includes Sizzle.js
 * http://sizzlejs.com/
 * Copyright 2010, The Dojo Foundation
 * Released under the MIT, BSD, and GPL Licenses.
 *
 * Date: Thu Nov 11 19:04:53 2010 -0500
 */
(function(E,B){function ka(a,b,d){if(d===B&&a.nodeType===1){d=a.getAttribute("data-"+b);if(typeof d==="string"){try{d=d==="true"?true:d==="false"?false:d==="null"?null:!c.isNaN(d)?parseFloat(d):Ja.test(d)?c.parseJSON(d):d}catch(e){}c.data(a,b,d)}else d=B}return d}function U(){return false}function ca(){return true}function la(a,b,d){d[0].type=a;return c.event.handle.apply(b,d)}function Ka(a){var b,d,e,f,h,l,k,o,x,r,A,C=[];f=[];h=c.data(this,this.nodeType?"events":"__events__");if(typeof h==="function")h=
h.events;if(!(a.liveFired===this||!h||!h.live||a.button&&a.type==="click")){if(a.namespace)A=RegExp("(^|\\.)"+a.namespace.split(".").join("\\.(?:.*\\.)?")+"(\\.|$)");a.liveFired=this;var J=h.live.slice(0);for(k=0;k<J.length;k++){h=J[k];h.origType.replace(X,"")===a.type?f.push(h.selector):J.splice(k--,1)}f=c(a.target).closest(f,a.currentTarget);o=0;for(x=f.length;o<x;o++){r=f[o];for(k=0;k<J.length;k++){h=J[k];if(r.selector===h.selector&&(!A||A.test(h.namespace))){l=r.elem;e=null;if(h.preType==="mouseenter"||
h.preType==="mouseleave"){a.type=h.preType;e=c(a.relatedTarget).closest(h.selector)[0]}if(!e||e!==l)C.push({elem:l,handleObj:h,level:r.level})}}}o=0;for(x=C.length;o<x;o++){f=C[o];if(d&&f.level>d)break;a.currentTarget=f.elem;a.data=f.handleObj.data;a.handleObj=f.handleObj;A=f.handleObj.origHandler.apply(f.elem,arguments);if(A===false||a.isPropagationStopped()){d=f.level;if(A===false)b=false;if(a.isImmediatePropagationStopped())break}}return b}}function Y(a,b){return(a&&a!=="*"?a+".":"")+b.replace(La,
"`").replace(Ma,"&")}function ma(a,b,d){if(c.isFunction(b))return c.grep(a,function(f,h){return!!b.call(f,h,f)===d});else if(b.nodeType)return c.grep(a,function(f){return f===b===d});else if(typeof b==="string"){var e=c.grep(a,function(f){return f.nodeType===1});if(Na.test(b))return c.filter(b,e,!d);else b=c.filter(b,e)}return c.grep(a,function(f){return c.inArray(f,b)>=0===d})}function na(a,b){var d=0;b.each(function(){if(this.nodeName===(a[d]&&a[d].nodeName)){var e=c.data(a[d++]),f=c.data(this,
e);if(e=e&&e.events){delete f.handle;f.events={};for(var h in e)for(var l in e[h])c.event.add(this,h,e[h][l],e[h][l].data)}}})}function Oa(a,b){b.src?c.ajax({url:b.src,async:false,dataType:"script"}):c.globalEval(b.text||b.textContent||b.innerHTML||"");b.parentNode&&b.parentNode.removeChild(b)}function oa(a,b,d){var e=b==="width"?a.offsetWidth:a.offsetHeight;if(d==="border")return e;c.each(b==="width"?Pa:Qa,function(){d||(e-=parseFloat(c.css(a,"padding"+this))||0);if(d==="margin")e+=parseFloat(c.css(a,
"margin"+this))||0;else e-=parseFloat(c.css(a,"border"+this+"Width"))||0});return e}function da(a,b,d,e){if(c.isArray(b)&&b.length)c.each(b,function(f,h){d||Ra.test(a)?e(a,h):da(a+"["+(typeof h==="object"||c.isArray(h)?f:"")+"]",h,d,e)});else if(!d&&b!=null&&typeof b==="object")c.isEmptyObject(b)?e(a,""):c.each(b,function(f,h){da(a+"["+f+"]",h,d,e)});else e(a,b)}function S(a,b){var d={};c.each(pa.concat.apply([],pa.slice(0,b)),function(){d[this]=a});return d}function qa(a){if(!ea[a]){var b=c("<"+
a+">").appendTo("body"),d=b.css("display");b.remove();if(d==="none"||d==="")d="block";ea[a]=d}return ea[a]}function fa(a){return c.isWindow(a)?a:a.nodeType===9?a.defaultView||a.parentWindow:false}var t=E.document,c=function(){function a(){if(!b.isReady){try{t.documentElement.doScroll("left")}catch(j){setTimeout(a,1);return}b.ready()}}var b=function(j,s){return new b.fn.init(j,s)},d=E.jQuery,e=E.$,f,h=/^(?:[^#<]*(<[\w\W]+>)[^>]*$|#([\w\-]+)$)/,l=/\S/,k=/^\s+/,o=/\s+$/,x=/\W/,r=/\d/,A=/^<(\w+)\s*\/?>(?:<\/\1>)?$/,
C=/^[\],:{}\s]*$/,J=/\\(?:["\\\/bfnrt]|u[0-9a-fA-F]{4})/g,w=/"[^"\\\n\r]*"|true|false|null|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?/g,I=/(?:^|:|,)(?:\s*\[)+/g,L=/(webkit)[ \/]([\w.]+)/,g=/(opera)(?:.*version)?[ \/]([\w.]+)/,i=/(msie) ([\w.]+)/,n=/(mozilla)(?:.*? rv:([\w.]+))?/,m=navigator.userAgent,p=false,q=[],u,y=Object.prototype.toString,F=Object.prototype.hasOwnProperty,M=Array.prototype.push,N=Array.prototype.slice,O=String.prototype.trim,D=Array.prototype.indexOf,R={};b.fn=b.prototype={init:function(j,
s){var v,z,H;if(!j)return this;if(j.nodeType){this.context=this[0]=j;this.length=1;return this}if(j==="body"&&!s&&t.body){this.context=t;this[0]=t.body;this.selector="body";this.length=1;return this}if(typeof j==="string")if((v=h.exec(j))&&(v[1]||!s))if(v[1]){H=s?s.ownerDocument||s:t;if(z=A.exec(j))if(b.isPlainObject(s)){j=[t.createElement(z[1])];b.fn.attr.call(j,s,true)}else j=[H.createElement(z[1])];else{z=b.buildFragment([v[1]],[H]);j=(z.cacheable?z.fragment.cloneNode(true):z.fragment).childNodes}return b.merge(this,
j)}else{if((z=t.getElementById(v[2]))&&z.parentNode){if(z.id!==v[2])return f.find(j);this.length=1;this[0]=z}this.context=t;this.selector=j;return this}else if(!s&&!x.test(j)){this.selector=j;this.context=t;j=t.getElementsByTagName(j);return b.merge(this,j)}else return!s||s.jquery?(s||f).find(j):b(s).find(j);else if(b.isFunction(j))return f.ready(j);if(j.selector!==B){this.selector=j.selector;this.context=j.context}return b.makeArray(j,this)},selector:"",jquery:"1.4.4",length:0,size:function(){return this.length},
toArray:function(){return N.call(this,0)},get:function(j){return j==null?this.toArray():j<0?this.slice(j)[0]:this[j]},pushStack:function(j,s,v){var z=b();b.isArray(j)?M.apply(z,j):b.merge(z,j);z.prevObject=this;z.context=this.context;if(s==="find")z.selector=this.selector+(this.selector?" ":"")+v;else if(s)z.selector=this.selector+"."+s+"("+v+")";return z},each:function(j,s){return b.each(this,j,s)},ready:function(j){b.bindReady();if(b.isReady)j.call(t,b);else q&&q.push(j);return this},eq:function(j){return j===
-1?this.slice(j):this.slice(j,+j+1)},first:function(){return this.eq(0)},last:function(){return this.eq(-1)},slice:function(){return this.pushStack(N.apply(this,arguments),"slice",N.call(arguments).join(","))},map:function(j){return this.pushStack(b.map(this,function(s,v){return j.call(s,v,s)}))},end:function(){return this.prevObject||b(null)},push:M,sort:[].sort,splice:[].splice};b.fn.init.prototype=b.fn;b.extend=b.fn.extend=function(){var j,s,v,z,H,G=arguments[0]||{},K=1,Q=arguments.length,ga=false;
if(typeof G==="boolean"){ga=G;G=arguments[1]||{};K=2}if(typeof G!=="object"&&!b.isFunction(G))G={};if(Q===K){G=this;--K}for(;K<Q;K++)if((j=arguments[K])!=null)for(s in j){v=G[s];z=j[s];if(s==="__proto__")continue;if(G!==z)if(ga&&z&&(b.isPlainObject(z)||(H=b.isArray(z)))){if(H){H=false;v=v&&b.isArray(v)?v:[]}else v=v&&b.isPlainObject(v)?v:{};G[s]=b.extend(ga,v,z)}else if(z!==B)G[s]=z}return G};b.extend({noConflict:function(j){E.$=e;if(j)E.jQuery=d;return b},isReady:false,readyWait:1,ready:function(j){j===true&&b.readyWait--;
if(!b.readyWait||j!==true&&!b.isReady){if(!t.body)return setTimeout(b.ready,1);b.isReady=true;if(!(j!==true&&--b.readyWait>0))if(q){var s=0,v=q;for(q=null;j=v[s++];)j.call(t,b);b.fn.trigger&&b(t).trigger("ready").unbind("ready")}}},bindReady:function(){if(!p){p=true;if(t.readyState==="complete")return setTimeout(b.ready,1);if(t.addEventListener){t.addEventListener("DOMContentLoaded",u,false);E.addEventListener("load",b.ready,false)}else if(t.attachEvent){t.attachEvent("onreadystatechange",u);E.attachEvent("onload",
b.ready);var j=false;try{j=E.frameElement==null}catch(s){}t.documentElement.doScroll&&j&&a()}}},isFunction:function(j){return b.type(j)==="function"},isArray:Array.isArray||function(j){return b.type(j)==="array"},isWindow:function(j){return j&&typeof j==="object"&&"setInterval"in j},isNaN:function(j){return j==null||!r.test(j)||isNaN(j)},type:function(j){return j==null?String(j):R[y.call(j)]||"object"},isPlainObject:function(j){if(!j||b.type(j)!=="object"||j.nodeType||b.isWindow(j))return false;if(j.constructor&&
!F.call(j,"constructor")&&!F.call(j.constructor.prototype,"isPrototypeOf"))return false;for(var s in j);return s===B||F.call(j,s)},isEmptyObject:function(j){for(var s in j)return false;return true},error:function(j){throw j;},parseJSON:function(j){if(typeof j!=="string"||!j)return null;j=b.trim(j);if(C.test(j.replace(J,"@").replace(w,"]").replace(I,"")))return E.JSON&&E.JSON.parse?E.JSON.parse(j):(new Function("return "+j))();else b.error("Invalid JSON: "+j)},noop:function(){},globalEval:function(j){if(j&&
l.test(j)){var s=t.getElementsByTagName("head")[0]||t.documentElement,v=t.createElement("script");v.type="text/javascript";if(b.support.scriptEval)v.appendChild(t.createTextNode(j));else v.text=j;s.insertBefore(v,s.firstChild);s.removeChild(v)}},nodeName:function(j,s){return j.nodeName&&j.nodeName.toUpperCase()===s.toUpperCase()},each:function(j,s,v){var z,H=0,G=j.length,K=G===B||b.isFunction(j);if(v)if(K)for(z in j){if(s.apply(j[z],v)===false)break}else for(;H<G;){if(s.apply(j[H++],v)===false)break}else if(K)for(z in j){if(s.call(j[z],
z,j[z])===false)break}else for(v=j[0];H<G&&s.call(v,H,v)!==false;v=j[++H]);return j},trim:O?function(j){return j==null?"":O.call(j)}:function(j){return j==null?"":j.toString().replace(k,"").replace(o,"")},makeArray:function(j,s){var v=s||[];if(j!=null){var z=b.type(j);j.length==null||z==="string"||z==="function"||z==="regexp"||b.isWindow(j)?M.call(v,j):b.merge(v,j)}return v},inArray:function(j,s){if(s.indexOf)return s.indexOf(j);for(var v=0,z=s.length;v<z;v++)if(s[v]===j)return v;return-1},merge:function(j,
s){var v=j.length,z=0;if(typeof s.length==="number")for(var H=s.length;z<H;z++)j[v++]=s[z];else for(;s[z]!==B;)j[v++]=s[z++];j.length=v;return j},grep:function(j,s,v){var z=[],H;v=!!v;for(var G=0,K=j.length;G<K;G++){H=!!s(j[G],G);v!==H&&z.push(j[G])}return z},map:function(j,s,v){for(var z=[],H,G=0,K=j.length;G<K;G++){H=s(j[G],G,v);if(H!=null)z[z.length]=H}return z.concat.apply([],z)},guid:1,proxy:function(j,s,v){if(arguments.length===2)if(typeof s==="string"){v=j;j=v[s];s=B}else if(s&&!b.isFunction(s)){v=
s;s=B}if(!s&&j)s=function(){return j.apply(v||this,arguments)};if(j)s.guid=j.guid=j.guid||s.guid||b.guid++;return s},access:function(j,s,v,z,H,G){var K=j.length;if(typeof s==="object"){for(var Q in s)b.access(j,Q,s[Q],z,H,v);return j}if(v!==B){z=!G&&z&&b.isFunction(v);for(Q=0;Q<K;Q++)H(j[Q],s,z?v.call(j[Q],Q,H(j[Q],s)):v,G);return j}return K?H(j[0],s):B},now:function(){return(new Date).getTime()},uaMatch:function(j){j=j.toLowerCase();j=L.exec(j)||g.exec(j)||i.exec(j)||j.indexOf("compatible")<0&&n.exec(j)||
[];return{browser:j[1]||"",version:j[2]||"0"}},browser:{}});b.each("Boolean Number String Function Array Date RegExp Object".split(" "),function(j,s){R["[object "+s+"]"]=s.toLowerCase()});m=b.uaMatch(m);if(m.browser){b.browser[m.browser]=true;b.browser.version=m.version}if(b.browser.webkit)b.browser.safari=true;if(D)b.inArray=function(j,s){return D.call(s,j)};if(!/\s/.test("\u00a0")){k=/^[\s\xA0]+/;o=/[\s\xA0]+$/}f=b(t);if(t.addEventListener)u=function(){t.removeEventListener("DOMContentLoaded",u,
false);b.ready()};else if(t.attachEvent)u=function(){if(t.readyState==="complete"){t.detachEvent("onreadystatechange",u);b.ready()}};return E.jQuery=E.$=b}();(function(){c.support={};var a=t.documentElement,b=t.createElement("script"),d=t.createElement("div"),e="script"+c.now();d.style.display="none";d.innerHTML="   <link/><table></table><a href='/a' style='color:red;float:left;opacity:.55;'>a</a><input type='checkbox'/>";var f=d.getElementsByTagName("*"),h=d.getElementsByTagName("a")[0],l=t.createElement("select"),
k=l.appendChild(t.createElement("option"));if(!(!f||!f.length||!h)){c.support={leadingWhitespace:d.firstChild.nodeType===3,tbody:!d.getElementsByTagName("tbody").length,htmlSerialize:!!d.getElementsByTagName("link").length,style:/red/.test(h.getAttribute("style")),hrefNormalized:h.getAttribute("href")==="/a",opacity:/^0.55$/.test(h.style.opacity),cssFloat:!!h.style.cssFloat,checkOn:d.getElementsByTagName("input")[0].value==="on",optSelected:k.selected,deleteExpando:true,optDisabled:false,checkClone:false,
scriptEval:false,noCloneEvent:true,boxModel:null,inlineBlockNeedsLayout:false,shrinkWrapBlocks:false,reliableHiddenOffsets:true};l.disabled=true;c.support.optDisabled=!k.disabled;b.type="text/javascript";try{b.appendChild(t.createTextNode("window."+e+"=1;"))}catch(o){}a.insertBefore(b,a.firstChild);if(E[e]){c.support.scriptEval=true;delete E[e]}try{delete b.test}catch(x){c.support.deleteExpando=false}a.removeChild(b);if(d.attachEvent&&d.fireEvent){d.attachEvent("onclick",function r(){c.support.noCloneEvent=
false;d.detachEvent("onclick",r)});d.cloneNode(true).fireEvent("onclick")}d=t.createElement("div");d.innerHTML="<input type='radio' name='radiotest' checked='checked'/>";a=t.createDocumentFragment();a.appendChild(d.firstChild);c.support.checkClone=a.cloneNode(true).cloneNode(true).lastChild.checked;c(function(){var r=t.createElement("div");r.style.width=r.style.paddingLeft="1px";t.body.appendChild(r);c.boxModel=c.support.boxModel=r.offsetWidth===2;if("zoom"in r.style){r.style.display="inline";r.style.zoom=
1;c.support.inlineBlockNeedsLayout=r.offsetWidth===2;r.style.display="";r.innerHTML="<div style='width:4px;'></div>";c.support.shrinkWrapBlocks=r.offsetWidth!==2}r.innerHTML="<table><tr><td style='padding:0;display:none'></td><td>t</td></tr></table>";var A=r.getElementsByTagName("td");c.support.reliableHiddenOffsets=A[0].offsetHeight===0;A[0].style.display="";A[1].style.display="none";c.support.reliableHiddenOffsets=c.support.reliableHiddenOffsets&&A[0].offsetHeight===0;r.innerHTML="";t.body.removeChild(r).style.display=
"none"});a=function(r){var A=t.createElement("div");r="on"+r;var C=r in A;if(!C){A.setAttribute(r,"return;");C=typeof A[r]==="function"}return C};c.support.submitBubbles=a("submit");c.support.changeBubbles=a("change");a=b=d=f=h=null}})();var ra={},Ja=/^(?:\{.*\}|\[.*\])$/;c.extend({cache:{},uuid:0,expando:"jQuery"+c.now(),noData:{embed:true,object:"clsid:D27CDB6E-AE6D-11cf-96B8-444553540000",applet:true},data:function(a,b,d){if(c.acceptData(a)){a=a==E?ra:a;var e=a.nodeType,f=e?a[c.expando]:null,h=
c.cache;if(!(e&&!f&&typeof b==="string"&&d===B)){if(e)f||(a[c.expando]=f=++c.uuid);else h=a;if(typeof b==="object")if(e)h[f]=c.extend(h[f],b);else c.extend(h,b);else if(e&&!h[f])h[f]={};a=e?h[f]:h;if(d!==B)a[b]=d;return typeof b==="string"?a[b]:a}}},removeData:function(a,b){if(c.acceptData(a)){a=a==E?ra:a;var d=a.nodeType,e=d?a[c.expando]:a,f=c.cache,h=d?f[e]:e;if(b){if(h){delete h[b];d&&c.isEmptyObject(h)&&c.removeData(a)}}else if(d&&c.support.deleteExpando)delete a[c.expando];else if(a.removeAttribute)a.removeAttribute(c.expando);
else if(d)delete f[e];else for(var l in a)delete a[l]}},acceptData:function(a){if(a.nodeName){var b=c.noData[a.nodeName.toLowerCase()];if(b)return!(b===true||a.getAttribute("classid")!==b)}return true}});c.fn.extend({data:function(a,b){var d=null;if(typeof a==="undefined"){if(this.length){var e=this[0].attributes,f;d=c.data(this[0]);for(var h=0,l=e.length;h<l;h++){f=e[h].name;if(f.indexOf("data-")===0){f=f.substr(5);ka(this[0],f,d[f])}}}return d}else if(typeof a==="object")return this.each(function(){c.data(this,
a)});var k=a.split(".");k[1]=k[1]?"."+k[1]:"";if(b===B){d=this.triggerHandler("getData"+k[1]+"!",[k[0]]);if(d===B&&this.length){d=c.data(this[0],a);d=ka(this[0],a,d)}return d===B&&k[1]?this.data(k[0]):d}else return this.each(function(){var o=c(this),x=[k[0],b];o.triggerHandler("setData"+k[1]+"!",x);c.data(this,a,b);o.triggerHandler("changeData"+k[1]+"!",x)})},removeData:function(a){return this.each(function(){c.removeData(this,a)})}});c.extend({queue:function(a,b,d){if(a){b=(b||"fx")+"queue";var e=
c.data(a,b);if(!d)return e||[];if(!e||c.isArray(d))e=c.data(a,b,c.makeArray(d));else e.push(d);return e}},dequeue:function(a,b){b=b||"fx";var d=c.queue(a,b),e=d.shift();if(e==="inprogress")e=d.shift();if(e){b==="fx"&&d.unshift("inprogress");e.call(a,function(){c.dequeue(a,b)})}}});c.fn.extend({queue:function(a,b){if(typeof a!=="string"){b=a;a="fx"}if(b===B)return c.queue(this[0],a);return this.each(function(){var d=c.queue(this,a,b);a==="fx"&&d[0]!=="inprogress"&&c.dequeue(this,a)})},dequeue:function(a){return this.each(function(){c.dequeue(this,
a)})},delay:function(a,b){a=c.fx?c.fx.speeds[a]||a:a;b=b||"fx";return this.queue(b,function(){var d=this;setTimeout(function(){c.dequeue(d,b)},a)})},clearQueue:function(a){return this.queue(a||"fx",[])}});var sa=/[\n\t]/g,ha=/\s+/,Sa=/\r/g,Ta=/^(?:href|src|style)$/,Ua=/^(?:button|input)$/i,Va=/^(?:button|input|object|select|textarea)$/i,Wa=/^a(?:rea)?$/i,ta=/^(?:radio|checkbox)$/i;c.props={"for":"htmlFor","class":"className",readonly:"readOnly",maxlength:"maxLength",cellspacing:"cellSpacing",rowspan:"rowSpan",
colspan:"colSpan",tabindex:"tabIndex",usemap:"useMap",frameborder:"frameBorder"};c.fn.extend({attr:function(a,b){return c.access(this,a,b,true,c.attr)},removeAttr:function(a){return this.each(function(){c.attr(this,a,"");this.nodeType===1&&this.removeAttribute(a)})},addClass:function(a){if(c.isFunction(a))return this.each(function(x){var r=c(this);r.addClass(a.call(this,x,r.attr("class")))});if(a&&typeof a==="string")for(var b=(a||"").split(ha),d=0,e=this.length;d<e;d++){var f=this[d];if(f.nodeType===
1)if(f.className){for(var h=" "+f.className+" ",l=f.className,k=0,o=b.length;k<o;k++)if(h.indexOf(" "+b[k]+" ")<0)l+=" "+b[k];f.className=c.trim(l)}else f.className=a}return this},removeClass:function(a){if(c.isFunction(a))return this.each(function(o){var x=c(this);x.removeClass(a.call(this,o,x.attr("class")))});if(a&&typeof a==="string"||a===B)for(var b=(a||"").split(ha),d=0,e=this.length;d<e;d++){var f=this[d];if(f.nodeType===1&&f.className)if(a){for(var h=(" "+f.className+" ").replace(sa," "),
l=0,k=b.length;l<k;l++)h=h.replace(" "+b[l]+" "," ");f.className=c.trim(h)}else f.className=""}return this},toggleClass:function(a,b){var d=typeof a,e=typeof b==="boolean";if(c.isFunction(a))return this.each(function(f){var h=c(this);h.toggleClass(a.call(this,f,h.attr("class"),b),b)});return this.each(function(){if(d==="string")for(var f,h=0,l=c(this),k=b,o=a.split(ha);f=o[h++];){k=e?k:!l.hasClass(f);l[k?"addClass":"removeClass"](f)}else if(d==="undefined"||d==="boolean"){this.className&&c.data(this,
"__className__",this.className);this.className=this.className||a===false?"":c.data(this,"__className__")||""}})},hasClass:function(a){a=" "+a+" ";for(var b=0,d=this.length;b<d;b++)if((" "+this[b].className+" ").replace(sa," ").indexOf(a)>-1)return true;return false},val:function(a){if(!arguments.length){var b=this[0];if(b){if(c.nodeName(b,"option")){var d=b.attributes.value;return!d||d.specified?b.value:b.text}if(c.nodeName(b,"select")){var e=b.selectedIndex;d=[];var f=b.options;b=b.type==="select-one";
if(e<0)return null;var h=b?e:0;for(e=b?e+1:f.length;h<e;h++){var l=f[h];if(l.selected&&(c.support.optDisabled?!l.disabled:l.getAttribute("disabled")===null)&&(!l.parentNode.disabled||!c.nodeName(l.parentNode,"optgroup"))){a=c(l).val();if(b)return a;d.push(a)}}return d}if(ta.test(b.type)&&!c.support.checkOn)return b.getAttribute("value")===null?"on":b.value;return(b.value||"").replace(Sa,"")}return B}var k=c.isFunction(a);return this.each(function(o){var x=c(this),r=a;if(this.nodeType===1){if(k)r=
a.call(this,o,x.val());if(r==null)r="";else if(typeof r==="number")r+="";else if(c.isArray(r))r=c.map(r,function(C){return C==null?"":C+""});if(c.isArray(r)&&ta.test(this.type))this.checked=c.inArray(x.val(),r)>=0;else if(c.nodeName(this,"select")){var A=c.makeArray(r);c("option",this).each(function(){this.selected=c.inArray(c(this).val(),A)>=0});if(!A.length)this.selectedIndex=-1}else this.value=r}})}});c.extend({attrFn:{val:true,css:true,html:true,text:true,data:true,width:true,height:true,offset:true},
attr:function(a,b,d,e){if(!a||a.nodeType===3||a.nodeType===8)return B;if(e&&b in c.attrFn)return c(a)[b](d);e=a.nodeType!==1||!c.isXMLDoc(a);var f=d!==B;b=e&&c.props[b]||b;var h=Ta.test(b);if((b in a||a[b]!==B)&&e&&!h){if(f){b==="type"&&Ua.test(a.nodeName)&&a.parentNode&&c.error("type property can't be changed");if(d===null)a.nodeType===1&&a.removeAttribute(b);else a[b]=d}if(c.nodeName(a,"form")&&a.getAttributeNode(b))return a.getAttributeNode(b).nodeValue;if(b==="tabIndex")return(b=a.getAttributeNode("tabIndex"))&&
b.specified?b.value:Va.test(a.nodeName)||Wa.test(a.nodeName)&&a.href?0:B;return a[b]}if(!c.support.style&&e&&b==="style"){if(f)a.style.cssText=""+d;return a.style.cssText}f&&a.setAttribute(b,""+d);if(!a.attributes[b]&&a.hasAttribute&&!a.hasAttribute(b))return B;a=!c.support.hrefNormalized&&e&&h?a.getAttribute(b,2):a.getAttribute(b);return a===null?B:a}});var X=/\.(.*)$/,ia=/^(?:textarea|input|select)$/i,La=/\./g,Ma=/ /g,Xa=/[^\w\s.|`]/g,Ya=function(a){return a.replace(Xa,"\\$&")},ua={focusin:0,focusout:0};
c.event={add:function(a,b,d,e){if(!(a.nodeType===3||a.nodeType===8)){if(c.isWindow(a)&&a!==E&&!a.frameElement)a=E;if(d===false)d=U;else if(!d)return;var f,h;if(d.handler){f=d;d=f.handler}if(!d.guid)d.guid=c.guid++;if(h=c.data(a)){var l=a.nodeType?"events":"__events__",k=h[l],o=h.handle;if(typeof k==="function"){o=k.handle;k=k.events}else if(!k){a.nodeType||(h[l]=h=function(){});h.events=k={}}if(!o)h.handle=o=function(){return typeof c!=="undefined"&&!c.event.triggered?c.event.handle.apply(o.elem,
arguments):B};o.elem=a;b=b.split(" ");for(var x=0,r;l=b[x++];){h=f?c.extend({},f):{handler:d,data:e};if(l.indexOf(".")>-1){r=l.split(".");l=r.shift();h.namespace=r.slice(0).sort().join(".")}else{r=[];h.namespace=""}h.type=l;if(!h.guid)h.guid=d.guid;var A=k[l],C=c.event.special[l]||{};if(!A){A=k[l]=[];if(!C.setup||C.setup.call(a,e,r,o)===false)if(a.addEventListener)a.addEventListener(l,o,false);else a.attachEvent&&a.attachEvent("on"+l,o)}if(C.add){C.add.call(a,h);if(!h.handler.guid)h.handler.guid=
d.guid}A.push(h);c.event.global[l]=true}a=null}}},global:{},remove:function(a,b,d,e){if(!(a.nodeType===3||a.nodeType===8)){if(d===false)d=U;var f,h,l=0,k,o,x,r,A,C,J=a.nodeType?"events":"__events__",w=c.data(a),I=w&&w[J];if(w&&I){if(typeof I==="function"){w=I;I=I.events}if(b&&b.type){d=b.handler;b=b.type}if(!b||typeof b==="string"&&b.charAt(0)==="."){b=b||"";for(f in I)c.event.remove(a,f+b)}else{for(b=b.split(" ");f=b[l++];){r=f;k=f.indexOf(".")<0;o=[];if(!k){o=f.split(".");f=o.shift();x=RegExp("(^|\\.)"+
c.map(o.slice(0).sort(),Ya).join("\\.(?:.*\\.)?")+"(\\.|$)")}if(A=I[f])if(d){r=c.event.special[f]||{};for(h=e||0;h<A.length;h++){C=A[h];if(d.guid===C.guid){if(k||x.test(C.namespace)){e==null&&A.splice(h--,1);r.remove&&r.remove.call(a,C)}if(e!=null)break}}if(A.length===0||e!=null&&A.length===1){if(!r.teardown||r.teardown.call(a,o)===false)c.removeEvent(a,f,w.handle);delete I[f]}}else for(h=0;h<A.length;h++){C=A[h];if(k||x.test(C.namespace)){c.event.remove(a,r,C.handler,h);A.splice(h--,1)}}}if(c.isEmptyObject(I)){if(b=
w.handle)b.elem=null;delete w.events;delete w.handle;if(typeof w==="function")c.removeData(a,J);else c.isEmptyObject(w)&&c.removeData(a)}}}}},trigger:function(a,b,d,e){var f=a.type||a;if(!e){a=typeof a==="object"?a[c.expando]?a:c.extend(c.Event(f),a):c.Event(f);if(f.indexOf("!")>=0){a.type=f=f.slice(0,-1);a.exclusive=true}if(!d){a.stopPropagation();c.event.global[f]&&c.each(c.cache,function(){this.events&&this.events[f]&&c.event.trigger(a,b,this.handle.elem)})}if(!d||d.nodeType===3||d.nodeType===
8)return B;a.result=B;a.target=d;b=c.makeArray(b);b.unshift(a)}a.currentTarget=d;(e=d.nodeType?c.data(d,"handle"):(c.data(d,"__events__")||{}).handle)&&e.apply(d,b);e=d.parentNode||d.ownerDocument;try{if(!(d&&d.nodeName&&c.noData[d.nodeName.toLowerCase()]))if(d["on"+f]&&d["on"+f].apply(d,b)===false){a.result=false;a.preventDefault()}}catch(h){}if(!a.isPropagationStopped()&&e)c.event.trigger(a,b,e,true);else if(!a.isDefaultPrevented()){var l;e=a.target;var k=f.replace(X,""),o=c.nodeName(e,"a")&&k===
"click",x=c.event.special[k]||{};if((!x._default||x._default.call(d,a)===false)&&!o&&!(e&&e.nodeName&&c.noData[e.nodeName.toLowerCase()])){try{if(e[k]){if(l=e["on"+k])e["on"+k]=null;c.event.triggered=true;e[k]()}}catch(r){}if(l)e["on"+k]=l;c.event.triggered=false}}},handle:function(a){var b,d,e,f;d=[];var h=c.makeArray(arguments);a=h[0]=c.event.fix(a||E.event);a.currentTarget=this;b=a.type.indexOf(".")<0&&!a.exclusive;if(!b){e=a.type.split(".");a.type=e.shift();d=e.slice(0).sort();e=RegExp("(^|\\.)"+
d.join("\\.(?:.*\\.)?")+"(\\.|$)")}a.namespace=a.namespace||d.join(".");f=c.data(this,this.nodeType?"events":"__events__");if(typeof f==="function")f=f.events;d=(f||{})[a.type];if(f&&d){d=d.slice(0);f=0;for(var l=d.length;f<l;f++){var k=d[f];if(b||e.test(k.namespace)){a.handler=k.handler;a.data=k.data;a.handleObj=k;k=k.handler.apply(this,h);if(k!==B){a.result=k;if(k===false){a.preventDefault();a.stopPropagation()}}if(a.isImmediatePropagationStopped())break}}}return a.result},props:"altKey attrChange attrName bubbles button cancelable charCode clientX clientY ctrlKey currentTarget data detail eventPhase fromElement handler keyCode layerX layerY metaKey newValue offsetX offsetY pageX pageY prevValue relatedNode relatedTarget screenX screenY shiftKey srcElement target toElement view wheelDelta which".split(" "),
fix:function(a){if(a[c.expando])return a;var b=a;a=c.Event(b);for(var d=this.props.length,e;d;){e=this.props[--d];a[e]=b[e]}if(!a.target)a.target=a.srcElement||t;if(a.target.nodeType===3)a.target=a.target.parentNode;if(!a.relatedTarget&&a.fromElement)a.relatedTarget=a.fromElement===a.target?a.toElement:a.fromElement;if(a.pageX==null&&a.clientX!=null){b=t.documentElement;d=t.body;a.pageX=a.clientX+(b&&b.scrollLeft||d&&d.scrollLeft||0)-(b&&b.clientLeft||d&&d.clientLeft||0);a.pageY=a.clientY+(b&&b.scrollTop||
d&&d.scrollTop||0)-(b&&b.clientTop||d&&d.clientTop||0)}if(a.which==null&&(a.charCode!=null||a.keyCode!=null))a.which=a.charCode!=null?a.charCode:a.keyCode;if(!a.metaKey&&a.ctrlKey)a.metaKey=a.ctrlKey;if(!a.which&&a.button!==B)a.which=a.button&1?1:a.button&2?3:a.button&4?2:0;return a},guid:1E8,proxy:c.proxy,special:{ready:{setup:c.bindReady,teardown:c.noop},live:{add:function(a){c.event.add(this,Y(a.origType,a.selector),c.extend({},a,{handler:Ka,guid:a.handler.guid}))},remove:function(a){c.event.remove(this,
Y(a.origType,a.selector),a)}},beforeunload:{setup:function(a,b,d){if(c.isWindow(this))this.onbeforeunload=d},teardown:function(a,b){if(this.onbeforeunload===b)this.onbeforeunload=null}}}};c.removeEvent=t.removeEventListener?function(a,b,d){a.removeEventListener&&a.removeEventListener(b,d,false)}:function(a,b,d){a.detachEvent&&a.detachEvent("on"+b,d)};c.Event=function(a){if(!this.preventDefault)return new c.Event(a);if(a&&a.type){this.originalEvent=a;this.type=a.type}else this.type=a;this.timeStamp=
c.now();this[c.expando]=true};c.Event.prototype={preventDefault:function(){this.isDefaultPrevented=ca;var a=this.originalEvent;if(a)if(a.preventDefault)a.preventDefault();else a.returnValue=false},stopPropagation:function(){this.isPropagationStopped=ca;var a=this.originalEvent;if(a){a.stopPropagation&&a.stopPropagation();a.cancelBubble=true}},stopImmediatePropagation:function(){this.isImmediatePropagationStopped=ca;this.stopPropagation()},isDefaultPrevented:U,isPropagationStopped:U,isImmediatePropagationStopped:U};
var va=function(a){var b=a.relatedTarget;try{for(;b&&b!==this;)b=b.parentNode;if(b!==this){a.type=a.data;c.event.handle.apply(this,arguments)}}catch(d){}},wa=function(a){a.type=a.data;c.event.handle.apply(this,arguments)};c.each({mouseenter:"mouseover",mouseleave:"mouseout"},function(a,b){c.event.special[a]={setup:function(d){c.event.add(this,b,d&&d.selector?wa:va,a)},teardown:function(d){c.event.remove(this,b,d&&d.selector?wa:va)}}});if(!c.support.submitBubbles)c.event.special.submit={setup:function(){if(this.nodeName.toLowerCase()!==
"form"){c.event.add(this,"click.specialSubmit",function(a){var b=a.target,d=b.type;if((d==="submit"||d==="image")&&c(b).closest("form").length){a.liveFired=B;return la("submit",this,arguments)}});c.event.add(this,"keypress.specialSubmit",function(a){var b=a.target,d=b.type;if((d==="text"||d==="password")&&c(b).closest("form").length&&a.keyCode===13){a.liveFired=B;return la("submit",this,arguments)}})}else return false},teardown:function(){c.event.remove(this,".specialSubmit")}};if(!c.support.changeBubbles){var V,
xa=function(a){var b=a.type,d=a.value;if(b==="radio"||b==="checkbox")d=a.checked;else if(b==="select-multiple")d=a.selectedIndex>-1?c.map(a.options,function(e){return e.selected}).join("-"):"";else if(a.nodeName.toLowerCase()==="select")d=a.selectedIndex;return d},Z=function(a,b){var d=a.target,e,f;if(!(!ia.test(d.nodeName)||d.readOnly)){e=c.data(d,"_change_data");f=xa(d);if(a.type!=="focusout"||d.type!=="radio")c.data(d,"_change_data",f);if(!(e===B||f===e))if(e!=null||f){a.type="change";a.liveFired=
B;return c.event.trigger(a,b,d)}}};c.event.special.change={filters:{focusout:Z,beforedeactivate:Z,click:function(a){var b=a.target,d=b.type;if(d==="radio"||d==="checkbox"||b.nodeName.toLowerCase()==="select")return Z.call(this,a)},keydown:function(a){var b=a.target,d=b.type;if(a.keyCode===13&&b.nodeName.toLowerCase()!=="textarea"||a.keyCode===32&&(d==="checkbox"||d==="radio")||d==="select-multiple")return Z.call(this,a)},beforeactivate:function(a){a=a.target;c.data(a,"_change_data",xa(a))}},setup:function(){if(this.type===
"file")return false;for(var a in V)c.event.add(this,a+".specialChange",V[a]);return ia.test(this.nodeName)},teardown:function(){c.event.remove(this,".specialChange");return ia.test(this.nodeName)}};V=c.event.special.change.filters;V.focus=V.beforeactivate}t.addEventListener&&c.each({focus:"focusin",blur:"focusout"},function(a,b){function d(e){e=c.event.fix(e);e.type=b;return c.event.trigger(e,null,e.target)}c.event.special[b]={setup:function(){ua[b]++===0&&t.addEventListener(a,d,true)},teardown:function(){--ua[b]===
0&&t.removeEventListener(a,d,true)}}});c.each(["bind","one"],function(a,b){c.fn[b]=function(d,e,f){if(typeof d==="object"){for(var h in d)this[b](h,e,d[h],f);return this}if(c.isFunction(e)||e===false){f=e;e=B}var l=b==="one"?c.proxy(f,function(o){c(this).unbind(o,l);return f.apply(this,arguments)}):f;if(d==="unload"&&b!=="one")this.one(d,e,f);else{h=0;for(var k=this.length;h<k;h++)c.event.add(this[h],d,l,e)}return this}});c.fn.extend({unbind:function(a,b){if(typeof a==="object"&&!a.preventDefault)for(var d in a)this.unbind(d,
a[d]);else{d=0;for(var e=this.length;d<e;d++)c.event.remove(this[d],a,b)}return this},delegate:function(a,b,d,e){return this.live(b,d,e,a)},undelegate:function(a,b,d){return arguments.length===0?this.unbind("live"):this.die(b,null,d,a)},trigger:function(a,b){return this.each(function(){c.event.trigger(a,b,this)})},triggerHandler:function(a,b){if(this[0]){var d=c.Event(a);d.preventDefault();d.stopPropagation();c.event.trigger(d,b,this[0]);return d.result}},toggle:function(a){for(var b=arguments,d=
1;d<b.length;)c.proxy(a,b[d++]);return this.click(c.proxy(a,function(e){var f=(c.data(this,"lastToggle"+a.guid)||0)%d;c.data(this,"lastToggle"+a.guid,f+1);e.preventDefault();return b[f].apply(this,arguments)||false}))},hover:function(a,b){return this.mouseenter(a).mouseleave(b||a)}});var ya={focus:"focusin",blur:"focusout",mouseenter:"mouseover",mouseleave:"mouseout"};c.each(["live","die"],function(a,b){c.fn[b]=function(d,e,f,h){var l,k=0,o,x,r=h||this.selector;h=h?this:c(this.context);if(typeof d===
"object"&&!d.preventDefault){for(l in d)h[b](l,e,d[l],r);return this}if(c.isFunction(e)){f=e;e=B}for(d=(d||"").split(" ");(l=d[k++])!=null;){o=X.exec(l);x="";if(o){x=o[0];l=l.replace(X,"")}if(l==="hover")d.push("mouseenter"+x,"mouseleave"+x);else{o=l;if(l==="focus"||l==="blur"){d.push(ya[l]+x);l+=x}else l=(ya[l]||l)+x;if(b==="live"){x=0;for(var A=h.length;x<A;x++)c.event.add(h[x],"live."+Y(l,r),{data:e,selector:r,handler:f,origType:l,origHandler:f,preType:o})}else h.unbind("live."+Y(l,r),f)}}return this}});
c.each("blur focus focusin focusout load resize scroll unload click dblclick mousedown mouseup mousemove mouseover mouseout mouseenter mouseleave change select submit keydown keypress keyup error".split(" "),function(a,b){c.fn[b]=function(d,e){if(e==null){e=d;d=null}return arguments.length>0?this.bind(b,d,e):this.trigger(b)};if(c.attrFn)c.attrFn[b]=true});E.attachEvent&&!E.addEventListener&&c(E).bind("unload",function(){for(var a in c.cache)if(c.cache[a].handle)try{c.event.remove(c.cache[a].handle.elem)}catch(b){}});
(function(){function a(g,i,n,m,p,q){p=0;for(var u=m.length;p<u;p++){var y=m[p];if(y){var F=false;for(y=y[g];y;){if(y.sizcache===n){F=m[y.sizset];break}if(y.nodeType===1&&!q){y.sizcache=n;y.sizset=p}if(y.nodeName.toLowerCase()===i){F=y;break}y=y[g]}m[p]=F}}}function b(g,i,n,m,p,q){p=0;for(var u=m.length;p<u;p++){var y=m[p];if(y){var F=false;for(y=y[g];y;){if(y.sizcache===n){F=m[y.sizset];break}if(y.nodeType===1){if(!q){y.sizcache=n;y.sizset=p}if(typeof i!=="string"){if(y===i){F=true;break}}else if(k.filter(i,
[y]).length>0){F=y;break}}y=y[g]}m[p]=F}}}var d=/((?:\((?:\([^()]+\)|[^()]+)+\)|\[(?:\[[^\[\]]*\]|['"][^'"]*['"]|[^\[\]'"]+)+\]|\\.|[^ >+~,(\[\\]+)+|[>+~])(\s*,\s*)?((?:.|\r|\n)*)/g,e=0,f=Object.prototype.toString,h=false,l=true;[0,0].sort(function(){l=false;return 0});var k=function(g,i,n,m){n=n||[];var p=i=i||t;if(i.nodeType!==1&&i.nodeType!==9)return[];if(!g||typeof g!=="string")return n;var q,u,y,F,M,N=true,O=k.isXML(i),D=[],R=g;do{d.exec("");if(q=d.exec(R)){R=q[3];D.push(q[1]);if(q[2]){F=q[3];
break}}}while(q);if(D.length>1&&x.exec(g))if(D.length===2&&o.relative[D[0]])u=L(D[0]+D[1],i);else for(u=o.relative[D[0]]?[i]:k(D.shift(),i);D.length;){g=D.shift();if(o.relative[g])g+=D.shift();u=L(g,u)}else{if(!m&&D.length>1&&i.nodeType===9&&!O&&o.match.ID.test(D[0])&&!o.match.ID.test(D[D.length-1])){q=k.find(D.shift(),i,O);i=q.expr?k.filter(q.expr,q.set)[0]:q.set[0]}if(i){q=m?{expr:D.pop(),set:C(m)}:k.find(D.pop(),D.length===1&&(D[0]==="~"||D[0]==="+")&&i.parentNode?i.parentNode:i,O);u=q.expr?k.filter(q.expr,
q.set):q.set;if(D.length>0)y=C(u);else N=false;for(;D.length;){q=M=D.pop();if(o.relative[M])q=D.pop();else M="";if(q==null)q=i;o.relative[M](y,q,O)}}else y=[]}y||(y=u);y||k.error(M||g);if(f.call(y)==="[object Array]")if(N)if(i&&i.nodeType===1)for(g=0;y[g]!=null;g++){if(y[g]&&(y[g]===true||y[g].nodeType===1&&k.contains(i,y[g])))n.push(u[g])}else for(g=0;y[g]!=null;g++)y[g]&&y[g].nodeType===1&&n.push(u[g]);else n.push.apply(n,y);else C(y,n);if(F){k(F,p,n,m);k.uniqueSort(n)}return n};k.uniqueSort=function(g){if(w){h=
l;g.sort(w);if(h)for(var i=1;i<g.length;i++)g[i]===g[i-1]&&g.splice(i--,1)}return g};k.matches=function(g,i){return k(g,null,null,i)};k.matchesSelector=function(g,i){return k(i,null,null,[g]).length>0};k.find=function(g,i,n){var m;if(!g)return[];for(var p=0,q=o.order.length;p<q;p++){var u,y=o.order[p];if(u=o.leftMatch[y].exec(g)){var F=u[1];u.splice(1,1);if(F.substr(F.length-1)!=="\\"){u[1]=(u[1]||"").replace(/\\/g,"");m=o.find[y](u,i,n);if(m!=null){g=g.replace(o.match[y],"");break}}}}m||(m=i.getElementsByTagName("*"));
return{set:m,expr:g}};k.filter=function(g,i,n,m){for(var p,q,u=g,y=[],F=i,M=i&&i[0]&&k.isXML(i[0]);g&&i.length;){for(var N in o.filter)if((p=o.leftMatch[N].exec(g))!=null&&p[2]){var O,D,R=o.filter[N];D=p[1];q=false;p.splice(1,1);if(D.substr(D.length-1)!=="\\"){if(F===y)y=[];if(o.preFilter[N])if(p=o.preFilter[N](p,F,n,y,m,M)){if(p===true)continue}else q=O=true;if(p)for(var j=0;(D=F[j])!=null;j++)if(D){O=R(D,p,j,F);var s=m^!!O;if(n&&O!=null)if(s)q=true;else F[j]=false;else if(s){y.push(D);q=true}}if(O!==
B){n||(F=y);g=g.replace(o.match[N],"");if(!q)return[];break}}}if(g===u)if(q==null)k.error(g);else break;u=g}return F};k.error=function(g){throw"Syntax error, unrecognized expression: "+g;};var o=k.selectors={order:["ID","NAME","TAG"],match:{ID:/#((?:[\w\u00c0-\uFFFF\-]|\\.)+)/,CLASS:/\.((?:[\w\u00c0-\uFFFF\-]|\\.)+)/,NAME:/\[name=['"]*((?:[\w\u00c0-\uFFFF\-]|\\.)+)['"]*\]/,ATTR:/\[\s*((?:[\w\u00c0-\uFFFF\-]|\\.)+)\s*(?:(\S?=)\s*(['"]*)(.*?)\3|)\s*\]/,TAG:/^((?:[\w\u00c0-\uFFFF\*\-]|\\.)+)/,CHILD:/:(only|nth|last|first)-child(?:\((even|odd|[\dn+\-]*)\))?/,
POS:/:(nth|eq|gt|lt|first|last|even|odd)(?:\((\d*)\))?(?=[^\-]|$)/,PSEUDO:/:((?:[\w\u00c0-\uFFFF\-]|\\.)+)(?:\((['"]?)((?:\([^\)]+\)|[^\(\)]*)+)\2\))?/},leftMatch:{},attrMap:{"class":"className","for":"htmlFor"},attrHandle:{href:function(g){return g.getAttribute("href")}},relative:{"+":function(g,i){var n=typeof i==="string",m=n&&!/\W/.test(i);n=n&&!m;if(m)i=i.toLowerCase();m=0;for(var p=g.length,q;m<p;m++)if(q=g[m]){for(;(q=q.previousSibling)&&q.nodeType!==1;);g[m]=n||q&&q.nodeName.toLowerCase()===
i?q||false:q===i}n&&k.filter(i,g,true)},">":function(g,i){var n,m=typeof i==="string",p=0,q=g.length;if(m&&!/\W/.test(i))for(i=i.toLowerCase();p<q;p++){if(n=g[p]){n=n.parentNode;g[p]=n.nodeName.toLowerCase()===i?n:false}}else{for(;p<q;p++)if(n=g[p])g[p]=m?n.parentNode:n.parentNode===i;m&&k.filter(i,g,true)}},"":function(g,i,n){var m,p=e++,q=b;if(typeof i==="string"&&!/\W/.test(i)){m=i=i.toLowerCase();q=a}q("parentNode",i,p,g,m,n)},"~":function(g,i,n){var m,p=e++,q=b;if(typeof i==="string"&&!/\W/.test(i)){m=
i=i.toLowerCase();q=a}q("previousSibling",i,p,g,m,n)}},find:{ID:function(g,i,n){if(typeof i.getElementById!=="undefined"&&!n)return(g=i.getElementById(g[1]))&&g.parentNode?[g]:[]},NAME:function(g,i){if(typeof i.getElementsByName!=="undefined"){for(var n=[],m=i.getElementsByName(g[1]),p=0,q=m.length;p<q;p++)m[p].getAttribute("name")===g[1]&&n.push(m[p]);return n.length===0?null:n}},TAG:function(g,i){return i.getElementsByTagName(g[1])}},preFilter:{CLASS:function(g,i,n,m,p,q){g=" "+g[1].replace(/\\/g,
"")+" ";if(q)return g;q=0;for(var u;(u=i[q])!=null;q++)if(u)if(p^(u.className&&(" "+u.className+" ").replace(/[\t\n]/g," ").indexOf(g)>=0))n||m.push(u);else if(n)i[q]=false;return false},ID:function(g){return g[1].replace(/\\/g,"")},TAG:function(g){return g[1].toLowerCase()},CHILD:function(g){if(g[1]==="nth"){var i=/(-?)(\d*)n((?:\+|-)?\d*)/.exec(g[2]==="even"&&"2n"||g[2]==="odd"&&"2n+1"||!/\D/.test(g[2])&&"0n+"+g[2]||g[2]);g[2]=i[1]+(i[2]||1)-0;g[3]=i[3]-0}g[0]=e++;return g},ATTR:function(g,i,n,
m,p,q){i=g[1].replace(/\\/g,"");if(!q&&o.attrMap[i])g[1]=o.attrMap[i];if(g[2]==="~=")g[4]=" "+g[4]+" ";return g},PSEUDO:function(g,i,n,m,p){if(g[1]==="not")if((d.exec(g[3])||"").length>1||/^\w/.test(g[3]))g[3]=k(g[3],null,null,i);else{g=k.filter(g[3],i,n,true^p);n||m.push.apply(m,g);return false}else if(o.match.POS.test(g[0])||o.match.CHILD.test(g[0]))return true;return g},POS:function(g){g.unshift(true);return g}},filters:{enabled:function(g){return g.disabled===false&&g.type!=="hidden"},disabled:function(g){return g.disabled===
true},checked:function(g){return g.checked===true},selected:function(g){return g.selected===true},parent:function(g){return!!g.firstChild},empty:function(g){return!g.firstChild},has:function(g,i,n){return!!k(n[3],g).length},header:function(g){return/h\d/i.test(g.nodeName)},text:function(g){return"text"===g.type},radio:function(g){return"radio"===g.type},checkbox:function(g){return"checkbox"===g.type},file:function(g){return"file"===g.type},password:function(g){return"password"===g.type},submit:function(g){return"submit"===
g.type},image:function(g){return"image"===g.type},reset:function(g){return"reset"===g.type},button:function(g){return"button"===g.type||g.nodeName.toLowerCase()==="button"},input:function(g){return/input|select|textarea|button/i.test(g.nodeName)}},setFilters:{first:function(g,i){return i===0},last:function(g,i,n,m){return i===m.length-1},even:function(g,i){return i%2===0},odd:function(g,i){return i%2===1},lt:function(g,i,n){return i<n[3]-0},gt:function(g,i,n){return i>n[3]-0},nth:function(g,i,n){return n[3]-
0===i},eq:function(g,i,n){return n[3]-0===i}},filter:{PSEUDO:function(g,i,n,m){var p=i[1],q=o.filters[p];if(q)return q(g,n,i,m);else if(p==="contains")return(g.textContent||g.innerText||k.getText([g])||"").indexOf(i[3])>=0;else if(p==="not"){i=i[3];n=0;for(m=i.length;n<m;n++)if(i[n]===g)return false;return true}else k.error("Syntax error, unrecognized expression: "+p)},CHILD:function(g,i){var n=i[1],m=g;switch(n){case "only":case "first":for(;m=m.previousSibling;)if(m.nodeType===1)return false;if(n===
"first")return true;m=g;case "last":for(;m=m.nextSibling;)if(m.nodeType===1)return false;return true;case "nth":n=i[2];var p=i[3];if(n===1&&p===0)return true;var q=i[0],u=g.parentNode;if(u&&(u.sizcache!==q||!g.nodeIndex)){var y=0;for(m=u.firstChild;m;m=m.nextSibling)if(m.nodeType===1)m.nodeIndex=++y;u.sizcache=q}m=g.nodeIndex-p;return n===0?m===0:m%n===0&&m/n>=0}},ID:function(g,i){return g.nodeType===1&&g.getAttribute("id")===i},TAG:function(g,i){return i==="*"&&g.nodeType===1||g.nodeName.toLowerCase()===
i},CLASS:function(g,i){return(" "+(g.className||g.getAttribute("class"))+" ").indexOf(i)>-1},ATTR:function(g,i){var n=i[1];n=o.attrHandle[n]?o.attrHandle[n](g):g[n]!=null?g[n]:g.getAttribute(n);var m=n+"",p=i[2],q=i[4];return n==null?p==="!=":p==="="?m===q:p==="*="?m.indexOf(q)>=0:p==="~="?(" "+m+" ").indexOf(q)>=0:!q?m&&n!==false:p==="!="?m!==q:p==="^="?m.indexOf(q)===0:p==="$="?m.substr(m.length-q.length)===q:p==="|="?m===q||m.substr(0,q.length+1)===q+"-":false},POS:function(g,i,n,m){var p=o.setFilters[i[2]];
if(p)return p(g,n,i,m)}}},x=o.match.POS,r=function(g,i){return"\\"+(i-0+1)},A;for(A in o.match){o.match[A]=RegExp(o.match[A].source+/(?![^\[]*\])(?![^\(]*\))/.source);o.leftMatch[A]=RegExp(/(^(?:.|\r|\n)*?)/.source+o.match[A].source.replace(/\\(\d+)/g,r))}var C=function(g,i){g=Array.prototype.slice.call(g,0);if(i){i.push.apply(i,g);return i}return g};try{Array.prototype.slice.call(t.documentElement.childNodes,0)}catch(J){C=function(g,i){var n=0,m=i||[];if(f.call(g)==="[object Array]")Array.prototype.push.apply(m,
g);else if(typeof g.length==="number")for(var p=g.length;n<p;n++)m.push(g[n]);else for(;g[n];n++)m.push(g[n]);return m}}var w,I;if(t.documentElement.compareDocumentPosition)w=function(g,i){if(g===i){h=true;return 0}if(!g.compareDocumentPosition||!i.compareDocumentPosition)return g.compareDocumentPosition?-1:1;return g.compareDocumentPosition(i)&4?-1:1};else{w=function(g,i){var n,m,p=[],q=[];n=g.parentNode;m=i.parentNode;var u=n;if(g===i){h=true;return 0}else if(n===m)return I(g,i);else if(n){if(!m)return 1}else return-1;
for(;u;){p.unshift(u);u=u.parentNode}for(u=m;u;){q.unshift(u);u=u.parentNode}n=p.length;m=q.length;for(u=0;u<n&&u<m;u++)if(p[u]!==q[u])return I(p[u],q[u]);return u===n?I(g,q[u],-1):I(p[u],i,1)};I=function(g,i,n){if(g===i)return n;for(g=g.nextSibling;g;){if(g===i)return-1;g=g.nextSibling}return 1}}k.getText=function(g){for(var i="",n,m=0;g[m];m++){n=g[m];if(n.nodeType===3||n.nodeType===4)i+=n.nodeValue;else if(n.nodeType!==8)i+=k.getText(n.childNodes)}return i};(function(){var g=t.createElement("div"),
i="script"+(new Date).getTime(),n=t.documentElement;g.innerHTML="<a name='"+i+"'/>";n.insertBefore(g,n.firstChild);if(t.getElementById(i)){o.find.ID=function(m,p,q){if(typeof p.getElementById!=="undefined"&&!q)return(p=p.getElementById(m[1]))?p.id===m[1]||typeof p.getAttributeNode!=="undefined"&&p.getAttributeNode("id").nodeValue===m[1]?[p]:B:[]};o.filter.ID=function(m,p){var q=typeof m.getAttributeNode!=="undefined"&&m.getAttributeNode("id");return m.nodeType===1&&q&&q.nodeValue===p}}n.removeChild(g);
n=g=null})();(function(){var g=t.createElement("div");g.appendChild(t.createComment(""));if(g.getElementsByTagName("*").length>0)o.find.TAG=function(i,n){var m=n.getElementsByTagName(i[1]);if(i[1]==="*"){for(var p=[],q=0;m[q];q++)m[q].nodeType===1&&p.push(m[q]);m=p}return m};g.innerHTML="<a href='#'></a>";if(g.firstChild&&typeof g.firstChild.getAttribute!=="undefined"&&g.firstChild.getAttribute("href")!=="#")o.attrHandle.href=function(i){return i.getAttribute("href",2)};g=null})();t.querySelectorAll&&
function(){var g=k,i=t.createElement("div");i.innerHTML="<p class='TEST'></p>";if(!(i.querySelectorAll&&i.querySelectorAll(".TEST").length===0)){k=function(m,p,q,u){p=p||t;m=m.replace(/\=\s*([^'"\]]*)\s*\]/g,"='$1']");if(!u&&!k.isXML(p))if(p.nodeType===9)try{return C(p.querySelectorAll(m),q)}catch(y){}else if(p.nodeType===1&&p.nodeName.toLowerCase()!=="object"){var F=p.getAttribute("id"),M=F||"__sizzle__";F||p.setAttribute("id",M);try{return C(p.querySelectorAll("#"+M+" "+m),q)}catch(N){}finally{F||
p.removeAttribute("id")}}return g(m,p,q,u)};for(var n in g)k[n]=g[n];i=null}}();(function(){var g=t.documentElement,i=g.matchesSelector||g.mozMatchesSelector||g.webkitMatchesSelector||g.msMatchesSelector,n=false;try{i.call(t.documentElement,"[test!='']:sizzle")}catch(m){n=true}if(i)k.matchesSelector=function(p,q){q=q.replace(/\=\s*([^'"\]]*)\s*\]/g,"='$1']");if(!k.isXML(p))try{if(n||!o.match.PSEUDO.test(q)&&!/!=/.test(q))return i.call(p,q)}catch(u){}return k(q,null,null,[p]).length>0}})();(function(){var g=
t.createElement("div");g.innerHTML="<div class='test e'></div><div class='test'></div>";if(!(!g.getElementsByClassName||g.getElementsByClassName("e").length===0)){g.lastChild.className="e";if(g.getElementsByClassName("e").length!==1){o.order.splice(1,0,"CLASS");o.find.CLASS=function(i,n,m){if(typeof n.getElementsByClassName!=="undefined"&&!m)return n.getElementsByClassName(i[1])};g=null}}})();k.contains=t.documentElement.contains?function(g,i){return g!==i&&(g.contains?g.contains(i):true)}:t.documentElement.compareDocumentPosition?
function(g,i){return!!(g.compareDocumentPosition(i)&16)}:function(){return false};k.isXML=function(g){return(g=(g?g.ownerDocument||g:0).documentElement)?g.nodeName!=="HTML":false};var L=function(g,i){for(var n,m=[],p="",q=i.nodeType?[i]:i;n=o.match.PSEUDO.exec(g);){p+=n[0];g=g.replace(o.match.PSEUDO,"")}g=o.relative[g]?g+"*":g;n=0;for(var u=q.length;n<u;n++)k(g,q[n],m);return k.filter(p,m)};c.find=k;c.expr=k.selectors;c.expr[":"]=c.expr.filters;c.unique=k.uniqueSort;c.text=k.getText;c.isXMLDoc=k.isXML;
c.contains=k.contains})();var Za=/Until$/,$a=/^(?:parents|prevUntil|prevAll)/,ab=/,/,Na=/^.[^:#\[\.,]*$/,bb=Array.prototype.slice,cb=c.expr.match.POS;c.fn.extend({find:function(a){for(var b=this.pushStack("","find",a),d=0,e=0,f=this.length;e<f;e++){d=b.length;c.find(a,this[e],b);if(e>0)for(var h=d;h<b.length;h++)for(var l=0;l<d;l++)if(b[l]===b[h]){b.splice(h--,1);break}}return b},has:function(a){var b=c(a);return this.filter(function(){for(var d=0,e=b.length;d<e;d++)if(c.contains(this,b[d]))return true})},
not:function(a){return this.pushStack(ma(this,a,false),"not",a)},filter:function(a){return this.pushStack(ma(this,a,true),"filter",a)},is:function(a){return!!a&&c.filter(a,this).length>0},closest:function(a,b){var d=[],e,f,h=this[0];if(c.isArray(a)){var l,k={},o=1;if(h&&a.length){e=0;for(f=a.length;e<f;e++){l=a[e];k[l]||(k[l]=c.expr.match.POS.test(l)?c(l,b||this.context):l)}for(;h&&h.ownerDocument&&h!==b;){for(l in k){e=k[l];if(e.jquery?e.index(h)>-1:c(h).is(e))d.push({selector:l,elem:h,level:o})}h=
h.parentNode;o++}}return d}l=cb.test(a)?c(a,b||this.context):null;e=0;for(f=this.length;e<f;e++)for(h=this[e];h;)if(l?l.index(h)>-1:c.find.matchesSelector(h,a)){d.push(h);break}else{h=h.parentNode;if(!h||!h.ownerDocument||h===b)break}d=d.length>1?c.unique(d):d;return this.pushStack(d,"closest",a)},index:function(a){if(!a||typeof a==="string")return c.inArray(this[0],a?c(a):this.parent().children());return c.inArray(a.jquery?a[0]:a,this)},add:function(a,b){var d=typeof a==="string"?c(a,b||this.context):
c.makeArray(a),e=c.merge(this.get(),d);return this.pushStack(!d[0]||!d[0].parentNode||d[0].parentNode.nodeType===11||!e[0]||!e[0].parentNode||e[0].parentNode.nodeType===11?e:c.unique(e))},andSelf:function(){return this.add(this.prevObject)}});c.each({parent:function(a){return(a=a.parentNode)&&a.nodeType!==11?a:null},parents:function(a){return c.dir(a,"parentNode")},parentsUntil:function(a,b,d){return c.dir(a,"parentNode",d)},next:function(a){return c.nth(a,2,"nextSibling")},prev:function(a){return c.nth(a,
2,"previousSibling")},nextAll:function(a){return c.dir(a,"nextSibling")},prevAll:function(a){return c.dir(a,"previousSibling")},nextUntil:function(a,b,d){return c.dir(a,"nextSibling",d)},prevUntil:function(a,b,d){return c.dir(a,"previousSibling",d)},siblings:function(a){return c.sibling(a.parentNode.firstChild,a)},children:function(a){return c.sibling(a.firstChild)},contents:function(a){return c.nodeName(a,"iframe")?a.contentDocument||a.contentWindow.document:c.makeArray(a.childNodes)}},function(a,
b){c.fn[a]=function(d,e){var f=c.map(this,b,d);Za.test(a)||(e=d);if(e&&typeof e==="string")f=c.filter(e,f);f=this.length>1?c.unique(f):f;if((this.length>1||ab.test(e))&&$a.test(a))f=f.reverse();return this.pushStack(f,a,bb.call(arguments).join(","))}});c.extend({filter:function(a,b,d){if(d)a=":not("+a+")";return b.length===1?c.find.matchesSelector(b[0],a)?[b[0]]:[]:c.find.matches(a,b)},dir:function(a,b,d){var e=[];for(a=a[b];a&&a.nodeType!==9&&(d===B||a.nodeType!==1||!c(a).is(d));){a.nodeType===1&&
e.push(a);a=a[b]}return e},nth:function(a,b,d){b=b||1;for(var e=0;a;a=a[d])if(a.nodeType===1&&++e===b)break;return a},sibling:function(a,b){for(var d=[];a;a=a.nextSibling)a.nodeType===1&&a!==b&&d.push(a);return d}});var za=/ jQuery\d+="(?:\d+|null)"/g,$=/^\s+/,Aa=/<(?!area|br|col|embed|hr|img|input|link|meta|param)(([\w:]+)[^>]*)\/>/ig,Ba=/<([\w:]+)/,db=/<tbody/i,eb=/<|&#?\w+;/,Ca=/<(?:script|object|embed|option|style)/i,Da=/checked\s*(?:[^=]|=\s*.checked.)/i,fb=/\=([^="'>\s]+\/)>/g,P={option:[1,
"<select multiple='multiple'>","</select>"],legend:[1,"<fieldset>","</fieldset>"],thead:[1,"<table>","</table>"],tr:[2,"<table><tbody>","</tbody></table>"],td:[3,"<table><tbody><tr>","</tr></tbody></table>"],col:[2,"<table><tbody></tbody><colgroup>","</colgroup></table>"],area:[1,"<map>","</map>"],_default:[0,"",""]};P.optgroup=P.option;P.tbody=P.tfoot=P.colgroup=P.caption=P.thead;P.th=P.td;if(!c.support.htmlSerialize)P._default=[1,"div<div>","</div>"];c.fn.extend({text:function(a){if(c.isFunction(a))return this.each(function(b){var d=
c(this);d.text(a.call(this,b,d.text()))});if(typeof a!=="object"&&a!==B)return this.empty().append((this[0]&&this[0].ownerDocument||t).createTextNode(a));return c.text(this)},wrapAll:function(a){if(c.isFunction(a))return this.each(function(d){c(this).wrapAll(a.call(this,d))});if(this[0]){var b=c(a,this[0].ownerDocument).eq(0).clone(true);this[0].parentNode&&b.insertBefore(this[0]);b.map(function(){for(var d=this;d.firstChild&&d.firstChild.nodeType===1;)d=d.firstChild;return d}).append(this)}return this},
wrapInner:function(a){if(c.isFunction(a))return this.each(function(b){c(this).wrapInner(a.call(this,b))});return this.each(function(){var b=c(this),d=b.contents();d.length?d.wrapAll(a):b.append(a)})},wrap:function(a){return this.each(function(){c(this).wrapAll(a)})},unwrap:function(){return this.parent().each(function(){c.nodeName(this,"body")||c(this).replaceWith(this.childNodes)}).end()},append:function(){return this.domManip(arguments,true,function(a){this.nodeType===1&&this.appendChild(a)})},
prepend:function(){return this.domManip(arguments,true,function(a){this.nodeType===1&&this.insertBefore(a,this.firstChild)})},before:function(){if(this[0]&&this[0].parentNode)return this.domManip(arguments,false,function(b){this.parentNode.insertBefore(b,this)});else if(arguments.length){var a=c(arguments[0]);a.push.apply(a,this.toArray());return this.pushStack(a,"before",arguments)}},after:function(){if(this[0]&&this[0].parentNode)return this.domManip(arguments,false,function(b){this.parentNode.insertBefore(b,
this.nextSibling)});else if(arguments.length){var a=this.pushStack(this,"after",arguments);a.push.apply(a,c(arguments[0]).toArray());return a}},remove:function(a,b){for(var d=0,e;(e=this[d])!=null;d++)if(!a||c.filter(a,[e]).length){if(!b&&e.nodeType===1){c.cleanData(e.getElementsByTagName("*"));c.cleanData([e])}e.parentNode&&e.parentNode.removeChild(e)}return this},empty:function(){for(var a=0,b;(b=this[a])!=null;a++)for(b.nodeType===1&&c.cleanData(b.getElementsByTagName("*"));b.firstChild;)b.removeChild(b.firstChild);
return this},clone:function(a){var b=this.map(function(){if(!c.support.noCloneEvent&&!c.isXMLDoc(this)){var d=this.outerHTML,e=this.ownerDocument;if(!d){d=e.createElement("div");d.appendChild(this.cloneNode(true));d=d.innerHTML}return c.clean([d.replace(za,"").replace(fb,'="$1">').replace($,"")],e)[0]}else return this.cloneNode(true)});if(a===true){na(this,b);na(this.find("*"),b.find("*"))}return b},html:function(a){if(a===B)return this[0]&&this[0].nodeType===1?this[0].innerHTML.replace(za,""):null;
else if(typeof a==="string"&&!Ca.test(a)&&(c.support.leadingWhitespace||!$.test(a))&&!P[(Ba.exec(a)||["",""])[1].toLowerCase()]){a=a.replace(Aa,"<$1></$2>");try{for(var b=0,d=this.length;b<d;b++)if(this[b].nodeType===1){c.cleanData(this[b].getElementsByTagName("*"));this[b].innerHTML=a}}catch(e){this.empty().append(a)}}else c.isFunction(a)?this.each(function(f){var h=c(this);h.html(a.call(this,f,h.html()))}):this.empty().append(a);return this},replaceWith:function(a){if(this[0]&&this[0].parentNode){if(c.isFunction(a))return this.each(function(b){var d=
c(this),e=d.html();d.replaceWith(a.call(this,b,e))});if(typeof a!=="string")a=c(a).detach();return this.each(function(){var b=this.nextSibling,d=this.parentNode;c(this).remove();b?c(b).before(a):c(d).append(a)})}else return this.pushStack(c(c.isFunction(a)?a():a),"replaceWith",a)},detach:function(a){return this.remove(a,true)},domManip:function(a,b,d){var e,f,h,l=a[0],k=[];if(!c.support.checkClone&&arguments.length===3&&typeof l==="string"&&Da.test(l))return this.each(function(){c(this).domManip(a,
b,d,true)});if(c.isFunction(l))return this.each(function(x){var r=c(this);a[0]=l.call(this,x,b?r.html():B);r.domManip(a,b,d)});if(this[0]){e=l&&l.parentNode;e=c.support.parentNode&&e&&e.nodeType===11&&e.childNodes.length===this.length?{fragment:e}:c.buildFragment(a,this,k);h=e.fragment;if(f=h.childNodes.length===1?h=h.firstChild:h.firstChild){b=b&&c.nodeName(f,"tr");f=0;for(var o=this.length;f<o;f++)d.call(b?c.nodeName(this[f],"table")?this[f].getElementsByTagName("tbody")[0]||this[f].appendChild(this[f].ownerDocument.createElement("tbody")):
this[f]:this[f],f>0||e.cacheable||this.length>1?h.cloneNode(true):h)}k.length&&c.each(k,Oa)}return this}});c.buildFragment=function(a,b,d){var e,f,h;b=b&&b[0]?b[0].ownerDocument||b[0]:t;if(a.length===1&&typeof a[0]==="string"&&a[0].length<512&&b===t&&!Ca.test(a[0])&&(c.support.checkClone||!Da.test(a[0]))){f=true;if(h=c.fragments[a[0]])if(h!==1)e=h}if(!e){e=b.createDocumentFragment();c.clean(a,b,e,d)}if(f)c.fragments[a[0]]=h?e:1;return{fragment:e,cacheable:f}};c.fragments={};c.each({appendTo:"append",
prependTo:"prepend",insertBefore:"before",insertAfter:"after",replaceAll:"replaceWith"},function(a,b){c.fn[a]=function(d){var e=[];d=c(d);var f=this.length===1&&this[0].parentNode;if(f&&f.nodeType===11&&f.childNodes.length===1&&d.length===1){d[b](this[0]);return this}else{f=0;for(var h=d.length;f<h;f++){var l=(f>0?this.clone(true):this).get();c(d[f])[b](l);e=e.concat(l)}return this.pushStack(e,a,d.selector)}}});c.extend({clean:function(a,b,d,e){b=b||t;if(typeof b.createElement==="undefined")b=b.ownerDocument||
b[0]&&b[0].ownerDocument||t;for(var f=[],h=0,l;(l=a[h])!=null;h++){if(typeof l==="number")l+="";if(l){if(typeof l==="string"&&!eb.test(l))l=b.createTextNode(l);else if(typeof l==="string"){l=l.replace(Aa,"<$1></$2>");var k=(Ba.exec(l)||["",""])[1].toLowerCase(),o=P[k]||P._default,x=o[0],r=b.createElement("div");for(r.innerHTML=o[1]+l+o[2];x--;)r=r.lastChild;if(!c.support.tbody){x=db.test(l);k=k==="table"&&!x?r.firstChild&&r.firstChild.childNodes:o[1]==="<table>"&&!x?r.childNodes:[];for(o=k.length-
1;o>=0;--o)c.nodeName(k[o],"tbody")&&!k[o].childNodes.length&&k[o].parentNode.removeChild(k[o])}!c.support.leadingWhitespace&&$.test(l)&&r.insertBefore(b.createTextNode($.exec(l)[0]),r.firstChild);l=r.childNodes}if(l.nodeType)f.push(l);else f=c.merge(f,l)}}if(d)for(h=0;f[h];h++)if(e&&c.nodeName(f[h],"script")&&(!f[h].type||f[h].type.toLowerCase()==="text/javascript"))e.push(f[h].parentNode?f[h].parentNode.removeChild(f[h]):f[h]);else{f[h].nodeType===1&&f.splice.apply(f,[h+1,0].concat(c.makeArray(f[h].getElementsByTagName("script"))));
d.appendChild(f[h])}return f},cleanData:function(a){for(var b,d,e=c.cache,f=c.event.special,h=c.support.deleteExpando,l=0,k;(k=a[l])!=null;l++)if(!(k.nodeName&&c.noData[k.nodeName.toLowerCase()]))if(d=k[c.expando]){if((b=e[d])&&b.events)for(var o in b.events)f[o]?c.event.remove(k,o):c.removeEvent(k,o,b.handle);if(h)delete k[c.expando];else k.removeAttribute&&k.removeAttribute(c.expando);delete e[d]}}});var Ea=/alpha\([^)]*\)/i,gb=/opacity=([^)]*)/,hb=/-([a-z])/ig,ib=/([A-Z])/g,Fa=/^-?\d+(?:px)?$/i,
jb=/^-?\d/,kb={position:"absolute",visibility:"hidden",display:"block"},Pa=["Left","Right"],Qa=["Top","Bottom"],W,Ga,aa,lb=function(a,b){return b.toUpperCase()};c.fn.css=function(a,b){if(arguments.length===2&&b===B)return this;return c.access(this,a,b,true,function(d,e,f){return f!==B?c.style(d,e,f):c.css(d,e)})};c.extend({cssHooks:{opacity:{get:function(a,b){if(b){var d=W(a,"opacity","opacity");return d===""?"1":d}else return a.style.opacity}}},cssNumber:{zIndex:true,fontWeight:true,opacity:true,
zoom:true,lineHeight:true},cssProps:{"float":c.support.cssFloat?"cssFloat":"styleFloat"},style:function(a,b,d,e){if(!(!a||a.nodeType===3||a.nodeType===8||!a.style)){var f,h=c.camelCase(b),l=a.style,k=c.cssHooks[h];b=c.cssProps[h]||h;if(d!==B){if(!(typeof d==="number"&&isNaN(d)||d==null)){if(typeof d==="number"&&!c.cssNumber[h])d+="px";if(!k||!("set"in k)||(d=k.set(a,d))!==B)try{l[b]=d}catch(o){}}}else{if(k&&"get"in k&&(f=k.get(a,false,e))!==B)return f;return l[b]}}},css:function(a,b,d){var e,f=c.camelCase(b),
h=c.cssHooks[f];b=c.cssProps[f]||f;if(h&&"get"in h&&(e=h.get(a,true,d))!==B)return e;else if(W)return W(a,b,f)},swap:function(a,b,d){var e={},f;for(f in b){e[f]=a.style[f];a.style[f]=b[f]}d.call(a);for(f in b)a.style[f]=e[f]},camelCase:function(a){return a.replace(hb,lb)}});c.curCSS=c.css;c.each(["height","width"],function(a,b){c.cssHooks[b]={get:function(d,e,f){var h;if(e){if(d.offsetWidth!==0)h=oa(d,b,f);else c.swap(d,kb,function(){h=oa(d,b,f)});if(h<=0){h=W(d,b,b);if(h==="0px"&&aa)h=aa(d,b,b);
if(h!=null)return h===""||h==="auto"?"0px":h}if(h<0||h==null){h=d.style[b];return h===""||h==="auto"?"0px":h}return typeof h==="string"?h:h+"px"}},set:function(d,e){if(Fa.test(e)){e=parseFloat(e);if(e>=0)return e+"px"}else return e}}});if(!c.support.opacity)c.cssHooks.opacity={get:function(a,b){return gb.test((b&&a.currentStyle?a.currentStyle.filter:a.style.filter)||"")?parseFloat(RegExp.$1)/100+"":b?"1":""},set:function(a,b){var d=a.style;d.zoom=1;var e=c.isNaN(b)?"":"alpha(opacity="+b*100+")",f=
d.filter||"";d.filter=Ea.test(f)?f.replace(Ea,e):d.filter+" "+e}};if(t.defaultView&&t.defaultView.getComputedStyle)Ga=function(a,b,d){var e;d=d.replace(ib,"-$1").toLowerCase();if(!(b=a.ownerDocument.defaultView))return B;if(b=b.getComputedStyle(a,null)){e=b.getPropertyValue(d);if(e===""&&!c.contains(a.ownerDocument.documentElement,a))e=c.style(a,d)}return e};if(t.documentElement.currentStyle)aa=function(a,b){var d,e,f=a.currentStyle&&a.currentStyle[b],h=a.style;if(!Fa.test(f)&&jb.test(f)){d=h.left;
e=a.runtimeStyle.left;a.runtimeStyle.left=a.currentStyle.left;h.left=b==="fontSize"?"1em":f||0;f=h.pixelLeft+"px";h.left=d;a.runtimeStyle.left=e}return f===""?"auto":f};W=Ga||aa;if(c.expr&&c.expr.filters){c.expr.filters.hidden=function(a){var b=a.offsetHeight;return a.offsetWidth===0&&b===0||!c.support.reliableHiddenOffsets&&(a.style.display||c.css(a,"display"))==="none"};c.expr.filters.visible=function(a){return!c.expr.filters.hidden(a)}}var mb=c.now(),nb=/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi,
ob=/^(?:select|textarea)/i,pb=/^(?:color|date|datetime|email|hidden|month|number|password|range|search|tel|text|time|url|week)$/i,qb=/^(?:GET|HEAD)$/,Ra=/\[\]$/,T=/\=\?(&|$)/,ja=/\?/,rb=/([?&])_=[^&]*/,sb=/^(\w+:)?\/\/([^\/?#]+)/,tb=/%20/g,ub=/#.*$/,Ha=c.fn.load;c.fn.extend({load:function(a,b,d){if(typeof a!=="string"&&Ha)return Ha.apply(this,arguments);else if(!this.length)return this;var e=a.indexOf(" ");if(e>=0){var f=a.slice(e,a.length);a=a.slice(0,e)}e="GET";if(b)if(c.isFunction(b)){d=b;b=null}else if(typeof b===
"object"){b=c.param(b,c.ajaxSettings.traditional);e="POST"}var h=this;c.ajax({url:a,type:e,dataType:"html",data:b,complete:function(l,k){if(k==="success"||k==="notmodified")h.html(f?c("<div>").append(l.responseText.replace(nb,"")).find(f):l.responseText);d&&h.each(d,[l.responseText,k,l])}});return this},serialize:function(){return c.param(this.serializeArray())},serializeArray:function(){return this.map(function(){return this.elements?c.makeArray(this.elements):this}).filter(function(){return this.name&&
!this.disabled&&(this.checked||ob.test(this.nodeName)||pb.test(this.type))}).map(function(a,b){var d=c(this).val();return d==null?null:c.isArray(d)?c.map(d,function(e){return{name:b.name,value:e}}):{name:b.name,value:d}}).get()}});c.each("ajaxStart ajaxStop ajaxComplete ajaxError ajaxSuccess ajaxSend".split(" "),function(a,b){c.fn[b]=function(d){return this.bind(b,d)}});c.extend({get:function(a,b,d,e){if(c.isFunction(b)){e=e||d;d=b;b=null}return c.ajax({type:"GET",url:a,data:b,success:d,dataType:e})},
getScript:function(a,b){return c.get(a,null,b,"script")},getJSON:function(a,b,d){return c.get(a,b,d,"json")},post:function(a,b,d,e){if(c.isFunction(b)){e=e||d;d=b;b={}}return c.ajax({type:"POST",url:a,data:b,success:d,dataType:e})},ajaxSetup:function(a){c.extend(c.ajaxSettings,a)},ajaxSettings:{url:location.href,global:true,type:"GET",contentType:"application/x-www-form-urlencoded",processData:true,async:true,xhr:function(){return new E.XMLHttpRequest},accepts:{xml:"application/xml, text/xml",html:"text/html",
script:"text/javascript, application/javascript",json:"application/json, text/javascript",text:"text/plain",_default:"*/*"}},ajax:function(a){var b=c.extend(true,{},c.ajaxSettings,a),d,e,f,h=b.type.toUpperCase(),l=qb.test(h);b.url=b.url.replace(ub,"");b.context=a&&a.context!=null?a.context:b;if(b.data&&b.processData&&typeof b.data!=="string")b.data=c.param(b.data,b.traditional);if(b.dataType==="jsonp"){if(h==="GET")T.test(b.url)||(b.url+=(ja.test(b.url)?"&":"?")+(b.jsonp||"callback")+"=?");else if(!b.data||
!T.test(b.data))b.data=(b.data?b.data+"&":"")+(b.jsonp||"callback")+"=?";b.dataType="json"}if(b.dataType==="json"&&(b.data&&T.test(b.data)||T.test(b.url))){d=b.jsonpCallback||"jsonp"+mb++;if(b.data)b.data=(b.data+"").replace(T,"="+d+"$1");b.url=b.url.replace(T,"="+d+"$1");b.dataType="script";var k=E[d];E[d]=function(m){if(c.isFunction(k))k(m);else{E[d]=B;try{delete E[d]}catch(p){}}f=m;c.handleSuccess(b,w,e,f);c.handleComplete(b,w,e,f);r&&r.removeChild(A)}}if(b.dataType==="script"&&b.cache===null)b.cache=
false;if(b.cache===false&&l){var o=c.now(),x=b.url.replace(rb,"$1_="+o);b.url=x+(x===b.url?(ja.test(b.url)?"&":"?")+"_="+o:"")}if(b.data&&l)b.url+=(ja.test(b.url)?"&":"?")+b.data;b.global&&c.active++===0&&c.event.trigger("ajaxStart");o=(o=sb.exec(b.url))&&(o[1]&&o[1].toLowerCase()!==location.protocol||o[2].toLowerCase()!==location.host);if(b.dataType==="script"&&h==="GET"&&o){var r=t.getElementsByTagName("head")[0]||t.documentElement,A=t.createElement("script");if(b.scriptCharset)A.charset=b.scriptCharset;
A.src=b.url;if(!d){var C=false;A.onload=A.onreadystatechange=function(){if(!C&&(!this.readyState||this.readyState==="loaded"||this.readyState==="complete")){C=true;c.handleSuccess(b,w,e,f);c.handleComplete(b,w,e,f);A.onload=A.onreadystatechange=null;r&&A.parentNode&&r.removeChild(A)}}}r.insertBefore(A,r.firstChild);return B}var J=false,w=b.xhr();if(w){b.username?w.open(h,b.url,b.async,b.username,b.password):w.open(h,b.url,b.async);try{if(b.data!=null&&!l||a&&a.contentType)w.setRequestHeader("Content-Type",
b.contentType);if(b.ifModified){c.lastModified[b.url]&&w.setRequestHeader("If-Modified-Since",c.lastModified[b.url]);c.etag[b.url]&&w.setRequestHeader("If-None-Match",c.etag[b.url])}o||w.setRequestHeader("X-Requested-With","XMLHttpRequest");w.setRequestHeader("Accept",b.dataType&&b.accepts[b.dataType]?b.accepts[b.dataType]+", */*; q=0.01":b.accepts._default)}catch(I){}if(b.beforeSend&&b.beforeSend.call(b.context,w,b)===false){b.global&&c.active--===1&&c.event.trigger("ajaxStop");w.abort();return false}b.global&&
c.triggerGlobal(b,"ajaxSend",[w,b]);var L=w.onreadystatechange=function(m){if(!w||w.readyState===0||m==="abort"){J||c.handleComplete(b,w,e,f);J=true;if(w)w.onreadystatechange=c.noop}else if(!J&&w&&(w.readyState===4||m==="timeout")){J=true;w.onreadystatechange=c.noop;e=m==="timeout"?"timeout":!c.httpSuccess(w)?"error":b.ifModified&&c.httpNotModified(w,b.url)?"notmodified":"success";var p;if(e==="success")try{f=c.httpData(w,b.dataType,b)}catch(q){e="parsererror";p=q}if(e==="success"||e==="notmodified")d||
c.handleSuccess(b,w,e,f);else c.handleError(b,w,e,p);d||c.handleComplete(b,w,e,f);m==="timeout"&&w.abort();if(b.async)w=null}};try{var g=w.abort;w.abort=function(){w&&Function.prototype.call.call(g,w);L("abort")}}catch(i){}b.async&&b.timeout>0&&setTimeout(function(){w&&!J&&L("timeout")},b.timeout);try{w.send(l||b.data==null?null:b.data)}catch(n){c.handleError(b,w,null,n);c.handleComplete(b,w,e,f)}b.async||L();return w}},param:function(a,b){var d=[],e=function(h,l){l=c.isFunction(l)?l():l;d[d.length]=
encodeURIComponent(h)+"="+encodeURIComponent(l)};if(b===B)b=c.ajaxSettings.traditional;if(c.isArray(a)||a.jquery)c.each(a,function(){e(this.name,this.value)});else for(var f in a)da(f,a[f],b,e);return d.join("&").replace(tb,"+")}});c.extend({active:0,lastModified:{},etag:{},handleError:function(a,b,d,e){a.error&&a.error.call(a.context,b,d,e);a.global&&c.triggerGlobal(a,"ajaxError",[b,a,e])},handleSuccess:function(a,b,d,e){a.success&&a.success.call(a.context,e,d,b);a.global&&c.triggerGlobal(a,"ajaxSuccess",
[b,a])},handleComplete:function(a,b,d){a.complete&&a.complete.call(a.context,b,d);a.global&&c.triggerGlobal(a,"ajaxComplete",[b,a]);a.global&&c.active--===1&&c.event.trigger("ajaxStop")},triggerGlobal:function(a,b,d){(a.context&&a.context.url==null?c(a.context):c.event).trigger(b,d)},httpSuccess:function(a){try{return!a.status&&location.protocol==="file:"||a.status>=200&&a.status<300||a.status===304||a.status===1223}catch(b){}return false},httpNotModified:function(a,b){var d=a.getResponseHeader("Last-Modified"),
e=a.getResponseHeader("Etag");if(d)c.lastModified[b]=d;if(e)c.etag[b]=e;return a.status===304},httpData:function(a,b,d){var e=a.getResponseHeader("content-type")||"",f=b==="xml"||!b&&e.indexOf("xml")>=0;a=f?a.responseXML:a.responseText;f&&a.documentElement.nodeName==="parsererror"&&c.error("parsererror");if(d&&d.dataFilter)a=d.dataFilter(a,b);if(typeof a==="string")if(b==="json"||!b&&e.indexOf("json")>=0)a=c.parseJSON(a);else if(b==="script"||!b&&e.indexOf("javascript")>=0)c.globalEval(a);return a}});
if(E.ActiveXObject)c.ajaxSettings.xhr=function(){if(E.location.protocol!=="file:")try{return new E.XMLHttpRequest}catch(a){}try{return new E.ActiveXObject("Microsoft.XMLHTTP")}catch(b){}};c.support.ajax=!!c.ajaxSettings.xhr();var ea={},vb=/^(?:toggle|show|hide)$/,wb=/^([+\-]=)?([\d+.\-]+)(.*)$/,ba,pa=[["height","marginTop","marginBottom","paddingTop","paddingBottom"],["width","marginLeft","marginRight","paddingLeft","paddingRight"],["opacity"]];c.fn.extend({show:function(a,b,d){if(a||a===0)return this.animate(S("show",
3),a,b,d);else{d=0;for(var e=this.length;d<e;d++){a=this[d];b=a.style.display;if(!c.data(a,"olddisplay")&&b==="none")b=a.style.display="";b===""&&c.css(a,"display")==="none"&&c.data(a,"olddisplay",qa(a.nodeName))}for(d=0;d<e;d++){a=this[d];b=a.style.display;if(b===""||b==="none")a.style.display=c.data(a,"olddisplay")||""}return this}},hide:function(a,b,d){if(a||a===0)return this.animate(S("hide",3),a,b,d);else{a=0;for(b=this.length;a<b;a++){d=c.css(this[a],"display");d!=="none"&&c.data(this[a],"olddisplay",
d)}for(a=0;a<b;a++)this[a].style.display="none";return this}},_toggle:c.fn.toggle,toggle:function(a,b,d){var e=typeof a==="boolean";if(c.isFunction(a)&&c.isFunction(b))this._toggle.apply(this,arguments);else a==null||e?this.each(function(){var f=e?a:c(this).is(":hidden");c(this)[f?"show":"hide"]()}):this.animate(S("toggle",3),a,b,d);return this},fadeTo:function(a,b,d,e){return this.filter(":hidden").css("opacity",0).show().end().animate({opacity:b},a,d,e)},animate:function(a,b,d,e){var f=c.speed(b,
d,e);if(c.isEmptyObject(a))return this.each(f.complete);return this[f.queue===false?"each":"queue"](function(){var h=c.extend({},f),l,k=this.nodeType===1,o=k&&c(this).is(":hidden"),x=this;for(l in a){var r=c.camelCase(l);if(l!==r){a[r]=a[l];delete a[l];l=r}if(a[l]==="hide"&&o||a[l]==="show"&&!o)return h.complete.call(this);if(k&&(l==="height"||l==="width")){h.overflow=[this.style.overflow,this.style.overflowX,this.style.overflowY];if(c.css(this,"display")==="inline"&&c.css(this,"float")==="none")if(c.support.inlineBlockNeedsLayout)if(qa(this.nodeName)===
"inline")this.style.display="inline-block";else{this.style.display="inline";this.style.zoom=1}else this.style.display="inline-block"}if(c.isArray(a[l])){(h.specialEasing=h.specialEasing||{})[l]=a[l][1];a[l]=a[l][0]}}if(h.overflow!=null)this.style.overflow="hidden";h.curAnim=c.extend({},a);c.each(a,function(A,C){var J=new c.fx(x,h,A);if(vb.test(C))J[C==="toggle"?o?"show":"hide":C](a);else{var w=wb.exec(C),I=J.cur()||0;if(w){var L=parseFloat(w[2]),g=w[3]||"px";if(g!=="px"){c.style(x,A,(L||1)+g);I=(L||
1)/J.cur()*I;c.style(x,A,I+g)}if(w[1])L=(w[1]==="-="?-1:1)*L+I;J.custom(I,L,g)}else J.custom(I,C,"")}});return true})},stop:function(a,b){var d=c.timers;a&&this.queue([]);this.each(function(){for(var e=d.length-1;e>=0;e--)if(d[e].elem===this){b&&d[e](true);d.splice(e,1)}});b||this.dequeue();return this}});c.each({slideDown:S("show",1),slideUp:S("hide",1),slideToggle:S("toggle",1),fadeIn:{opacity:"show"},fadeOut:{opacity:"hide"},fadeToggle:{opacity:"toggle"}},function(a,b){c.fn[a]=function(d,e,f){return this.animate(b,
d,e,f)}});c.extend({speed:function(a,b,d){var e=a&&typeof a==="object"?c.extend({},a):{complete:d||!d&&b||c.isFunction(a)&&a,duration:a,easing:d&&b||b&&!c.isFunction(b)&&b};e.duration=c.fx.off?0:typeof e.duration==="number"?e.duration:e.duration in c.fx.speeds?c.fx.speeds[e.duration]:c.fx.speeds._default;e.old=e.complete;e.complete=function(){e.queue!==false&&c(this).dequeue();c.isFunction(e.old)&&e.old.call(this)};return e},easing:{linear:function(a,b,d,e){return d+e*a},swing:function(a,b,d,e){return(-Math.cos(a*
Math.PI)/2+0.5)*e+d}},timers:[],fx:function(a,b,d){this.options=b;this.elem=a;this.prop=d;if(!b.orig)b.orig={}}});c.fx.prototype={update:function(){this.options.step&&this.options.step.call(this.elem,this.now,this);(c.fx.step[this.prop]||c.fx.step._default)(this)},cur:function(){if(this.elem[this.prop]!=null&&(!this.elem.style||this.elem.style[this.prop]==null))return this.elem[this.prop];var a=parseFloat(c.css(this.elem,this.prop));return a&&a>-1E4?a:0},custom:function(a,b,d){function e(l){return f.step(l)}
var f=this,h=c.fx;this.startTime=c.now();this.start=a;this.end=b;this.unit=d||this.unit||"px";this.now=this.start;this.pos=this.state=0;e.elem=this.elem;if(e()&&c.timers.push(e)&&!ba)ba=setInterval(h.tick,h.interval)},show:function(){this.options.orig[this.prop]=c.style(this.elem,this.prop);this.options.show=true;this.custom(this.prop==="width"||this.prop==="height"?1:0,this.cur());c(this.elem).show()},hide:function(){this.options.orig[this.prop]=c.style(this.elem,this.prop);this.options.hide=true;
this.custom(this.cur(),0)},step:function(a){var b=c.now(),d=true;if(a||b>=this.options.duration+this.startTime){this.now=this.end;this.pos=this.state=1;this.update();this.options.curAnim[this.prop]=true;for(var e in this.options.curAnim)if(this.options.curAnim[e]!==true)d=false;if(d){if(this.options.overflow!=null&&!c.support.shrinkWrapBlocks){var f=this.elem,h=this.options;c.each(["","X","Y"],function(k,o){f.style["overflow"+o]=h.overflow[k]})}this.options.hide&&c(this.elem).hide();if(this.options.hide||
this.options.show)for(var l in this.options.curAnim)c.style(this.elem,l,this.options.orig[l]);this.options.complete.call(this.elem)}return false}else{a=b-this.startTime;this.state=a/this.options.duration;b=this.options.easing||(c.easing.swing?"swing":"linear");this.pos=c.easing[this.options.specialEasing&&this.options.specialEasing[this.prop]||b](this.state,a,0,1,this.options.duration);this.now=this.start+(this.end-this.start)*this.pos;this.update()}return true}};c.extend(c.fx,{tick:function(){for(var a=
c.timers,b=0;b<a.length;b++)a[b]()||a.splice(b--,1);a.length||c.fx.stop()},interval:13,stop:function(){clearInterval(ba);ba=null},speeds:{slow:600,fast:200,_default:400},step:{opacity:function(a){c.style(a.elem,"opacity",a.now)},_default:function(a){if(a.elem.style&&a.elem.style[a.prop]!=null)a.elem.style[a.prop]=(a.prop==="width"||a.prop==="height"?Math.max(0,a.now):a.now)+a.unit;else a.elem[a.prop]=a.now}}});if(c.expr&&c.expr.filters)c.expr.filters.animated=function(a){return c.grep(c.timers,function(b){return a===
b.elem}).length};var xb=/^t(?:able|d|h)$/i,Ia=/^(?:body|html)$/i;c.fn.offset="getBoundingClientRect"in t.documentElement?function(a){var b=this[0],d;if(a)return this.each(function(l){c.offset.setOffset(this,a,l)});if(!b||!b.ownerDocument)return null;if(b===b.ownerDocument.body)return c.offset.bodyOffset(b);try{d=b.getBoundingClientRect()}catch(e){}var f=b.ownerDocument,h=f.documentElement;if(!d||!c.contains(h,b))return d||{top:0,left:0};b=f.body;f=fa(f);return{top:d.top+(f.pageYOffset||c.support.boxModel&&
h.scrollTop||b.scrollTop)-(h.clientTop||b.clientTop||0),left:d.left+(f.pageXOffset||c.support.boxModel&&h.scrollLeft||b.scrollLeft)-(h.clientLeft||b.clientLeft||0)}}:function(a){var b=this[0];if(a)return this.each(function(x){c.offset.setOffset(this,a,x)});if(!b||!b.ownerDocument)return null;if(b===b.ownerDocument.body)return c.offset.bodyOffset(b);c.offset.initialize();var d,e=b.offsetParent,f=b.ownerDocument,h=f.documentElement,l=f.body;d=(f=f.defaultView)?f.getComputedStyle(b,null):b.currentStyle;
for(var k=b.offsetTop,o=b.offsetLeft;(b=b.parentNode)&&b!==l&&b!==h;){if(c.offset.supportsFixedPosition&&d.position==="fixed")break;d=f?f.getComputedStyle(b,null):b.currentStyle;k-=b.scrollTop;o-=b.scrollLeft;if(b===e){k+=b.offsetTop;o+=b.offsetLeft;if(c.offset.doesNotAddBorder&&!(c.offset.doesAddBorderForTableAndCells&&xb.test(b.nodeName))){k+=parseFloat(d.borderTopWidth)||0;o+=parseFloat(d.borderLeftWidth)||0}e=b.offsetParent}if(c.offset.subtractsBorderForOverflowNotVisible&&d.overflow!=="visible"){k+=
parseFloat(d.borderTopWidth)||0;o+=parseFloat(d.borderLeftWidth)||0}d=d}if(d.position==="relative"||d.position==="static"){k+=l.offsetTop;o+=l.offsetLeft}if(c.offset.supportsFixedPosition&&d.position==="fixed"){k+=Math.max(h.scrollTop,l.scrollTop);o+=Math.max(h.scrollLeft,l.scrollLeft)}return{top:k,left:o}};c.offset={initialize:function(){var a=t.body,b=t.createElement("div"),d,e,f,h=parseFloat(c.css(a,"marginTop"))||0;c.extend(b.style,{position:"absolute",top:0,left:0,margin:0,border:0,width:"1px",
height:"1px",visibility:"hidden"});b.innerHTML="<div style='position:absolute;top:0;left:0;margin:0;border:5px solid #000;padding:0;width:1px;height:1px;'><div></div></div><table style='position:absolute;top:0;left:0;margin:0;border:5px solid #000;padding:0;width:1px;height:1px;' cellpadding='0' cellspacing='0'><tr><td></td></tr></table>";a.insertBefore(b,a.firstChild);d=b.firstChild;e=d.firstChild;f=d.nextSibling.firstChild.firstChild;this.doesNotAddBorder=e.offsetTop!==5;this.doesAddBorderForTableAndCells=
f.offsetTop===5;e.style.position="fixed";e.style.top="20px";this.supportsFixedPosition=e.offsetTop===20||e.offsetTop===15;e.style.position=e.style.top="";d.style.overflow="hidden";d.style.position="relative";this.subtractsBorderForOverflowNotVisible=e.offsetTop===-5;this.doesNotIncludeMarginInBodyOffset=a.offsetTop!==h;a.removeChild(b);c.offset.initialize=c.noop},bodyOffset:function(a){var b=a.offsetTop,d=a.offsetLeft;c.offset.initialize();if(c.offset.doesNotIncludeMarginInBodyOffset){b+=parseFloat(c.css(a,
"marginTop"))||0;d+=parseFloat(c.css(a,"marginLeft"))||0}return{top:b,left:d}},setOffset:function(a,b,d){var e=c.css(a,"position");if(e==="static")a.style.position="relative";var f=c(a),h=f.offset(),l=c.css(a,"top"),k=c.css(a,"left"),o=e==="absolute"&&c.inArray("auto",[l,k])>-1;e={};var x={};if(o)x=f.position();l=o?x.top:parseInt(l,10)||0;k=o?x.left:parseInt(k,10)||0;if(c.isFunction(b))b=b.call(a,d,h);if(b.top!=null)e.top=b.top-h.top+l;if(b.left!=null)e.left=b.left-h.left+k;"using"in b?b.using.call(a,
e):f.css(e)}};c.fn.extend({position:function(){if(!this[0])return null;var a=this[0],b=this.offsetParent(),d=this.offset(),e=Ia.test(b[0].nodeName)?{top:0,left:0}:b.offset();d.top-=parseFloat(c.css(a,"marginTop"))||0;d.left-=parseFloat(c.css(a,"marginLeft"))||0;e.top+=parseFloat(c.css(b[0],"borderTopWidth"))||0;e.left+=parseFloat(c.css(b[0],"borderLeftWidth"))||0;return{top:d.top-e.top,left:d.left-e.left}},offsetParent:function(){return this.map(function(){for(var a=this.offsetParent||t.body;a&&!Ia.test(a.nodeName)&&
c.css(a,"position")==="static";)a=a.offsetParent;return a})}});c.each(["Left","Top"],function(a,b){var d="scroll"+b;c.fn[d]=function(e){var f=this[0],h;if(!f)return null;if(e!==B)return this.each(function(){if(h=fa(this))h.scrollTo(!a?e:c(h).scrollLeft(),a?e:c(h).scrollTop());else this[d]=e});else return(h=fa(f))?"pageXOffset"in h?h[a?"pageYOffset":"pageXOffset"]:c.support.boxModel&&h.document.documentElement[d]||h.document.body[d]:f[d]}});c.each(["Height","Width"],function(a,b){var d=b.toLowerCase();
c.fn["inner"+b]=function(){return this[0]?parseFloat(c.css(this[0],d,"padding")):null};c.fn["outer"+b]=function(e){return this[0]?parseFloat(c.css(this[0],d,e?"margin":"border")):null};c.fn[d]=function(e){var f=this[0];if(!f)return e==null?null:this;if(c.isFunction(e))return this.each(function(l){var k=c(this);k[d](e.call(this,l,k[d]()))});if(c.isWindow(f))return f.document.compatMode==="CSS1Compat"&&f.document.documentElement["client"+b]||f.document.body["client"+b];else if(f.nodeType===9)return Math.max(f.documentElement["client"+
b],f.body["scroll"+b],f.documentElement["scroll"+b],f.body["offset"+b],f.documentElement["offset"+b]);else if(e===B){f=c.css(f,d);var h=parseFloat(f);return c.isNaN(h)?f:h}else return this.css(d,typeof e==="string"?e:e+"px")}})})(window);

!function() {
    'use strict'
    // Object.create polyfill
    if (typeof Object.create != 'function') {
        Object.create = (function() {
            var temp = function() {};
            return function (prototype) {
                if (arguments.length > 1) {
                    throw Error();
                }
                if (typeof prototype != 'object') {
                    throw TypeError();
                }
                temp.prototype = prototype;
                var result = new temp();
                temp.prototype = null;
                return result;
            };
        })();
    }

    var re = {
        not_string: /[^s]/,
        not_bool: /[^t]/,
        not_type: /[^T]/,
        not_primitive: /[^v]/,
        number: /[diefg]/,
        numeric_arg: /[bcdiefguxX]/,
        json: /[j]/,
        not_json: /[^j]/,
        text: /^[^\x25]+/,
        modulo: /^\x25{2}/,
        placeholder: /^\x25(?:([1-9]\d*)\$|\(([^\)]+)\))?(\+)?(0|'[^$])?(-)?(\d+)?(?:\.(\d+))?([b-gijostTuvxX])/,
        key: /^([a-z_][a-z_\d]*)/i,
        key_access: /^\.([a-z_][a-z_\d]*)/i,
        index_access: /^\[(\d+)\]/,
        sign: /^[\+\-]/
    }

    function sprintf(key) {
        try {
            // `arguments` is not an array, but should be fine for this call
            return sprintf_format(sprintf_parse(key), arguments);
        } catch (e) {
            return key;
        }
    }

    function vsprintf(fmt, argv) {
        return sprintf.apply(null, [fmt].concat(argv || []))
    }

    function sprintf_format(parse_tree, argv) {
        var cursor = 1, tree_length = parse_tree.length, arg, output = '', i, k, ph, pad, pad_character, pad_length, is_positive, sign
        for (i = 0; i < tree_length; i++) {
            if (typeof parse_tree[i] === 'string') {
                output += parse_tree[i]
            }
            else if (typeof parse_tree[i] === 'object') {
                ph = parse_tree[i] // convenience purposes only
                if (ph.keys) { // keyword argument
                    arg = argv[cursor]
                    for (k = 0; k < ph.keys.length; k++) {
                        if (arg == undefined) {
                            throw new Error(sprintf('[sprintf] Cannot access property "%s" of undefined value "%s"', ph.keys[k], ph.keys[k-1]))
                        }
                        arg = arg[ph.keys[k]]
                    }
                }
                else if (ph.param_no) { // positional argument (explicit)
                    arg = argv[ph.param_no]
                }
                else { // positional argument (implicit)
                    arg = argv[cursor++]
                }

                if (re.not_type.test(ph.type) && re.not_primitive.test(ph.type) && arg instanceof Function) {
                    arg = arg()
                }

                if (re.numeric_arg.test(ph.type) && (typeof arg !== 'number' && isNaN(arg))) {
                    throw new TypeError(sprintf('[sprintf] expecting number but found %T', arg))
                }

                if (re.number.test(ph.type)) {
                    is_positive = arg >= 0
                }

                switch (ph.type) {
                    case 'b':
                        arg = parseInt(arg, 10).toString(2)
                        break
                    case 'c':
                        arg = String.fromCharCode(parseInt(arg, 10))
                        break
                    case 'd':
                    case 'i':
                        arg = parseInt(arg, 10)
                        break
                    case 'j':
                        arg = JSON.stringify(arg, null, ph.width ? parseInt(ph.width) : 0)
                        break
                    case 'e':
                        arg = ph.precision ? parseFloat(arg).toExponential(ph.precision) : parseFloat(arg).toExponential()
                        break
                    case 'f':
                        arg = ph.precision ? parseFloat(arg).toFixed(ph.precision) : parseFloat(arg)
                        break
                    case 'g':
                        arg = ph.precision ? String(Number(arg.toPrecision(ph.precision))) : parseFloat(arg)
                        break
                    case 'o':
                        arg = (parseInt(arg, 10) >>> 0).toString(8)
                        break
                    case 's':
                        arg = String(arg)
                        arg = (ph.precision ? arg.substring(0, ph.precision) : arg)
                        break
                    case 't':
                        arg = String(!!arg)
                        arg = (ph.precision ? arg.substring(0, ph.precision) : arg)
                        break
                    case 'T':
                        arg = Object.prototype.toString.call(arg).slice(8, -1).toLowerCase()
                        arg = (ph.precision ? arg.substring(0, ph.precision) : arg)
                        break
                    case 'u':
                        arg = parseInt(arg, 10) >>> 0
                        break
                    case 'v':
                        arg = arg.valueOf()
                        arg = (ph.precision ? arg.substring(0, ph.precision) : arg)
                        break
                    case 'x':
                        arg = (parseInt(arg, 10) >>> 0).toString(16)
                        break
                    case 'X':
                        arg = (parseInt(arg, 10) >>> 0).toString(16).toUpperCase()
                        break
                }
                if (re.json.test(ph.type)) {
                    output += arg
                }
                else {
                    if (re.number.test(ph.type) && (!is_positive || ph.sign)) {
                        sign = is_positive ? '+' : '-'
                        arg = arg.toString().replace(re.sign, '')
                    }
                    else {
                        sign = ''
                    }
                    pad_character = ph.pad_char ? ph.pad_char === '0' ? '0' : ph.pad_char.charAt(1) : ' '
                    pad_length = ph.width - (sign + arg).length
                    pad = ph.width ? (pad_length > 0 ? pad_character.repeat(pad_length) : '') : ''
                    output += ph.align ? sign + arg + pad : (pad_character === '0' ? sign + pad + arg : pad + sign + arg)
                }
            }
        }
        return output
    }

    var sprintf_cache = Object.create(null)

    function sprintf_parse(fmt) {
        if (sprintf_cache[fmt]) {
            return sprintf_cache[fmt]
        }

        var _fmt = fmt, match, parse_tree = [], arg_names = 0
        while (_fmt) {
            if ((match = re.text.exec(_fmt)) !== null) {
                parse_tree.push(match[0])
            }
            else if ((match = re.modulo.exec(_fmt)) !== null) {
                parse_tree.push('%')
            }
            else if ((match = re.placeholder.exec(_fmt)) !== null) {
                if (match[2]) {
                    arg_names |= 1
                    var field_list = [], replacement_field = match[2], field_match = []
                    if ((field_match = re.key.exec(replacement_field)) !== null) {
                        field_list.push(field_match[1])
                        while ((replacement_field = replacement_field.substring(field_match[0].length)) !== '') {
                            if ((field_match = re.key_access.exec(replacement_field)) !== null) {
                                field_list.push(field_match[1])
                            }
                            else if ((field_match = re.index_access.exec(replacement_field)) !== null) {
                                field_list.push(field_match[1])
                            }
                            else {
                                throw new SyntaxError('[sprintf] failed to parse named argument key')
                            }
                        }
                    }
                    else {
                        throw new SyntaxError('[sprintf] failed to parse named argument key')
                    }
                    match[2] = field_list
                }
                else {
                    arg_names |= 2
                }
                if (arg_names === 3) {
                    throw new Error('[sprintf] mixing positional and named placeholders is not (yet) supported')
                }
                parse_tree.push(
                    {
                        placeholder: match[0],
                        param_no:    match[1],
                        keys:        match[2],
                        sign:        match[3],
                        pad_char:    match[4],
                        align:       match[5],
                        width:       match[6],
                        precision:   match[7],
                        type:        match[8]
                    }
                )
            } else {
                throw new SyntaxError('[sprintf] unexpected placeholder')
            }
            _fmt = _fmt.substring(match[0].length)
        }
        return sprintf_cache[fmt] = parse_tree
    }

    /**
     * export to either browser or node.js
     */
    /* eslint-disable quote-props */
    if (typeof exports !== 'undefined') {
        exports['sprintf'] = sprintf
        exports['vsprintf'] = vsprintf
    }
    if (typeof window !== 'undefined') {
        window['sprintf'] = sprintf
        window['vsprintf'] = vsprintf

        if (typeof define === 'function' && define['amd']) {
            define(function() {
                return {
                    'sprintf': sprintf,
                    'vsprintf': vsprintf
                }
            })
        }
    }
    /* eslint-enable quote-props */
}();

/*!
 * jQuery UI 1.8.24
 *
 * Copyright 2012, AUTHORS.txt (http://jqueryui.com/about)
 * Dual licensed under the MIT or GPL Version 2 licenses.
 * http://jquery.org/license
 *
 * http://docs.jquery.com/UI
 */
(function( $, undefined ) {

// prevent duplicate loading
// this is only a problem because we proxy existing functions
// and we don't want to double proxy them
$.ui = $.ui || {};
if ( $.ui.version ) {
    return;
}

$.extend( $.ui, {
    version: "1.8.24",

    keyCode: {
    ALT: 18,
    BACKSPACE: 8,
    CAPS_LOCK: 20,
    COMMA: 188,
    COMMAND: 91,
    COMMAND_LEFT: 91, // COMMAND
    COMMAND_RIGHT: 93,
    CONTROL: 17,
    DELETE: 46,
    DOWN: 40,
    END: 35,
    ENTER: 13,
    ESCAPE: 27,
    HOME: 36,
    INSERT: 45,
    LEFT: 37,
    MENU: 93, // COMMAND_RIGHT
    NUMPAD_ADD: 107,
    NUMPAD_DECIMAL: 110,
    NUMPAD_DIVIDE: 111,
    NUMPAD_ENTER: 108,
    NUMPAD_MULTIPLY: 106,
    NUMPAD_SUBTRACT: 109,
    PAGE_DOWN: 34,
    PAGE_UP: 33,
    PERIOD: 190,
    RIGHT: 39,
    SHIFT: 16,
    SPACE: 32,
    TAB: 9,
    UP: 38,
    WINDOWS: 91 // COMMAND
    }
});

// plugins
$.fn.extend({
    propAttr: $.fn.prop || $.fn.attr,

    _focus: $.fn.focus,
    focus: function( delay, fn ) {
    return typeof delay === "number" ?
    this.each(function() {
    var elem = this;
    setTimeout(function() {
    $( elem ).focus();
    if ( fn ) {
    fn.call( elem );
    }
    }, delay );
    }) :
    this._focus.apply( this, arguments );
    },

    scrollParent: function() {
    var scrollParent;
    if (($.browser.msie && (/(static|relative)/).test(this.css('position'))) || (/absolute/).test(this.css('position'))) {
    scrollParent = this.parents().filter(function() {
    return (/(relative|absolute|fixed)/).test($.curCSS(this,'position',1)) && (/(auto|scroll)/).test($.curCSS(this,'overflow',1)+$.curCSS(this,'overflow-y',1)+$.curCSS(this,'overflow-x',1));
    }).eq(0);
    } else {
    scrollParent = this.parents().filter(function() {
    return (/(auto|scroll)/).test($.curCSS(this,'overflow',1)+$.curCSS(this,'overflow-y',1)+$.curCSS(this,'overflow-x',1));
    }).eq(0);
    }

    return (/fixed/).test(this.css('position')) || !scrollParent.length ? $(document) : scrollParent;
    },

    zIndex: function( zIndex ) {
    if ( zIndex !== undefined ) {
    return this.css( "zIndex", zIndex );
    }

    if ( this.length ) {
    var elem = $( this[ 0 ] ), position, value;
    while ( elem.length && elem[ 0 ] !== document ) {
    // Ignore z-index if position is set to a value where z-index is ignored by the browser
    // This makes behavior of this function consistent across browsers
    // WebKit always returns auto if the element is positioned
    position = elem.css( "position" );
    if ( position === "absolute" || position === "relative" || position === "fixed" ) {
    // IE returns 0 when zIndex is not specified
    // other browsers return a string
    // we ignore the case of nested elements with an explicit value of 0
    // <div style="z-index: -10;"><div style="z-index: 0;"></div></div>
    value = parseInt( elem.css( "zIndex" ), 10 );
    if ( !isNaN( value ) && value !== 0 ) {
    return value;
    }
    }
    elem = elem.parent();
    }
    }

    return 0;
    },

    disableSelection: function() {
    return this.bind( ( $.support.selectstart ? "selectstart" : "mousedown" ) +
    ".ui-disableSelection", function( event ) {
    event.preventDefault();
    });
    },

    enableSelection: function() {
    return this.unbind( ".ui-disableSelection" );
    }
});

// support: jQuery <1.8
if ( !$( "<a>" ).outerWidth( 1 ).jquery ) {
    $.each( [ "Width", "Height" ], function( i, name ) {
    var side = name === "Width" ? [ "Left", "Right" ] : [ "Top", "Bottom" ],
    type = name.toLowerCase(),
    orig = {
    innerWidth: $.fn.innerWidth,
    innerHeight: $.fn.innerHeight,
    outerWidth: $.fn.outerWidth,
    outerHeight: $.fn.outerHeight
    };

    function reduce( elem, size, border, margin ) {
    $.each( side, function() {
    size -= parseFloat( $.curCSS( elem, "padding" + this, true) ) || 0;
    if ( border ) {
    size -= parseFloat( $.curCSS( elem, "border" + this + "Width", true) ) || 0;
    }
    if ( margin ) {
    size -= parseFloat( $.curCSS( elem, "margin" + this, true) ) || 0;
    }
    });
    return size;
    }

    $.fn[ "inner" + name ] = function( size ) {
    if ( size === undefined ) {
    return orig[ "inner" + name ].call( this );
    }

    return this.each(function() {
    $( this ).css( type, reduce( this, size ) + "px" );
    });
    };

    $.fn[ "outer" + name] = function( size, margin ) {
    if ( typeof size !== "number" ) {
    return orig[ "outer" + name ].call( this, size );
    }

    return this.each(function() {
    $( this).css( type, reduce( this, size, true, margin ) + "px" );
    });
    };
    });
}

// selectors
function focusable( element, isTabIndexNotNaN ) {
    var nodeName = element.nodeName.toLowerCase();
    if ( "area" === nodeName ) {
    var map = element.parentNode,
    mapName = map.name,
    img;
    if ( !element.href || !mapName || map.nodeName.toLowerCase() !== "map" ) {
    return false;
    }
    img = $( "img[usemap=#" + mapName + "]" )[0];
    return !!img && visible( img );
    }
    return ( /input|select|textarea|button|object/.test( nodeName )
    ? !element.disabled
    : "a" == nodeName
    ? element.href || isTabIndexNotNaN
    : isTabIndexNotNaN)
    // the element and all of its ancestors must be visible
    && visible( element );
}

function visible( element ) {
    return !$( element ).parents().andSelf().filter(function() {
    return $.curCSS( this, "visibility" ) === "hidden" ||
    $.expr.filters.hidden( this );
    }).length;
}

$.extend( $.expr[ ":" ], {
    data: $.expr.createPseudo ?
    $.expr.createPseudo(function( dataName ) {
    return function( elem ) {
    return !!$.data( elem, dataName );
    };
    }) :
    // support: jQuery <1.8
    function( elem, i, match ) {
    return !!$.data( elem, match[ 3 ] );
    },

    focusable: function( element ) {
    return focusable( element, !isNaN( $.attr( element, "tabindex" ) ) );
    },

    tabbable: function( element ) {
    var tabIndex = $.attr( element, "tabindex" ),
    isTabIndexNaN = isNaN( tabIndex );
    return ( isTabIndexNaN || tabIndex >= 0 ) && focusable( element, !isTabIndexNaN );
    }
});

// support
$(function() {
    var body = document.body,
    div = body.appendChild( div = document.createElement( "div" ) );

    // access offsetHeight before setting the style to prevent a layout bug
    // in IE 9 which causes the elemnt to continue to take up space even
    // after it is removed from the DOM (#8026)
    div.offsetHeight;

    $.extend( div.style, {
    minHeight: "100px",
    height: "auto",
    padding: 0,
    borderWidth: 0
    });

    $.support.minHeight = div.offsetHeight === 100;
    $.support.selectstart = "onselectstart" in div;

    // set display to none to avoid a layout bug in IE
    // http://dev.jquery.com/ticket/4014
    body.removeChild( div ).style.display = "none";
});

// jQuery <1.4.3 uses curCSS, in 1.4.3 - 1.7.2 curCSS = css, 1.8+ only has css
if ( !$.curCSS ) {
    $.curCSS = $.css;
}





// deprecated
$.extend( $.ui, {
    // $.ui.plugin is deprecated.  Use the proxy pattern instead.
    plugin: {
    add: function( module, option, set ) {
    var proto = $.ui[ module ].prototype;
    for ( var i in set ) {
    proto.plugins[ i ] = proto.plugins[ i ] || [];
    proto.plugins[ i ].push( [ option, set[ i ] ] );
    }
    },
    call: function( instance, name, args ) {
    var set = instance.plugins[ name ];
    if ( !set || !instance.element[ 0 ].parentNode ) {
    return;
    }

    for ( var i = 0; i < set.length; i++ ) {
    if ( instance.options[ set[ i ][ 0 ] ] ) {
    set[ i ][ 1 ].apply( instance.element, args );
    }
    }
    }
    },

    // will be deprecated when we switch to jQuery 1.4 - use jQuery.contains()
    contains: function( a, b ) {
    return document.compareDocumentPosition ?
    a.compareDocumentPosition( b ) & 16 :
    a !== b && a.contains( b );
    },

    // only used by resizable
    hasScroll: function( el, a ) {

    //If overflow is hidden, the element might have extra content, but the user wants to hide it
    if ( $( el ).css( "overflow" ) === "hidden") {
    return false;
    }

    var scroll = ( a && a === "left" ) ? "scrollLeft" : "scrollTop",
    has = false;

    if ( el[ scroll ] > 0 ) {
    return true;
    }

    // TODO: determine which cases actually cause this to happen
    // if the element doesn't have the scroll set, see if it's possible to
    // set the scroll
    el[ scroll ] = 1;
    has = ( el[ scroll ] > 0 );
    el[ scroll ] = 0;
    return has;
    },

    // these are odd functions, fix the API or move into individual plugins
    isOverAxis: function( x, reference, size ) {
    //Determines when x coordinate is over "b" element axis
    return ( x > reference ) && ( x < ( reference + size ) );
    },
    isOver: function( y, x, top, left, height, width ) {
    //Determines when x, y coordinates is over "b" element
    return $.ui.isOverAxis( y, top, height ) && $.ui.isOverAxis( x, left, width );
    }
});

})( jQuery );

/*!
 * jQuery UI Widget 1.8.24
 *
 * Copyright 2012, AUTHORS.txt (http://jqueryui.com/about)
 * Dual licensed under the MIT or GPL Version 2 licenses.
 * http://jquery.org/license
 *
 * http://docs.jquery.com/UI/Widget
 */
(function( $, undefined ) {

// jQuery 1.4+
if ( $.cleanData ) {
    var _cleanData = $.cleanData;
    $.cleanData = function( elems ) {
    for ( var i = 0, elem; (elem = elems[i]) != null; i++ ) {
    try {
    $( elem ).triggerHandler( "remove" );
    // http://bugs.jquery.com/ticket/8235
    } catch( e ) {}
    }
    _cleanData( elems );
    };
} else {
    var _remove = $.fn.remove;
    $.fn.remove = function( selector, keepData ) {
    return this.each(function() {
    if ( !keepData ) {
    if ( !selector || $.filter( selector, [ this ] ).length ) {
    $( "*", this ).add( [ this ] ).each(function() {
    try {
    $( this ).triggerHandler( "remove" );
    // http://bugs.jquery.com/ticket/8235
    } catch( e ) {}
    });
    }
    }
    return _remove.call( $(this), selector, keepData );
    });
    };
}

$.widget = function( name, base, prototype ) {
    var namespace = name.split( "." )[ 0 ],
    fullName;
    name = name.split( "." )[ 1 ];
    fullName = namespace + "-" + name;

    if ( !prototype ) {
    prototype = base;
    base = $.Widget;
    }

    // create selector for plugin
    $.expr[ ":" ][ fullName ] = function( elem ) {
    return !!$.data( elem, name );
    };

    $[ namespace ] = $[ namespace ] || {};
    $[ namespace ][ name ] = function( options, element ) {
    // allow instantiation without initializing for simple inheritance
    if ( arguments.length ) {
    this._createWidget( options, element );
    }
    };

    var basePrototype = new base();
    // we need to make the options hash a property directly on the new instance
    // otherwise we'll modify the options hash on the prototype that we're
    // inheriting from
//    $.each( basePrototype, function( key, val ) {
//    if ( $.isPlainObject(val) ) {
//    basePrototype[ key ] = $.extend( {}, val );
//    }
//    });
    basePrototype.options = $.extend( true, {}, basePrototype.options );
    $[ namespace ][ name ].prototype = $.extend( true, basePrototype, {
    namespace: namespace,
    widgetName: name,
    widgetEventPrefix: $[ namespace ][ name ].prototype.widgetEventPrefix || name,
    widgetBaseClass: fullName
    }, prototype );

    $.widget.bridge( name, $[ namespace ][ name ] );
};

$.widget.bridge = function( name, object ) {
    $.fn[ name ] = function( options ) {
    var isMethodCall = typeof options === "string",
    args = Array.prototype.slice.call( arguments, 1 ),
    returnValue = this;

    // allow multiple hashes to be passed on init
    options = !isMethodCall && args.length ?
    $.extend.apply( null, [ true, options ].concat(args) ) :
    options;

    // prevent calls to internal methods
    if ( isMethodCall && options.charAt( 0 ) === "_" ) {
    return returnValue;
    }

    if ( isMethodCall ) {
    this.each(function() {
    var instance = $.data( this, name ),
    methodValue = instance && $.isFunction( instance[options] ) ?
    instance[ options ].apply( instance, args ) :
    instance;
    // TODO: add this back in 1.9 and use $.error() (see #5972)
//    if ( !instance ) {
//    throw "cannot call methods on " + name + " prior to initialization; " +
//    "attempted to call method '" + options + "'";
//    }
//    if ( !$.isFunction( instance[options] ) ) {
//    throw "no such method '" + options + "' for " + name + " widget instance";
//    }
//    var methodValue = instance[ options ].apply( instance, args );
    if ( methodValue !== instance && methodValue !== undefined ) {
    returnValue = methodValue;
    return false;
    }
    });
    } else {
    this.each(function() {
    var instance = $.data( this, name );
    if ( instance ) {
    instance.option( options || {} )._init();
    } else {
    $.data( this, name, new object( options, this ) );
    }
    });
    }

    return returnValue;
    };
};

$.Widget = function( options, element ) {
    // allow instantiation without initializing for simple inheritance
    if ( arguments.length ) {
    this._createWidget( options, element );
    }
};

$.Widget.prototype = {
    widgetName: "widget",
    widgetEventPrefix: "",
    options: {
    disabled: false
    },
    _createWidget: function( options, element ) {
    // $.widget.bridge stores the plugin instance, but we do it anyway
    // so that it's stored even before the _create function runs
    $.data( element, this.widgetName, this );
    this.element = $( element );
    this.options = $.extend( true, {},
    this.options,
    this._getCreateOptions(),
    options );

    var self = this;
    this.element.bind( "remove." + this.widgetName, function() {
    self.destroy();
    });

    this._create();
    this._trigger( "create" );
    this._init();
    },
    _getCreateOptions: function() {
    return $.metadata && $.metadata.get( this.element[0] )[ this.widgetName ];
    },
    _create: function() {},
    _init: function() {},

    destroy: function() {
    this.element
    .unbind( "." + this.widgetName )
    .removeData( this.widgetName );
    this.widget()
    .unbind( "." + this.widgetName )
    .removeAttr( "aria-disabled" )
    .removeClass(
    this.widgetBaseClass + "-disabled " +
    "ui-state-disabled" );
    },

    widget: function() {
    return this.element;
    },

    option: function( key, value ) {
    var options = key;

    if ( arguments.length === 0 ) {
    // don't return a reference to the internal hash
    return $.extend( {}, this.options );
    }

    if  (typeof key === "string" ) {
    if ( value === undefined ) {
    return this.options[ key ];
    }
    options = {};
    options[ key ] = value;
    }

    this._setOptions( options );

    return this;
    },
    _setOptions: function( options ) {
    var self = this;
    $.each( options, function( key, value ) {
    self._setOption( key, value );
    });

    return this;
    },
    _setOption: function( key, value ) {
    this.options[ key ] = value;

    if ( key === "disabled" ) {
    this.widget()
    [ value ? "addClass" : "removeClass"](
    this.widgetBaseClass + "-disabled" + " " +
    "ui-state-disabled" )
    .attr( "aria-disabled", value );
    }

    return this;
    },

    enable: function() {
    return this._setOption( "disabled", false );
    },
    disable: function() {
    return this._setOption( "disabled", true );
    },

    _trigger: function( type, event, data ) {
    var prop, orig,
    callback = this.options[ type ];

    data = data || {};
    event = $.Event( event );
    event.type = ( type === this.widgetEventPrefix ?
    type :
    this.widgetEventPrefix + type ).toLowerCase();
    // the original event may come from any element
    // so we need to reset the target on the new event
    event.target = this.element[ 0 ];

    // copy original event properties over to the new event
    orig = event.originalEvent;
    if ( orig ) {
    for ( prop in orig ) {
    if ( !( prop in event ) ) {
    event[ prop ] = orig[ prop ];
    }
    }
    }

    this.element.trigger( event, data );

    return !( $.isFunction(callback) &&
    callback.call( this.element[0], event, data ) === false ||
    event.isDefaultPrevented() );
    }
};

})( jQuery );

/*!
 * jQuery UI Mouse 1.8.24
 *
 * Copyright 2012, AUTHORS.txt (http://jqueryui.com/about)
 * Dual licensed under the MIT or GPL Version 2 licenses.
 * http://jquery.org/license
 *
 * http://docs.jquery.com/UI/Mouse
 *
 * Depends:
 *    jquery.ui.widget.js
 */
(function( $, undefined ) {

var mouseHandled = false;
$( document ).mouseup( function( e ) {
    mouseHandled = false;
});

$.widget("ui.mouse", {
    options: {
    cancel: ':input,option',
    distance: 1,
    delay: 0
    },
    _mouseInit: function() {
    var self = this;

    this.element
    .bind('mousedown.'+this.widgetName, function(event) {
    return self._mouseDown(event);
    })
    .bind('click.'+this.widgetName, function(event) {
    if (true === $.data(event.target, self.widgetName + '.preventClickEvent')) {
        $.removeData(event.target, self.widgetName + '.preventClickEvent');
    event.stopImmediatePropagation();
    return false;
    }
    });

    this.started = false;
    },

    // TODO: make sure destroying one instance of mouse doesn't mess with
    // other instances of mouse
    _mouseDestroy: function() {
    this.element.unbind('.'+this.widgetName);
    if ( this._mouseMoveDelegate ) {
    $(document)
    .unbind('mousemove.'+this.widgetName, this._mouseMoveDelegate)
    .unbind('mouseup.'+this.widgetName, this._mouseUpDelegate);
    }
    },

    _mouseDown: function(event) {
    // don't let more than one widget handle mouseStart
    if( mouseHandled ) { return };

    // we may have missed mouseup (out of window)
    (this._mouseStarted && this._mouseUp(event));

    this._mouseDownEvent = event;

    var self = this,
    btnIsLeft = (event.which == 1),
    // event.target.nodeName works around a bug in IE 8 with
    // disabled inputs (#7620)
    elIsCancel = (typeof this.options.cancel == "string" && event.target.nodeName ? $(event.target).closest(this.options.cancel).length : false);
    if (!btnIsLeft || elIsCancel || !this._mouseCapture(event)) {
    return true;
    }

    this.mouseDelayMet = !this.options.delay;
    if (!this.mouseDelayMet) {
    this._mouseDelayTimer = setTimeout(function() {
    self.mouseDelayMet = true;
    }, this.options.delay);
    }

    if (this._mouseDistanceMet(event) && this._mouseDelayMet(event)) {
    this._mouseStarted = (this._mouseStart(event) !== false);
    if (!this._mouseStarted) {
    event.preventDefault();
    return true;
    }
    }

    // Click event may never have fired (Gecko & Opera)
    if (true === $.data(event.target, this.widgetName + '.preventClickEvent')) {
    $.removeData(event.target, this.widgetName + '.preventClickEvent');
    }

    // these delegates are required to keep context
    this._mouseMoveDelegate = function(event) {
    return self._mouseMove(event);
    };
    this._mouseUpDelegate = function(event) {
    return self._mouseUp(event);
    };
    $(document)
    .bind('mousemove.'+this.widgetName, this._mouseMoveDelegate)
    .bind('mouseup.'+this.widgetName, this._mouseUpDelegate);

    event.preventDefault();

    mouseHandled = true;
    return true;
    },

    _mouseMove: function(event) {
    // IE mouseup check - mouseup happened when mouse was out of window
    if ($.browser.msie && !(document.documentMode >= 9) && !event.button) {
    return this._mouseUp(event);
    }

    if (this._mouseStarted) {
    this._mouseDrag(event);
    return event.preventDefault();
    }

    if (this._mouseDistanceMet(event) && this._mouseDelayMet(event)) {
    this._mouseStarted =
    (this._mouseStart(this._mouseDownEvent, event) !== false);
    (this._mouseStarted ? this._mouseDrag(event) : this._mouseUp(event));
    }

    return !this._mouseStarted;
    },

    _mouseUp: function(event) {
    $(document)
    .unbind('mousemove.'+this.widgetName, this._mouseMoveDelegate)
    .unbind('mouseup.'+this.widgetName, this._mouseUpDelegate);

    if (this._mouseStarted) {
    this._mouseStarted = false;

    if (event.target == this._mouseDownEvent.target) {
        $.data(event.target, this.widgetName + '.preventClickEvent', true);
    }

    this._mouseStop(event);
    }

    return false;
    },

    _mouseDistanceMet: function(event) {
    return (Math.max(
    Math.abs(this._mouseDownEvent.pageX - event.pageX),
    Math.abs(this._mouseDownEvent.pageY - event.pageY)
    ) >= this.options.distance
    );
    },

    _mouseDelayMet: function(event) {
    return this.mouseDelayMet;
    },

    // These are placeholder methods, to be overriden by extending plugin
    _mouseStart: function(event) {},
    _mouseDrag: function(event) {},
    _mouseStop: function(event) {},
    _mouseCapture: function(event) { return true; }
});

})(jQuery);

/*!
 * jQuery UI Draggable 1.8.24
 *
 * Copyright 2012, AUTHORS.txt (http://jqueryui.com/about)
 * Dual licensed under the MIT or GPL Version 2 licenses.
 * http://jquery.org/license
 *
 * http://docs.jquery.com/UI/Draggables
 *
 * Depends:
 *    jquery.ui.core.js
 *    jquery.ui.mouse.js
 *    jquery.ui.widget.js
 */
(function( $, undefined ) {

$.widget("ui.draggable", $.ui.mouse, {
    widgetEventPrefix: "drag",
    options: {
    addClasses: true,
    appendTo: "parent",
    axis: false,
    connectToSortable: false,
    containment: false,
    cursor: "auto",
    cursorAt: false,
    grid: false,
    handle: false,
    helper: "original",
    iframeFix: false,
    opacity: false,
    refreshPositions: false,
    revert: false,
    revertDuration: 500,
    scope: "default",
    scroll: true,
    scrollSensitivity: 20,
    scrollSpeed: 20,
    snap: false,
    snapMode: "both",
    snapTolerance: 20,
    stack: false,
    zIndex: false
    },
    _create: function() {

    if (this.options.helper == 'original' && !(/^(?:r|a|f)/).test(this.element.css("position")))
    this.element[0].style.position = 'relative';

    (this.options.addClasses && this.element.addClass("ui-draggable"));
    (this.options.disabled && this.element.addClass("ui-draggable-disabled"));

    this._mouseInit();

    },

    destroy: function() {
    if(!this.element.data('draggable')) return;
    this.element
    .removeData("draggable")
    .unbind(".draggable")
    .removeClass("ui-draggable"
    + " ui-draggable-dragging"
    + " ui-draggable-disabled");
    this._mouseDestroy();

    return this;
    },

    _mouseCapture: function(event) {

    var o = this.options;

    // among others, prevent a drag on a resizable-handle
    if (this.helper || o.disabled || $(event.target).is('.ui-resizable-handle'))
    return false;

    //Quit if we're not on a valid handle
    this.handle = this._getHandle(event);
    if (!this.handle)
    return false;

    if ( o.iframeFix ) {
    $(o.iframeFix === true ? "iframe" : o.iframeFix).each(function() {
    $('<div class="ui-draggable-iframeFix" style="background: #fff;"></div>')
    .css({
    width: this.offsetWidth+"px", height: this.offsetHeight+"px",
    position: "absolute", opacity: "0.001", zIndex: 1000
    })
    .css($(this).offset())
    .appendTo("body");
    });
    }

    return true;

    },

    _mouseStart: function(event) {

    var o = this.options;

    //Create and append the visible helper
    this.helper = this._createHelper(event);

    this.helper.addClass("ui-draggable-dragging");

    //Cache the helper size
    this._cacheHelperProportions();

    //If ddmanager is used for droppables, set the global draggable
    if($.ui.ddmanager)
    $.ui.ddmanager.current = this;

    /*
     * - Position generation -
     * This block generates everything position related - it's the core of draggables.
     */

    //Cache the margins of the original element
    this._cacheMargins();

    //Store the helper's css position
    this.cssPosition = this.helper.css("position");
    this.scrollParent = this.helper.scrollParent();

    //The element's absolute position on the page minus margins
    this.offset = this.positionAbs = this.element.offset();
    this.offset = {
    top: this.offset.top - this.margins.top,
    left: this.offset.left - this.margins.left
    };

    $.extend(this.offset, {
    click: { //Where the click happened, relative to the element
    left: event.pageX - this.offset.left,
    top: event.pageY - this.offset.top
    },
    parent: this._getParentOffset(),
    relative: this._getRelativeOffset() //This is a relative to absolute position minus the actual position calculation - only used for relative positioned helper
    });

    //Generate the original position
    this.originalPosition = this.position = this._generatePosition(event);
    this.originalPageX = event.pageX;
    this.originalPageY = event.pageY;

    //Adjust the mouse offset relative to the helper if 'cursorAt' is supplied
    (o.cursorAt && this._adjustOffsetFromHelper(o.cursorAt));

    //Set a containment if given in the options
    if(o.containment)
    this._setContainment();

    //Trigger event + callbacks
    if(this._trigger("start", event) === false) {
    this._clear();
    return false;
    }

    //Recache the helper size
    this._cacheHelperProportions();

    //Prepare the droppable offsets
    if ($.ui.ddmanager && !o.dropBehaviour)
    $.ui.ddmanager.prepareOffsets(this, event);


    this._mouseDrag(event, true); //Execute the drag once - this causes the helper not to be visible before getting its correct position

    //If the ddmanager is used for droppables, inform the manager that dragging has started (see #5003)
    if ( $.ui.ddmanager ) $.ui.ddmanager.dragStart(this, event);

    return true;
    },

    _mouseDrag: function(event, noPropagation) {

    //Compute the helpers position
    this.position = this._generatePosition(event);
    this.positionAbs = this._convertPositionTo("absolute");

    //Call plugins and callbacks and use the resulting position if something is returned
    if (!noPropagation) {
    var ui = this._uiHash();
    if(this._trigger('drag', event, ui) === false) {
    this._mouseUp({});
    return false;
    }
    this.position = ui.position;
    }

    if(!this.options.axis || this.options.axis != "y") this.helper[0].style.left = this.position.left+'px';
    if(!this.options.axis || this.options.axis != "x") this.helper[0].style.top = this.position.top+'px';
    if($.ui.ddmanager) $.ui.ddmanager.drag(this, event);

    return false;
    },

    _mouseStop: function(event) {

    //If we are using droppables, inform the manager about the drop
    var dropped = false;
    if ($.ui.ddmanager && !this.options.dropBehaviour)
    dropped = $.ui.ddmanager.drop(this, event);

    //if a drop comes from outside (a sortable)
    if(this.dropped) {
    dropped = this.dropped;
    this.dropped = false;
    }

    //if the original element is no longer in the DOM don't bother to continue (see #8269)
    var element = this.element[0], elementInDom = false;
    while ( element && (element = element.parentNode) ) {
    if (element == document ) {
    elementInDom = true;
    }
    }
    if ( !elementInDom && this.options.helper === "original" )
    return false;

    if((this.options.revert == "invalid" && !dropped) || (this.options.revert == "valid" && dropped) || this.options.revert === true || ($.isFunction(this.options.revert) && this.options.revert.call(this.element, dropped))) {
    var self = this;
    $(this.helper).animate(this.originalPosition, parseInt(this.options.revertDuration, 10), function() {
    if(self._trigger("stop", event) !== false) {
    self._clear();
    }
    });
    } else {
    if(this._trigger("stop", event) !== false) {
    this._clear();
    }
    }

    return false;
    },

    _mouseUp: function(event) {
    //Remove frame helpers
    $("div.ui-draggable-iframeFix").each(function() {
    this.parentNode.removeChild(this);
    });

    //If the ddmanager is used for droppables, inform the manager that dragging has stopped (see #5003)
    if( $.ui.ddmanager ) $.ui.ddmanager.dragStop(this, event);

    return $.ui.mouse.prototype._mouseUp.call(this, event);
    },

    cancel: function() {

    if(this.helper.is(".ui-draggable-dragging")) {
    this._mouseUp({});
    } else {
    this._clear();
    }

    return this;

    },

    _getHandle: function(event) {

    var handle = !this.options.handle || !$(this.options.handle, this.element).length ? true : false;
    $(this.options.handle, this.element)
    .find("*")
    .andSelf()
    .each(function() {
    if(this == event.target) handle = true;
    });

    return handle;

    },

    _createHelper: function(event) {

    var o = this.options;
    var helper = $.isFunction(o.helper) ? $(o.helper.apply(this.element[0], [event])) : (o.helper == 'clone' ? this.element.clone().removeAttr('id') : this.element);

    if(!helper.parents('body').length)
    helper.appendTo((o.appendTo == 'parent' ? this.element[0].parentNode : o.appendTo));

    if(helper[0] != this.element[0] && !(/(fixed|absolute)/).test(helper.css("position")))
    helper.css("position", "absolute");

    return helper;

    },

    _adjustOffsetFromHelper: function(obj) {
    if (typeof obj == 'string') {
    obj = obj.split(' ');
    }
    if ($.isArray(obj)) {
    obj = {left: +obj[0], top: +obj[1] || 0};
    }
    if ('left' in obj) {
    this.offset.click.left = obj.left + this.margins.left;
    }
    if ('right' in obj) {
    this.offset.click.left = this.helperProportions.width - obj.right + this.margins.left;
    }
    if ('top' in obj) {
    this.offset.click.top = obj.top + this.margins.top;
    }
    if ('bottom' in obj) {
    this.offset.click.top = this.helperProportions.height - obj.bottom + this.margins.top;
    }
    },

    _getParentOffset: function() {

    //Get the offsetParent and cache its position
    this.offsetParent = this.helper.offsetParent();
    var po = this.offsetParent.offset();

    // This is a special case where we need to modify a offset calculated on start, since the following happened:
    // 1. The position of the helper is absolute, so it's position is calculated based on the next positioned parent
    // 2. The actual offset parent is a child of the scroll parent, and the scroll parent isn't the document, which means that
    //    the scroll is included in the initial calculation of the offset of the parent, and never recalculated upon drag
    if(this.cssPosition == 'absolute' && this.scrollParent[0] != document && $.ui.contains(this.scrollParent[0], this.offsetParent[0])) {
    po.left += this.scrollParent.scrollLeft();
    po.top += this.scrollParent.scrollTop();
    }

    if((this.offsetParent[0] == document.body) //This needs to be actually done for all browsers, since pageX/pageY includes this information
    || (this.offsetParent[0].tagName && this.offsetParent[0].tagName.toLowerCase() == 'html' && $.browser.msie)) //Ugly IE fix
    po = { top: 0, left: 0 };

    return {
    top: po.top + (parseInt(this.offsetParent.css("borderTopWidth"),10) || 0),
    left: po.left + (parseInt(this.offsetParent.css("borderLeftWidth"),10) || 0)
    };

    },

    _getRelativeOffset: function() {

    if(this.cssPosition == "relative") {
    var p = this.element.position();
    return {
    top: p.top - (parseInt(this.helper.css("top"),10) || 0) + this.scrollParent.scrollTop(),
    left: p.left - (parseInt(this.helper.css("left"),10) || 0) + this.scrollParent.scrollLeft()
    };
    } else {
    return { top: 0, left: 0 };
    }

    },

    _cacheMargins: function() {
    this.margins = {
    left: (parseInt(this.element.css("marginLeft"),10) || 0),
    top: (parseInt(this.element.css("marginTop"),10) || 0),
    right: (parseInt(this.element.css("marginRight"),10) || 0),
    bottom: (parseInt(this.element.css("marginBottom"),10) || 0)
    };
    },

    _cacheHelperProportions: function() {
    this.helperProportions = {
    width: this.helper.outerWidth(),
    height: this.helper.outerHeight()
    };
    },

    _setContainment: function() {

    var o = this.options;
    if(o.containment == 'parent') o.containment = this.helper[0].parentNode;
    if(o.containment == 'document' || o.containment == 'window') this.containment = [
    o.containment == 'document' ? 0 : $(window).scrollLeft() - this.offset.relative.left - this.offset.parent.left,
    o.containment == 'document' ? 0 : $(window).scrollTop() - this.offset.relative.top - this.offset.parent.top,
    (o.containment == 'document' ? 0 : $(window).scrollLeft()) + $(o.containment == 'document' ? document : window).width() - this.helperProportions.width - this.margins.left,
    (o.containment == 'document' ? 0 : $(window).scrollTop()) + ($(o.containment == 'document' ? document : window).height() || document.body.parentNode.scrollHeight) - this.helperProportions.height - this.margins.top
    ];

    if(!(/^(document|window|parent)$/).test(o.containment) && o.containment.constructor != Array) {
            var c = $(o.containment);
    var ce = c[0]; if(!ce) return;
    var co = c.offset();
    var over = ($(ce).css("overflow") != 'hidden');

    this.containment = [
    (parseInt($(ce).css("borderLeftWidth"),10) || 0) + (parseInt($(ce).css("paddingLeft"),10) || 0),
    (parseInt($(ce).css("borderTopWidth"),10) || 0) + (parseInt($(ce).css("paddingTop"),10) || 0),
    (over ? Math.max(ce.scrollWidth,ce.offsetWidth) : ce.offsetWidth) - (parseInt($(ce).css("borderLeftWidth"),10) || 0) - (parseInt($(ce).css("paddingRight"),10) || 0) - this.helperProportions.width - this.margins.left - this.margins.right,
    (over ? Math.max(ce.scrollHeight,ce.offsetHeight) : ce.offsetHeight) - (parseInt($(ce).css("borderTopWidth"),10) || 0) - (parseInt($(ce).css("paddingBottom"),10) || 0) - this.helperProportions.height - this.margins.top  - this.margins.bottom
    ];
    this.relative_container = c;

    } else if(o.containment.constructor == Array) {
    this.containment = o.containment;
    }

    },

    _convertPositionTo: function(d, pos) {

    if(!pos) pos = this.position;
    var mod = d == "absolute" ? 1 : -1;
    var o = this.options, scroll = this.cssPosition == 'absolute' && !(this.scrollParent[0] != document && $.ui.contains(this.scrollParent[0], this.offsetParent[0])) ? this.offsetParent : this.scrollParent, scrollIsRootNode = (/(html|body)/i).test(scroll[0].tagName);

    return {
    top: (
    pos.top    // The absolute mouse position
    + this.offset.relative.top * mod    // Only for relative positioned nodes: Relative offset from element to offset parent
    + this.offset.parent.top * mod    // The offsetParent's offset without borders (offset + border)
    - ($.browser.safari && $.browser.version < 526 && this.cssPosition == 'fixed' ? 0 : ( this.cssPosition == 'fixed' ? -this.scrollParent.scrollTop() : ( scrollIsRootNode ? 0 : scroll.scrollTop() ) ) * mod)
    ),
    left: (
    pos.left    // The absolute mouse position
    + this.offset.relative.left * mod    // Only for relative positioned nodes: Relative offset from element to offset parent
    + this.offset.parent.left * mod    // The offsetParent's offset without borders (offset + border)
    - ($.browser.safari && $.browser.version < 526 && this.cssPosition == 'fixed' ? 0 : ( this.cssPosition == 'fixed' ? -this.scrollParent.scrollLeft() : scrollIsRootNode ? 0 : scroll.scrollLeft() ) * mod)
    )
    };

    },

    _generatePosition: function(event) {

    var o = this.options, scroll = this.cssPosition == 'absolute' && !(this.scrollParent[0] != document && $.ui.contains(this.scrollParent[0], this.offsetParent[0])) ? this.offsetParent : this.scrollParent, scrollIsRootNode = (/(html|body)/i).test(scroll[0].tagName);
    var pageX = event.pageX;
    var pageY = event.pageY;

    /*
     * - Position constraining -
     * Constrain the position to a mix of grid, containment.
     */

    if(this.originalPosition) { //If we are not dragging yet, we won't check for options
             var containment;
             if(this.containment) {
     if (this.relative_container){
         var co = this.relative_container.offset();
         containment = [ this.containment[0] + co.left,
         this.containment[1] + co.top,
         this.containment[2] + co.left,
         this.containment[3] + co.top ];
     }
     else {
         containment = this.containment;
     }

    if(event.pageX - this.offset.click.left < containment[0]) pageX = containment[0] + this.offset.click.left;
    if(event.pageY - this.offset.click.top < containment[1]) pageY = containment[1] + this.offset.click.top;
    if(event.pageX - this.offset.click.left > containment[2]) pageX = containment[2] + this.offset.click.left;
    if(event.pageY - this.offset.click.top > containment[3]) pageY = containment[3] + this.offset.click.top;
    }

    if(o.grid) {
    //Check for grid elements set to 0 to prevent divide by 0 error causing invalid argument errors in IE (see ticket #6950)
    var top = o.grid[1] ? this.originalPageY + Math.round((pageY - this.originalPageY) / o.grid[1]) * o.grid[1] : this.originalPageY;
    pageY = containment ? (!(top - this.offset.click.top < containment[1] || top - this.offset.click.top > containment[3]) ? top : (!(top - this.offset.click.top < containment[1]) ? top - o.grid[1] : top + o.grid[1])) : top;

    var left = o.grid[0] ? this.originalPageX + Math.round((pageX - this.originalPageX) / o.grid[0]) * o.grid[0] : this.originalPageX;
    pageX = containment ? (!(left - this.offset.click.left < containment[0] || left - this.offset.click.left > containment[2]) ? left : (!(left - this.offset.click.left < containment[0]) ? left - o.grid[0] : left + o.grid[0])) : left;
    }

    }

    return {
    top: (
    pageY    // The absolute mouse position
    - this.offset.click.top    // Click offset (relative to the element)
    - this.offset.relative.top    // Only for relative positioned nodes: Relative offset from element to offset parent
    - this.offset.parent.top    // The offsetParent's offset without borders (offset + border)
    + ($.browser.safari && $.browser.version < 526 && this.cssPosition == 'fixed' ? 0 : ( this.cssPosition == 'fixed' ? -this.scrollParent.scrollTop() : ( scrollIsRootNode ? 0 : scroll.scrollTop() ) ))
    ),
    left: (
    pageX    // The absolute mouse position
    - this.offset.click.left    // Click offset (relative to the element)
    - this.offset.relative.left    // Only for relative positioned nodes: Relative offset from element to offset parent
    - this.offset.parent.left    // The offsetParent's offset without borders (offset + border)
    + ($.browser.safari && $.browser.version < 526 && this.cssPosition == 'fixed' ? 0 : ( this.cssPosition == 'fixed' ? -this.scrollParent.scrollLeft() : scrollIsRootNode ? 0 : scroll.scrollLeft() ))
    )
    };

    },

    _clear: function() {
    this.helper.removeClass("ui-draggable-dragging");
    if(this.helper[0] != this.element[0] && !this.cancelHelperRemoval) this.helper.remove();
    //if($.ui.ddmanager) $.ui.ddmanager.current = null;
    this.helper = null;
    this.cancelHelperRemoval = false;
    },

    // From now on bulk stuff - mainly helpers

    _trigger: function(type, event, ui) {
    ui = ui || this._uiHash();
    $.ui.plugin.call(this, type, [event, ui]);
    if(type == "drag") this.positionAbs = this._convertPositionTo("absolute"); //The absolute position has to be recalculated after plugins
    return $.Widget.prototype._trigger.call(this, type, event, ui);
    },

    plugins: {},

    _uiHash: function(event) {
    return {
    helper: this.helper,
    position: this.position,
    originalPosition: this.originalPosition,
    offset: this.positionAbs
    };
    }

});

$.extend($.ui.draggable, {
    version: "1.8.24"
});

$.ui.plugin.add("draggable", "connectToSortable", {
    start: function(event, ui) {

    var inst = $(this).data("draggable"), o = inst.options,
    uiSortable = $.extend({}, ui, { item: inst.element });
    inst.sortables = [];
    $(o.connectToSortable).each(function() {
    var sortable = $.data(this, 'sortable');
    if (sortable && !sortable.options.disabled) {
    inst.sortables.push({
    instance: sortable,
    shouldRevert: sortable.options.revert
    });
    sortable.refreshPositions();    // Call the sortable's refreshPositions at drag start to refresh the containerCache since the sortable container cache is used in drag and needs to be up to date (this will ensure it's initialised as well as being kept in step with any changes that might have happened on the page).
    sortable._trigger("activate", event, uiSortable);
    }
    });

    },
    stop: function(event, ui) {

    //If we are still over the sortable, we fake the stop event of the sortable, but also remove helper
    var inst = $(this).data("draggable"),
    uiSortable = $.extend({}, ui, { item: inst.element });

    $.each(inst.sortables, function() {
    if(this.instance.isOver) {

    this.instance.isOver = 0;

    inst.cancelHelperRemoval = true; //Don't remove the helper in the draggable instance
    this.instance.cancelHelperRemoval = false; //Remove it in the sortable instance (so sortable plugins like revert still work)

    //The sortable revert is supported, and we have to set a temporary dropped variable on the draggable to support revert: 'valid/invalid'
    if(this.shouldRevert) this.instance.options.revert = true;

    //Trigger the stop of the sortable
    this.instance._mouseStop(event);

    this.instance.options.helper = this.instance.options._helper;

    //If the helper has been the original item, restore properties in the sortable
    if(inst.options.helper == 'original')
    this.instance.currentItem.css({ top: 'auto', left: 'auto' });

    } else {
    this.instance.cancelHelperRemoval = false; //Remove the helper in the sortable instance
    this.instance._trigger("deactivate", event, uiSortable);
    }

    });

    },
    drag: function(event, ui) {

    var inst = $(this).data("draggable"), self = this;

    var checkPos = function(o) {
    var dyClick = this.offset.click.top, dxClick = this.offset.click.left;
    var helperTop = this.positionAbs.top, helperLeft = this.positionAbs.left;
    var itemHeight = o.height, itemWidth = o.width;
    var itemTop = o.top, itemLeft = o.left;

    return $.ui.isOver(helperTop + dyClick, helperLeft + dxClick, itemTop, itemLeft, itemHeight, itemWidth);
    };

    $.each(inst.sortables, function(i) {

    //Copy over some variables to allow calling the sortable's native _intersectsWith
    this.instance.positionAbs = inst.positionAbs;
    this.instance.helperProportions = inst.helperProportions;
    this.instance.offset.click = inst.offset.click;

    if(this.instance._intersectsWith(this.instance.containerCache)) {

    //If it intersects, we use a little isOver variable and set it once, so our move-in stuff gets fired only once
    if(!this.instance.isOver) {

    this.instance.isOver = 1;
    //Now we fake the start of dragging for the sortable instance,
    //by cloning the list group item, appending it to the sortable and using it as inst.currentItem
    //We can then fire the start event of the sortable with our passed browser event, and our own helper (so it doesn't create a new one)
    this.instance.currentItem = $(self).clone().removeAttr('id').appendTo(this.instance.element).data("sortable-item", true);
    this.instance.options._helper = this.instance.options.helper; //Store helper option to later restore it
    this.instance.options.helper = function() { return ui.helper[0]; };

    event.target = this.instance.currentItem[0];
    this.instance._mouseCapture(event, true);
    this.instance._mouseStart(event, true, true);

    //Because the browser event is way off the new appended portlet, we modify a couple of variables to reflect the changes
    this.instance.offset.click.top = inst.offset.click.top;
    this.instance.offset.click.left = inst.offset.click.left;
    this.instance.offset.parent.left -= inst.offset.parent.left - this.instance.offset.parent.left;
    this.instance.offset.parent.top -= inst.offset.parent.top - this.instance.offset.parent.top;

    inst._trigger("toSortable", event);
    inst.dropped = this.instance.element; //draggable revert needs that
    //hack so receive/update callbacks work (mostly)
    inst.currentItem = inst.element;
    this.instance.fromOutside = inst;

    }

    //Provided we did all the previous steps, we can fire the drag event of the sortable on every draggable drag, when it intersects with the sortable
    if(this.instance.currentItem) this.instance._mouseDrag(event);

    } else {

    //If it doesn't intersect with the sortable, and it intersected before,
    //we fake the drag stop of the sortable, but make sure it doesn't remove the helper by using cancelHelperRemoval
    if(this.instance.isOver) {

    this.instance.isOver = 0;
    this.instance.cancelHelperRemoval = true;

    //Prevent reverting on this forced stop
    this.instance.options.revert = false;

    // The out event needs to be triggered independently
    this.instance._trigger('out', event, this.instance._uiHash(this.instance));

    this.instance._mouseStop(event, true);
    this.instance.options.helper = this.instance.options._helper;

    //Now we remove our currentItem, the list group clone again, and the placeholder, and animate the helper back to it's original size
    this.instance.currentItem.remove();
    if(this.instance.placeholder) this.instance.placeholder.remove();

    inst._trigger("fromSortable", event);
    inst.dropped = false; //draggable revert needs that
    }

    };

    });

    }
});

$.ui.plugin.add("draggable", "cursor", {
    start: function(event, ui) {
    var t = $('body'), o = $(this).data('draggable').options;
    if (t.css("cursor")) o._cursor = t.css("cursor");
    t.css("cursor", o.cursor);
    },
    stop: function(event, ui) {
    var o = $(this).data('draggable').options;
    if (o._cursor) $('body').css("cursor", o._cursor);
    }
});

$.ui.plugin.add("draggable", "opacity", {
    start: function(event, ui) {
    var t = $(ui.helper), o = $(this).data('draggable').options;
    if(t.css("opacity")) o._opacity = t.css("opacity");
    t.css('opacity', o.opacity);
    },
    stop: function(event, ui) {
    var o = $(this).data('draggable').options;
    if(o._opacity) $(ui.helper).css('opacity', o._opacity);
    }
});

$.ui.plugin.add("draggable", "scroll", {
    start: function(event, ui) {
    var i = $(this).data("draggable");
    if(i.scrollParent[0] != document && i.scrollParent[0].tagName != 'HTML') i.overflowOffset = i.scrollParent.offset();
    },
    drag: function(event, ui) {

    var i = $(this).data("draggable"), o = i.options, scrolled = false;

    if(i.scrollParent[0] != document && i.scrollParent[0].tagName != 'HTML') {

    if(!o.axis || o.axis != 'x') {
    if((i.overflowOffset.top + i.scrollParent[0].offsetHeight) - event.pageY < o.scrollSensitivity)
    i.scrollParent[0].scrollTop = scrolled = i.scrollParent[0].scrollTop + o.scrollSpeed;
    else if(event.pageY - i.overflowOffset.top < o.scrollSensitivity)
    i.scrollParent[0].scrollTop = scrolled = i.scrollParent[0].scrollTop - o.scrollSpeed;
    }

    if(!o.axis || o.axis != 'y') {
    if((i.overflowOffset.left + i.scrollParent[0].offsetWidth) - event.pageX < o.scrollSensitivity)
    i.scrollParent[0].scrollLeft = scrolled = i.scrollParent[0].scrollLeft + o.scrollSpeed;
    else if(event.pageX - i.overflowOffset.left < o.scrollSensitivity)
    i.scrollParent[0].scrollLeft = scrolled = i.scrollParent[0].scrollLeft - o.scrollSpeed;
    }

    } else {

    if(!o.axis || o.axis != 'x') {
    if(event.pageY - $(document).scrollTop() < o.scrollSensitivity)
    scrolled = $(document).scrollTop($(document).scrollTop() - o.scrollSpeed);
    else if($(window).height() - (event.pageY - $(document).scrollTop()) < o.scrollSensitivity)
    scrolled = $(document).scrollTop($(document).scrollTop() + o.scrollSpeed);
    }

    if(!o.axis || o.axis != 'y') {
    if(event.pageX - $(document).scrollLeft() < o.scrollSensitivity)
    scrolled = $(document).scrollLeft($(document).scrollLeft() - o.scrollSpeed);
    else if($(window).width() - (event.pageX - $(document).scrollLeft()) < o.scrollSensitivity)
    scrolled = $(document).scrollLeft($(document).scrollLeft() + o.scrollSpeed);
    }

    }

    if(scrolled !== false && $.ui.ddmanager && !o.dropBehaviour)
    $.ui.ddmanager.prepareOffsets(i, event);

    }
});

$.ui.plugin.add("draggable", "snap", {
    start: function(event, ui) {

    var i = $(this).data("draggable"), o = i.options;
    i.snapElements = [];

    $(o.snap.constructor != String ? ( o.snap.items || ':data(draggable)' ) : o.snap).each(function() {
    var $t = $(this); var $o = $t.offset();
    if(this != i.element[0]) i.snapElements.push({
    item: this,
    width: $t.outerWidth(), height: $t.outerHeight(),
    top: $o.top, left: $o.left
    });
    });

    },
    drag: function(event, ui) {

    var inst = $(this).data("draggable"), o = inst.options;
    var d = o.snapTolerance;

    var x1 = ui.offset.left, x2 = x1 + inst.helperProportions.width,
    y1 = ui.offset.top, y2 = y1 + inst.helperProportions.height;

    for (var i = inst.snapElements.length - 1; i >= 0; i--){

    var l = inst.snapElements[i].left, r = l + inst.snapElements[i].width,
    t = inst.snapElements[i].top, b = t + inst.snapElements[i].height;

    //Yes, I know, this is insane ;)
    if(!((l-d < x1 && x1 < r+d && t-d < y1 && y1 < b+d) || (l-d < x1 && x1 < r+d && t-d < y2 && y2 < b+d) || (l-d < x2 && x2 < r+d && t-d < y1 && y1 < b+d) || (l-d < x2 && x2 < r+d && t-d < y2 && y2 < b+d))) {
    if(inst.snapElements[i].snapping) (inst.options.snap.release && inst.options.snap.release.call(inst.element, event, $.extend(inst._uiHash(), { snapItem: inst.snapElements[i].item })));
    inst.snapElements[i].snapping = false;
    continue;
    }

    if(o.snapMode != 'inner') {
    var ts = Math.abs(t - y2) <= d;
    var bs = Math.abs(b - y1) <= d;
    var ls = Math.abs(l - x2) <= d;
    var rs = Math.abs(r - x1) <= d;
    if(ts) ui.position.top = inst._convertPositionTo("relative", { top: t - inst.helperProportions.height, left: 0 }).top - inst.margins.top;
    if(bs) ui.position.top = inst._convertPositionTo("relative", { top: b, left: 0 }).top - inst.margins.top;
    if(ls) ui.position.left = inst._convertPositionTo("relative", { top: 0, left: l - inst.helperProportions.width }).left - inst.margins.left;
    if(rs) ui.position.left = inst._convertPositionTo("relative", { top: 0, left: r }).left - inst.margins.left;
    }

    var first = (ts || bs || ls || rs);

    if(o.snapMode != 'outer') {
    var ts = Math.abs(t - y1) <= d;
    var bs = Math.abs(b - y2) <= d;
    var ls = Math.abs(l - x1) <= d;
    var rs = Math.abs(r - x2) <= d;
    if(ts) ui.position.top = inst._convertPositionTo("relative", { top: t, left: 0 }).top - inst.margins.top;
    if(bs) ui.position.top = inst._convertPositionTo("relative", { top: b - inst.helperProportions.height, left: 0 }).top - inst.margins.top;
    if(ls) ui.position.left = inst._convertPositionTo("relative", { top: 0, left: l }).left - inst.margins.left;
    if(rs) ui.position.left = inst._convertPositionTo("relative", { top: 0, left: r - inst.helperProportions.width }).left - inst.margins.left;
    }

    if(!inst.snapElements[i].snapping && (ts || bs || ls || rs || first))
    (inst.options.snap.snap && inst.options.snap.snap.call(inst.element, event, $.extend(inst._uiHash(), { snapItem: inst.snapElements[i].item })));
    inst.snapElements[i].snapping = (ts || bs || ls || rs || first);

    };

    }
});

$.ui.plugin.add("draggable", "stack", {
    start: function(event, ui) {

    var o = $(this).data("draggable").options;

    var group = $.makeArray($(o.stack)).sort(function(a,b) {
    return (parseInt($(a).css("zIndex"),10) || 0) - (parseInt($(b).css("zIndex"),10) || 0);
    });
    if (!group.length) { return; }

    var min = parseInt(group[0].style.zIndex) || 0;
    $(group).each(function(i) {
    this.style.zIndex = min + i;
    });

    this[0].style.zIndex = min + group.length;

    }
});

$.ui.plugin.add("draggable", "zIndex", {
    start: function(event, ui) {
    var t = $(ui.helper), o = $(this).data("draggable").options;
    if(t.css("zIndex")) o._zIndex = t.css("zIndex");
    t.css('zIndex', o.zIndex);
    },
    stop: function(event, ui) {
    var o = $(this).data("draggable").options;
    if(o._zIndex) $(ui.helper).css('zIndex', o._zIndex);
    }
});

})(jQuery);

/*!
 * jQuery UI Droppable 1.8.24
 *
 * Copyright 2012, AUTHORS.txt (http://jqueryui.com/about)
 * Dual licensed under the MIT or GPL Version 2 licenses.
 * http://jquery.org/license
 *
 * http://docs.jquery.com/UI/Droppables
 *
 * Depends:
 *    jquery.ui.core.js
 *    jquery.ui.widget.js
 *    jquery.ui.mouse.js
 *    jquery.ui.draggable.js
 */
(function( $, undefined ) {

$.widget("ui.droppable", {
    widgetEventPrefix: "drop",
    options: {
    accept: '*',
    activeClass: false,
    addClasses: true,
    greedy: false,
    hoverClass: false,
    scope: 'default',
    tolerance: 'intersect'
    },
    _create: function() {

    var o = this.options, accept = o.accept;
    this.isover = 0; this.isout = 1;

    this.accept = $.isFunction(accept) ? accept : function(d) {
    return d.is(accept);
    };

    //Store the droppable's proportions
    this.proportions = { width: this.element[0].offsetWidth, height: this.element[0].offsetHeight };

    // Add the reference and positions to the manager
    $.ui.ddmanager.droppables[o.scope] = $.ui.ddmanager.droppables[o.scope] || [];
    $.ui.ddmanager.droppables[o.scope].push(this);

    (o.addClasses && this.element.addClass("ui-droppable"));

    },

    destroy: function() {
    var drop = $.ui.ddmanager.droppables[this.options.scope];
    for ( var i = 0; i < drop.length; i++ )
    if ( drop[i] == this )
    drop.splice(i, 1);

    this.element
    .removeClass("ui-droppable ui-droppable-disabled")
    .removeData("droppable")
    .unbind(".droppable");

    return this;
    },

    _setOption: function(key, value) {

    if(key == 'accept') {
    this.accept = $.isFunction(value) ? value : function(d) {
    return d.is(value);
    };
    }
    $.Widget.prototype._setOption.apply(this, arguments);
    },

    _activate: function(event) {
    var draggable = $.ui.ddmanager.current;
    if(this.options.activeClass) this.element.addClass(this.options.activeClass);
    (draggable && this._trigger('activate', event, this.ui(draggable)));
    },

    _deactivate: function(event) {
    var draggable = $.ui.ddmanager.current;
    if(this.options.activeClass) this.element.removeClass(this.options.activeClass);
    (draggable && this._trigger('deactivate', event, this.ui(draggable)));
    },

    _over: function(event) {

    var draggable = $.ui.ddmanager.current;
    if (!draggable || (draggable.currentItem || draggable.element)[0] == this.element[0]) return; // Bail if draggable and droppable are same element

    if (this.accept.call(this.element[0],(draggable.currentItem || draggable.element))) {
    if(this.options.hoverClass) this.element.addClass(this.options.hoverClass);
    this._trigger('over', event, this.ui(draggable));
    }

    },

    _out: function(event) {

    var draggable = $.ui.ddmanager.current;
    if (!draggable || (draggable.currentItem || draggable.element)[0] == this.element[0]) return; // Bail if draggable and droppable are same element

    if (this.accept.call(this.element[0],(draggable.currentItem || draggable.element))) {
    if(this.options.hoverClass) this.element.removeClass(this.options.hoverClass);
    this._trigger('out', event, this.ui(draggable));
    }

    },

    _drop: function(event,custom) {

    var draggable = custom || $.ui.ddmanager.current;
    if (!draggable || (draggable.currentItem || draggable.element)[0] == this.element[0]) return false; // Bail if draggable and droppable are same element

    var childrenIntersection = false;
    this.element.find(":data(droppable)").not(".ui-draggable-dragging").each(function() {
    var inst = $.data(this, 'droppable');
    if(
    inst.options.greedy
    && !inst.options.disabled
    && inst.options.scope == draggable.options.scope
    && inst.accept.call(inst.element[0], (draggable.currentItem || draggable.element))
    && $.ui.intersect(draggable, $.extend(inst, { offset: inst.element.offset() }), inst.options.tolerance)
    ) { childrenIntersection = true; return false; }
    });
    if(childrenIntersection) return false;

    if(this.accept.call(this.element[0],(draggable.currentItem || draggable.element))) {
    if(this.options.activeClass) this.element.removeClass(this.options.activeClass);
    if(this.options.hoverClass) this.element.removeClass(this.options.hoverClass);
    this._trigger('drop', event, this.ui(draggable));
    return this.element;
    }

    return false;

    },

    ui: function(c) {
    return {
    draggable: (c.currentItem || c.element),
    helper: c.helper,
    position: c.position,
    offset: c.positionAbs
    };
    }

});

$.extend($.ui.droppable, {
    version: "1.8.24"
});

$.ui.intersect = function(draggable, droppable, toleranceMode) {

    if (!droppable.offset) return false;

    var x1 = (draggable.positionAbs || draggable.position.absolute).left, x2 = x1 + draggable.helperProportions.width,
    y1 = (draggable.positionAbs || draggable.position.absolute).top, y2 = y1 + draggable.helperProportions.height;
    var l = droppable.offset.left, r = l + droppable.proportions.width,
    t = droppable.offset.top, b = t + droppable.proportions.height;

    switch (toleranceMode) {
    case 'fit':
    return (l <= x1 && x2 <= r
    && t <= y1 && y2 <= b);
    break;
    case 'intersect':
    return (l < x1 + (draggable.helperProportions.width / 2) // Right Half
    && x2 - (draggable.helperProportions.width / 2) < r // Left Half
    && t < y1 + (draggable.helperProportions.height / 2) // Bottom Half
    && y2 - (draggable.helperProportions.height / 2) < b ); // Top Half
    break;
    case 'pointer':
    var draggableLeft = ((draggable.positionAbs || draggable.position.absolute).left + (draggable.clickOffset || draggable.offset.click).left),
    draggableTop = ((draggable.positionAbs || draggable.position.absolute).top + (draggable.clickOffset || draggable.offset.click).top),
    isOver = $.ui.isOver(draggableTop, draggableLeft, t, l, droppable.proportions.height, droppable.proportions.width);
    return isOver;
    break;
    case 'touch':
    return (
    (y1 >= t && y1 <= b) ||    // Top edge touching
    (y2 >= t && y2 <= b) ||    // Bottom edge touching
    (y1 < t && y2 > b)    // Surrounded vertically
    ) && (
    (x1 >= l && x1 <= r) ||    // Left edge touching
    (x2 >= l && x2 <= r) ||    // Right edge touching
    (x1 < l && x2 > r)    // Surrounded horizontally
    );
    break;
    default:
    return false;
    break;
    }

};

/*
    This manager tracks offsets of draggables and droppables
*/
$.ui.ddmanager = {
    current: null,
    droppables: { 'default': [] },
    prepareOffsets: function(t, event) {

    var m = $.ui.ddmanager.droppables[t.options.scope] || [];
    var type = event ? event.type : null; // workaround for #2317
    var list = (t.currentItem || t.element).find(":data(droppable)").andSelf();

    droppablesLoop: for (var i = 0; i < m.length; i++) {

    if(m[i].options.disabled || (t && !m[i].accept.call(m[i].element[0],(t.currentItem || t.element)))) continue;    //No disabled and non-accepted
    for (var j=0; j < list.length; j++) { if(list[j] == m[i].element[0]) { m[i].proportions.height = 0; continue droppablesLoop; } }; //Filter out elements in the current dragged item
    m[i].visible = m[i].element.css("display") != "none"; if(!m[i].visible) continue;     //If the element is not visible, continue

    if(type == "mousedown") m[i]._activate.call(m[i], event); //Activate the droppable if used directly from draggables

    m[i].offset = m[i].element.offset();
    m[i].proportions = { width: m[i].element[0].offsetWidth, height: m[i].element[0].offsetHeight };

    }

    },
    drop: function(draggable, event) {

    var dropped = false;
    $.each($.ui.ddmanager.droppables[draggable.options.scope] || [], function() {

    if(!this.options) return;
    if (!this.options.disabled && this.visible && $.ui.intersect(draggable, this, this.options.tolerance))
    dropped = this._drop.call(this, event) || dropped;

    if (!this.options.disabled && this.visible && this.accept.call(this.element[0],(draggable.currentItem || draggable.element))) {
    this.isout = 1; this.isover = 0;
    this._deactivate.call(this, event);
    }

    });
    return dropped;

    },
    dragStart: function( draggable, event ) {
    //Listen for scrolling so that if the dragging causes scrolling the position of the droppables can be recalculated (see #5003)
    draggable.element.parents( ":not(body,html)" ).bind( "scroll.droppable", function() {
    if( !draggable.options.refreshPositions ) $.ui.ddmanager.prepareOffsets( draggable, event );
    });
    },
    drag: function(draggable, event) {

    //If you have a highly dynamic page, you might try this option. It renders positions every time you move the mouse.
    if(draggable.options.refreshPositions) $.ui.ddmanager.prepareOffsets(draggable, event);

    //Run through all droppables and check their positions based on specific tolerance options
    $.each($.ui.ddmanager.droppables[draggable.options.scope] || [], function() {

    if(this.options.disabled || this.greedyChild || !this.visible) return;
    var intersects = $.ui.intersect(draggable, this, this.options.tolerance);

    var c = !intersects && this.isover == 1 ? 'isout' : (intersects && this.isover == 0 ? 'isover' : null);
    if(!c) return;

    var parentInstance;
    if (this.options.greedy) {
    // find droppable parents with same scope
    var scope = this.options.scope;
    var parent = this.element.parents(':data(droppable)').filter(function () {
    return $.data(this, 'droppable').options.scope === scope;
    });

    if (parent.length) {
    parentInstance = $.data(parent[0], 'droppable');
    parentInstance.greedyChild = (c == 'isover' ? 1 : 0);
    }
    }

    // we just moved into a greedy child
    if (parentInstance && c == 'isover') {
    parentInstance['isover'] = 0;
    parentInstance['isout'] = 1;
    parentInstance._out.call(parentInstance, event);
    }

    this[c] = 1; this[c == 'isout' ? 'isover' : 'isout'] = 0;
    this[c == "isover" ? "_over" : "_out"].call(this, event);

    // we just moved out of a greedy child
    if (parentInstance && c == 'isout') {
    parentInstance['isout'] = 0;
    parentInstance['isover'] = 1;
    parentInstance._over.call(parentInstance, event);
    }
    });

    },
    dragStop: function( draggable, event ) {
    draggable.element.parents( ":not(body,html)" ).unbind( "scroll.droppable" );
    //Call prepareOffsets one final time since IE does not fire return scroll events when overflow was caused by drag (see #5003)
    if( !draggable.options.refreshPositions ) $.ui.ddmanager.prepareOffsets( draggable, event );
    }
};

})(jQuery);

/*!
 * jQuery UI Sortable 1.8.24
 *
 * Copyright 2012, AUTHORS.txt (http://jqueryui.com/about)
 * Dual licensed under the MIT or GPL Version 2 licenses.
 * http://jquery.org/license
 *
 * http://docs.jquery.com/UI/Sortables
 *
 * Depends:
 *    jquery.ui.core.js
 *    jquery.ui.mouse.js
 *    jquery.ui.widget.js
 */
(function( $, undefined ) {

$.widget("ui.sortable", $.ui.mouse, {
    widgetEventPrefix: "sort",
    ready: false,
    options: {
    appendTo: "parent",
    axis: false,
    connectWith: false,
    containment: false,
    cursor: 'auto',
    cursorAt: false,
    dropOnEmpty: true,
    forcePlaceholderSize: false,
    forceHelperSize: false,
    grid: false,
    handle: false,
    helper: "original",
    items: '> *',
    opacity: false,
    placeholder: false,
    revert: false,
    scroll: true,
    scrollSensitivity: 20,
    scrollSpeed: 20,
    scope: "default",
    tolerance: "intersect",
    zIndex: 1000
    },
    _create: function() {

    var o = this.options;
    this.containerCache = {};
    this.element.addClass("ui-sortable");

    //Get the items
    this.refresh();

    //Let's determine if the items are being displayed horizontally
    this.floating = this.items.length ? o.axis === 'x' || (/left|right/).test(this.items[0].item.css('float')) || (/inline|table-cell/).test(this.items[0].item.css('display')) : false;

    //Let's determine the parent's offset
    this.offset = this.element.offset();

    //Initialize mouse events for interaction
    this._mouseInit();

    //We're ready to go
    this.ready = true

    },

    destroy: function() {
    $.Widget.prototype.destroy.call( this );
    this.element
    .removeClass("ui-sortable ui-sortable-disabled");
    this._mouseDestroy();

    for ( var i = this.items.length - 1; i >= 0; i-- )
    this.items[i].item.removeData(this.widgetName + "-item");

    return this;
    },

    _setOption: function(key, value){
    if ( key === "disabled" ) {
    this.options[ key ] = value;

    this.widget()
    [ value ? "addClass" : "removeClass"]( "ui-sortable-disabled" );
    } else {
    // Don't call widget base _setOption for disable as it adds ui-state-disabled class
    $.Widget.prototype._setOption.apply(this, arguments);
    }
    },

    _mouseCapture: function(event, overrideHandle) {
    var that = this;

    if (this.reverting) {
    return false;
    }

    if(this.options.disabled || this.options.type == 'static') return false;

    //We have to refresh the items data once first
    this._refreshItems(event);

    //Find out if the clicked node (or one of its parents) is a actual item in this.items
    var currentItem = null, self = this, nodes = $(event.target).parents().each(function() {
    if($.data(this, that.widgetName + '-item') == self) {
    currentItem = $(this);
    return false;
    }
    });
    if($.data(event.target, that.widgetName + '-item') == self) currentItem = $(event.target);

    if(!currentItem) return false;
    if(this.options.handle && !overrideHandle) {
    var validHandle = false;

    $(this.options.handle, currentItem).find("*").andSelf().each(function() { if(this == event.target) validHandle = true; });
    if(!validHandle) return false;
    }

    this.currentItem = currentItem;
    this._removeCurrentsFromItems();
    return true;

    },

    _mouseStart: function(event, overrideHandle, noActivation) {

    var o = this.options, self = this;
    this.currentContainer = this;

    //We only need to call refreshPositions, because the refreshItems call has been moved to mouseCapture
    this.refreshPositions();

    //Create and append the visible helper
    this.helper = this._createHelper(event);

    //Cache the helper size
    this._cacheHelperProportions();

    /*
     * - Position generation -
     * This block generates everything position related - it's the core of draggables.
     */

    //Cache the margins of the original element
    this._cacheMargins();

    //Get the next scrolling parent
    this.scrollParent = this.helper.scrollParent();

    //The element's absolute position on the page minus margins
    this.offset = this.currentItem.offset();
    this.offset = {
    top: this.offset.top - this.margins.top,
    left: this.offset.left - this.margins.left
    };

    $.extend(this.offset, {
    click: { //Where the click happened, relative to the element
    left: event.pageX - this.offset.left,
    top: event.pageY - this.offset.top
    },
    parent: this._getParentOffset(),
    relative: this._getRelativeOffset() //This is a relative to absolute position minus the actual position calculation - only used for relative positioned helper
    });

    // Only after we got the offset, we can change the helper's position to absolute
    // TODO: Still need to figure out a way to make relative sorting possible
    this.helper.css("position", "absolute");
    this.cssPosition = this.helper.css("position");

    //Generate the original position
    this.originalPosition = this._generatePosition(event);
    this.originalPageX = event.pageX;
    this.originalPageY = event.pageY;

    //Adjust the mouse offset relative to the helper if 'cursorAt' is supplied
    (o.cursorAt && this._adjustOffsetFromHelper(o.cursorAt));

    //Cache the former DOM position
    this.domPosition = { prev: this.currentItem.prev()[0], parent: this.currentItem.parent()[0] };

    //If the helper is not the original, hide the original so it's not playing any role during the drag, won't cause anything bad this way
    if(this.helper[0] != this.currentItem[0]) {
    this.currentItem.hide();
    }

    //Create the placeholder
    this._createPlaceholder();

    //Set a containment if given in the options
    if(o.containment)
    this._setContainment();

    if(o.cursor) { // cursor option
    if ($('body').css("cursor")) this._storedCursor = $('body').css("cursor");
    $('body').css("cursor", o.cursor);
    }

    if(o.opacity) { // opacity option
    if (this.helper.css("opacity")) this._storedOpacity = this.helper.css("opacity");
    this.helper.css("opacity", o.opacity);
    }

    if(o.zIndex) { // zIndex option
    if (this.helper.css("zIndex")) this._storedZIndex = this.helper.css("zIndex");
    this.helper.css("zIndex", o.zIndex);
    }

    //Prepare scrolling
    if(this.scrollParent[0] != document && this.scrollParent[0].tagName != 'HTML')
    this.overflowOffset = this.scrollParent.offset();

    //Call callbacks
    this._trigger("start", event, this._uiHash());

    //Recache the helper size
    if(!this._preserveHelperProportions)
    this._cacheHelperProportions();


    //Post 'activate' events to possible containers
    if(!noActivation) {
     for (var i = this.containers.length - 1; i >= 0; i--) { this.containers[i]._trigger("activate", event, self._uiHash(this)); }
    }

    //Prepare possible droppables
    if($.ui.ddmanager)
    $.ui.ddmanager.current = this;

    if ($.ui.ddmanager && !o.dropBehaviour)
    $.ui.ddmanager.prepareOffsets(this, event);

    this.dragging = true;

    this.helper.addClass("ui-sortable-helper");
    this._mouseDrag(event); //Execute the drag once - this causes the helper not to be visible before getting its correct position
    return true;

    },

    _mouseDrag: function(event) {

    //Compute the helpers position
    this.position = this._generatePosition(event);
    this.positionAbs = this._convertPositionTo("absolute");

    if (!this.lastPositionAbs) {
    this.lastPositionAbs = this.positionAbs;
    }

    //Do scrolling
    if(this.options.scroll) {
    var o = this.options, scrolled = false;
    if(this.scrollParent[0] != document && this.scrollParent[0].tagName != 'HTML') {

    if((this.overflowOffset.top + this.scrollParent[0].offsetHeight) - event.pageY < o.scrollSensitivity)
    this.scrollParent[0].scrollTop = scrolled = this.scrollParent[0].scrollTop + o.scrollSpeed;
    else if(event.pageY - this.overflowOffset.top < o.scrollSensitivity)
    this.scrollParent[0].scrollTop = scrolled = this.scrollParent[0].scrollTop - o.scrollSpeed;

    if((this.overflowOffset.left + this.scrollParent[0].offsetWidth) - event.pageX < o.scrollSensitivity)
    this.scrollParent[0].scrollLeft = scrolled = this.scrollParent[0].scrollLeft + o.scrollSpeed;
    else if(event.pageX - this.overflowOffset.left < o.scrollSensitivity)
    this.scrollParent[0].scrollLeft = scrolled = this.scrollParent[0].scrollLeft - o.scrollSpeed;

    } else {

    if(event.pageY - $(document).scrollTop() < o.scrollSensitivity)
    scrolled = $(document).scrollTop($(document).scrollTop() - o.scrollSpeed);
    else if($(window).height() - (event.pageY - $(document).scrollTop()) < o.scrollSensitivity)
    scrolled = $(document).scrollTop($(document).scrollTop() + o.scrollSpeed);

    if(event.pageX - $(document).scrollLeft() < o.scrollSensitivity)
    scrolled = $(document).scrollLeft($(document).scrollLeft() - o.scrollSpeed);
    else if($(window).width() - (event.pageX - $(document).scrollLeft()) < o.scrollSensitivity)
    scrolled = $(document).scrollLeft($(document).scrollLeft() + o.scrollSpeed);

    }

    if(scrolled !== false && $.ui.ddmanager && !o.dropBehaviour)
    $.ui.ddmanager.prepareOffsets(this, event);
    }

    //Regenerate the absolute position used for position checks
    this.positionAbs = this._convertPositionTo("absolute");

    //Set the helper position
    if(!this.options.axis || this.options.axis != "y") this.helper[0].style.left = this.position.left+'px';
    if(!this.options.axis || this.options.axis != "x") this.helper[0].style.top = this.position.top+'px';

    //Rearrange
    for (var i = this.items.length - 1; i >= 0; i--) {

    //Cache variables and intersection, continue if no intersection
    var item = this.items[i], itemElement = item.item[0], intersection = this._intersectsWithPointer(item);
    if (!intersection) continue;

    // Only put the placeholder inside the current Container, skip all
    // items form other containers. This works because when moving
    // an item from one container to another the
    // currentContainer is switched before the placeholder is moved.
    //
    // Without this moving items in "sub-sortables" can cause the placeholder to jitter
    // beetween the outer and inner container.
    if (item.instance !== this.currentContainer) continue;

    if (itemElement != this.currentItem[0] //cannot intersect with itself
    &&    this.placeholder[intersection == 1 ? "next" : "prev"]()[0] != itemElement //no useless actions that have been done before
    &&    !$.ui.contains(this.placeholder[0], itemElement) //no action if the item moved is the parent of the item checked
    && (this.options.type == 'semi-dynamic' ? !$.ui.contains(this.element[0], itemElement) : true)
    //&& itemElement.parentNode == this.placeholder[0].parentNode // only rearrange items within the same container
    ) {

    this.direction = intersection == 1 ? "down" : "up";

    if (this.options.tolerance == "pointer" || this._intersectsWithSides(item)) {
    this._rearrange(event, item);
    } else {
    break;
    }

    this._trigger("change", event, this._uiHash());
    break;
    }
    }

    //Post events to containers
    this._contactContainers(event);

    //Interconnect with droppables
    if($.ui.ddmanager) $.ui.ddmanager.drag(this, event);

    //Call callbacks
    this._trigger('sort', event, this._uiHash());

    this.lastPositionAbs = this.positionAbs;
    return false;

    },

    _mouseStop: function(event, noPropagation) {

    if(!event) return;

    //If we are using droppables, inform the manager about the drop
    if ($.ui.ddmanager && !this.options.dropBehaviour)
    $.ui.ddmanager.drop(this, event);

    if(this.options.revert) {
    var self = this;
    var cur = self.placeholder.offset();

    self.reverting = true;

    $(this.helper).animate({
    left: cur.left - this.offset.parent.left - self.margins.left + (this.offsetParent[0] == document.body ? 0 : this.offsetParent[0].scrollLeft),
    top: cur.top - this.offset.parent.top - self.margins.top + (this.offsetParent[0] == document.body ? 0 : this.offsetParent[0].scrollTop)
    }, parseInt(this.options.revert, 10) || 500, function() {
    self._clear(event);
    });
    } else {
    this._clear(event, noPropagation);
    }

    return false;

    },

    cancel: function() {

    var self = this;

    if(this.dragging) {

    this._mouseUp({ target: null });

    if(this.options.helper == "original")
    this.currentItem.css(this._storedCSS).removeClass("ui-sortable-helper");
    else
    this.currentItem.show();

    //Post deactivating events to containers
    for (var i = this.containers.length - 1; i >= 0; i--){
    this.containers[i]._trigger("deactivate", null, self._uiHash(this));
    if(this.containers[i].containerCache.over) {
    this.containers[i]._trigger("out", null, self._uiHash(this));
    this.containers[i].containerCache.over = 0;
    }
    }

    }

    if (this.placeholder) {
    //$(this.placeholder[0]).remove(); would have been the jQuery way - unfortunately, it unbinds ALL events from the original node!
    if(this.placeholder[0].parentNode) this.placeholder[0].parentNode.removeChild(this.placeholder[0]);
    if(this.options.helper != "original" && this.helper && this.helper[0].parentNode) this.helper.remove();

    $.extend(this, {
    helper: null,
    dragging: false,
    reverting: false,
    _noFinalSort: null
    });

    if(this.domPosition.prev) {
    $(this.domPosition.prev).after(this.currentItem);
    } else {
    $(this.domPosition.parent).prepend(this.currentItem);
    }
    }

    return this;

    },

    serialize: function(o) {

    var items = this._getItemsAsjQuery(o && o.connected);
    var str = []; o = o || {};

    $(items).each(function() {
    var res = ($(o.item || this).attr(o.attribute || 'id') || '').match(o.expression || (/(.+)[-=_](.+)/));
    if(res) str.push((o.key || res[1]+'[]')+'='+(o.key && o.expression ? res[1] : res[2]));
    });

    if(!str.length && o.key) {
    str.push(o.key + '=');
    }

    return str.join('&');

    },

    toArray: function(o) {

    var items = this._getItemsAsjQuery(o && o.connected);
    var ret = []; o = o || {};

    items.each(function() { ret.push($(o.item || this).attr(o.attribute || 'id') || ''); });
    return ret;

    },

    /* Be careful with the following core functions */
    _intersectsWith: function(item) {

    var x1 = this.positionAbs.left,
    x2 = x1 + this.helperProportions.width,
    y1 = this.positionAbs.top,
    y2 = y1 + this.helperProportions.height;

    var l = item.left,
    r = l + item.width,
    t = item.top,
    b = t + item.height;

    var dyClick = this.offset.click.top,
    dxClick = this.offset.click.left;

    var isOverElement = (y1 + dyClick) > t && (y1 + dyClick) < b && (x1 + dxClick) > l && (x1 + dxClick) < r;

    if(       this.options.tolerance == "pointer"
    || this.options.forcePointerForContainers
    || (this.options.tolerance != "pointer" && this.helperProportions[this.floating ? 'width' : 'height'] > item[this.floating ? 'width' : 'height'])
    ) {
    return isOverElement;
    } else {

    return (l < x1 + (this.helperProportions.width / 2) // Right Half
    && x2 - (this.helperProportions.width / 2) < r // Left Half
    && t < y1 + (this.helperProportions.height / 2) // Bottom Half
    && y2 - (this.helperProportions.height / 2) < b ); // Top Half

    }
    },

    _intersectsWithPointer: function(item) {

    var isOverElementHeight = (this.options.axis === 'x') || $.ui.isOverAxis(this.positionAbs.top + this.offset.click.top, item.top, item.height),
    isOverElementWidth = (this.options.axis === 'y') || $.ui.isOverAxis(this.positionAbs.left + this.offset.click.left, item.left, item.width),
    isOverElement = isOverElementHeight && isOverElementWidth,
    verticalDirection = this._getDragVerticalDirection(),
    horizontalDirection = this._getDragHorizontalDirection();

    if (!isOverElement)
    return false;

    return this.floating ?
    ( ((horizontalDirection && horizontalDirection == "right") || verticalDirection == "down") ? 2 : 1 )
    : ( verticalDirection && (verticalDirection == "down" ? 2 : 1) );

    },

    _intersectsWithSides: function(item) {

    var isOverBottomHalf = $.ui.isOverAxis(this.positionAbs.top + this.offset.click.top, item.top + (item.height/2), item.height),
    isOverRightHalf = $.ui.isOverAxis(this.positionAbs.left + this.offset.click.left, item.left + (item.width/2), item.width),
    verticalDirection = this._getDragVerticalDirection(),
    horizontalDirection = this._getDragHorizontalDirection();

    if (this.floating && horizontalDirection) {
    return ((horizontalDirection == "right" && isOverRightHalf) || (horizontalDirection == "left" && !isOverRightHalf));
    } else {
    return verticalDirection && ((verticalDirection == "down" && isOverBottomHalf) || (verticalDirection == "up" && !isOverBottomHalf));
    }

    },

    _getDragVerticalDirection: function() {
    var delta = this.positionAbs.top - this.lastPositionAbs.top;
    return delta != 0 && (delta > 0 ? "down" : "up");
    },

    _getDragHorizontalDirection: function() {
    var delta = this.positionAbs.left - this.lastPositionAbs.left;
    return delta != 0 && (delta > 0 ? "right" : "left");
    },

    refresh: function(event) {
    this._refreshItems(event);
    this.refreshPositions();
    return this;
    },

    _connectWith: function() {
    var options = this.options;
    return options.connectWith.constructor == String
    ? [options.connectWith]
    : options.connectWith;
    },

    _getItemsAsjQuery: function(connected) {

    var self = this;
    var items = [];
    var queries = [];
    var connectWith = this._connectWith();

    if(connectWith && connected) {
    for (var i = connectWith.length - 1; i >= 0; i--){
    var cur = $(connectWith[i]);
    for (var j = cur.length - 1; j >= 0; j--){
    var inst = $.data(cur[j], this.widgetName);
    if(inst && inst != this && !inst.options.disabled) {
    queries.push([$.isFunction(inst.options.items) ? inst.options.items.call(inst.element) : $(inst.options.items, inst.element).not(".ui-sortable-helper").not('.ui-sortable-placeholder'), inst]);
    }
    };
    };
    }

    queries.push([$.isFunction(this.options.items) ? this.options.items.call(this.element, null, { options: this.options, item: this.currentItem }) : $(this.options.items, this.element).not(".ui-sortable-helper").not('.ui-sortable-placeholder'), this]);

    for (var i = queries.length - 1; i >= 0; i--){
    queries[i][0].each(function() {
    items.push(this);
    });
    };

    return $(items);

    },

    _removeCurrentsFromItems: function() {

    var list = this.currentItem.find(":data(" + this.widgetName + "-item)");

    for (var i=0; i < this.items.length; i++) {

    for (var j=0; j < list.length; j++) {
    if(list[j] == this.items[i].item[0])
    this.items.splice(i,1);
    };

    };

    },

    _refreshItems: function(event) {

    this.items = [];
    this.containers = [this];
    var items = this.items;
    var self = this;
    var queries = [[$.isFunction(this.options.items) ? this.options.items.call(this.element[0], event, { item: this.currentItem }) : $(this.options.items, this.element), this]];
    var connectWith = this._connectWith();

    if(connectWith && this.ready) { //Shouldn't be run the first time through due to massive slow-down
    for (var i = connectWith.length - 1; i >= 0; i--){
    var cur = $(connectWith[i]);
    for (var j = cur.length - 1; j >= 0; j--){
    var inst = $.data(cur[j], this.widgetName);
    if(inst && inst != this && !inst.options.disabled) {
    queries.push([$.isFunction(inst.options.items) ? inst.options.items.call(inst.element[0], event, { item: this.currentItem }) : $(inst.options.items, inst.element), inst]);
    this.containers.push(inst);
    }
    };
    };
    }

    for (var i = queries.length - 1; i >= 0; i--) {
    var targetData = queries[i][1];
    var _queries = queries[i][0];

    for (var j=0, queriesLength = _queries.length; j < queriesLength; j++) {
    var item = $(_queries[j]);

    item.data(this.widgetName + '-item', targetData); // Data for target checking (mouse manager)

    items.push({
    item: item,
    instance: targetData,
    width: 0, height: 0,
    left: 0, top: 0
    });
    };
    };

    },

    refreshPositions: function(fast) {

    //This has to be redone because due to the item being moved out/into the offsetParent, the offsetParent's position will change
    if(this.offsetParent && this.helper) {
    this.offset.parent = this._getParentOffset();
    }

    for (var i = this.items.length - 1; i >= 0; i--){
    var item = this.items[i];

    //We ignore calculating positions of all connected containers when we're not over them
    if(item.instance != this.currentContainer && this.currentContainer && item.item[0] != this.currentItem[0])
    continue;

    var t = this.options.toleranceElement ? $(this.options.toleranceElement, item.item) : item.item;

    if (!fast) {
    item.width = t.outerWidth();
    item.height = t.outerHeight();
    }

    var p = t.offset();
    item.left = p.left;
    item.top = p.top;
    };

    if(this.options.custom && this.options.custom.refreshContainers) {
    this.options.custom.refreshContainers.call(this);
    } else {
    for (var i = this.containers.length - 1; i >= 0; i--){
    var p = this.containers[i].element.offset();
    this.containers[i].containerCache.left = p.left;
    this.containers[i].containerCache.top = p.top;
    this.containers[i].containerCache.width    = this.containers[i].element.outerWidth();
    this.containers[i].containerCache.height = this.containers[i].element.outerHeight();
    };
    }

    return this;
    },

    _createPlaceholder: function(that) {

    var self = that || this, o = self.options;

    if(!o.placeholder || o.placeholder.constructor == String) {
    var className = o.placeholder;
    o.placeholder = {
    element: function() {

    var el = $(document.createElement(self.currentItem[0].nodeName))
    .addClass(className || self.currentItem[0].className+" ui-sortable-placeholder")
    .removeClass("ui-sortable-helper")[0];

    if(!className)
    el.style.visibility = "hidden";

    return el;
    },
    update: function(container, p) {

    // 1. If a className is set as 'placeholder option, we don't force sizes - the class is responsible for that
    // 2. The option 'forcePlaceholderSize can be enabled to force it even if a class name is specified
    if(className && !o.forcePlaceholderSize) return;

    //If the element doesn't have a actual height by itself (without styles coming from a stylesheet), it receives the inline height from the dragged item
    if(!p.height()) { p.height(self.currentItem.innerHeight() - parseInt(self.currentItem.css('paddingTop')||0, 10) - parseInt(self.currentItem.css('paddingBottom')||0, 10)); };
    if(!p.width()) { p.width(self.currentItem.innerWidth() - parseInt(self.currentItem.css('paddingLeft')||0, 10) - parseInt(self.currentItem.css('paddingRight')||0, 10)); };
    }
    };
    }

    //Create the placeholder
    self.placeholder = $(o.placeholder.element.call(self.element, self.currentItem));

    //Append it after the actual current item
    self.currentItem.after(self.placeholder);

    //Update the size of the placeholder (TODO: Logic to fuzzy, see line 316/317)
    o.placeholder.update(self, self.placeholder);

    },

    _contactContainers: function(event) {

    // get innermost container that intersects with item
    var innermostContainer = null, innermostIndex = null;


    for (var i = this.containers.length - 1; i >= 0; i--){

    // never consider a container that's located within the item itself
    if($.ui.contains(this.currentItem[0], this.containers[i].element[0]))
    continue;

    if(this._intersectsWith(this.containers[i].containerCache)) {

    // if we've already found a container and it's more "inner" than this, then continue
    if(innermostContainer && $.ui.contains(this.containers[i].element[0], innermostContainer.element[0]))
    continue;

    innermostContainer = this.containers[i];
    innermostIndex = i;

    } else {
    // container doesn't intersect. trigger "out" event if necessary
    if(this.containers[i].containerCache.over) {
    this.containers[i]._trigger("out", event, this._uiHash(this));
    this.containers[i].containerCache.over = 0;
    }
    }

    }

    // if no intersecting containers found, return
    if(!innermostContainer) return;

    // move the item into the container if it's not there already
    if(this.containers.length === 1) {
    this.containers[innermostIndex]._trigger("over", event, this._uiHash(this));
    this.containers[innermostIndex].containerCache.over = 1;
    } else if(this.currentContainer != this.containers[innermostIndex]) {

    //When entering a new container, we will find the item with the least distance and append our item near it
    var dist = 10000; var itemWithLeastDistance = null; var base = this.positionAbs[this.containers[innermostIndex].floating ? 'left' : 'top'];
    for (var j = this.items.length - 1; j >= 0; j--) {
    if(!$.ui.contains(this.containers[innermostIndex].element[0], this.items[j].item[0])) continue;
    var cur = this.containers[innermostIndex].floating ? this.items[j].item.offset().left : this.items[j].item.offset().top;
    if(Math.abs(cur - base) < dist) {
    dist = Math.abs(cur - base); itemWithLeastDistance = this.items[j];
    this.direction = (cur - base > 0) ? 'down' : 'up';
    }
    }

    if(!itemWithLeastDistance && !this.options.dropOnEmpty) //Check if dropOnEmpty is enabled
    return;

    this.currentContainer = this.containers[innermostIndex];
    itemWithLeastDistance ? this._rearrange(event, itemWithLeastDistance, null, true) : this._rearrange(event, null, this.containers[innermostIndex].element, true);
    this._trigger("change", event, this._uiHash());
    this.containers[innermostIndex]._trigger("change", event, this._uiHash(this));

    //Update the placeholder
    this.options.placeholder.update(this.currentContainer, this.placeholder);

    this.containers[innermostIndex]._trigger("over", event, this._uiHash(this));
    this.containers[innermostIndex].containerCache.over = 1;
    }


    },

    _createHelper: function(event) {

    var o = this.options;
    var helper = $.isFunction(o.helper) ? $(o.helper.apply(this.element[0], [event, this.currentItem])) : (o.helper == 'clone' ? this.currentItem.clone() : this.currentItem);

    if(!helper.parents('body').length) //Add the helper to the DOM if that didn't happen already
    $(o.appendTo != 'parent' ? o.appendTo : this.currentItem[0].parentNode)[0].appendChild(helper[0]);

    if(helper[0] == this.currentItem[0])
    this._storedCSS = { width: this.currentItem[0].style.width, height: this.currentItem[0].style.height, position: this.currentItem.css("position"), top: this.currentItem.css("top"), left: this.currentItem.css("left") };

    if(helper[0].style.width == '' || o.forceHelperSize) helper.width(this.currentItem.width());
    if(helper[0].style.height == '' || o.forceHelperSize) helper.height(this.currentItem.height());

    return helper;

    },

    _adjustOffsetFromHelper: function(obj) {
    if (typeof obj == 'string') {
    obj = obj.split(' ');
    }
    if ($.isArray(obj)) {
    obj = {left: +obj[0], top: +obj[1] || 0};
    }
    if ('left' in obj) {
    this.offset.click.left = obj.left + this.margins.left;
    }
    if ('right' in obj) {
    this.offset.click.left = this.helperProportions.width - obj.right + this.margins.left;
    }
    if ('top' in obj) {
    this.offset.click.top = obj.top + this.margins.top;
    }
    if ('bottom' in obj) {
    this.offset.click.top = this.helperProportions.height - obj.bottom + this.margins.top;
    }
    },

    _getParentOffset: function() {


    //Get the offsetParent and cache its position
    this.offsetParent = this.helper.offsetParent();
    var po = this.offsetParent.offset();

    // This is a special case where we need to modify a offset calculated on start, since the following happened:
    // 1. The position of the helper is absolute, so it's position is calculated based on the next positioned parent
    // 2. The actual offset parent is a child of the scroll parent, and the scroll parent isn't the document, which means that
    //    the scroll is included in the initial calculation of the offset of the parent, and never recalculated upon drag
    if(this.cssPosition == 'absolute' && this.scrollParent[0] != document && $.ui.contains(this.scrollParent[0], this.offsetParent[0])) {
    po.left += this.scrollParent.scrollLeft();
    po.top += this.scrollParent.scrollTop();
    }

    if((this.offsetParent[0] == document.body) //This needs to be actually done for all browsers, since pageX/pageY includes this information
    || (this.offsetParent[0].tagName && this.offsetParent[0].tagName.toLowerCase() == 'html' && $.browser.msie)) //Ugly IE fix
    po = { top: 0, left: 0 };

    return {
    top: po.top + (parseInt(this.offsetParent.css("borderTopWidth"),10) || 0),
    left: po.left + (parseInt(this.offsetParent.css("borderLeftWidth"),10) || 0)
    };

    },

    _getRelativeOffset: function() {

    if(this.cssPosition == "relative") {
    var p = this.currentItem.position();
    return {
    top: p.top - (parseInt(this.helper.css("top"),10) || 0) + this.scrollParent.scrollTop(),
    left: p.left - (parseInt(this.helper.css("left"),10) || 0) + this.scrollParent.scrollLeft()
    };
    } else {
    return { top: 0, left: 0 };
    }

    },

    _cacheMargins: function() {
    this.margins = {
    left: (parseInt(this.currentItem.css("marginLeft"),10) || 0),
    top: (parseInt(this.currentItem.css("marginTop"),10) || 0)
    };
    },

    _cacheHelperProportions: function() {
    this.helperProportions = {
    width: this.helper.outerWidth(),
    height: this.helper.outerHeight()
    };
    },

    _setContainment: function() {

    var o = this.options;
    if(o.containment == 'parent') o.containment = this.helper[0].parentNode;
    if(o.containment == 'document' || o.containment == 'window') this.containment = [
    0 - this.offset.relative.left - this.offset.parent.left,
    0 - this.offset.relative.top - this.offset.parent.top,
    $(o.containment == 'document' ? document : window).width() - this.helperProportions.width - this.margins.left,
    ($(o.containment == 'document' ? document : window).height() || document.body.parentNode.scrollHeight) - this.helperProportions.height - this.margins.top
    ];

    if(!(/^(document|window|parent)$/).test(o.containment)) {
    var ce = $(o.containment)[0];
    var co = $(o.containment).offset();
    var over = ($(ce).css("overflow") != 'hidden');

    this.containment = [
    co.left + (parseInt($(ce).css("borderLeftWidth"),10) || 0) + (parseInt($(ce).css("paddingLeft"),10) || 0) - this.margins.left,
    co.top + (parseInt($(ce).css("borderTopWidth"),10) || 0) + (parseInt($(ce).css("paddingTop"),10) || 0) - this.margins.top,
    co.left+(over ? Math.max(ce.scrollWidth,ce.offsetWidth) : ce.offsetWidth) - (parseInt($(ce).css("borderLeftWidth"),10) || 0) - (parseInt($(ce).css("paddingRight"),10) || 0) - this.helperProportions.width - this.margins.left,
    co.top+(over ? Math.max(ce.scrollHeight,ce.offsetHeight) : ce.offsetHeight) - (parseInt($(ce).css("borderTopWidth"),10) || 0) - (parseInt($(ce).css("paddingBottom"),10) || 0) - this.helperProportions.height - this.margins.top
    ];
    }

    },

    _convertPositionTo: function(d, pos) {

    if(!pos) pos = this.position;
    var mod = d == "absolute" ? 1 : -1;
    var o = this.options, scroll = this.cssPosition == 'absolute' && !(this.scrollParent[0] != document && $.ui.contains(this.scrollParent[0], this.offsetParent[0])) ? this.offsetParent : this.scrollParent, scrollIsRootNode = (/(html|body)/i).test(scroll[0].tagName);

    return {
    top: (
    pos.top    // The absolute mouse position
    + this.offset.relative.top * mod    // Only for relative positioned nodes: Relative offset from element to offset parent
    + this.offset.parent.top * mod    // The offsetParent's offset without borders (offset + border)
    - ($.browser.safari && this.cssPosition == 'fixed' ? 0 : ( this.cssPosition == 'fixed' ? -this.scrollParent.scrollTop() : ( scrollIsRootNode ? 0 : scroll.scrollTop() ) ) * mod)
    ),
    left: (
    pos.left    // The absolute mouse position
    + this.offset.relative.left * mod    // Only for relative positioned nodes: Relative offset from element to offset parent
    + this.offset.parent.left * mod    // The offsetParent's offset without borders (offset + border)
    - ($.browser.safari && this.cssPosition == 'fixed' ? 0 : ( this.cssPosition == 'fixed' ? -this.scrollParent.scrollLeft() : scrollIsRootNode ? 0 : scroll.scrollLeft() ) * mod)
    )
    };

    },

    _generatePosition: function(event) {

    var o = this.options, scroll = this.cssPosition == 'absolute' && !(this.scrollParent[0] != document && $.ui.contains(this.scrollParent[0], this.offsetParent[0])) ? this.offsetParent : this.scrollParent, scrollIsRootNode = (/(html|body)/i).test(scroll[0].tagName);

    // This is another very weird special case that only happens for relative elements:
    // 1. If the css position is relative
    // 2. and the scroll parent is the document or similar to the offset parent
    // we have to refresh the relative offset during the scroll so there are no jumps
    if(this.cssPosition == 'relative' && !(this.scrollParent[0] != document && this.scrollParent[0] != this.offsetParent[0])) {
    this.offset.relative = this._getRelativeOffset();
    }

    var pageX = event.pageX;
    var pageY = event.pageY;

    /*
     * - Position constraining -
     * Constrain the position to a mix of grid, containment.
     */

    if(this.originalPosition) { //If we are not dragging yet, we won't check for options

    if(this.containment) {
    if(event.pageX - this.offset.click.left < this.containment[0]) pageX = this.containment[0] + this.offset.click.left;
    if(event.pageY - this.offset.click.top < this.containment[1]) pageY = this.containment[1] + this.offset.click.top;
    if(event.pageX - this.offset.click.left > this.containment[2]) pageX = this.containment[2] + this.offset.click.left;
    if(event.pageY - this.offset.click.top > this.containment[3]) pageY = this.containment[3] + this.offset.click.top;
    }

    if(o.grid) {
    var top = this.originalPageY + Math.round((pageY - this.originalPageY) / o.grid[1]) * o.grid[1];
    pageY = this.containment ? (!(top - this.offset.click.top < this.containment[1] || top - this.offset.click.top > this.containment[3]) ? top : (!(top - this.offset.click.top < this.containment[1]) ? top - o.grid[1] : top + o.grid[1])) : top;

    var left = this.originalPageX + Math.round((pageX - this.originalPageX) / o.grid[0]) * o.grid[0];
    pageX = this.containment ? (!(left - this.offset.click.left < this.containment[0] || left - this.offset.click.left > this.containment[2]) ? left : (!(left - this.offset.click.left < this.containment[0]) ? left - o.grid[0] : left + o.grid[0])) : left;
    }

    }

    return {
    top: (
    pageY    // The absolute mouse position
    - this.offset.click.top    // Click offset (relative to the element)
    - this.offset.relative.top    // Only for relative positioned nodes: Relative offset from element to offset parent
    - this.offset.parent.top    // The offsetParent's offset without borders (offset + border)
    + ($.browser.safari && this.cssPosition == 'fixed' ? 0 : ( this.cssPosition == 'fixed' ? -this.scrollParent.scrollTop() : ( scrollIsRootNode ? 0 : scroll.scrollTop() ) ))
    ),
    left: (
    pageX    // The absolute mouse position
    - this.offset.click.left    // Click offset (relative to the element)
    - this.offset.relative.left    // Only for relative positioned nodes: Relative offset from element to offset parent
    - this.offset.parent.left    // The offsetParent's offset without borders (offset + border)
    + ($.browser.safari && this.cssPosition == 'fixed' ? 0 : ( this.cssPosition == 'fixed' ? -this.scrollParent.scrollLeft() : scrollIsRootNode ? 0 : scroll.scrollLeft() ))
    )
    };

    },

    _rearrange: function(event, i, a, hardRefresh) {

    a ? a[0].appendChild(this.placeholder[0]) : i.item[0].parentNode.insertBefore(this.placeholder[0], (this.direction == 'down' ? i.item[0] : i.item[0].nextSibling));

    //Various things done here to improve the performance:
    // 1. we create a setTimeout, that calls refreshPositions
    // 2. on the instance, we have a counter variable, that get's higher after every append
    // 3. on the local scope, we copy the counter variable, and check in the timeout, if it's still the same
    // 4. this lets only the last addition to the timeout stack through
    this.counter = this.counter ? ++this.counter : 1;
    var self = this, counter = this.counter;

    window.setTimeout(function() {
    if(counter == self.counter) self.refreshPositions(!hardRefresh); //Precompute after each DOM insertion, NOT on mousemove
    },0);

    },

    _clear: function(event, noPropagation) {

    this.reverting = false;
    // We delay all events that have to be triggered to after the point where the placeholder has been removed and
    // everything else normalized again
    var delayedTriggers = [], self = this;

    // We first have to update the dom position of the actual currentItem
    // Note: don't do it if the current item is already removed (by a user), or it gets reappended (see #4088)
    if(!this._noFinalSort && this.currentItem.parent().length) this.placeholder.before(this.currentItem);
    this._noFinalSort = null;

    if(this.helper[0] == this.currentItem[0]) {
    for(var i in this._storedCSS) {
    if(this._storedCSS[i] == 'auto' || this._storedCSS[i] == 'static') this._storedCSS[i] = '';
    }
    this.currentItem.css(this._storedCSS).removeClass("ui-sortable-helper");
    } else {
    this.currentItem.show();
    }

    if(this.fromOutside && !noPropagation) delayedTriggers.push(function(event) { this._trigger("receive", event, this._uiHash(this.fromOutside)); });
    if((this.fromOutside || this.domPosition.prev != this.currentItem.prev().not(".ui-sortable-helper")[0] || this.domPosition.parent != this.currentItem.parent()[0]) && !noPropagation) delayedTriggers.push(function(event) { this._trigger("update", event, this._uiHash()); }); //Trigger update callback if the DOM position has changed

    // Check if the items Container has Changed and trigger appropriate
    // events.
    if (this !== this.currentContainer) {
    if(!noPropagation) {
    delayedTriggers.push(function(event) { this._trigger("remove", event, this._uiHash()); });
    delayedTriggers.push((function(c) { return function(event) { c._trigger("receive", event, this._uiHash(this)); };  }).call(this, this.currentContainer));
    delayedTriggers.push((function(c) { return function(event) { c._trigger("update", event, this._uiHash(this));  }; }).call(this, this.currentContainer));
    }
    }

    //Post events to containers
    for (var i = this.containers.length - 1; i >= 0; i--){
    if(!noPropagation) delayedTriggers.push((function(c) { return function(event) { c._trigger("deactivate", event, this._uiHash(this)); };  }).call(this, this.containers[i]));
    if(this.containers[i].containerCache.over) {
    delayedTriggers.push((function(c) { return function(event) { c._trigger("out", event, this._uiHash(this)); };  }).call(this, this.containers[i]));
    this.containers[i].containerCache.over = 0;
    }
    }

    //Do what was originally in plugins
    if(this._storedCursor) $('body').css("cursor", this._storedCursor); //Reset cursor
    if(this._storedOpacity) this.helper.css("opacity", this._storedOpacity); //Reset opacity
    if(this._storedZIndex) this.helper.css("zIndex", this._storedZIndex == 'auto' ? '' : this._storedZIndex); //Reset z-index

    this.dragging = false;
    if(this.cancelHelperRemoval) {
    if(!noPropagation) {
    this._trigger("beforeStop", event, this._uiHash());
    for (var i=0; i < delayedTriggers.length; i++) { delayedTriggers[i].call(this, event); }; //Trigger all delayed events
    this._trigger("stop", event, this._uiHash());
    }

    this.fromOutside = false;
    return false;
    }

    if(!noPropagation) this._trigger("beforeStop", event, this._uiHash());

    //$(this.placeholder[0]).remove(); would have been the jQuery way - unfortunately, it unbinds ALL events from the original node!
    this.placeholder[0].parentNode.removeChild(this.placeholder[0]);

    if(this.helper[0] != this.currentItem[0]) this.helper.remove(); this.helper = null;

    if(!noPropagation) {
    for (var i=0; i < delayedTriggers.length; i++) { delayedTriggers[i].call(this, event); }; //Trigger all delayed events
    this._trigger("stop", event, this._uiHash());
    }

    this.fromOutside = false;
    return true;

    },

    _trigger: function() {
    if ($.Widget.prototype._trigger.apply(this, arguments) === false) {
    this.cancel();
    }
    },

    _uiHash: function(inst) {
    var self = inst || this;
    return {
    helper: self.helper,
    placeholder: self.placeholder || $([]),
    position: self.position,
    originalPosition: self.originalPosition,
    offset: self.positionAbs,
    item: self.currentItem,
    sender: inst ? inst.element : null
    };
    }

});

$.extend($.ui.sortable, {
    version: "1.8.24"
});

})(jQuery);

/*! Copyright (c) 2013 Brandon Aaron (http://brandonaaron.net)
 * Licensed under the MIT License (LICENSE.txt).
 *
 * Version 3.0.0
 */

(function (factory) {
    if (typeof define === 'function' && define.amd) {
        // AMD. Register as an anonymous module.
        define(['jquery'], factory);
    } else {
        // Browser globals
        factory(jQuery);
    }
}(function ($) {
    $.fn.bgiframe = function(s) {
        s = $.extend({
            top         : 'auto', // auto == borderTopWidth
            left        : 'auto', // auto == borderLeftWidth
            width       : 'auto', // auto == offsetWidth
            height      : 'auto', // auto == offsetHeight
            opacity     : true,
            src         : 'javascript:false;',
            conditional : /MSIE 6.0/.test(navigator.userAgent) // expresion or function. return false to prevent iframe insertion
        }, s);

        // wrap conditional in a function if it isn't already
        if (!$.isFunction(s.conditional)) {
            var condition = s.conditional;
            s.conditional = function() { return condition; };
        }

        var $iframe = $('<iframe class="bgiframe"frameborder="0"tabindex="-1"src="'+s.src+'"'+
                           'style="display:block;position:absolute;z-index:-1;"/>');

        return this.each(function() {
            var $this = $(this);
            if ( s.conditional(this) === false ) { return; }
            var existing = $this.children('iframe.bgiframe');
            var $el = existing.length === 0 ? $iframe.clone() : existing;
            $el.css({
                'top': s.top == 'auto' ?
                    ((parseInt($this.css('borderTopWidth'),10)||0)*-1)+'px' : prop(s.top),
                'left': s.left == 'auto' ?
                    ((parseInt($this.css('borderLeftWidth'),10)||0)*-1)+'px' : prop(s.left),
                'width': s.width == 'auto' ? (this.offsetWidth + 'px') : prop(s.width),
                'height': s.height == 'auto' ? (this.offsetHeight + 'px') : prop(s.height),
                'opacity': s.opacity === true ? 0 : undefined
            });

            if ( existing.length === 0 ) {
                $this.prepend($el);
            }
        });
    };

    // old alias
    $.fn.bgIframe = $.fn.bgiframe;

    function prop(n) {
        return n && n.constructor === Number ? n + 'px' : n;
    }

}));
/**
 * Cookie plugin
 *
 * Copyright (c) 2006 Klaus Hartl (stilbuero.de)
 * Dual licensed under the MIT and GPL licenses:
 * http://www.opensource.org/licenses/mit-license.php
 * http://www.gnu.org/licenses/gpl.html
 *
 */

/**
 * Create a cookie with the given name and value and other optional parameters.
 *
 * @example $.cookie('the_cookie', 'the_value');
 * @desc Set the value of a cookie.
 * @example $.cookie('the_cookie', 'the_value', { expires: 7, path: '/', domain: 'jquery.com', secure: true });
 * @desc Create a cookie with all available options.
 * @example $.cookie('the_cookie', 'the_value');
 * @desc Create a session cookie.
 * @example $.cookie('the_cookie', null);
 * @desc Delete a cookie by passing null as value. Keep in mind that you have to use the same path and domain
 *       used when the cookie was set.
 *
 * @param String name The name of the cookie.
 * @param String value The value of the cookie.
 * @param Object options An object literal containing key/value pairs to provide optional cookie attributes.
 * @option Number|Date expires Either an integer specifying the expiration date from now on in days or a Date object.
 *                             If a negative value is specified (e.g. a date in the past), the cookie will be deleted.
 *                             If set to null or omitted, the cookie will be a session cookie and will not be retained
 *                             when the the browser exits.
 * @option String path The value of the path atribute of the cookie (default: path of page that created the cookie).
 * @option String domain The value of the domain attribute of the cookie (default: domain of page that created the cookie).
 * @option Boolean secure If true, the secure attribute of the cookie will be set and the cookie transmission will
 *                        require a secure protocol (like HTTPS).
 * @type undefined
 *
 * @name $.cookie
 * @cat Plugins/Cookie
 * @author Klaus Hartl/klaus.hartl@stilbuero.de
 */

/**
 * Get the value of a cookie with the given name.
 *
 * @example $.cookie('the_cookie');
 * @desc Get the value of a cookie.
 *
 * @param String name The name of the cookie.
 * @return The value of the cookie.
 * @type String
 *
 * @name $.cookie
 * @cat Plugins/Cookie
 * @author Klaus Hartl/klaus.hartl@stilbuero.de
 */
jQuery.cookie = function(name, value, options) {
    if (typeof value != 'undefined') { // name and value given, set cookie
        options = options || {};
        if (value === null) {
            value = '';
            options = $.extend({}, options); // clone object since it's unexpected behavior if the expired property were changed
            options.expires = -1;
        }
        var expires = '';
        if (options.expires && (typeof options.expires == 'number' || options.expires.toUTCString)) {
            var date;
            if (typeof options.expires == 'number') {
                date = new Date();
                date.setTime(date.getTime() + (options.expires * 24 * 60 * 60 * 1000));
            } else {
                date = options.expires;
            }
            expires = '; expires=' + date.toUTCString(); // use expires attribute, max-age is not supported by IE
        }
        // NOTE Needed to parenthesize options.path and options.domain
        // in the following expressions, otherwise they evaluate to undefined
        // in the packed version for some reason...
        var path = options.path ? '; path=' + (options.path) : '';
        var domain = options.domain ? '; domain=' + (options.domain) : '';
        var secure = options.secure ? '; secure' : '';
        document.cookie = [name, '=', encodeURIComponent(value), expires, path, domain, secure].join('');
    } else { // only name given, get cookie
        var cookieValue = null;
        if (document.cookie && document.cookie != '') {
            var cookies = document.cookie.split(';');
            for (var i = 0; i < cookies.length; i++) {
                var cookie = jQuery.trim(cookies[i]);
                // Does this cookie string begin with the name we want?
                if (cookie.substring(0, name.length + 1) == (name + '=')) {
                    cookieValue = decodeURIComponent(cookie.substring(name.length + 1));
                    break;
                }
            }
        }
        return cookieValue;
    }
};
/* Copyright (c) 2007 Paul Bakaus (paul.bakaus@googlemail.com) and Brandon Aaron (brandon.aaron@gmail.com || http://brandonaaron.net)
 * Dual licensed under the MIT (http://www.opensource.org/licenses/mit-license.php)
 * and GPL (http://www.opensource.org/licenses/gpl-license.php) licenses.
 *
 * $LastChangedDate: 2007-12-20 08:46:55 -0600 (Thu, 20 Dec 2007) $
 * $Rev: 4259 $
 *
 * Version: 1.2
 *
 * Requires: jQuery 1.2+
 */

(function($){

$.dimensions = {
    version: '1.2'
};

// Create innerHeight, innerWidth, outerHeight and outerWidth methods
$.each( [ 'Height', 'Width' ], function(i, name){

    // innerHeight and innerWidth
    $.fn[ 'inner' + name ] = function() {
    if (!this[0]) return;

    var torl = name == 'Height' ? 'Top'    : 'Left',  // top or left
        borr = name == 'Height' ? 'Bottom' : 'Right'; // bottom or right

    return this.is(':visible') ? this[0]['client' + name] : num( this, name.toLowerCase() ) + num(this, 'padding' + torl) + num(this, 'padding' + borr);
    };

    // outerHeight and outerWidth
    $.fn[ 'outer' + name ] = function(options) {
    if (!this[0]) return;

    var torl = name == 'Height' ? 'Top'    : 'Left',  // top or left
        borr = name == 'Height' ? 'Bottom' : 'Right'; // bottom or right

    options = $.extend({ margin: false }, options || {});

    var val = this.is(':visible') ?
    this[0]['offset' + name] :
    num( this, name.toLowerCase() )
    + num(this, 'border' + torl + 'Width') + num(this, 'border' + borr + 'Width')
    + num(this, 'padding' + torl) + num(this, 'padding' + borr);

    return val + (options.margin ? (num(this, 'margin' + torl) + num(this, 'margin' + borr)) : 0);
    };
});

// Create scrollLeft and scrollTop methods
$.each( ['Left', 'Top'], function(i, name) {
    $.fn[ 'scroll' + name ] = function(val) {
    if (!this[0]) return;

    return val != undefined ?

    // Set the scroll offset
    this.each(function() {
    this == window || this == document ?
    window.scrollTo(
    name == 'Left' ? val : $(window)[ 'scrollLeft' ](),
    name == 'Top'  ? val : $(window)[ 'scrollTop'  ]()
    ) :
    this[ 'scroll' + name ] = val;
    }) :

    // Return the scroll offset
    this[0] == window || this[0] == document ?
    self[ (name == 'Left' ? 'pageXOffset' : 'pageYOffset') ] ||
    $.boxModel && document.documentElement[ 'scroll' + name ] ||
    document.body[ 'scroll' + name ] :
    this[0][ 'scroll' + name ];
    };
});

$.fn.extend({
    position: function() {
    var left = 0, top = 0, elem = this[0], offset, parentOffset, offsetParent, results;

    if (elem) {
    // Get *real* offsetParent
    offsetParent = this.offsetParent();

    // Get correct offsets
    offset       = this.offset();
    parentOffset = offsetParent.offset();

    // Subtract element margins
    offset.top  -= num(elem, 'marginTop');
    offset.left -= num(elem, 'marginLeft');

    // Add offsetParent borders
    parentOffset.top  += num(offsetParent, 'borderTopWidth');
    parentOffset.left += num(offsetParent, 'borderLeftWidth');

    // Subtract the two offsets
    results = {
    top:  offset.top  - parentOffset.top,
    left: offset.left - parentOffset.left
    };
    }

    return results;
    },

    offsetParent: function() {
    var offsetParent = this[0].offsetParent;
    while ( offsetParent && (!/^body|html$/i.test(offsetParent.tagName) && $.css(offsetParent, 'position') == 'static') )
    offsetParent = offsetParent.offsetParent;
    return $(offsetParent);
    }
});

function num(el, prop) {
    return parseInt($.curCSS(el.jquery?el[0]:el,prop,true))||0;
};

})(jQuery);

/*
 * jQuery Easing v1.1.1 - http://gsgd.co.uk/sandbox/jquery.easing.php
 *
 * Uses the built in easing capabilities added in jQuery 1.1
 * to offer multiple easing options
 *
 * Copyright (c) 2007 George Smith
 * Licensed under the MIT License:
 *   http://www.opensource.org/licenses/mit-license.php
 */

jQuery.extend(jQuery.easing, {
    easein: function(x, t, b, c, d) {
    return c*(t/=d)*t + b; // in
    },
    easeinout: function(x, t, b, c, d) {
    if (t < d/2) return 2*c*t*t/(d*d) + b;
    var ts = t - d/2;
    return -2*c*ts*ts/(d*d) + 2*c*ts/d + c/2 + b;
    },
    easeout: function(x, t, b, c, d) {
    return -c*t*t/(d*d) + 2*c*t/d + b;
    },
    expoin: function(x, t, b, c, d) {
    var flip = 1;
    if (c < 0) {
    flip *= -1;
    c *= -1;
    }
    return flip * (Math.exp(Math.log(c)/d * t)) + b;
    },
    expoout: function(x, t, b, c, d) {
    var flip = 1;
    if (c < 0) {
    flip *= -1;
    c *= -1;
    }
    return flip * (-Math.exp(-Math.log(c)/d * (t-d)) + c + 1) + b;
    },
    expoinout: function(x, t, b, c, d) {
    var flip = 1;
    if (c < 0) {
    flip *= -1;
    c *= -1;
    }
    if (t < d/2) return flip * (Math.exp(Math.log(c/2)/(d/2) * t)) + b;
    return flip * (-Math.exp(-2*Math.log(c/2)/d * (t-d)) + c + 1) + b;
    },
    bouncein: function(x, t, b, c, d) {
    return c - jQuery.easing['bounceout'](x, d-t, 0, c, d) + b;
    },
    bounceout: function(x, t, b, c, d) {
    if ((t/=d) < (1/2.75)) {
    return c*(7.5625*t*t) + b;
    } else if (t < (2/2.75)) {
    return c*(7.5625*(t-=(1.5/2.75))*t + .75) + b;
    } else if (t < (2.5/2.75)) {
    return c*(7.5625*(t-=(2.25/2.75))*t + .9375) + b;
    } else {
    return c*(7.5625*(t-=(2.625/2.75))*t + .984375) + b;
    }
    },
    bounceinout: function(x, t, b, c, d) {
    if (t < d/2) return jQuery.easing['bouncein'] (x, t*2, 0, c, d) * .5 + b;
    return jQuery.easing['bounceout'] (x, t*2-d,0, c, d) * .5 + c*.5 + b;
    },
    elasin: function(x, t, b, c, d) {
    var s=1.70158;var p=0;var a=c;
    if (t==0) return b;  if ((t/=d)==1) return b+c;  if (!p) p=d*.3;
    if (a < Math.abs(c)) { a=c; var s=p/4; }
    else var s = p/(2*Math.PI) * Math.asin (c/a);
    return -(a*Math.pow(2,10*(t-=1)) * Math.sin( (t*d-s)*(2*Math.PI)/p )) + b;
    },
    elasout: function(x, t, b, c, d) {
    var s=1.70158;var p=0;var a=c;
    if (t==0) return b;  if ((t/=d)==1) return b+c;  if (!p) p=d*.3;
    if (a < Math.abs(c)) { a=c; var s=p/4; }
    else var s = p/(2*Math.PI) * Math.asin (c/a);
    return a*Math.pow(2,-10*t) * Math.sin( (t*d-s)*(2*Math.PI)/p ) + c + b;
    },
    elasinout: function(x, t, b, c, d) {
    var s=1.70158;var p=0;var a=c;
    if (t==0) return b;  if ((t/=d/2)==2) return b+c;  if (!p) p=d*(.3*1.5);
    if (a < Math.abs(c)) { a=c; var s=p/4; }
    else var s = p/(2*Math.PI) * Math.asin (c/a);
    if (t < 1) return -.5*(a*Math.pow(2,10*(t-=1)) * Math.sin( (t*d-s)*(2*Math.PI)/p )) + b;
    return a*Math.pow(2,-10*(t-=1)) * Math.sin( (t*d-s)*(2*Math.PI)/p )*.5 + c + b;
    },
    backin: function(x, t, b, c, d) {
    var s=1.70158;
    return c*(t/=d)*t*((s+1)*t - s) + b;
    },
    backout: function(x, t, b, c, d) {
    var s=1.70158;
    return c*((t=t/d-1)*t*((s+1)*t + s) + 1) + b;
    },
    backinout: function(x, t, b, c, d) {
    var s=1.70158;
    if ((t/=d/2) < 1) return c/2*(t*t*(((s*=(1.525))+1)*t - s)) + b;
    return c/2*((t-=2)*t*(((s*=(1.525))+1)*t + s) + 2) + b;
    }
});
/*
 * Metadata - jQuery plugin for parsing metadata from elements
 *
 * Copyright (c) 2006 John Resig, Yehuda Katz, J�örn Zaefferer, Paul McLanahan
 *
 * Dual licensed under the MIT and GPL licenses:
 *   http://www.opensource.org/licenses/mit-license.php
 *   http://www.gnu.org/licenses/gpl.html
 *
 * Revision: $Id$
 *
 */

/**
 * Sets the type of metadata to use. Metadata is encoded in JSON, and each property
 * in the JSON will become a property of the element itself.
 *
 * There are three supported types of metadata storage:
 *
 *   attr:  Inside an attribute. The name parameter indicates *which* attribute.
 *
 *   class: Inside the class attribute, wrapped in curly braces: { }
 *
 *   elem:  Inside a child element (e.g. a script tag). The
 *          name parameter indicates *which* element.
 *
 * The metadata for an element is loaded the first time the element is accessed via jQuery.
 *
 * As a result, you can define the metadata type, use $(expr) to load the metadata into the elements
 * matched by expr, then redefine the metadata type and run another $(expr) for other elements.
 *
 * @name $.metadata.setType
 *
 * @example <p id="one" class="some_class {item_id: 1, item_label: 'Label'}">This is a p</p>
 * @before $.metadata.setType("class")
 * @after $("#one").metadata().item_id == 1; $("#one").metadata().item_label == "Label"
 * @desc Reads metadata from the class attribute
 *
 * @example <p id="one" class="some_class" data="{item_id: 1, item_label: 'Label'}">This is a p</p>
 * @before $.metadata.setType("attr", "data")
 * @after $("#one").metadata().item_id == 1; $("#one").metadata().item_label == "Label"
 * @desc Reads metadata from a "data" attribute
 *
 * @example <p id="one" class="some_class"><script>{item_id: 1, item_label: 'Label'}</script>This is a p</p>
 * @before $.metadata.setType("elem", "script")
 * @after $("#one").metadata().item_id == 1; $("#one").metadata().item_label == "Label"
 * @desc Reads metadata from a nested script element
 *
 * @param String type The encoding type
 * @param String name The name of the attribute to be used to get metadata (optional)
 * @cat Plugins/Metadata
 * @descr Sets the type of encoding to be used when loading metadata for the first time
 * @type undefined
 * @see metadata()
 */

(function($) {

$.extend({
    metadata : {
    defaults : {
    type: 'class',
    name: 'metadata',
    cre: /({.*})/,
    single: 'metadata'
    },
    setType: function( type, name ){
    this.defaults.type = type;
    this.defaults.name = name;
    },
    get: function( elem, opts ){
    var settings = $.extend({},this.defaults,opts);
    // check for empty string in single property
    if ( !settings.single.length ) settings.single = 'metadata';

    var data = $.data(elem, settings.single);
    // returned cached data if it already exists
    if ( data ) return data;

    data = "{}";

    if ( settings.type == "class" ) {
    var m = settings.cre.exec( elem.className );
    if ( m )
    data = m[1];
    } else if ( settings.type == "elem" ) {
    if( !elem.getElementsByTagName )
    return undefined;
    var e = elem.getElementsByTagName(settings.name);
    if ( e.length )
    data = $.trim(e[0].innerHTML);
    } else if ( elem.getAttribute != undefined ) {
    var attr = elem.getAttribute( settings.name );
    if ( attr )
    data = attr;
    }

    if ( data.indexOf( '{' ) <0 )
    data = "{" + data + "}";

    data = eval("(" + data + ")");

    $.data( elem, settings.single, data );
    return data;
    }
    }
});

/**
 * Returns the metadata object for the first member of the jQuery object.
 *
 * @name metadata
 * @descr Returns element's metadata object
 * @param Object opts An object contianing settings to override the defaults
 * @type jQuery
 * @cat Plugins/Metadata
 */
$.fn.metadata = function( opts ){
    return $.metadata.get( this[0], opts );
};

})(jQuery);

/**
 * Really Simple Color Picker in jQuery
 *
 * Copyright (c) 2008 Lakshan Perera (www.laktek.com)
 * Licensed under the MIT (MIT-LICENSE.txt)  licenses.
 *
 */

(function($){
  $.fn.colorPicker = function(){
    if(this.length > 0) buildSelector();
    return this.each(function(i) {
      buildPicker(this)});
  };

  var selectorOwner;
  var selectorShowing = false;

  buildPicker = function(element){
    //build color picker
    control = $("<span class='color_picker'>&nbsp;&nbsp;&nbsp;</span>");
    control.css({
    'background-color': $(element).val(),
    'cursor' : 'pointer'
    });

    //bind click event to color picker
    control.bind("click", toggleSelector);

    //add the color picker section
    $(element).after(control);

    //add even listener to input box
    $(element).bind("change", function() {
      selectedValue = toHex($(element).val());
      $(element).next(".color_picker").css("background-color", selectedValue);
    });

    //hide the input box
    //$(element).hide();

  };

  buildSelector = function(){
    selector = $("<div id='color_selector' class='color-selector-panel'></div>");

     //add color pallete
     $.each($.fn.colorPicker.defaultColors, function(i){
    swatch = $("<div class='color_swatch'>&nbsp;</div>");

    swatch.css("background-color", "#" + this);

    swatch.bind("click", function(e){
    changeColor($(this).css("background-color"));
    });

    swatch.bind("mouseover", function(e){
    $(this).css("border-color", "#598FEF");
    $("input#color_value").val(toHex($(this).css("background-color")));
    });

    swatch.bind("mouseout", function(e){
    $(this).css("border-color", "#000");
    $("input#color_value").val(toHex($(selectorOwner).css("background-color")));
    });

    swatch.appendTo(selector);

     });

     //add HEX value field
     hex_field = $("<label for='color_value'>Hex</label><input type='text' size='8' id='color_value'/>");
     hex_field.bind("keydown", function(event){
    if(event.keyCode == 13) {changeColor($(this).val());}
    if(event.keyCode == 27) {toggleSelector()}
     });

     $("<div id='color_custom'></div>").append(hex_field).appendTo(selector);

     $("body").append(selector);
     selector.hide();
  };

  checkMouse = function(event){
    //check the click was on selector itself or on selectorOwner
    var selector = "div.color-selector-panel";
    var selectorParent = $(event.target).parents(selector).length;
    if(event.target == $(selector)[0] || event.target == selectorOwner || selectorParent > 0) return;

    hideSelector();
  };

  hideSelector = function(){
    var selector = $("div.color-selector-panel");

    $(document).unbind("mousedown", checkMouse);
    selector.hide();
    selectorShowing = false;
  };

  showSelector = function(){
    var selector = $("div.color-selector-panel");

    //alert($(selectorOwner).offset().top);

    selector.css({
      top: $(selectorOwner).offset().top + ($(selectorOwner).outerHeight()),
      left: $(selectorOwner).offset().left
    });
    hexColor = $(selectorOwner).prev("input").val();
    $("input#color_value").val(hexColor);
    selector.show();

    //bind close event handler
    $(document).bind("mousedown", checkMouse);
    selectorShowing = true;
  };

  toggleSelector = function(event){
    selectorOwner = this;
    selectorShowing ? hideSelector() : showSelector();
  };

  changeColor = function(value){
    if(selectedValue = toHex(value)){
      $(selectorOwner).css("background-color", selectedValue);
      $(selectorOwner).prev("input").val(selectedValue).change();

      //close the selector
      hideSelector();
    }
  };

  //converts RGB string to HEX - inspired by http://code.google.com/p/jquery-color-utils
  toHex = function(color){
    //valid HEX code is entered
    if(color.match(/[0-9a-fA-F]{3}$/) || color.match(/[0-9a-fA-F]{6}$/)){
      color = (color.charAt(0) == "#") ? color : ("#" + color);
    }
    //rgb color value is entered (by selecting a swatch)
    else if(color.match(/^rgb\(([0-9]|[1-9][0-9]|[1][0-9]{2}|[2][0-4][0-9]|[2][5][0-5]),[ ]{0,1}([0-9]|[1-9][0-9]|[1][0-9]{2}|[2][0-4][0-9]|[2][5][0-5]),[ ]{0,1}([0-9]|[1-9][0-9]|[1][0-9]{2}|[2][0-4][0-9]|[2][5][0-5])\)$/)){
      var c = ([parseInt(RegExp.$1),parseInt(RegExp.$2),parseInt(RegExp.$3)]);

      var pad = function(str){
            if(str.length < 2){
              for(var i = 0,len = 2 - str.length ; i<len ; i++){
                str = '0'+str;
              };
            };
            return str;
      };

      if(c.length == 3){
        var r = pad(c[0].toString(16)),g = pad(c[1].toString(16)),b= pad(c[2].toString(16));
        color = '#' + r + g + b;
      };
    }
    else color = false;

    return color
  };


  //public methods
  $.fn.colorPicker.addColors = function(colorArray){
    $.fn.colorPicker.defaultColors = $.fn.colorPicker.defaultColors.concat(colorArray);
  };

  $.fn.colorPicker.defaultColors =
    [ '000000', '993300','333300', '000080', '333399', '333333', '800000', 'FF6600', '808000', '008000', '008080', '0000FF', '666699', '808080', 'FF0000', 'FF9900', '99CC00', '339966', '33CCCC', '3366FF', '800080', '999999', 'FF00FF', 'FFCC00', 'FFFF00', '00FF00', '00FFFF', '00CCFF', '993366', 'C0C0C0', 'FF99CC', 'FFCC99', 'FFFF99' , 'CCFFFF', '99CCFF', 'FFFFFF'];

})(jQuery);



/*
 * Treeview 1.5pre - jQuery plugin to hide and show branches of a tree
 *
 * http://bassistance.de/jquery-plugins/jquery-plugin-treeview/
 * http://docs.jquery.com/Plugins/Treeview
 *
 * Copyright (c) 2007 Jörn Zaefferer
 *
 * Dual licensed under the MIT and GPL licenses:
 *   http://www.opensource.org/licenses/mit-license.php
 *   http://www.gnu.org/licenses/gpl.html
 *
 * Revision: $Id: jquery.treeview.js 5759 2008-07-01 07:50:28Z joern.zaefferer $
 *
 */

;(function($) {

    // TODO rewrite as a widget, removing all the extra plugins
    $.extend($.fn, {
    swapClass: function(c1, c2) {
    var c1Elements = this.filter('.' + c1);
    this.filter('.' + c2).removeClass(c2).addClass(c1);
    c1Elements.removeClass(c1).addClass(c2);
    return this;
    },
    replaceClass: function(c1, c2) {
    return this.filter('.' + c1).removeClass(c1).addClass(c2).end();
    },
    hoverClass: function(className) {
    className = className || "hover";
    return this.hover(function() {
    $(this).addClass(className);
    }, function() {
    $(this).removeClass(className);
    });
    },
    heightToggle: function(animated, callback) {
    animated ?
    this.animate({ height: "toggle" }, animated, callback) :
    this.each(function(){
    jQuery(this)[ jQuery(this).is(":hidden") ? "show" : "hide" ]();
    if(callback)
    callback.apply(this, arguments);
    });
    },
    heightHide: function(animated, callback) {
    if (animated) {
    this.animate({ height: "hide" }, animated, callback);
    } else {
    this.hide();
    if (callback)
    this.each(callback);
    }
    },
    prepareBranches: function(settings) {
    if (!settings.prerendered) {
    // mark last tree items
    this.filter(":last-child:not(ul)").addClass(CLASSES.last);
    // collapse whole tree, or only those marked as closed, anyway except those marked as open
    this.filter((settings.collapsed ? "" : "." + CLASSES.closed) + ":not(." + CLASSES.open + ")").find(">ul").hide();
    }
    // return all items with sublists
    return this.filter(":has(>ul)");
    },
    applyClasses: function(settings, toggler) {
    // TODO use event delegation
    this.filter(":has(>ul):not(:has(>a))").find(">span").unbind("click.treeview").bind("click.treeview", function(event) {
    // don't handle click events on children, eg. checkboxes
    if ( this == event.target )
    toggler.apply($(this).next());
    }).add( $("a", this) ).hoverClass();

    if (!settings.prerendered) {
    // handle closed ones first
    this.filter(":has(>ul:hidden)")
    .addClass(CLASSES.expandable)
    .replaceClass(CLASSES.last, CLASSES.lastExpandable);

    // handle open ones
    this.not(":has(>ul:hidden)")
    .addClass(CLASSES.collapsable)
    .replaceClass(CLASSES.last, CLASSES.lastCollapsable);

                // create hitarea if not present
    var hitarea = this.find("div." + CLASSES.hitarea);
    if (!hitarea.length)
    hitarea = this.prepend("<div class=\"" + CLASSES.hitarea + "\"/>").find("div." + CLASSES.hitarea);
    hitarea.removeClass().addClass(CLASSES.hitarea).each(function() {
    var classes = "";
    $.each($(this).parent().attr("class").split(" "), function() {
    classes += this + "-hitarea ";
    });
    $(this).addClass( classes );
    })
    }

    // apply event to hitarea
    this.find("div." + CLASSES.hitarea).click( toggler );
    },
    treeview: function(settings) {

    settings = $.extend({
    cookieId: "treeview"
    }, settings);

    if ( settings.toggle ) {
    var callback = settings.toggle;
    settings.toggle = function() {
    return callback.apply($(this).parent()[0], arguments);
    };
    }

    // factory for treecontroller
    function treeController(tree, control) {
    // factory for click handlers
    function handler(filter) {
    return function() {
    // reuse toggle event handler, applying the elements to toggle
    // start searching for all hitareas
    toggler.apply( $("div." + CLASSES.hitarea, tree).filter(function() {
    // for plain toggle, no filter is provided, otherwise we need to check the parent element
    return filter ? $(this).parent("." + filter).length : true;
    }) );
    return false;
    };
    }
    // click on first element to collapse tree
    $("a:eq(0)", control).click( handler(CLASSES.collapsable) );
    // click on second to expand tree
    $("a:eq(1)", control).click( handler(CLASSES.expandable) );
    // click on third to toggle tree
    $("a:eq(2)", control).click( handler() );
    }

    // handle toggle event
    function toggler() {
    $(this)
    .parent()
    // swap classes for hitarea
    .find(">.hitarea")
    .swapClass( CLASSES.collapsableHitarea, CLASSES.expandableHitarea )
    .swapClass( CLASSES.lastCollapsableHitarea, CLASSES.lastExpandableHitarea )
    .end()
    // swap classes for parent li
    .swapClass( CLASSES.collapsable, CLASSES.expandable )
    .swapClass( CLASSES.lastCollapsable, CLASSES.lastExpandable )
    // find child lists
    .find( ">ul" )
    // toggle them
    .heightToggle( settings.animated, settings.toggle );
    if ( settings.unique ) {
    $(this).parent()
    .siblings()
    // swap classes for hitarea
    .find(">.hitarea")
    .replaceClass( CLASSES.collapsableHitarea, CLASSES.expandableHitarea )
    .replaceClass( CLASSES.lastCollapsableHitarea, CLASSES.lastExpandableHitarea )
    .end()
    .replaceClass( CLASSES.collapsable, CLASSES.expandable )
    .replaceClass( CLASSES.lastCollapsable, CLASSES.lastExpandable )
    .find( ">ul" )
    .heightHide( settings.animated, settings.toggle );
    }
    }
    this.data("toggler", toggler);

    function serialize() {
    function binary(arg) {
    return arg ? 1 : 0;
    }
    var data = [];
    branches.each(function(i, e) {
    data[i] = $(e).is(":has(>ul:visible)") ? 1 : 0;
    });
    $.cookie(settings.cookieId, data.join(""), settings.cookieOptions );
    }

    function deserialize() {
    var stored = $.cookie(settings.cookieId);
    if ( stored ) {
    var data = stored.split("");
    branches.each(function(i, e) {
    $(e).find(">ul")[ parseInt(data[i]) ? "show" : "hide" ]();
    });
    }
    }

    // add treeview class to activate styles
    this.addClass("treeview");

    // prepare branches and find all tree items with child lists
    var branches = this.find("li").prepareBranches(settings);

    switch(settings.persist) {
    case "cookie":
    var toggleCallback = settings.toggle;
    settings.toggle = function() {
    serialize();
    if (toggleCallback) {
    toggleCallback.apply(this, arguments);
    }
    };
    deserialize();
    break;
    case "location":
    var current = this.find("a").filter(function() {
    return this.href.toLowerCase() == location.href.toLowerCase();
    });
    if ( current.length ) {
    // TODO update the open/closed classes
    var items = current.addClass("selected").parents("ul, li").add( current.next() ).show();
    if (settings.prerendered) {
    // if prerendered is on, replicate the basic class swapping
    items.filter("li")
    .swapClass( CLASSES.collapsable, CLASSES.expandable )
    .swapClass( CLASSES.lastCollapsable, CLASSES.lastExpandable )
    .find(">.hitarea")
    .swapClass( CLASSES.collapsableHitarea, CLASSES.expandableHitarea )
    .swapClass( CLASSES.lastCollapsableHitarea, CLASSES.lastExpandableHitarea );
    }
    }
    break;
    }

    branches.applyClasses(settings, toggler);

    // if control option is set, create the treecontroller and show it
    if ( settings.control ) {
    treeController(this, settings.control);
    $(settings.control).show();
    }

    return this;
    }
    });

    // classes used by the plugin
    // need to be styled via external stylesheet, see first example
    $.treeview = {};
    var CLASSES = ($.treeview.classes = {
    open: "open",
    closed: "closed",
    expandable: "expandable",
    expandableHitarea: "expandable-hitarea",
    lastExpandableHitarea: "lastExpandable-hitarea",
    collapsable: "collapsable",
    collapsableHitarea: "collapsable-hitarea",
    lastCollapsableHitarea: "lastCollapsable-hitarea",
    lastCollapsable: "lastCollapsable",
    lastExpandable: "lastExpandable",
    last: "last",
    hitarea: "hitarea"
    });

})(jQuery);
/**
 * Editor에 필요한 기본 변수 및 함수 정의
 */

if (typeof SDE == 'undefined') var SDE = {};

$.extend(SDE, {
    File : {},
    List : {
        Favorite : {},
        Tab : {},
        Tree : {},
        TabList : {}
    },
    View : {},
    Util : {},
    Editor : {},
    Layer : {},
    Ghost : {},
    Component : {},
    Prop : {}
});

function makeRandomString() {
    return Math.random().toString(36).substring(7);
}

String.prototype.capitalize = function() {
    return (this + '').replace(/^([a-z])|\s+([a-z])/g, function ($1) {
        return $1.toUpperCase();
    });
};

// Get Associative Array Size
Object.size = function(obj) {
    var size = 0,
        key;

    for (key in obj) {
        if (obj.hasOwnProperty(key)) size++;
    }

    return size;
};

Object.arraysort = function ($ph, $pfCallBack)
{
    var $a = [];
    for (var $k in $ph) {
        if ($ph.hasOwnProperty($k)) {
            $a.push({key: $k, val: $ph[$k]});
        }
    }
    var $aSorted = [];
    for (var $item, $icol=$a.sort($pfCallBack || function($a, $b){return $a.key > $b.key? 1: $a.key < $b.key? -1: 0;}), $i=0, $ilen=$icol.length; $item=$icol[$i], $i < $ilen; $i++) {
        $aSorted.push($item.val);
    }
    return $aSorted;
}



// Return GET Parameter
function getQueryParams() {
    var
    match,
    params = {},
    pl     = /\+/g,  // Regex for replacing addition symbol with a space
    search = /([^&=]+)=?([^&]*)/g,
    decode = function (s) { return unescape(s.replace(pl, " ")); },
    query  = window.location.search.substring(1);

    while (match = search.exec(query))
        params[decode(match[1])] = decode(match[2]);

    return params;
};

// Add selector of finding insensitive src
$.expr[':'].srcCaseInsensitive = function(node, stackIndex, properties){
    return node.src.toLowerCase() == properties[3];
};


SDE.mo = function () {
    return !!((SDE.EDITOR_TYPE === "mobile") || window.mobileWeb);
};

;(function (exports) {
    exports.htmlutil =
    {
        /*rev.b160.20131204.1*/
        REGEX_HTML_SPLITTER: /(?:<(script|style)(?:[\s\S]*?)<\/\1>|<!doctype(?:[^>]*?)>|<!--(?:[\s\S]*?)-->|\{literal\}(?:[\s\S]*?)\{\/literal\}|\{[^\}]+\}|<\/?\w+(?:\s+(?:[\w-]+\s*=\s*"(?:[^"]|\\\\")*(?!\\\\)"|[^>]*))*\s*\/?>|\s+|[^<]+)/gi,
        TAG_COMMENT: "!--;",
        TAG_SINGLEBLOCK: "noscript;script;style;",
        TAG_SINGLE: "!;!doctype;area;base;basefont;br;col;embed;frame;hr;img;input;keygen;link;meta;param;source;track;",
        TAG_MULTIBLOCK: "address;article;audio;body;datalist;div;dl;fieldset;footer;form;h1;h2;h3;h4;h5;h6;head;header;html;map;menu;nav;li;ol;object;optgroup;p;pre;section;select;table;tbody;td;tfoot;th;thead;textarea;tr;ul;video;",
        REGEX_TAG_FIND: /^<(\/?)(\w+|!(?:DOCTYPE|--))/i,
        _formatting: function ($paMap)
        {
            $sindent = "    ";
            $max = $paMap.length-1;
            $current = $paMap[$max]||[];
            $previous = $paMap[$max-1]||[];
            $ilen = $current[1].length;
            if ($ilen < 0) {
                $ilen  = 0;
            }
            $sindexttr = "\n"+str_repeat($sindent,$ilen);
            $sin = "";
            if (($current[0]=="TXU" && $previous[0]=="TIBO") || ($current[0]=="TIBC" && $previous[0]=="TXU")) {
            } else
            if ($current[0]=="TSB" || $current[0]=="TXU" || $previous[0]=="TXU") {
                $sin = $sindexttr;
            } else
            if ($current[0]=="TMBC") {
                $sin = ($previous[0]=="TMBO"? "": $sindexttr);
            } else
            if (($current[0]=="TXT" || $current[0]=="TIBC") && ($previous[0]=="TS" || $previous[0]=="TIBC")) {
                $sin = "";
            } else
            {
                if ($previous[0]=="TMBO" || $previous[0]=="TMBC" || $previous[0]=="TIBC" || $previous[0]=="TS" || $previous[0]=="TSB" || $previous[0]=="TC") {
                    $sin = $sindexttr;
                }
                if ($previous[0]=="TIBO") {
                    $sin = "";
                }
                if ($previous[0]=="TXT") {
                    $sin = (false===strpos($paMap[$max-1][2],"\n")? "": $sindexttr);
                }
            }
            $text = $sin+$current[2];
            if ($current[0] == "TSB") {
                if (/([ \"\']>)[\s\n]*(<\/\w+>)$/.test($text)) {
                    $text = $text.replace(/([ \"\']>)[\s\n]*(<\/\w+>)$/, function($0,$1,$2){return ($1+$2).toLowerCase();});
                } else {
                    $text = $text.replace(/\s*(<\/\w+>)$/, function($0,$1){return $sin+$1.toLowerCase();});
                }
            }
            if ($current[0]=="TC" || ($current[0]=="TXT" && false!==strpos($paMap[$max][2],"\n"))) {
                $text = "\n"+$text;//.replace(/^\s*/mg,str_repeat($sindent,$ilen));
            }
            return $current[0] == "TSB"?
                $text:
                $text.replace(/\n\n+/g,"\n");
        },
        splitContext: function ($psText)
        {
            var $matches = $psText.match(this.REGEX_HTML_SPLITTER)||[];
            return $matches;
        },
        tokenize: function ($psText)
        {
            return this.classify(this.splitContext($psText));
        },
        prettify: function ($psText)
        {
            $aClassified = this.tokenize($psText);
            $prettyline = [];
            $pretty = [];
            for (var $k=0; $k in $aClassified; $k++) {
                $prettyline.push($aClassified[$k]);
                $pretty.push(this._formatting($prettyline.slice($prettyline.length-2)));
            }
            return implode("", $pretty);
        },
        classify: function ($paText)
        {
            $map = [];
            $blocks = [];
            for (var $k=0; $k in $paText; $k++) {
                var $v = $paText[$k];
                var $matches = $v.match(this.REGEX_TAG_FIND);
                if ($matches) {
                    $tag = strtolower($matches[2]);
                    if ("" === $matches[1]) {
                        if (false !== strpos(";"+this.TAG_COMMENT, ";"+$tag+";")) {
                            $map.push(["TC",$blocks.slice(),$v,"",$k]);
                        } else
                        if (false !== strpos(";"+this.TAG_SINGLEBLOCK, ";"+$tag+";")) {
                            $map.push(["TSB",$blocks.slice(),$v,"",$k]);
                        } else
                        if (false !== strpos(";"+this.TAG_SINGLE, ";"+$tag+";")) {
                            $map.push(["TS",$blocks.slice(),$v,$tag,$k]);
                        } else
                        {
                            if (false !== strpos(";"+this.TAG_MULTIBLOCK, ";"+$tag+";")) {
                                $map.push(["TMBO",$blocks.slice(),$v,$tag,$k]);
                                $blocks.push($tag+":"+$k);
                            } else {
                                $map.push(["TIBO",$blocks.slice(),$v,$tag,$k]);
                            }
                        }
                    } else {
                        if (false !== strpos(";"+this.TAG_MULTIBLOCK, ";"+$tag+";")) {
                            $blocks.pop();
                            $map.push(["TMBC",$blocks.slice(),$v,$tag,$k]);
                        } else {
                            $map.push(["TIBC",$blocks.slice(),$v,$tag,$k]);
                        }
                    }
                } else {
                    if (/^\{/.test($v)) {
                        $map.push(["TXU",$blocks.slice(),$v,"",$k]);
                    } else
                    if (!$v.match(/^[\s\n]+$/)) {
                        $map.push(["TXT",$blocks.slice(),$v.replace(/^\s+|\s+$/g,""),"",$k]);
                    } else {
                        $map.push(["TXT",$blocks.slice(),$v,"",$k]);
                    }
                }
            }
            return $map;
        }
    };
    String.prototype.repeat = function ($pi) {
        return new Array($pi + 1).join(this);
    }
    function str_repeat ($str, $count) {
        return $str.repeat($count);
    }
    function strpos ($str, $needle) {
        var $i = $str.indexOf($needle);
        return $i === -1? false: $i;
    }
    function implode ($str, $arr) {
        return $arr.join($str);
    }
    function strtolower ($str) {
        return $str.toLowerCase();
    }
})(typeof exports === "undefined" ? exports = {} : exports);
/* Simple JavaScript Inheritance
 * By John Resig http://ejohn.org/
 * MIT Licensed.
 */
// Inspired by base2 and Prototype
(function(){
  var initializing = false, fnTest = /xyz/.test(function(){xyz;}) ? /\b_super\b/ : /.*/;
  // The base Class implementation (does nothing)
  this.Class = function(){};
  
  // Create a new Class that inherits from this class
  Class.extend = function(prop) {
    var _super = this.prototype;
    
    // Instantiate a base class (but only create the instance,
    // don't run the init constructor)
    initializing = true;
    var prototype = new this();
    initializing = false;
    
    // Copy the properties over onto the new prototype
    for (var name in prop) {
      // Check if we're overwriting an existing function
      prototype[name] = typeof prop[name] == "function" && 
        typeof _super[name] == "function" && fnTest.test(prop[name]) ?
        (function(name, fn){
          return function() {
            var tmp = this._super;
            
            // Add a new ._super() method that is the same method
            // but on the super-class
            this._super = _super[name];
            
            // The method only need to be bound temporarily, so we
            // remove it when we're done executing
            var ret = fn.apply(this, arguments);        
            this._super = tmp;
            
            return ret;
          };
        })(name, prop[name]) :
        prop[name];
    }
    
    // The dummy class constructor
    function Class() {
      // All construction is actually done in the init method
      if ( !initializing && this.init )
        this.init.apply(this, arguments);
    }
    
    // Populate our constructed prototype object
    Class.prototype = prototype;
    
    // Enforce the constructor to be what we expect
    Class.prototype.constructor = Class;

    // And make this class extendable
    Class.extend = arguments.callee;
    
    return Class;
  };
})();
/*
 * jQuery UI Menu (not officially released)
 * 
 * This widget isn't yet finished and the API is subject to change. We plan to finish
 * it for the next release. You're welcome to give it a try anyway and give us feedback,
 * as long as you're okay with migrating your code later on. We can help with that, too.
 *
 * Copyright 2010, AUTHORS.txt (http://jqueryui.com/about)
 * Dual licensed under the MIT or GPL Version 2 licenses.
 * http://jquery.org/license
 *
 * http://docs.jquery.com/UI/Menu
 *
 * Depends:
 *  jquery.ui.core.js
 *  jquery.ui.widget.js
 */
(function($) {

$.widget("ui.menu", {
    _create: function() {
        var self = this;
        this.element
            .addClass("ui-menu ui-widget ui-widget-content ui-corner-all")
            .attr({
                role: "listbox",
                "aria-activedescendant": "ui-active-menuitem"
            })
            .click(function( event ) {
                if ( !$( event.target ).closest( ".ui-menu-item a" ).length ) {
                    return;
                }
                // temporary
                event.preventDefault();
                self.select( event );
            });
        this.refresh();
    },
    
    refresh: function() {
        var self = this;

        // don't refresh list items that are already adapted
        var items = this.element.children("li:not(.ui-menu-item):has(a)")
            .addClass("ui-menu-item")
            .attr("role", "menuitem");
        
        items.children("a")
            .addClass("ui-corner-all")
            .attr("tabindex", -1)
            // mouseenter doesn't work with event delegation
            .mouseenter(function( event ) {
                self.activate( event, $(this).parent() );
            })
            .mouseleave(function() {
                self.deactivate();
            });
    },

    activate: function( event, item ) {
        this.deactivate();
        if (this.hasScroll()) {
            var offset = item.offset().top - this.element.offset().top,
                scroll = this.element.scrollTop(),
                elementHeight = this.element.height();
            if (offset < 0) {
                this.element.scrollTop( scroll + offset);
            } else if (offset >= elementHeight) {
                this.element.scrollTop( scroll + offset - elementHeight + item.height());
            }
        }
        this.active = item.eq(0)
            .children("a")
                .addClass("ui-state-hover")
                .attr("id", "ui-active-menuitem")
            .end();
        this._trigger("focus", event, { item: item });
    },

    deactivate: function() {
        if (!this.active) { return; }

        this.active.children("a")
            .removeClass("ui-state-hover")
            .removeAttr("id");
        this._trigger("blur");
        this.active = null;
    },

    next: function(event) {
        this.move("next", ".ui-menu-item:first", event);
    },

    previous: function(event) {
        this.move("prev", ".ui-menu-item:last", event);
    },

    first: function() {
        return this.active && !this.active.prevAll(".ui-menu-item").length;
    },

    last: function() {
        return this.active && !this.active.nextAll(".ui-menu-item").length;
    },

    move: function(direction, edge, event) {
        if (!this.active) {
            this.activate(event, this.element.children(edge));
            return;
        }
        var next = this.active[direction + "All"](".ui-menu-item").eq(0);
        if (next.length) {
            this.activate(event, next);
        } else {
            this.activate(event, this.element.children(edge));
        }
    },

    // TODO merge with previousPage
    nextPage: function(event) {
        if (this.hasScroll()) {
            // TODO merge with no-scroll-else
            if (!this.active || this.last()) {
                this.activate(event, this.element.children(".ui-menu-item:first"));
                return;
            }
            var base = this.active.offset().top,
                height = this.element.height(),
                result = this.element.children(".ui-menu-item").filter(function() {
                    var close = $(this).offset().top - base - height + $(this).height();
                    // TODO improve approximation
                    return close < 10 && close > -10;
                });

            // TODO try to catch this earlier when scrollTop indicates the last page anyway
            if (!result.length) {
                result = this.element.children(".ui-menu-item:last");
            }
            this.activate(event, result);
        } else {
            this.activate(event, this.element.children(".ui-menu-item")
                .filter(!this.active || this.last() ? ":first" : ":last"));
        }
    },

    // TODO merge with nextPage
    previousPage: function(event) {
        if (this.hasScroll()) {
            // TODO merge with no-scroll-else
            if (!this.active || this.first()) {
                this.activate(event, this.element.children(".ui-menu-item:last"));
                return;
            }

            var base = this.active.offset().top,
                height = this.element.height();
                result = this.element.children(".ui-menu-item").filter(function() {
                    var close = $(this).offset().top - base + height - $(this).height();
                    // TODO improve approximation
                    return close < 10 && close > -10;
                });

            // TODO try to catch this earlier when scrollTop indicates the last page anyway
            if (!result.length) {
                result = this.element.children(".ui-menu-item:first");
            }
            this.activate(event, result);
        } else {
            this.activate(event, this.element.children(".ui-menu-item")
                .filter(!this.active || this.first() ? ":last" : ":first"));
        }
    },

    hasScroll: function() {
        return this.element.height() < this.element[ $.fn.prop ? "prop" : "attr" ]("scrollHeight");
    },

    select: function( event ) {
        this._trigger("selected", event, { item: this.active });
    }
});

}(jQuery));

// CodeMirror, copyright (c) by Marijn Haverbeke and others
// Distributed under an MIT license: http://codemirror.net/LICENSE

// This is CodeMirror (http://codemirror.net), a code editor
// implemented in JavaScript on top of the browser's DOM.
//
// You can find some technical background for some of the code below
// at http://marijnhaverbeke.nl/blog/#cm-internals .

(function (global, factory) {
    typeof exports === 'object' && typeof module !== 'undefined' ? module.exports = factory() :
        typeof define === 'function' && define.amd ? define(factory) :
            (global.CodeMirror = factory());
}(this, (function () { 'use strict';

// Kludges for bugs and behavior differences that can't be feature
// detected are enabled based on userAgent etc sniffing.
    var userAgent = navigator.userAgent
    var platform = navigator.platform

    var gecko = /gecko\/\d/i.test(userAgent)
    var ie_upto10 = /MSIE \d/.test(userAgent)
    var ie_11up = /Trident\/(?:[7-9]|\d{2,})\..*rv:(\d+)/.exec(userAgent)
    var edge = /Edge\/(\d+)/.exec(userAgent)
    var ie = ie_upto10 || ie_11up || edge
    var ie_version = ie && (ie_upto10 ? document.documentMode || 6 : +(edge || ie_11up)[1])
    var webkit = !edge && /WebKit\//.test(userAgent)
    var qtwebkit = webkit && /Qt\/\d+\.\d+/.test(userAgent)
    var chrome = !edge && /Chrome\//.test(userAgent)
    var presto = /Opera\//.test(userAgent)
    var safari = /Apple Computer/.test(navigator.vendor)
    var mac_geMountainLion = /Mac OS X 1\d\D([8-9]|\d\d)\D/.test(userAgent)
    var phantom = /PhantomJS/.test(userAgent)

    var ios = !edge && /AppleWebKit/.test(userAgent) && /Mobile\/\w+/.test(userAgent)
    var android = /Android/.test(userAgent)
// This is woefully incomplete. Suggestions for alternative methods welcome.
    var mobile = ios || android || /webOS|BlackBerry|Opera Mini|Opera Mobi|IEMobile/i.test(userAgent)
    var mac = ios || /Mac/.test(platform)
    var chromeOS = /\bCrOS\b/.test(userAgent)
    var windows = /win/i.test(platform)

    var presto_version = presto && userAgent.match(/Version\/(\d*\.\d*)/)
    if (presto_version) { presto_version = Number(presto_version[1]) }
    if (presto_version && presto_version >= 15) { presto = false; webkit = true }
// Some browsers use the wrong event properties to signal cmd/ctrl on OS X
    var flipCtrlCmd = mac && (qtwebkit || presto && (presto_version == null || presto_version < 12.11))
    var captureRightClick = gecko || (ie && ie_version >= 9)

    function classTest(cls) { return new RegExp("(^|\\s)" + cls + "(?:$|\\s)\\s*") }

    var rmClass = function(node, cls) {
        var current = node.className
        var match = classTest(cls).exec(current)
        if (match) {
            var after = current.slice(match.index + match[0].length)
            node.className = current.slice(0, match.index) + (after ? match[1] + after : "")
        }
    }

    function removeChildren(e) {
        for (var count = e.childNodes.length; count > 0; --count)
        { e.removeChild(e.firstChild) }
        return e
    }

    function removeChildrenAndAdd(parent, e) {
        return removeChildren(parent).appendChild(e)
    }

    function elt(tag, content, className, style) {
        var e = document.createElement(tag)
        if (className) { e.className = className }
        if (style) { e.style.cssText = style }
        if (typeof content == "string") { e.appendChild(document.createTextNode(content)) }
        else if (content) { for (var i = 0; i < content.length; ++i) { e.appendChild(content[i]) } }
        return e
    }
// wrapper for elt, which removes the elt from the accessibility tree
    function eltP(tag, content, className, style) {
        var e = elt(tag, content, className, style)
        e.setAttribute("role", "presentation")
        return e
    }

    var range
    if (document.createRange) { range = function(node, start, end, endNode) {
        var r = document.createRange()
        r.setEnd(endNode || node, end)
        r.setStart(node, start)
        return r
    } }
    else { range = function(node, start, end) {
        var r = document.body.createTextRange()
        try { r.moveToElementText(node.parentNode) }
        catch(e) { return r }
        r.collapse(true)
        r.moveEnd("character", end)
        r.moveStart("character", start)
        return r
    } }

    function contains(parent, child) {
        if (child.nodeType == 3) // Android browser always returns false when child is a textnode
        { child = child.parentNode }
        if (parent.contains)
        { return parent.contains(child) }
        do {
            if (child.nodeType == 11) { child = child.host }
            if (child == parent) { return true }
        } while (child = child.parentNode)
    }

    function activeElt() {
        // IE and Edge may throw an "Unspecified Error" when accessing document.activeElement.
        // IE < 10 will throw when accessed while the page is loading or in an iframe.
        // IE > 9 and Edge will throw when accessed in an iframe if document.body is unavailable.
        var activeElement
        try {
            activeElement = document.activeElement
        } catch(e) {
            activeElement = document.body || null
        }
        while (activeElement && activeElement.shadowRoot && activeElement.shadowRoot.activeElement)
        { activeElement = activeElement.shadowRoot.activeElement }
        return activeElement
    }

    function addClass(node, cls) {
        var current = node.className
        if (!classTest(cls).test(current)) { node.className += (current ? " " : "") + cls }
    }
    function joinClasses(a, b) {
        var as = a.split(" ")
        for (var i = 0; i < as.length; i++)
        { if (as[i] && !classTest(as[i]).test(b)) { b += " " + as[i] } }
        return b
    }

    var selectInput = function(node) { node.select() }
    if (ios) // Mobile Safari apparently has a bug where select() is broken.
    { selectInput = function(node) { node.selectionStart = 0; node.selectionEnd = node.value.length } }
    else if (ie) // Suppress mysterious IE10 errors
    { selectInput = function(node) { try { node.select() } catch(_e) {} } }

    function bind(f) {
        var args = Array.prototype.slice.call(arguments, 1)
        return function(){return f.apply(null, args)}
    }

    function copyObj(obj, target, overwrite) {
        if (!target) { target = {} }
        for (var prop in obj)
        { if (obj.hasOwnProperty(prop) && (overwrite !== false || !target.hasOwnProperty(prop)))
        { target[prop] = obj[prop] } }
        return target
    }

// Counts the column offset in a string, taking tabs into account.
// Used mostly to find indentation.
    function countColumn(string, end, tabSize, startIndex, startValue) {
        if (end == null) {
            end = string.search(/[^\s\u00a0]/)
            if (end == -1) { end = string.length }
        }
        for (var i = startIndex || 0, n = startValue || 0;;) {
            var nextTab = string.indexOf("\t", i)
            if (nextTab < 0 || nextTab >= end)
            { return n + (end - i) }
            n += nextTab - i
            n += tabSize - (n % tabSize)
            i = nextTab + 1
        }
    }

    var Delayed = function() {this.id = null};
    Delayed.prototype.set = function (ms, f) {
        clearTimeout(this.id)
        this.id = setTimeout(f, ms)
    };

    function indexOf(array, elt) {
        for (var i = 0; i < array.length; ++i)
        { if (array[i] == elt) { return i } }
        return -1
    }

// Number of pixels added to scroller and sizer to hide scrollbar
    var scrollerGap = 30

// Returned or thrown by various protocols to signal 'I'm not
// handling this'.
    var Pass = {toString: function(){return "CodeMirror.Pass"}}

// Reused option objects for setSelection & friends
    var sel_dontScroll = {scroll: false};
    var sel_mouse = {origin: "*mouse"};
    var sel_move = {origin: "+move"};
// The inverse of countColumn -- find the offset that corresponds to
// a particular column.
    function findColumn(string, goal, tabSize) {
        for (var pos = 0, col = 0;;) {
            var nextTab = string.indexOf("\t", pos)
            if (nextTab == -1) { nextTab = string.length }
            var skipped = nextTab - pos
            if (nextTab == string.length || col + skipped >= goal)
            { return pos + Math.min(skipped, goal - col) }
            col += nextTab - pos
            col += tabSize - (col % tabSize)
            pos = nextTab + 1
            if (col >= goal) { return pos }
        }
    }

    var spaceStrs = [""]
    function spaceStr(n) {
        while (spaceStrs.length <= n)
        { spaceStrs.push(lst(spaceStrs) + " ") }
        return spaceStrs[n]
    }

    function lst(arr) { return arr[arr.length-1] }

    function map(array, f) {
        var out = []
        for (var i = 0; i < array.length; i++) { out[i] = f(array[i], i) }
        return out
    }

    function insertSorted(array, value, score) {
        var pos = 0, priority = score(value)
        while (pos < array.length && score(array[pos]) <= priority) { pos++ }
        array.splice(pos, 0, value)
    }

    function nothing() {}

    function createObj(base, props) {
        var inst
        if (Object.create) {
            inst = Object.create(base)
        } else {
            nothing.prototype = base
            inst = new nothing()
        }
        if (props) { copyObj(props, inst) }
        return inst
    }

    var nonASCIISingleCaseWordChar = /[\u00df\u0587\u0590-\u05f4\u0600-\u06ff\u3040-\u309f\u30a0-\u30ff\u3400-\u4db5\u4e00-\u9fcc\uac00-\ud7af]/
    function isWordCharBasic(ch) {
        return /\w/.test(ch) || ch > "\x80" &&
            (ch.toUpperCase() != ch.toLowerCase() || nonASCIISingleCaseWordChar.test(ch))
    }
    function isWordChar(ch, helper) {
        if (!helper) { return isWordCharBasic(ch) }
        if (helper.source.indexOf("\\w") > -1 && isWordCharBasic(ch)) { return true }
        return helper.test(ch)
    }

    function isEmpty(obj) {
        for (var n in obj) { if (obj.hasOwnProperty(n) && obj[n]) { return false } }
        return true
    }

// Extending unicode characters. A series of a non-extending char +
// any number of extending chars is treated as a single unit as far
// as editing and measuring is concerned. This is not fully correct,
// since some scripts/fonts/browsers also treat other configurations
// of code points as a group.
    var extendingChars = /[\u0300-\u036f\u0483-\u0489\u0591-\u05bd\u05bf\u05c1\u05c2\u05c4\u05c5\u05c7\u0610-\u061a\u064b-\u065e\u0670\u06d6-\u06dc\u06de-\u06e4\u06e7\u06e8\u06ea-\u06ed\u0711\u0730-\u074a\u07a6-\u07b0\u07eb-\u07f3\u0816-\u0819\u081b-\u0823\u0825-\u0827\u0829-\u082d\u0900-\u0902\u093c\u0941-\u0948\u094d\u0951-\u0955\u0962\u0963\u0981\u09bc\u09be\u09c1-\u09c4\u09cd\u09d7\u09e2\u09e3\u0a01\u0a02\u0a3c\u0a41\u0a42\u0a47\u0a48\u0a4b-\u0a4d\u0a51\u0a70\u0a71\u0a75\u0a81\u0a82\u0abc\u0ac1-\u0ac5\u0ac7\u0ac8\u0acd\u0ae2\u0ae3\u0b01\u0b3c\u0b3e\u0b3f\u0b41-\u0b44\u0b4d\u0b56\u0b57\u0b62\u0b63\u0b82\u0bbe\u0bc0\u0bcd\u0bd7\u0c3e-\u0c40\u0c46-\u0c48\u0c4a-\u0c4d\u0c55\u0c56\u0c62\u0c63\u0cbc\u0cbf\u0cc2\u0cc6\u0ccc\u0ccd\u0cd5\u0cd6\u0ce2\u0ce3\u0d3e\u0d41-\u0d44\u0d4d\u0d57\u0d62\u0d63\u0dca\u0dcf\u0dd2-\u0dd4\u0dd6\u0ddf\u0e31\u0e34-\u0e3a\u0e47-\u0e4e\u0eb1\u0eb4-\u0eb9\u0ebb\u0ebc\u0ec8-\u0ecd\u0f18\u0f19\u0f35\u0f37\u0f39\u0f71-\u0f7e\u0f80-\u0f84\u0f86\u0f87\u0f90-\u0f97\u0f99-\u0fbc\u0fc6\u102d-\u1030\u1032-\u1037\u1039\u103a\u103d\u103e\u1058\u1059\u105e-\u1060\u1071-\u1074\u1082\u1085\u1086\u108d\u109d\u135f\u1712-\u1714\u1732-\u1734\u1752\u1753\u1772\u1773\u17b7-\u17bd\u17c6\u17c9-\u17d3\u17dd\u180b-\u180d\u18a9\u1920-\u1922\u1927\u1928\u1932\u1939-\u193b\u1a17\u1a18\u1a56\u1a58-\u1a5e\u1a60\u1a62\u1a65-\u1a6c\u1a73-\u1a7c\u1a7f\u1b00-\u1b03\u1b34\u1b36-\u1b3a\u1b3c\u1b42\u1b6b-\u1b73\u1b80\u1b81\u1ba2-\u1ba5\u1ba8\u1ba9\u1c2c-\u1c33\u1c36\u1c37\u1cd0-\u1cd2\u1cd4-\u1ce0\u1ce2-\u1ce8\u1ced\u1dc0-\u1de6\u1dfd-\u1dff\u200c\u200d\u20d0-\u20f0\u2cef-\u2cf1\u2de0-\u2dff\u302a-\u302f\u3099\u309a\ua66f-\ua672\ua67c\ua67d\ua6f0\ua6f1\ua802\ua806\ua80b\ua825\ua826\ua8c4\ua8e0-\ua8f1\ua926-\ua92d\ua947-\ua951\ua980-\ua982\ua9b3\ua9b6-\ua9b9\ua9bc\uaa29-\uaa2e\uaa31\uaa32\uaa35\uaa36\uaa43\uaa4c\uaab0\uaab2-\uaab4\uaab7\uaab8\uaabe\uaabf\uaac1\uabe5\uabe8\uabed\udc00-\udfff\ufb1e\ufe00-\ufe0f\ufe20-\ufe26\uff9e\uff9f]/
    function isExtendingChar(ch) { return ch.charCodeAt(0) >= 768 && extendingChars.test(ch) }

// Returns a number from the range [`0`; `str.length`] unless `pos` is outside that range.
    function skipExtendingChars(str, pos, dir) {
        while ((dir < 0 ? pos > 0 : pos < str.length) && isExtendingChar(str.charAt(pos))) { pos += dir }
        return pos
    }

// Returns the value from the range [`from`; `to`] that satisfies
// `pred` and is closest to `from`. Assumes that at least `to`
// satisfies `pred`. Supports `from` being greater than `to`.
    function findFirst(pred, from, to) {
        // At any point we are certain `to` satisfies `pred`, don't know
        // whether `from` does.
        var dir = from > to ? -1 : 1
        for (;;) {
            if (from == to) { return from }
            var midF = (from + to) / 2, mid = dir < 0 ? Math.ceil(midF) : Math.floor(midF)
            if (mid == from) { return pred(mid) ? from : to }
            if (pred(mid)) { to = mid }
            else { from = mid + dir }
        }
    }

// The display handles the DOM integration, both for input reading
// and content drawing. It holds references to DOM nodes and
// display-related state.

    function Display(place, doc, input) {
        var d = this
        this.input = input

        // Covers bottom-right square when both scrollbars are present.
        d.scrollbarFiller = elt("div", null, "CodeMirror-scrollbar-filler")
        d.scrollbarFiller.setAttribute("cm-not-content", "true")
        // Covers bottom of gutter when coverGutterNextToScrollbar is on
        // and h scrollbar is present.
        d.gutterFiller = elt("div", null, "CodeMirror-gutter-filler")
        d.gutterFiller.setAttribute("cm-not-content", "true")
        // Will contain the actual code, positioned to cover the viewport.
        d.lineDiv = eltP("div", null, "CodeMirror-code")
        // Elements are added to these to represent selection and cursors.
        d.selectionDiv = elt("div", null, null, "position: relative; z-index: 1")
        d.cursorDiv = elt("div", null, "CodeMirror-cursors")
        // A visibility: hidden element used to find the size of things.
        d.measure = elt("div", null, "CodeMirror-measure")
        // When lines outside of the viewport are measured, they are drawn in this.
        d.lineMeasure = elt("div", null, "CodeMirror-measure")
        // Wraps everything that needs to exist inside the vertically-padded coordinate system
        d.lineSpace = eltP("div", [d.measure, d.lineMeasure, d.selectionDiv, d.cursorDiv, d.lineDiv],
            null, "position: relative; outline: none")
        var lines = eltP("div", [d.lineSpace], "CodeMirror-lines")
        // Moved around its parent to cover visible view.
        d.mover = elt("div", [lines], null, "position: relative")
        // Set to the height of the document, allowing scrolling.
        d.sizer = elt("div", [d.mover], "CodeMirror-sizer")
        d.sizerWidth = null
        // Behavior of elts with overflow: auto and padding is
        // inconsistent across browsers. This is used to ensure the
        // scrollable area is big enough.
        d.heightForcer = elt("div", null, null, "position: absolute; height: " + scrollerGap + "px; width: 1px;")
        // Will contain the gutters, if any.
        d.gutters = elt("div", null, "CodeMirror-gutters")
        d.lineGutter = null
        // Actual scrollable element.
        d.scroller = elt("div", [d.sizer, d.heightForcer, d.gutters], "CodeMirror-scroll")
        d.scroller.setAttribute("tabIndex", "-1")
        // The element in which the editor lives.
        d.wrapper = elt("div", [d.scrollbarFiller, d.gutterFiller, d.scroller], "CodeMirror")

        // Work around IE7 z-index bug (not perfect, hence IE7 not really being supported)
        if (ie && ie_version < 8) { d.gutters.style.zIndex = -1; d.scroller.style.paddingRight = 0 }
        if (!webkit && !(gecko && mobile)) { d.scroller.draggable = true }

        if (place) {
            if (place.appendChild) { place.appendChild(d.wrapper) }
            else { place(d.wrapper) }
        }

        // Current rendered range (may be bigger than the view window).
        d.viewFrom = d.viewTo = doc.first
        d.reportedViewFrom = d.reportedViewTo = doc.first
        // Information about the rendered lines.
        d.view = []
        d.renderedView = null
        // Holds info about a single rendered line when it was rendered
        // for measurement, while not in view.
        d.externalMeasured = null
        // Empty space (in pixels) above the view
        d.viewOffset = 0
        d.lastWrapHeight = d.lastWrapWidth = 0
        d.updateLineNumbers = null

        d.nativeBarWidth = d.barHeight = d.barWidth = 0
        d.scrollbarsClipped = false

        // Used to only resize the line number gutter when necessary (when
        // the amount of lines crosses a boundary that makes its width change)
        d.lineNumWidth = d.lineNumInnerWidth = d.lineNumChars = null
        // Set to true when a non-horizontal-scrolling line widget is
        // added. As an optimization, line widget aligning is skipped when
        // this is false.
        d.alignWidgets = false

        d.cachedCharWidth = d.cachedTextHeight = d.cachedPaddingH = null

        // Tracks the maximum line length so that the horizontal scrollbar
        // can be kept static when scrolling.
        d.maxLine = null
        d.maxLineLength = 0
        d.maxLineChanged = false

        // Used for measuring wheel scrolling granularity
        d.wheelDX = d.wheelDY = d.wheelStartX = d.wheelStartY = null

        // True when shift is held down.
        d.shift = false

        // Used to track whether anything happened since the context menu
        // was opened.
        d.selForContextMenu = null

        d.activeTouch = null

        input.init(d)
    }

// Find the line object corresponding to the given line number.
    function getLine(doc, n) {
        n -= doc.first
        if (n < 0 || n >= doc.size) { throw new Error("There is no line " + (n + doc.first) + " in the document.") }
        var chunk = doc
        while (!chunk.lines) {
            for (var i = 0;; ++i) {
                var child = chunk.children[i], sz = child.chunkSize()
                if (n < sz) { chunk = child; break }
                n -= sz
            }
        }
        return chunk.lines[n]
    }

// Get the part of a document between two positions, as an array of
// strings.
    function getBetween(doc, start, end) {
        var out = [], n = start.line
        doc.iter(start.line, end.line + 1, function (line) {
            var text = line.text
            if (n == end.line) { text = text.slice(0, end.ch) }
            if (n == start.line) { text = text.slice(start.ch) }
            out.push(text)
            ++n
        })
        return out
    }
// Get the lines between from and to, as array of strings.
    function getLines(doc, from, to) {
        var out = []
        doc.iter(from, to, function (line) { out.push(line.text) }) // iter aborts when callback returns truthy value
        return out
    }

// Update the height of a line, propagating the height change
// upwards to parent nodes.
    function updateLineHeight(line, height) {
        var diff = height - line.height
        if (diff) { for (var n = line; n; n = n.parent) { n.height += diff } }
    }

// Given a line object, find its line number by walking up through
// its parent links.
    function lineNo(line) {
        if (line.parent == null) { return null }
        var cur = line.parent, no = indexOf(cur.lines, line)
        for (var chunk = cur.parent; chunk; cur = chunk, chunk = chunk.parent) {
            for (var i = 0;; ++i) {
                if (chunk.children[i] == cur) { break }
                no += chunk.children[i].chunkSize()
            }
        }
        return no + cur.first
    }

// Find the line at the given vertical position, using the height
// information in the document tree.
    function lineAtHeight(chunk, h) {
        var n = chunk.first
        outer: do {
            for (var i$1 = 0; i$1 < chunk.children.length; ++i$1) {
                var child = chunk.children[i$1], ch = child.height
                if (h < ch) { chunk = child; continue outer }
                h -= ch
                n += child.chunkSize()
            }
            return n
        } while (!chunk.lines)
        var i = 0
        for (; i < chunk.lines.length; ++i) {
            var line = chunk.lines[i], lh = line.height
            if (h < lh) { break }
            h -= lh
        }
        return n + i
    }

    function isLine(doc, l) {return l >= doc.first && l < doc.first + doc.size}

    function lineNumberFor(options, i) {
        return String(options.lineNumberFormatter(i + options.firstLineNumber))
    }

// A Pos instance represents a position within the text.
    function Pos(line, ch, sticky) {
        if ( sticky === void 0 ) sticky = null;

        if (!(this instanceof Pos)) { return new Pos(line, ch, sticky) }
        this.line = line
        this.ch = ch
        this.sticky = sticky
    }

// Compare two positions, return 0 if they are the same, a negative
// number when a is less, and a positive number otherwise.
    function cmp(a, b) { return a.line - b.line || a.ch - b.ch }

    function equalCursorPos(a, b) { return a.sticky == b.sticky && cmp(a, b) == 0 }

    function copyPos(x) {return Pos(x.line, x.ch)}
    function maxPos(a, b) { return cmp(a, b) < 0 ? b : a }
    function minPos(a, b) { return cmp(a, b) < 0 ? a : b }

// Most of the external API clips given positions to make sure they
// actually exist within the document.
    function clipLine(doc, n) {return Math.max(doc.first, Math.min(n, doc.first + doc.size - 1))}
    function clipPos(doc, pos) {
        if (pos.line < doc.first) { return Pos(doc.first, 0) }
        var last = doc.first + doc.size - 1
        if (pos.line > last) { return Pos(last, getLine(doc, last).text.length) }
        return clipToLen(pos, getLine(doc, pos.line).text.length)
    }
    function clipToLen(pos, linelen) {
        var ch = pos.ch
        if (ch == null || ch > linelen) { return Pos(pos.line, linelen) }
        else if (ch < 0) { return Pos(pos.line, 0) }
        else { return pos }
    }
    function clipPosArray(doc, array) {
        var out = []
        for (var i = 0; i < array.length; i++) { out[i] = clipPos(doc, array[i]) }
        return out
    }

// Optimize some code when these features are not used.
    var sawReadOnlySpans = false;
    var sawCollapsedSpans = false;
    function seeReadOnlySpans() {
        sawReadOnlySpans = true
    }

    function seeCollapsedSpans() {
        sawCollapsedSpans = true
    }

// TEXTMARKER SPANS

    function MarkedSpan(marker, from, to) {
        this.marker = marker
        this.from = from; this.to = to
    }

// Search an array of spans for a span matching the given marker.
    function getMarkedSpanFor(spans, marker) {
        if (spans) { for (var i = 0; i < spans.length; ++i) {
            var span = spans[i]
            if (span.marker == marker) { return span }
        } }
    }
// Remove a span from an array, returning undefined if no spans are
// left (we don't store arrays for lines without spans).
    function removeMarkedSpan(spans, span) {
        var r
        for (var i = 0; i < spans.length; ++i)
        { if (spans[i] != span) { (r || (r = [])).push(spans[i]) } }
        return r
    }
// Add a span to a line.
    function addMarkedSpan(line, span) {
        line.markedSpans = line.markedSpans ? line.markedSpans.concat([span]) : [span]
        span.marker.attachLine(line)
    }

// Used for the algorithm that adjusts markers for a change in the
// document. These functions cut an array of spans at a given
// character position, returning an array of remaining chunks (or
// undefined if nothing remains).
    function markedSpansBefore(old, startCh, isInsert) {
        var nw
        if (old) { for (var i = 0; i < old.length; ++i) {
            var span = old[i], marker = span.marker
            var startsBefore = span.from == null || (marker.inclusiveLeft ? span.from <= startCh : span.from < startCh)
            if (startsBefore || span.from == startCh && marker.type == "bookmark" && (!isInsert || !span.marker.insertLeft)) {
                var endsAfter = span.to == null || (marker.inclusiveRight ? span.to >= startCh : span.to > startCh)
                ;(nw || (nw = [])).push(new MarkedSpan(marker, span.from, endsAfter ? null : span.to))
            }
        } }
        return nw
    }
    function markedSpansAfter(old, endCh, isInsert) {
        var nw
        if (old) { for (var i = 0; i < old.length; ++i) {
            var span = old[i], marker = span.marker
            var endsAfter = span.to == null || (marker.inclusiveRight ? span.to >= endCh : span.to > endCh)
            if (endsAfter || span.from == endCh && marker.type == "bookmark" && (!isInsert || span.marker.insertLeft)) {
                var startsBefore = span.from == null || (marker.inclusiveLeft ? span.from <= endCh : span.from < endCh)
                ;(nw || (nw = [])).push(new MarkedSpan(marker, startsBefore ? null : span.from - endCh,
                    span.to == null ? null : span.to - endCh))
            }
        } }
        return nw
    }

// Given a change object, compute the new set of marker spans that
// cover the line in which the change took place. Removes spans
// entirely within the change, reconnects spans belonging to the
// same marker that appear on both sides of the change, and cuts off
// spans partially within the change. Returns an array of span
// arrays with one element for each line in (after) the change.
    function stretchSpansOverChange(doc, change) {
        if (change.full) { return null }
        var oldFirst = isLine(doc, change.from.line) && getLine(doc, change.from.line).markedSpans
        var oldLast = isLine(doc, change.to.line) && getLine(doc, change.to.line).markedSpans
        if (!oldFirst && !oldLast) { return null }

        var startCh = change.from.ch, endCh = change.to.ch, isInsert = cmp(change.from, change.to) == 0
        // Get the spans that 'stick out' on both sides
        var first = markedSpansBefore(oldFirst, startCh, isInsert)
        var last = markedSpansAfter(oldLast, endCh, isInsert)

        // Next, merge those two ends
        var sameLine = change.text.length == 1, offset = lst(change.text).length + (sameLine ? startCh : 0)
        if (first) {
            // Fix up .to properties of first
            for (var i = 0; i < first.length; ++i) {
                var span = first[i]
                if (span.to == null) {
                    var found = getMarkedSpanFor(last, span.marker)
                    if (!found) { span.to = startCh }
                    else if (sameLine) { span.to = found.to == null ? null : found.to + offset }
                }
            }
        }
        if (last) {
            // Fix up .from in last (or move them into first in case of sameLine)
            for (var i$1 = 0; i$1 < last.length; ++i$1) {
                var span$1 = last[i$1]
                if (span$1.to != null) { span$1.to += offset }
                if (span$1.from == null) {
                    var found$1 = getMarkedSpanFor(first, span$1.marker)
                    if (!found$1) {
                        span$1.from = offset
                        if (sameLine) { (first || (first = [])).push(span$1) }
                    }
                } else {
                    span$1.from += offset
                    if (sameLine) { (first || (first = [])).push(span$1) }
                }
            }
        }
        // Make sure we didn't create any zero-length spans
        if (first) { first = clearEmptySpans(first) }
        if (last && last != first) { last = clearEmptySpans(last) }

        var newMarkers = [first]
        if (!sameLine) {
            // Fill gap with whole-line-spans
            var gap = change.text.length - 2, gapMarkers
            if (gap > 0 && first)
            { for (var i$2 = 0; i$2 < first.length; ++i$2)
            { if (first[i$2].to == null)
            { (gapMarkers || (gapMarkers = [])).push(new MarkedSpan(first[i$2].marker, null, null)) } } }
            for (var i$3 = 0; i$3 < gap; ++i$3)
            { newMarkers.push(gapMarkers) }
            newMarkers.push(last)
        }
        return newMarkers
    }

// Remove spans that are empty and don't have a clearWhenEmpty
// option of false.
    function clearEmptySpans(spans) {
        for (var i = 0; i < spans.length; ++i) {
            var span = spans[i]
            if (span.from != null && span.from == span.to && span.marker.clearWhenEmpty !== false)
            { spans.splice(i--, 1) }
        }
        if (!spans.length) { return null }
        return spans
    }

// Used to 'clip' out readOnly ranges when making a change.
    function removeReadOnlyRanges(doc, from, to) {
        var markers = null
        doc.iter(from.line, to.line + 1, function (line) {
            if (line.markedSpans) { for (var i = 0; i < line.markedSpans.length; ++i) {
                var mark = line.markedSpans[i].marker
                if (mark.readOnly && (!markers || indexOf(markers, mark) == -1))
                { (markers || (markers = [])).push(mark) }
            } }
        })
        if (!markers) { return null }
        var parts = [{from: from, to: to}]
        for (var i = 0; i < markers.length; ++i) {
            var mk = markers[i], m = mk.find(0)
            for (var j = 0; j < parts.length; ++j) {
                var p = parts[j]
                if (cmp(p.to, m.from) < 0 || cmp(p.from, m.to) > 0) { continue }
                var newParts = [j, 1], dfrom = cmp(p.from, m.from), dto = cmp(p.to, m.to)
                if (dfrom < 0 || !mk.inclusiveLeft && !dfrom)
                { newParts.push({from: p.from, to: m.from}) }
                if (dto > 0 || !mk.inclusiveRight && !dto)
                { newParts.push({from: m.to, to: p.to}) }
                parts.splice.apply(parts, newParts)
                j += newParts.length - 3
            }
        }
        return parts
    }

// Connect or disconnect spans from a line.
    function detachMarkedSpans(line) {
        var spans = line.markedSpans
        if (!spans) { return }
        for (var i = 0; i < spans.length; ++i)
        { spans[i].marker.detachLine(line) }
        line.markedSpans = null
    }
    function attachMarkedSpans(line, spans) {
        if (!spans) { return }
        for (var i = 0; i < spans.length; ++i)
        { spans[i].marker.attachLine(line) }
        line.markedSpans = spans
    }

// Helpers used when computing which overlapping collapsed span
// counts as the larger one.
    function extraLeft(marker) { return marker.inclusiveLeft ? -1 : 0 }
    function extraRight(marker) { return marker.inclusiveRight ? 1 : 0 }

// Returns a number indicating which of two overlapping collapsed
// spans is larger (and thus includes the other). Falls back to
// comparing ids when the spans cover exactly the same range.
    function compareCollapsedMarkers(a, b) {
        var lenDiff = a.lines.length - b.lines.length
        if (lenDiff != 0) { return lenDiff }
        var aPos = a.find(), bPos = b.find()
        var fromCmp = cmp(aPos.from, bPos.from) || extraLeft(a) - extraLeft(b)
        if (fromCmp) { return -fromCmp }
        var toCmp = cmp(aPos.to, bPos.to) || extraRight(a) - extraRight(b)
        if (toCmp) { return toCmp }
        return b.id - a.id
    }

// Find out whether a line ends or starts in a collapsed span. If
// so, return the marker for that span.
    function collapsedSpanAtSide(line, start) {
        var sps = sawCollapsedSpans && line.markedSpans, found
        if (sps) { for (var sp = (void 0), i = 0; i < sps.length; ++i) {
            sp = sps[i]
            if (sp.marker.collapsed && (start ? sp.from : sp.to) == null &&
                (!found || compareCollapsedMarkers(found, sp.marker) < 0))
            { found = sp.marker }
        } }
        return found
    }
    function collapsedSpanAtStart(line) { return collapsedSpanAtSide(line, true) }
    function collapsedSpanAtEnd(line) { return collapsedSpanAtSide(line, false) }

// Test whether there exists a collapsed span that partially
// overlaps (covers the start or end, but not both) of a new span.
// Such overlap is not allowed.
    function conflictingCollapsedRange(doc, lineNo, from, to, marker) {
        var line = getLine(doc, lineNo)
        var sps = sawCollapsedSpans && line.markedSpans
        if (sps) { for (var i = 0; i < sps.length; ++i) {
            var sp = sps[i]
            if (!sp.marker.collapsed) { continue }
            var found = sp.marker.find(0)
            var fromCmp = cmp(found.from, from) || extraLeft(sp.marker) - extraLeft(marker)
            var toCmp = cmp(found.to, to) || extraRight(sp.marker) - extraRight(marker)
            if (fromCmp >= 0 && toCmp <= 0 || fromCmp <= 0 && toCmp >= 0) { continue }
            if (fromCmp <= 0 && (sp.marker.inclusiveRight && marker.inclusiveLeft ? cmp(found.to, from) >= 0 : cmp(found.to, from) > 0) ||
                fromCmp >= 0 && (sp.marker.inclusiveRight && marker.inclusiveLeft ? cmp(found.from, to) <= 0 : cmp(found.from, to) < 0))
            { return true }
        } }
    }

// A visual line is a line as drawn on the screen. Folding, for
// example, can cause multiple logical lines to appear on the same
// visual line. This finds the start of the visual line that the
// given line is part of (usually that is the line itself).
    function visualLine(line) {
        var merged
        while (merged = collapsedSpanAtStart(line))
        { line = merged.find(-1, true).line }
        return line
    }

    function visualLineEnd(line) {
        var merged
        while (merged = collapsedSpanAtEnd(line))
        { line = merged.find(1, true).line }
        return line
    }

// Returns an array of logical lines that continue the visual line
// started by the argument, or undefined if there are no such lines.
    function visualLineContinued(line) {
        var merged, lines
        while (merged = collapsedSpanAtEnd(line)) {
            line = merged.find(1, true).line
            ;(lines || (lines = [])).push(line)
        }
        return lines
    }

// Get the line number of the start of the visual line that the
// given line number is part of.
    function visualLineNo(doc, lineN) {
        var line = getLine(doc, lineN), vis = visualLine(line)
        if (line == vis) { return lineN }
        return lineNo(vis)
    }

// Get the line number of the start of the next visual line after
// the given line.
    function visualLineEndNo(doc, lineN) {
        if (lineN > doc.lastLine()) { return lineN }
        var line = getLine(doc, lineN), merged
        if (!lineIsHidden(doc, line)) { return lineN }
        while (merged = collapsedSpanAtEnd(line))
        { line = merged.find(1, true).line }
        return lineNo(line) + 1
    }

// Compute whether a line is hidden. Lines count as hidden when they
// are part of a visual line that starts with another line, or when
// they are entirely covered by collapsed, non-widget span.
    function lineIsHidden(doc, line) {
        var sps = sawCollapsedSpans && line.markedSpans
        if (sps) { for (var sp = (void 0), i = 0; i < sps.length; ++i) {
            sp = sps[i]
            if (!sp.marker.collapsed) { continue }
            if (sp.from == null) { return true }
            if (sp.marker.widgetNode) { continue }
            if (sp.from == 0 && sp.marker.inclusiveLeft && lineIsHiddenInner(doc, line, sp))
            { return true }
        } }
    }
    function lineIsHiddenInner(doc, line, span) {
        if (span.to == null) {
            var end = span.marker.find(1, true)
            return lineIsHiddenInner(doc, end.line, getMarkedSpanFor(end.line.markedSpans, span.marker))
        }
        if (span.marker.inclusiveRight && span.to == line.text.length)
        { return true }
        for (var sp = (void 0), i = 0; i < line.markedSpans.length; ++i) {
            sp = line.markedSpans[i]
            if (sp.marker.collapsed && !sp.marker.widgetNode && sp.from == span.to &&
                (sp.to == null || sp.to != span.from) &&
                (sp.marker.inclusiveLeft || span.marker.inclusiveRight) &&
                lineIsHiddenInner(doc, line, sp)) { return true }
        }
    }

// Find the height above the given line.
    function heightAtLine(lineObj) {
        lineObj = visualLine(lineObj)

        var h = 0, chunk = lineObj.parent
        for (var i = 0; i < chunk.lines.length; ++i) {
            var line = chunk.lines[i]
            if (line == lineObj) { break }
            else { h += line.height }
        }
        for (var p = chunk.parent; p; chunk = p, p = chunk.parent) {
            for (var i$1 = 0; i$1 < p.children.length; ++i$1) {
                var cur = p.children[i$1]
                if (cur == chunk) { break }
                else { h += cur.height }
            }
        }
        return h
    }

// Compute the character length of a line, taking into account
// collapsed ranges (see markText) that might hide parts, and join
// other lines onto it.
    function lineLength(line) {
        if (line.height == 0) { return 0 }
        var len = line.text.length, merged, cur = line
        while (merged = collapsedSpanAtStart(cur)) {
            var found = merged.find(0, true)
            cur = found.from.line
            len += found.from.ch - found.to.ch
        }
        cur = line
        while (merged = collapsedSpanAtEnd(cur)) {
            var found$1 = merged.find(0, true)
            len -= cur.text.length - found$1.from.ch
            cur = found$1.to.line
            len += cur.text.length - found$1.to.ch
        }
        return len
    }

// Find the longest line in the document.
    function findMaxLine(cm) {
        var d = cm.display, doc = cm.doc
        d.maxLine = getLine(doc, doc.first)
        d.maxLineLength = lineLength(d.maxLine)
        d.maxLineChanged = true
        doc.iter(function (line) {
            var len = lineLength(line)
            if (len > d.maxLineLength) {
                d.maxLineLength = len
                d.maxLine = line
            }
        })
    }

// BIDI HELPERS

    function iterateBidiSections(order, from, to, f) {
        if (!order) { return f(from, to, "ltr", 0) }
        var found = false
        for (var i = 0; i < order.length; ++i) {
            var part = order[i]
            if (part.from < to && part.to > from || from == to && part.to == from) {
                f(Math.max(part.from, from), Math.min(part.to, to), part.level == 1 ? "rtl" : "ltr", i)
                found = true
            }
        }
        if (!found) { f(from, to, "ltr") }
    }

    var bidiOther = null
    function getBidiPartAt(order, ch, sticky) {
        var found
        bidiOther = null
        for (var i = 0; i < order.length; ++i) {
            var cur = order[i]
            if (cur.from < ch && cur.to > ch) { return i }
            if (cur.to == ch) {
                if (cur.from != cur.to && sticky == "before") { found = i }
                else { bidiOther = i }
            }
            if (cur.from == ch) {
                if (cur.from != cur.to && sticky != "before") { found = i }
                else { bidiOther = i }
            }
        }
        return found != null ? found : bidiOther
    }

// Bidirectional ordering algorithm
// See http://unicode.org/reports/tr9/tr9-13.html for the algorithm
// that this (partially) implements.

// One-char codes used for character types:
// L (L):   Left-to-Right
// R (R):   Right-to-Left
// r (AL):  Right-to-Left Arabic
// 1 (EN):  European Number
// + (ES):  European Number Separator
// % (ET):  European Number Terminator
// n (AN):  Arabic Number
// , (CS):  Common Number Separator
// m (NSM): Non-Spacing Mark
// b (BN):  Boundary Neutral
// s (B):   Paragraph Separator
// t (S):   Segment Separator
// w (WS):  Whitespace
// N (ON):  Other Neutrals

// Returns null if characters are ordered as they appear
// (left-to-right), or an array of sections ({from, to, level}
// objects) in the order in which they occur visually.
    var bidiOrdering = (function() {
        // Character types for codepoints 0 to 0xff
        var lowTypes = "bbbbbbbbbtstwsbbbbbbbbbbbbbbssstwNN%%%NNNNNN,N,N1111111111NNNNNNNLLLLLLLLLLLLLLLLLLLLLLLLLLNNNNNNLLLLLLLLLLLLLLLLLLLLLLLLLLNNNNbbbbbbsbbbbbbbbbbbbbbbbbbbbbbbbbb,N%%%%NNNNLNNNNN%%11NLNNN1LNNNNNLLLLLLLLLLLLLLLLLLLLLLLNLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLN"
        // Character types for codepoints 0x600 to 0x6f9
        var arabicTypes = "nnnnnnNNr%%r,rNNmmmmmmmmmmmrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrmmmmmmmmmmmmmmmmmmmmmnnnnnnnnnn%nnrrrmrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrmmmmmmmnNmmmmmmrrmmNmmmmrr1111111111"
        function charType(code) {
            if (code <= 0xf7) { return lowTypes.charAt(code) }
            else if (0x590 <= code && code <= 0x5f4) { return "R" }
            else if (0x600 <= code && code <= 0x6f9) { return arabicTypes.charAt(code - 0x600) }
            else if (0x6ee <= code && code <= 0x8ac) { return "r" }
            else if (0x2000 <= code && code <= 0x200b) { return "w" }
            else if (code == 0x200c) { return "b" }
            else { return "L" }
        }

        var bidiRE = /[\u0590-\u05f4\u0600-\u06ff\u0700-\u08ac]/
        var isNeutral = /[stwN]/, isStrong = /[LRr]/, countsAsLeft = /[Lb1n]/, countsAsNum = /[1n]/

        function BidiSpan(level, from, to) {
            this.level = level
            this.from = from; this.to = to
        }

        return function(str, direction) {
            var outerType = direction == "ltr" ? "L" : "R"

            if (str.length == 0 || direction == "ltr" && !bidiRE.test(str)) { return false }
            var len = str.length, types = []
            for (var i = 0; i < len; ++i)
            { types.push(charType(str.charCodeAt(i))) }

            // W1. Examine each non-spacing mark (NSM) in the level run, and
            // change the type of the NSM to the type of the previous
            // character. If the NSM is at the start of the level run, it will
            // get the type of sor.
            for (var i$1 = 0, prev = outerType; i$1 < len; ++i$1) {
                var type = types[i$1]
                if (type == "m") { types[i$1] = prev }
                else { prev = type }
            }

            // W2. Search backwards from each instance of a European number
            // until the first strong type (R, L, AL, or sor) is found. If an
            // AL is found, change the type of the European number to Arabic
            // number.
            // W3. Change all ALs to R.
            for (var i$2 = 0, cur = outerType; i$2 < len; ++i$2) {
                var type$1 = types[i$2]
                if (type$1 == "1" && cur == "r") { types[i$2] = "n" }
                else if (isStrong.test(type$1)) { cur = type$1; if (type$1 == "r") { types[i$2] = "R" } }
            }

            // W4. A single European separator between two European numbers
            // changes to a European number. A single common separator between
            // two numbers of the same type changes to that type.
            for (var i$3 = 1, prev$1 = types[0]; i$3 < len - 1; ++i$3) {
                var type$2 = types[i$3]
                if (type$2 == "+" && prev$1 == "1" && types[i$3+1] == "1") { types[i$3] = "1" }
                else if (type$2 == "," && prev$1 == types[i$3+1] &&
                    (prev$1 == "1" || prev$1 == "n")) { types[i$3] = prev$1 }
                prev$1 = type$2
            }

            // W5. A sequence of European terminators adjacent to European
            // numbers changes to all European numbers.
            // W6. Otherwise, separators and terminators change to Other
            // Neutral.
            for (var i$4 = 0; i$4 < len; ++i$4) {
                var type$3 = types[i$4]
                if (type$3 == ",") { types[i$4] = "N" }
                else if (type$3 == "%") {
                    var end = (void 0)
                    for (end = i$4 + 1; end < len && types[end] == "%"; ++end) {}
                    var replace = (i$4 && types[i$4-1] == "!") || (end < len && types[end] == "1") ? "1" : "N"
                    for (var j = i$4; j < end; ++j) { types[j] = replace }
                    i$4 = end - 1
                }
            }

            // W7. Search backwards from each instance of a European number
            // until the first strong type (R, L, or sor) is found. If an L is
            // found, then change the type of the European number to L.
            for (var i$5 = 0, cur$1 = outerType; i$5 < len; ++i$5) {
                var type$4 = types[i$5]
                if (cur$1 == "L" && type$4 == "1") { types[i$5] = "L" }
                else if (isStrong.test(type$4)) { cur$1 = type$4 }
            }

            // N1. A sequence of neutrals takes the direction of the
            // surrounding strong text if the text on both sides has the same
            // direction. European and Arabic numbers act as if they were R in
            // terms of their influence on neutrals. Start-of-level-run (sor)
            // and end-of-level-run (eor) are used at level run boundaries.
            // N2. Any remaining neutrals take the embedding direction.
            for (var i$6 = 0; i$6 < len; ++i$6) {
                if (isNeutral.test(types[i$6])) {
                    var end$1 = (void 0)
                    for (end$1 = i$6 + 1; end$1 < len && isNeutral.test(types[end$1]); ++end$1) {}
                    var before = (i$6 ? types[i$6-1] : outerType) == "L"
                    var after = (end$1 < len ? types[end$1] : outerType) == "L"
                    var replace$1 = before == after ? (before ? "L" : "R") : outerType
                    for (var j$1 = i$6; j$1 < end$1; ++j$1) { types[j$1] = replace$1 }
                    i$6 = end$1 - 1
                }
            }

            // Here we depart from the documented algorithm, in order to avoid
            // building up an actual levels array. Since there are only three
            // levels (0, 1, 2) in an implementation that doesn't take
            // explicit embedding into account, we can build up the order on
            // the fly, without following the level-based algorithm.
            var order = [], m
            for (var i$7 = 0; i$7 < len;) {
                if (countsAsLeft.test(types[i$7])) {
                    var start = i$7
                    for (++i$7; i$7 < len && countsAsLeft.test(types[i$7]); ++i$7) {}
                    order.push(new BidiSpan(0, start, i$7))
                } else {
                    var pos = i$7, at = order.length
                    for (++i$7; i$7 < len && types[i$7] != "L"; ++i$7) {}
                    for (var j$2 = pos; j$2 < i$7;) {
                        if (countsAsNum.test(types[j$2])) {
                            if (pos < j$2) { order.splice(at, 0, new BidiSpan(1, pos, j$2)) }
                            var nstart = j$2
                            for (++j$2; j$2 < i$7 && countsAsNum.test(types[j$2]); ++j$2) {}
                            order.splice(at, 0, new BidiSpan(2, nstart, j$2))
                            pos = j$2
                        } else { ++j$2 }
                    }
                    if (pos < i$7) { order.splice(at, 0, new BidiSpan(1, pos, i$7)) }
                }
            }
            if (direction == "ltr") {
                if (order[0].level == 1 && (m = str.match(/^\s+/))) {
                    order[0].from = m[0].length
                    order.unshift(new BidiSpan(0, 0, m[0].length))
                }
                if (lst(order).level == 1 && (m = str.match(/\s+$/))) {
                    lst(order).to -= m[0].length
                    order.push(new BidiSpan(0, len - m[0].length, len))
                }
            }

            return direction == "rtl" ? order.reverse() : order
        }
    })()

// Get the bidi ordering for the given line (and cache it). Returns
// false for lines that are fully left-to-right, and an array of
// BidiSpan objects otherwise.
    function getOrder(line, direction) {
        var order = line.order
        if (order == null) { order = line.order = bidiOrdering(line.text, direction) }
        return order
    }

// EVENT HANDLING

// Lightweight event framework. on/off also work on DOM nodes,
// registering native DOM handlers.

    var noHandlers = []

    var on = function(emitter, type, f) {
        if (emitter.addEventListener) {
            emitter.addEventListener(type, f, false)
        } else if (emitter.attachEvent) {
            emitter.attachEvent("on" + type, f)
        } else {
            var map = emitter._handlers || (emitter._handlers = {})
            map[type] = (map[type] || noHandlers).concat(f)
        }
    }

    function getHandlers(emitter, type) {
        return emitter._handlers && emitter._handlers[type] || noHandlers
    }

    function off(emitter, type, f) {
        if (emitter.removeEventListener) {
            emitter.removeEventListener(type, f, false)
        } else if (emitter.detachEvent) {
            emitter.detachEvent("on" + type, f)
        } else {
            var map = emitter._handlers, arr = map && map[type]
            if (arr) {
                var index = indexOf(arr, f)
                if (index > -1)
                { map[type] = arr.slice(0, index).concat(arr.slice(index + 1)) }
            }
        }
    }

    function signal(emitter, type /*, values...*/) {
        var handlers = getHandlers(emitter, type)
        if (!handlers.length) { return }
        var args = Array.prototype.slice.call(arguments, 2)
        for (var i = 0; i < handlers.length; ++i) { handlers[i].apply(null, args) }
    }

// The DOM events that CodeMirror handles can be overridden by
// registering a (non-DOM) handler on the editor for the event name,
// and preventDefault-ing the event in that handler.
    function signalDOMEvent(cm, e, override) {
        if (typeof e == "string")
        { e = {type: e, preventDefault: function() { this.defaultPrevented = true }} }
        signal(cm, override || e.type, cm, e)
        return e_defaultPrevented(e) || e.codemirrorIgnore
    }

    function signalCursorActivity(cm) {
        var arr = cm._handlers && cm._handlers.cursorActivity
        if (!arr) { return }
        var set = cm.curOp.cursorActivityHandlers || (cm.curOp.cursorActivityHandlers = [])
        for (var i = 0; i < arr.length; ++i) { if (indexOf(set, arr[i]) == -1)
        { set.push(arr[i]) } }
    }

    function hasHandler(emitter, type) {
        return getHandlers(emitter, type).length > 0
    }

// Add on and off methods to a constructor's prototype, to make
// registering events on such objects more convenient.
    function eventMixin(ctor) {
        ctor.prototype.on = function(type, f) {on(this, type, f)}
        ctor.prototype.off = function(type, f) {off(this, type, f)}
    }

// Due to the fact that we still support jurassic IE versions, some
// compatibility wrappers are needed.

    function e_preventDefault(e) {
        if (e.preventDefault) { e.preventDefault() }
        else { e.returnValue = false }
    }
    function e_stopPropagation(e) {
        if (e.stopPropagation) { e.stopPropagation() }
        else { e.cancelBubble = true }
    }
    function e_defaultPrevented(e) {
        return e.defaultPrevented != null ? e.defaultPrevented : e.returnValue == false
    }
    function e_stop(e) {e_preventDefault(e); e_stopPropagation(e)}

    function e_target(e) {return e.target || e.srcElement}
    function e_button(e) {
        var b = e.which
        if (b == null) {
            if (e.button & 1) { b = 1 }
            else if (e.button & 2) { b = 3 }
            else if (e.button & 4) { b = 2 }
        }
        if (mac && e.ctrlKey && b == 1) { b = 3 }
        return b
    }

// Detect drag-and-drop
    var dragAndDrop = function() {
        // There is *some* kind of drag-and-drop support in IE6-8, but I
        // couldn't get it to work yet.
        if (ie && ie_version < 9) { return false }
        var div = elt('div')
        return "draggable" in div || "dragDrop" in div
    }()

    var zwspSupported
    function zeroWidthElement(measure) {
        if (zwspSupported == null) {
            var test = elt("span", "\u200b")
            removeChildrenAndAdd(measure, elt("span", [test, document.createTextNode("x")]))
            if (measure.firstChild.offsetHeight != 0)
            { zwspSupported = test.offsetWidth <= 1 && test.offsetHeight > 2 && !(ie && ie_version < 8) }
        }
        var node = zwspSupported ? elt("span", "\u200b") :
            elt("span", "\u00a0", null, "display: inline-block; width: 1px; margin-right: -1px")
        node.setAttribute("cm-text", "")
        return node
    }

// Feature-detect IE's crummy client rect reporting for bidi text
    var badBidiRects
    function hasBadBidiRects(measure) {
        if (badBidiRects != null) { return badBidiRects }
        var txt = removeChildrenAndAdd(measure, document.createTextNode("A\u062eA"))
        var r0 = range(txt, 0, 1).getBoundingClientRect()
        var r1 = range(txt, 1, 2).getBoundingClientRect()
        removeChildren(measure)
        if (!r0 || r0.left == r0.right) { return false } // Safari returns null in some cases (#2780)
        return badBidiRects = (r1.right - r0.right < 3)
    }

// See if "".split is the broken IE version, if so, provide an
// alternative way to split lines.
    var splitLinesAuto = "\n\nb".split(/\n/).length != 3 ? function (string) {
        var pos = 0, result = [], l = string.length
        while (pos <= l) {
            var nl = string.indexOf("\n", pos)
            if (nl == -1) { nl = string.length }
            var line = string.slice(pos, string.charAt(nl - 1) == "\r" ? nl - 1 : nl)
            var rt = line.indexOf("\r")
            if (rt != -1) {
                result.push(line.slice(0, rt))
                pos += rt + 1
            } else {
                result.push(line)
                pos = nl + 1
            }
        }
        return result
    } : function (string) { return string.split(/\r\n?|\n/); }

    var hasSelection = window.getSelection ? function (te) {
        try { return te.selectionStart != te.selectionEnd }
        catch(e) { return false }
    } : function (te) {
        var range
        try {range = te.ownerDocument.selection.createRange()}
        catch(e) {}
        if (!range || range.parentElement() != te) { return false }
        return range.compareEndPoints("StartToEnd", range) != 0
    }

    var hasCopyEvent = (function () {
        var e = elt("div")
        if ("oncopy" in e) { return true }
        e.setAttribute("oncopy", "return;")
        return typeof e.oncopy == "function"
    })()

    var badZoomedRects = null
    function hasBadZoomedRects(measure) {
        if (badZoomedRects != null) { return badZoomedRects }
        var node = removeChildrenAndAdd(measure, elt("span", "x"))
        var normal = node.getBoundingClientRect()
        var fromRange = range(node, 0, 1).getBoundingClientRect()
        return badZoomedRects = Math.abs(normal.left - fromRange.left) > 1
    }

    var modes = {};
    var mimeModes = {};
// Extra arguments are stored as the mode's dependencies, which is
// used by (legacy) mechanisms like loadmode.js to automatically
// load a mode. (Preferred mechanism is the require/define calls.)
    function defineMode(name, mode) {
        if (arguments.length > 2)
        { mode.dependencies = Array.prototype.slice.call(arguments, 2) }
        modes[name] = mode
    }

    function defineMIME(mime, spec) {
        mimeModes[mime] = spec
    }

// Given a MIME type, a {name, ...options} config object, or a name
// string, return a mode config object.
    function resolveMode(spec) {
        if (typeof spec == "string" && mimeModes.hasOwnProperty(spec)) {
            spec = mimeModes[spec]
        } else if (spec && typeof spec.name == "string" && mimeModes.hasOwnProperty(spec.name)) {
            var found = mimeModes[spec.name]
            if (typeof found == "string") { found = {name: found} }
            spec = createObj(found, spec)
            spec.name = found.name
        } else if (typeof spec == "string" && /^[\w\-]+\/[\w\-]+\+xml$/.test(spec)) {
            return resolveMode("application/xml")
        } else if (typeof spec == "string" && /^[\w\-]+\/[\w\-]+\+json$/.test(spec)) {
            return resolveMode("application/json")
        }
        if (typeof spec == "string") { return {name: spec} }
        else { return spec || {name: "null"} }
    }

// Given a mode spec (anything that resolveMode accepts), find and
// initialize an actual mode object.
    function getMode(options, spec) {
        spec = resolveMode(spec)
        var mfactory = modes[spec.name]
        if (!mfactory) { return getMode(options, "text/plain") }
        var modeObj = mfactory(options, spec)
        if (modeExtensions.hasOwnProperty(spec.name)) {
            var exts = modeExtensions[spec.name]
            for (var prop in exts) {
                if (!exts.hasOwnProperty(prop)) { continue }
                if (modeObj.hasOwnProperty(prop)) { modeObj["_" + prop] = modeObj[prop] }
                modeObj[prop] = exts[prop]
            }
        }
        modeObj.name = spec.name
        if (spec.helperType) { modeObj.helperType = spec.helperType }
        if (spec.modeProps) { for (var prop$1 in spec.modeProps)
        { modeObj[prop$1] = spec.modeProps[prop$1] } }

        return modeObj
    }

// This can be used to attach properties to mode objects from
// outside the actual mode definition.
    var modeExtensions = {}
    function extendMode(mode, properties) {
        var exts = modeExtensions.hasOwnProperty(mode) ? modeExtensions[mode] : (modeExtensions[mode] = {})
        copyObj(properties, exts)
    }

    function copyState(mode, state) {
        if (state === true) { return state }
        if (mode.copyState) { return mode.copyState(state) }
        var nstate = {}
        for (var n in state) {
            var val = state[n]
            if (val instanceof Array) { val = val.concat([]) }
            nstate[n] = val
        }
        return nstate
    }

// Given a mode and a state (for that mode), find the inner mode and
// state at the position that the state refers to.
    function innerMode(mode, state) {
        var info
        while (mode.innerMode) {
            info = mode.innerMode(state)
            if (!info || info.mode == mode) { break }
            state = info.state
            mode = info.mode
        }
        return info || {mode: mode, state: state}
    }

    function startState(mode, a1, a2) {
        return mode.startState ? mode.startState(a1, a2) : true
    }

// STRING STREAM

// Fed to the mode parsers, provides helper functions to make
// parsers more succinct.

    var StringStream = function(string, tabSize, lineOracle) {
        this.pos = this.start = 0
        this.string = string
        this.tabSize = tabSize || 8
        this.lastColumnPos = this.lastColumnValue = 0
        this.lineStart = 0
        this.lineOracle = lineOracle
    };

    StringStream.prototype.eol = function () {return this.pos >= this.string.length};
    StringStream.prototype.sol = function () {return this.pos == this.lineStart};
    StringStream.prototype.peek = function () {return this.string.charAt(this.pos) || undefined};
    StringStream.prototype.next = function () {
        if (this.pos < this.string.length)
        { return this.string.charAt(this.pos++) }
    };
    StringStream.prototype.eat = function (match) {
        var ch = this.string.charAt(this.pos)
        var ok
        if (typeof match == "string") { ok = ch == match }
        else { ok = ch && (match.test ? match.test(ch) : match(ch)) }
        if (ok) {++this.pos; return ch}
    };
    StringStream.prototype.eatWhile = function (match) {
        var start = this.pos
        while (this.eat(match)){}
        return this.pos > start
    };
    StringStream.prototype.eatSpace = function () {
        var this$1 = this;

        var start = this.pos
        while (/[\s\u00a0]/.test(this.string.charAt(this.pos))) { ++this$1.pos }
        return this.pos > start
    };
    StringStream.prototype.skipToEnd = function () {this.pos = this.string.length};
    StringStream.prototype.skipTo = function (ch) {
        var found = this.string.indexOf(ch, this.pos)
        if (found > -1) {this.pos = found; return true}
    };
    StringStream.prototype.backUp = function (n) {this.pos -= n};
    StringStream.prototype.column = function () {
        if (this.lastColumnPos < this.start) {
            this.lastColumnValue = countColumn(this.string, this.start, this.tabSize, this.lastColumnPos, this.lastColumnValue)
            this.lastColumnPos = this.start
        }
        return this.lastColumnValue - (this.lineStart ? countColumn(this.string, this.lineStart, this.tabSize) : 0)
    };
    StringStream.prototype.indentation = function () {
        return countColumn(this.string, null, this.tabSize) -
            (this.lineStart ? countColumn(this.string, this.lineStart, this.tabSize) : 0)
    };
    StringStream.prototype.match = function (pattern, consume, caseInsensitive) {
        if (typeof pattern == "string") {
            var cased = function (str) { return caseInsensitive ? str.toLowerCase() : str; }
            var substr = this.string.substr(this.pos, pattern.length)
            if (cased(substr) == cased(pattern)) {
                if (consume !== false) { this.pos += pattern.length }
                return true
            }
        } else {
            var match = this.string.slice(this.pos).match(pattern)
            if (match && match.index > 0) { return null }
            if (match && consume !== false) { this.pos += match[0].length }
            return match
        }
    };
    StringStream.prototype.current = function (){return this.string.slice(this.start, this.pos)};
    StringStream.prototype.hideFirstChars = function (n, inner) {
        this.lineStart += n
        try { return inner() }
        finally { this.lineStart -= n }
    };
    StringStream.prototype.lookAhead = function (n) {
        var oracle = this.lineOracle
        return oracle && oracle.lookAhead(n)
    };
    StringStream.prototype.baseToken = function () {
        var oracle = this.lineOracle
        return oracle && oracle.baseToken(this.pos)
    };

    var SavedContext = function(state, lookAhead) {
        this.state = state
        this.lookAhead = lookAhead
    };

    var Context = function(doc, state, line, lookAhead) {
        this.state = state
        this.doc = doc
        this.line = line
        this.maxLookAhead = lookAhead || 0
        this.baseTokens = null
        this.baseTokenPos = 1
    };

    Context.prototype.lookAhead = function (n) {
        var line = this.doc.getLine(this.line + n)
        if (line != null && n > this.maxLookAhead) { this.maxLookAhead = n }
        return line
    };

    Context.prototype.baseToken = function (n) {
        var this$1 = this;

        if (!this.baseTokens) { return null }
        while (this.baseTokens[this.baseTokenPos] <= n)
        { this$1.baseTokenPos += 2 }
        var type = this.baseTokens[this.baseTokenPos + 1]
        return {type: type && type.replace(/( |^)overlay .*/, ""),
            size: this.baseTokens[this.baseTokenPos] - n}
    };

    Context.prototype.nextLine = function () {
        this.line++
        if (this.maxLookAhead > 0) { this.maxLookAhead-- }
    };

    Context.fromSaved = function (doc, saved, line) {
        if (saved instanceof SavedContext)
        { return new Context(doc, copyState(doc.mode, saved.state), line, saved.lookAhead) }
        else
        { return new Context(doc, copyState(doc.mode, saved), line) }
    };

    Context.prototype.save = function (copy) {
        var state = copy !== false ? copyState(this.doc.mode, this.state) : this.state
        return this.maxLookAhead > 0 ? new SavedContext(state, this.maxLookAhead) : state
    };


// Compute a style array (an array starting with a mode generation
// -- for invalidation -- followed by pairs of end positions and
// style strings), which is used to highlight the tokens on the
// line.
    function highlightLine(cm, line, context, forceToEnd) {
        // A styles array always starts with a number identifying the
        // mode/overlays that it is based on (for easy invalidation).
        var st = [cm.state.modeGen], lineClasses = {}
        // Compute the base array of styles
        runMode(cm, line.text, cm.doc.mode, context, function (end, style) { return st.push(end, style); },
            lineClasses, forceToEnd)
        var state = context.state

        // Run overlays, adjust style array.
        var loop = function ( o ) {
            context.baseTokens = st
            var overlay = cm.state.overlays[o], i = 1, at = 0
            context.state = true
            runMode(cm, line.text, overlay.mode, context, function (end, style) {
                var start = i
                // Ensure there's a token end at the current position, and that i points at it
                while (at < end) {
                    var i_end = st[i]
                    if (i_end > end)
                    { st.splice(i, 1, end, st[i+1], i_end) }
                    i += 2
                    at = Math.min(end, i_end)
                }
                if (!style) { return }
                if (overlay.opaque) {
                    st.splice(start, i - start, end, "overlay " + style)
                    i = start + 2
                } else {
                    for (; start < i; start += 2) {
                        var cur = st[start+1]
                        st[start+1] = (cur ? cur + " " : "") + "overlay " + style
                    }
                }
            }, lineClasses)
            context.state = state
            context.baseTokens = null
            context.baseTokenPos = 1
        };

        for (var o = 0; o < cm.state.overlays.length; ++o) loop( o );

        return {styles: st, classes: lineClasses.bgClass || lineClasses.textClass ? lineClasses : null}
    }

    function getLineStyles(cm, line, updateFrontier) {
        if (!line.styles || line.styles[0] != cm.state.modeGen) {
            var context = getContextBefore(cm, lineNo(line))
            var resetState = line.text.length > cm.options.maxHighlightLength && copyState(cm.doc.mode, context.state)
            var result = highlightLine(cm, line, context)
            if (resetState) { context.state = resetState }
            line.stateAfter = context.save(!resetState)
            line.styles = result.styles
            if (result.classes) { line.styleClasses = result.classes }
            else if (line.styleClasses) { line.styleClasses = null }
            if (updateFrontier === cm.doc.highlightFrontier)
            { cm.doc.modeFrontier = Math.max(cm.doc.modeFrontier, ++cm.doc.highlightFrontier) }
        }
        return line.styles
    }

    function getContextBefore(cm, n, precise) {
        var doc = cm.doc, display = cm.display
        if (!doc.mode.startState) { return new Context(doc, true, n) }
        var start = findStartLine(cm, n, precise)
        var saved = start > doc.first && getLine(doc, start - 1).stateAfter
        var context = saved ? Context.fromSaved(doc, saved, start) : new Context(doc, startState(doc.mode), start)

        doc.iter(start, n, function (line) {
            processLine(cm, line.text, context)
            var pos = context.line
            line.stateAfter = pos == n - 1 || pos % 5 == 0 || pos >= display.viewFrom && pos < display.viewTo ? context.save() : null
            context.nextLine()
        })
        if (precise) { doc.modeFrontier = context.line }
        return context
    }

// Lightweight form of highlight -- proceed over this line and
// update state, but don't save a style array. Used for lines that
// aren't currently visible.
    function processLine(cm, text, context, startAt) {
        var mode = cm.doc.mode
        var stream = new StringStream(text, cm.options.tabSize, context)
        stream.start = stream.pos = startAt || 0
        if (text == "") { callBlankLine(mode, context.state) }
        while (!stream.eol()) {
            readToken(mode, stream, context.state)
            stream.start = stream.pos
        }
    }

    function callBlankLine(mode, state) {
        if (mode.blankLine) { return mode.blankLine(state) }
        if (!mode.innerMode) { return }
        var inner = innerMode(mode, state)
        if (inner.mode.blankLine) { return inner.mode.blankLine(inner.state) }
    }

    function readToken(mode, stream, state, inner) {
        for (var i = 0; i < 10; i++) {
            if (inner) { inner[0] = innerMode(mode, state).mode }
            var style = mode.token(stream, state)
            if (stream.pos > stream.start) { return style }
        }
        throw new Error("Mode " + mode.name + " failed to advance stream.")
    }

    var Token = function(stream, type, state) {
        this.start = stream.start; this.end = stream.pos
        this.string = stream.current()
        this.type = type || null
        this.state = state
    };

// Utility for getTokenAt and getLineTokens
    function takeToken(cm, pos, precise, asArray) {
        var doc = cm.doc, mode = doc.mode, style
        pos = clipPos(doc, pos)
        var line = getLine(doc, pos.line), context = getContextBefore(cm, pos.line, precise)
        var stream = new StringStream(line.text, cm.options.tabSize, context), tokens
        if (asArray) { tokens = [] }
        while ((asArray || stream.pos < pos.ch) && !stream.eol()) {
            stream.start = stream.pos
            style = readToken(mode, stream, context.state)
            if (asArray) { tokens.push(new Token(stream, style, copyState(doc.mode, context.state))) }
        }
        return asArray ? tokens : new Token(stream, style, context.state)
    }

    function extractLineClasses(type, output) {
        if (type) { for (;;) {
            var lineClass = type.match(/(?:^|\s+)line-(background-)?(\S+)/)
            if (!lineClass) { break }
            type = type.slice(0, lineClass.index) + type.slice(lineClass.index + lineClass[0].length)
            var prop = lineClass[1] ? "bgClass" : "textClass"
            if (output[prop] == null)
            { output[prop] = lineClass[2] }
            else if (!(new RegExp("(?:^|\s)" + lineClass[2] + "(?:$|\s)")).test(output[prop]))
            { output[prop] += " " + lineClass[2] }
        } }
        return type
    }

// Run the given mode's parser over a line, calling f for each token.
    function runMode(cm, text, mode, context, f, lineClasses, forceToEnd) {
        var flattenSpans = mode.flattenSpans
        if (flattenSpans == null) { flattenSpans = cm.options.flattenSpans }
        var curStart = 0, curStyle = null
        var stream = new StringStream(text, cm.options.tabSize, context), style
        var inner = cm.options.addModeClass && [null]
        if (text == "") { extractLineClasses(callBlankLine(mode, context.state), lineClasses) }
        while (!stream.eol()) {
            if (stream.pos > cm.options.maxHighlightLength) {
                flattenSpans = false
                if (forceToEnd) { processLine(cm, text, context, stream.pos) }
                stream.pos = text.length
                style = null
            } else {
                style = extractLineClasses(readToken(mode, stream, context.state, inner), lineClasses)
            }
            if (inner) {
                var mName = inner[0].name
                if (mName) { style = "m-" + (style ? mName + " " + style : mName) }
            }
            if (!flattenSpans || curStyle != style) {
                while (curStart < stream.start) {
                    curStart = Math.min(stream.start, curStart + 5000)
                    f(curStart, curStyle)
                }
                curStyle = style
            }
            stream.start = stream.pos
        }
        while (curStart < stream.pos) {
            // Webkit seems to refuse to render text nodes longer than 57444
            // characters, and returns inaccurate measurements in nodes
            // starting around 5000 chars.
            var pos = Math.min(stream.pos, curStart + 5000)
            f(pos, curStyle)
            curStart = pos
        }
    }

// Finds the line to start with when starting a parse. Tries to
// find a line with a stateAfter, so that it can start with a
// valid state. If that fails, it returns the line with the
// smallest indentation, which tends to need the least context to
// parse correctly.
    function findStartLine(cm, n, precise) {
        var minindent, minline, doc = cm.doc
        var lim = precise ? -1 : n - (cm.doc.mode.innerMode ? 1000 : 100)
        for (var search = n; search > lim; --search) {
            if (search <= doc.first) { return doc.first }
            var line = getLine(doc, search - 1), after = line.stateAfter
            if (after && (!precise || search + (after instanceof SavedContext ? after.lookAhead : 0) <= doc.modeFrontier))
            { return search }
            var indented = countColumn(line.text, null, cm.options.tabSize)
            if (minline == null || minindent > indented) {
                minline = search - 1
                minindent = indented
            }
        }
        return minline
    }

    function retreatFrontier(doc, n) {
        doc.modeFrontier = Math.min(doc.modeFrontier, n)
        if (doc.highlightFrontier < n - 10) { return }
        var start = doc.first
        for (var line = n - 1; line > start; line--) {
            var saved = getLine(doc, line).stateAfter
            // change is on 3
            // state on line 1 looked ahead 2 -- so saw 3
            // test 1 + 2 < 3 should cover this
            if (saved && (!(saved instanceof SavedContext) || line + saved.lookAhead < n)) {
                start = line + 1
                break
            }
        }
        doc.highlightFrontier = Math.min(doc.highlightFrontier, start)
    }

// LINE DATA STRUCTURE

// Line objects. These hold state related to a line, including
// highlighting info (the styles array).
    var Line = function(text, markedSpans, estimateHeight) {
        this.text = text
        attachMarkedSpans(this, markedSpans)
        this.height = estimateHeight ? estimateHeight(this) : 1
    };

    Line.prototype.lineNo = function () { return lineNo(this) };
    eventMixin(Line)

// Change the content (text, markers) of a line. Automatically
// invalidates cached information and tries to re-estimate the
// line's height.
    function updateLine(line, text, markedSpans, estimateHeight) {
        line.text = text
        if (line.stateAfter) { line.stateAfter = null }
        if (line.styles) { line.styles = null }
        if (line.order != null) { line.order = null }
        detachMarkedSpans(line)
        attachMarkedSpans(line, markedSpans)
        var estHeight = estimateHeight ? estimateHeight(line) : 1
        if (estHeight != line.height) { updateLineHeight(line, estHeight) }
    }

// Detach a line from the document tree and its markers.
    function cleanUpLine(line) {
        line.parent = null
        detachMarkedSpans(line)
    }

// Convert a style as returned by a mode (either null, or a string
// containing one or more styles) to a CSS style. This is cached,
// and also looks for line-wide styles.
    var styleToClassCache = {};
    var styleToClassCacheWithMode = {};
    function interpretTokenStyle(style, options) {
        if (!style || /^\s*$/.test(style)) { return null }
        var cache = options.addModeClass ? styleToClassCacheWithMode : styleToClassCache
        return cache[style] ||
            (cache[style] = style.replace(/\S+/g, "cm-$&"))
    }

// Render the DOM representation of the text of a line. Also builds
// up a 'line map', which points at the DOM nodes that represent
// specific stretches of text, and is used by the measuring code.
// The returned object contains the DOM node, this map, and
// information about line-wide styles that were set by the mode.
    function buildLineContent(cm, lineView) {
        // The padding-right forces the element to have a 'border', which
        // is needed on Webkit to be able to get line-level bounding
        // rectangles for it (in measureChar).
        var content = eltP("span", null, null, webkit ? "padding-right: .1px" : null)
        var builder = {pre: eltP("pre", [content], "CodeMirror-line"), content: content,
            col: 0, pos: 0, cm: cm,
            trailingSpace: false,
            splitSpaces: (ie || webkit) && cm.getOption("lineWrapping")}
        lineView.measure = {}

        // Iterate over the logical lines that make up this visual line.
        for (var i = 0; i <= (lineView.rest ? lineView.rest.length : 0); i++) {
            var line = i ? lineView.rest[i - 1] : lineView.line, order = (void 0)
            builder.pos = 0
            builder.addToken = buildToken
            // Optionally wire in some hacks into the token-rendering
            // algorithm, to deal with browser quirks.
            if (hasBadBidiRects(cm.display.measure) && (order = getOrder(line, cm.doc.direction)))
            { builder.addToken = buildTokenBadBidi(builder.addToken, order) }
            builder.map = []
            var allowFrontierUpdate = lineView != cm.display.externalMeasured && lineNo(line)
            insertLineContent(line, builder, getLineStyles(cm, line, allowFrontierUpdate))
            if (line.styleClasses) {
                if (line.styleClasses.bgClass)
                { builder.bgClass = joinClasses(line.styleClasses.bgClass, builder.bgClass || "") }
                if (line.styleClasses.textClass)
                { builder.textClass = joinClasses(line.styleClasses.textClass, builder.textClass || "") }
            }

            // Ensure at least a single node is present, for measuring.
            if (builder.map.length == 0)
            { builder.map.push(0, 0, builder.content.appendChild(zeroWidthElement(cm.display.measure))) }

            // Store the map and a cache object for the current logical line
            if (i == 0) {
                lineView.measure.map = builder.map
                lineView.measure.cache = {}
            } else {
                ;(lineView.measure.maps || (lineView.measure.maps = [])).push(builder.map)
                ;(lineView.measure.caches || (lineView.measure.caches = [])).push({})
            }
        }

        // See issue #2901
        if (webkit) {
            var last = builder.content.lastChild
            if (/\bcm-tab\b/.test(last.className) || (last.querySelector && last.querySelector(".cm-tab")))
            { builder.content.className = "cm-tab-wrap-hack" }
        }

        signal(cm, "renderLine", cm, lineView.line, builder.pre)
        if (builder.pre.className)
        { builder.textClass = joinClasses(builder.pre.className, builder.textClass || "") }

        return builder
    }

    function defaultSpecialCharPlaceholder(ch) {
        var token = elt("span", "\u2022", "cm-invalidchar")
        token.title = "\\u" + ch.charCodeAt(0).toString(16)
        token.setAttribute("aria-label", token.title)
        return token
    }

// Build up the DOM representation for a single token, and add it to
// the line map. Takes care to render special characters separately.
    function buildToken(builder, text, style, startStyle, endStyle, title, css) {
        if (!text) { return }
        var displayText = builder.splitSpaces ? splitSpaces(text, builder.trailingSpace) : text
        var special = builder.cm.state.specialChars, mustWrap = false
        var content
        if (!special.test(text)) {
            builder.col += text.length
            content = document.createTextNode(displayText)
            builder.map.push(builder.pos, builder.pos + text.length, content)
            if (ie && ie_version < 9) { mustWrap = true }
            builder.pos += text.length
        } else {
            content = document.createDocumentFragment()
            var pos = 0
            while (true) {
                special.lastIndex = pos
                var m = special.exec(text)
                var skipped = m ? m.index - pos : text.length - pos
                if (skipped) {
                    var txt = document.createTextNode(displayText.slice(pos, pos + skipped))
                    if (ie && ie_version < 9) { content.appendChild(elt("span", [txt])) }
                    else { content.appendChild(txt) }
                    builder.map.push(builder.pos, builder.pos + skipped, txt)
                    builder.col += skipped
                    builder.pos += skipped
                }
                if (!m) { break }
                pos += skipped + 1
                var txt$1 = (void 0)
                if (m[0] == "\t") {
                    var tabSize = builder.cm.options.tabSize, tabWidth = tabSize - builder.col % tabSize
                    txt$1 = content.appendChild(elt("span", spaceStr(tabWidth), "cm-tab"))
                    txt$1.setAttribute("role", "presentation")
                    txt$1.setAttribute("cm-text", "\t")
                    builder.col += tabWidth
                } else if (m[0] == "\r" || m[0] == "\n") {
                    txt$1 = content.appendChild(elt("span", m[0] == "\r" ? "\u240d" : "\u2424", "cm-invalidchar"))
                    txt$1.setAttribute("cm-text", m[0])
                    builder.col += 1
                } else {
                    txt$1 = builder.cm.options.specialCharPlaceholder(m[0])
                    txt$1.setAttribute("cm-text", m[0])
                    if (ie && ie_version < 9) { content.appendChild(elt("span", [txt$1])) }
                    else { content.appendChild(txt$1) }
                    builder.col += 1
                }
                builder.map.push(builder.pos, builder.pos + 1, txt$1)
                builder.pos++
            }
        }
        builder.trailingSpace = displayText.charCodeAt(text.length - 1) == 32
        if (style || startStyle || endStyle || mustWrap || css) {
            var fullStyle = style || ""
            if (startStyle) { fullStyle += startStyle }
            if (endStyle) { fullStyle += endStyle }
            var token = elt("span", [content], fullStyle, css)
            if (title) { token.title = title }
            return builder.content.appendChild(token)
        }
        builder.content.appendChild(content)
    }

    function splitSpaces(text, trailingBefore) {
        if (text.length > 1 && !/  /.test(text)) { return text }
        var spaceBefore = trailingBefore, result = ""
        for (var i = 0; i < text.length; i++) {
            var ch = text.charAt(i)
            if (ch == " " && spaceBefore && (i == text.length - 1 || text.charCodeAt(i + 1) == 32))
            { ch = "\u00a0" }
            result += ch
            spaceBefore = ch == " "
        }
        return result
    }

// Work around nonsense dimensions being reported for stretches of
// right-to-left text.
    function buildTokenBadBidi(inner, order) {
        return function (builder, text, style, startStyle, endStyle, title, css) {
            style = style ? style + " cm-force-border" : "cm-force-border"
            var start = builder.pos, end = start + text.length
            for (;;) {
                // Find the part that overlaps with the start of this text
                var part = (void 0)
                for (var i = 0; i < order.length; i++) {
                    part = order[i]
                    if (part.to > start && part.from <= start) { break }
                }
                if (part.to >= end) { return inner(builder, text, style, startStyle, endStyle, title, css) }
                inner(builder, text.slice(0, part.to - start), style, startStyle, null, title, css)
                startStyle = null
                text = text.slice(part.to - start)
                start = part.to
            }
        }
    }

    function buildCollapsedSpan(builder, size, marker, ignoreWidget) {
        var widget = !ignoreWidget && marker.widgetNode
        if (widget) { builder.map.push(builder.pos, builder.pos + size, widget) }
        if (!ignoreWidget && builder.cm.display.input.needsContentAttribute) {
            if (!widget)
            { widget = builder.content.appendChild(document.createElement("span")) }
            widget.setAttribute("cm-marker", marker.id)
        }
        if (widget) {
            builder.cm.display.input.setUneditable(widget)
            builder.content.appendChild(widget)
        }
        builder.pos += size
        builder.trailingSpace = false
    }

// Outputs a number of spans to make up a line, taking highlighting
// and marked text into account.
    function insertLineContent(line, builder, styles) {
        var spans = line.markedSpans, allText = line.text, at = 0
        if (!spans) {
            for (var i$1 = 1; i$1 < styles.length; i$1+=2)
            { builder.addToken(builder, allText.slice(at, at = styles[i$1]), interpretTokenStyle(styles[i$1+1], builder.cm.options)) }
            return
        }

        var len = allText.length, pos = 0, i = 1, text = "", style, css
        var nextChange = 0, spanStyle, spanEndStyle, spanStartStyle, title, collapsed
        for (;;) {
            if (nextChange == pos) { // Update current marker set
                spanStyle = spanEndStyle = spanStartStyle = title = css = ""
                collapsed = null; nextChange = Infinity
                var foundBookmarks = [], endStyles = (void 0)
                for (var j = 0; j < spans.length; ++j) {
                    var sp = spans[j], m = sp.marker
                    if (m.type == "bookmark" && sp.from == pos && m.widgetNode) {
                        foundBookmarks.push(m)
                    } else if (sp.from <= pos && (sp.to == null || sp.to > pos || m.collapsed && sp.to == pos && sp.from == pos)) {
                        if (sp.to != null && sp.to != pos && nextChange > sp.to) {
                            nextChange = sp.to
                            spanEndStyle = ""
                        }
                        if (m.className) { spanStyle += " " + m.className }
                        if (m.css) { css = (css ? css + ";" : "") + m.css }
                        if (m.startStyle && sp.from == pos) { spanStartStyle += " " + m.startStyle }
                        if (m.endStyle && sp.to == nextChange) { (endStyles || (endStyles = [])).push(m.endStyle, sp.to) }
                        if (m.title && !title) { title = m.title }
                        if (m.collapsed && (!collapsed || compareCollapsedMarkers(collapsed.marker, m) < 0))
                        { collapsed = sp }
                    } else if (sp.from > pos && nextChange > sp.from) {
                        nextChange = sp.from
                    }
                }
                if (endStyles) { for (var j$1 = 0; j$1 < endStyles.length; j$1 += 2)
                { if (endStyles[j$1 + 1] == nextChange) { spanEndStyle += " " + endStyles[j$1] } } }

                if (!collapsed || collapsed.from == pos) { for (var j$2 = 0; j$2 < foundBookmarks.length; ++j$2)
                { buildCollapsedSpan(builder, 0, foundBookmarks[j$2]) } }
                if (collapsed && (collapsed.from || 0) == pos) {
                    buildCollapsedSpan(builder, (collapsed.to == null ? len + 1 : collapsed.to) - pos,
                        collapsed.marker, collapsed.from == null)
                    if (collapsed.to == null) { return }
                    if (collapsed.to == pos) { collapsed = false }
                }
            }
            if (pos >= len) { break }

            var upto = Math.min(len, nextChange)
            while (true) {
                if (text) {
                    var end = pos + text.length
                    if (!collapsed) {
                        var tokenText = end > upto ? text.slice(0, upto - pos) : text
                        builder.addToken(builder, tokenText, style ? style + spanStyle : spanStyle,
                            spanStartStyle, pos + tokenText.length == nextChange ? spanEndStyle : "", title, css)
                    }
                    if (end >= upto) {text = text.slice(upto - pos); pos = upto; break}
                    pos = end
                    spanStartStyle = ""
                }
                text = allText.slice(at, at = styles[i++])
                style = interpretTokenStyle(styles[i++], builder.cm.options)
            }
        }
    }


// These objects are used to represent the visible (currently drawn)
// part of the document. A LineView may correspond to multiple
// logical lines, if those are connected by collapsed ranges.
    function LineView(doc, line, lineN) {
        // The starting line
        this.line = line
        // Continuing lines, if any
        this.rest = visualLineContinued(line)
        // Number of logical lines in this visual line
        this.size = this.rest ? lineNo(lst(this.rest)) - lineN + 1 : 1
        this.node = this.text = null
        this.hidden = lineIsHidden(doc, line)
    }

// Create a range of LineView objects for the given lines.
    function buildViewArray(cm, from, to) {
        var array = [], nextPos
        for (var pos = from; pos < to; pos = nextPos) {
            var view = new LineView(cm.doc, getLine(cm.doc, pos), pos)
            nextPos = pos + view.size
            array.push(view)
        }
        return array
    }

    var operationGroup = null

    function pushOperation(op) {
        if (operationGroup) {
            operationGroup.ops.push(op)
        } else {
            op.ownsGroup = operationGroup = {
                ops: [op],
                delayedCallbacks: []
            }
        }
    }

    function fireCallbacksForOps(group) {
        // Calls delayed callbacks and cursorActivity handlers until no
        // new ones appear
        var callbacks = group.delayedCallbacks, i = 0
        do {
            for (; i < callbacks.length; i++)
            { callbacks[i].call(null) }
            for (var j = 0; j < group.ops.length; j++) {
                var op = group.ops[j]
                if (op.cursorActivityHandlers)
                { while (op.cursorActivityCalled < op.cursorActivityHandlers.length)
                { op.cursorActivityHandlers[op.cursorActivityCalled++].call(null, op.cm) } }
            }
        } while (i < callbacks.length)
    }

    function finishOperation(op, endCb) {
        var group = op.ownsGroup
        if (!group) { return }

        try { fireCallbacksForOps(group) }
        finally {
            operationGroup = null
            endCb(group)
        }
    }

    var orphanDelayedCallbacks = null

// Often, we want to signal events at a point where we are in the
// middle of some work, but don't want the handler to start calling
// other methods on the editor, which might be in an inconsistent
// state or simply not expect any other events to happen.
// signalLater looks whether there are any handlers, and schedules
// them to be executed when the last operation ends, or, if no
// operation is active, when a timeout fires.
    function signalLater(emitter, type /*, values...*/) {
        var arr = getHandlers(emitter, type)
        if (!arr.length) { return }
        var args = Array.prototype.slice.call(arguments, 2), list
        if (operationGroup) {
            list = operationGroup.delayedCallbacks
        } else if (orphanDelayedCallbacks) {
            list = orphanDelayedCallbacks
        } else {
            list = orphanDelayedCallbacks = []
            setTimeout(fireOrphanDelayed, 0)
        }
        var loop = function ( i ) {
            list.push(function () { return arr[i].apply(null, args); })
        };

        for (var i = 0; i < arr.length; ++i)
            loop( i );
    }

    function fireOrphanDelayed() {
        var delayed = orphanDelayedCallbacks
        orphanDelayedCallbacks = null
        for (var i = 0; i < delayed.length; ++i) { delayed[i]() }
    }

// When an aspect of a line changes, a string is added to
// lineView.changes. This updates the relevant part of the line's
// DOM structure.
    function updateLineForChanges(cm, lineView, lineN, dims) {
        for (var j = 0; j < lineView.changes.length; j++) {
            var type = lineView.changes[j]
            if (type == "text") { updateLineText(cm, lineView) }
            else if (type == "gutter") { updateLineGutter(cm, lineView, lineN, dims) }
            else if (type == "class") { updateLineClasses(cm, lineView) }
            else if (type == "widget") { updateLineWidgets(cm, lineView, dims) }
        }
        lineView.changes = null
    }

// Lines with gutter elements, widgets or a background class need to
// be wrapped, and have the extra elements added to the wrapper div
    function ensureLineWrapped(lineView) {
        if (lineView.node == lineView.text) {
            lineView.node = elt("div", null, null, "position: relative")
            if (lineView.text.parentNode)
            { lineView.text.parentNode.replaceChild(lineView.node, lineView.text) }
            lineView.node.appendChild(lineView.text)
            if (ie && ie_version < 8) { lineView.node.style.zIndex = 2 }
        }
        return lineView.node
    }

    function updateLineBackground(cm, lineView) {
        var cls = lineView.bgClass ? lineView.bgClass + " " + (lineView.line.bgClass || "") : lineView.line.bgClass
        if (cls) { cls += " CodeMirror-linebackground" }
        if (lineView.background) {
            if (cls) { lineView.background.className = cls }
            else { lineView.background.parentNode.removeChild(lineView.background); lineView.background = null }
        } else if (cls) {
            var wrap = ensureLineWrapped(lineView)
            lineView.background = wrap.insertBefore(elt("div", null, cls), wrap.firstChild)
            cm.display.input.setUneditable(lineView.background)
        }
    }

// Wrapper around buildLineContent which will reuse the structure
// in display.externalMeasured when possible.
    function getLineContent(cm, lineView) {
        var ext = cm.display.externalMeasured
        if (ext && ext.line == lineView.line) {
            cm.display.externalMeasured = null
            lineView.measure = ext.measure
            return ext.built
        }
        return buildLineContent(cm, lineView)
    }

// Redraw the line's text. Interacts with the background and text
// classes because the mode may output tokens that influence these
// classes.
    function updateLineText(cm, lineView) {
        var cls = lineView.text.className
        var built = getLineContent(cm, lineView)
        if (lineView.text == lineView.node) { lineView.node = built.pre }
        lineView.text.parentNode.replaceChild(built.pre, lineView.text)
        lineView.text = built.pre
        if (built.bgClass != lineView.bgClass || built.textClass != lineView.textClass) {
            lineView.bgClass = built.bgClass
            lineView.textClass = built.textClass
            updateLineClasses(cm, lineView)
        } else if (cls) {
            lineView.text.className = cls
        }
    }

    function updateLineClasses(cm, lineView) {
        updateLineBackground(cm, lineView)
        if (lineView.line.wrapClass)
        { ensureLineWrapped(lineView).className = lineView.line.wrapClass }
        else if (lineView.node != lineView.text)
        { lineView.node.className = "" }
        var textClass = lineView.textClass ? lineView.textClass + " " + (lineView.line.textClass || "") : lineView.line.textClass
        lineView.text.className = textClass || ""
    }

    function updateLineGutter(cm, lineView, lineN, dims) {
        if (lineView.gutter) {
            lineView.node.removeChild(lineView.gutter)
            lineView.gutter = null
        }
        if (lineView.gutterBackground) {
            lineView.node.removeChild(lineView.gutterBackground)
            lineView.gutterBackground = null
        }
        if (lineView.line.gutterClass) {
            var wrap = ensureLineWrapped(lineView)
            lineView.gutterBackground = elt("div", null, "CodeMirror-gutter-background " + lineView.line.gutterClass,
                ("left: " + (cm.options.fixedGutter ? dims.fixedPos : -dims.gutterTotalWidth) + "px; width: " + (dims.gutterTotalWidth) + "px"))
            cm.display.input.setUneditable(lineView.gutterBackground)
            wrap.insertBefore(lineView.gutterBackground, lineView.text)
        }
        var markers = lineView.line.gutterMarkers
        if (cm.options.lineNumbers || markers) {
            var wrap$1 = ensureLineWrapped(lineView)
            var gutterWrap = lineView.gutter = elt("div", null, "CodeMirror-gutter-wrapper", ("left: " + (cm.options.fixedGutter ? dims.fixedPos : -dims.gutterTotalWidth) + "px"))
            cm.display.input.setUneditable(gutterWrap)
            wrap$1.insertBefore(gutterWrap, lineView.text)
            if (lineView.line.gutterClass)
            { gutterWrap.className += " " + lineView.line.gutterClass }
            if (cm.options.lineNumbers && (!markers || !markers["CodeMirror-linenumbers"]))
            { lineView.lineNumber = gutterWrap.appendChild(
                elt("div", lineNumberFor(cm.options, lineN),
                    "CodeMirror-linenumber CodeMirror-gutter-elt",
                    ("left: " + (dims.gutterLeft["CodeMirror-linenumbers"]) + "px; width: " + (cm.display.lineNumInnerWidth) + "px"))) }
            if (markers) { for (var k = 0; k < cm.options.gutters.length; ++k) {
                var id = cm.options.gutters[k], found = markers.hasOwnProperty(id) && markers[id]
                if (found)
                { gutterWrap.appendChild(elt("div", [found], "CodeMirror-gutter-elt",
                    ("left: " + (dims.gutterLeft[id]) + "px; width: " + (dims.gutterWidth[id]) + "px"))) }
            } }
        }
    }

    function updateLineWidgets(cm, lineView, dims) {
        if (lineView.alignable) { lineView.alignable = null }
        for (var node = lineView.node.firstChild, next = (void 0); node; node = next) {
            next = node.nextSibling
            if (node.className == "CodeMirror-linewidget")
            { lineView.node.removeChild(node) }
        }
        insertLineWidgets(cm, lineView, dims)
    }

// Build a line's DOM representation from scratch
    function buildLineElement(cm, lineView, lineN, dims) {
        var built = getLineContent(cm, lineView)
        lineView.text = lineView.node = built.pre
        if (built.bgClass) { lineView.bgClass = built.bgClass }
        if (built.textClass) { lineView.textClass = built.textClass }

        updateLineClasses(cm, lineView)
        updateLineGutter(cm, lineView, lineN, dims)
        insertLineWidgets(cm, lineView, dims)
        return lineView.node
    }

// A lineView may contain multiple logical lines (when merged by
// collapsed spans). The widgets for all of them need to be drawn.
    function insertLineWidgets(cm, lineView, dims) {
        insertLineWidgetsFor(cm, lineView.line, lineView, dims, true)
        if (lineView.rest) { for (var i = 0; i < lineView.rest.length; i++)
        { insertLineWidgetsFor(cm, lineView.rest[i], lineView, dims, false) } }
    }

    function insertLineWidgetsFor(cm, line, lineView, dims, allowAbove) {
        if (!line.widgets) { return }
        var wrap = ensureLineWrapped(lineView)
        for (var i = 0, ws = line.widgets; i < ws.length; ++i) {
            var widget = ws[i], node = elt("div", [widget.node], "CodeMirror-linewidget")
            if (!widget.handleMouseEvents) { node.setAttribute("cm-ignore-events", "true") }
            positionLineWidget(widget, node, lineView, dims)
            cm.display.input.setUneditable(node)
            if (allowAbove && widget.above)
            { wrap.insertBefore(node, lineView.gutter || lineView.text) }
            else
            { wrap.appendChild(node) }
            signalLater(widget, "redraw")
        }
    }

    function positionLineWidget(widget, node, lineView, dims) {
        if (widget.noHScroll) {
            ;(lineView.alignable || (lineView.alignable = [])).push(node)
            var width = dims.wrapperWidth
            node.style.left = dims.fixedPos + "px"
            if (!widget.coverGutter) {
                width -= dims.gutterTotalWidth
                node.style.paddingLeft = dims.gutterTotalWidth + "px"
            }
            node.style.width = width + "px"
        }
        if (widget.coverGutter) {
            node.style.zIndex = 5
            node.style.position = "relative"
            if (!widget.noHScroll) { node.style.marginLeft = -dims.gutterTotalWidth + "px" }
        }
    }

    function widgetHeight(widget) {
        if (widget.height != null) { return widget.height }
        var cm = widget.doc.cm
        if (!cm) { return 0 }
        if (!contains(document.body, widget.node)) {
            var parentStyle = "position: relative;"
            if (widget.coverGutter)
            { parentStyle += "margin-left: -" + cm.display.gutters.offsetWidth + "px;" }
            if (widget.noHScroll)
            { parentStyle += "width: " + cm.display.wrapper.clientWidth + "px;" }
            removeChildrenAndAdd(cm.display.measure, elt("div", [widget.node], null, parentStyle))
        }
        return widget.height = widget.node.parentNode.offsetHeight
    }

// Return true when the given mouse event happened in a widget
    function eventInWidget(display, e) {
        for (var n = e_target(e); n != display.wrapper; n = n.parentNode) {
            if (!n || (n.nodeType == 1 && n.getAttribute("cm-ignore-events") == "true") ||
                (n.parentNode == display.sizer && n != display.mover))
            { return true }
        }
    }

// POSITION MEASUREMENT

    function paddingTop(display) {return display.lineSpace.offsetTop}
    function paddingVert(display) {return display.mover.offsetHeight - display.lineSpace.offsetHeight}
    function paddingH(display) {
        if (display.cachedPaddingH) { return display.cachedPaddingH }
        var e = removeChildrenAndAdd(display.measure, elt("pre", "x"))
        var style = window.getComputedStyle ? window.getComputedStyle(e) : e.currentStyle
        var data = {left: parseInt(style.paddingLeft), right: parseInt(style.paddingRight)}
        if (!isNaN(data.left) && !isNaN(data.right)) { display.cachedPaddingH = data }
        return data
    }

    function scrollGap(cm) { return scrollerGap - cm.display.nativeBarWidth }
    function displayWidth(cm) {
        return cm.display.scroller.clientWidth - scrollGap(cm) - cm.display.barWidth
    }
    function displayHeight(cm) {
        return cm.display.scroller.clientHeight - scrollGap(cm) - cm.display.barHeight
    }

// Ensure the lineView.wrapping.heights array is populated. This is
// an array of bottom offsets for the lines that make up a drawn
// line. When lineWrapping is on, there might be more than one
// height.
    function ensureLineHeights(cm, lineView, rect) {
        var wrapping = cm.options.lineWrapping
        var curWidth = wrapping && displayWidth(cm)
        if (!lineView.measure.heights || wrapping && lineView.measure.width != curWidth) {
            var heights = lineView.measure.heights = []
            if (wrapping) {
                lineView.measure.width = curWidth
                var rects = lineView.text.firstChild.getClientRects()
                for (var i = 0; i < rects.length - 1; i++) {
                    var cur = rects[i], next = rects[i + 1]
                    if (Math.abs(cur.bottom - next.bottom) > 2)
                    { heights.push((cur.bottom + next.top) / 2 - rect.top) }
                }
            }
            heights.push(rect.bottom - rect.top)
        }
    }

// Find a line map (mapping character offsets to text nodes) and a
// measurement cache for the given line number. (A line view might
// contain multiple lines when collapsed ranges are present.)
    function mapFromLineView(lineView, line, lineN) {
        if (lineView.line == line)
        { return {map: lineView.measure.map, cache: lineView.measure.cache} }
        for (var i = 0; i < lineView.rest.length; i++)
        { if (lineView.rest[i] == line)
        { return {map: lineView.measure.maps[i], cache: lineView.measure.caches[i]} } }
        for (var i$1 = 0; i$1 < lineView.rest.length; i$1++)
        { if (lineNo(lineView.rest[i$1]) > lineN)
        { return {map: lineView.measure.maps[i$1], cache: lineView.measure.caches[i$1], before: true} } }
    }

// Render a line into the hidden node display.externalMeasured. Used
// when measurement is needed for a line that's not in the viewport.
    function updateExternalMeasurement(cm, line) {
        line = visualLine(line)
        var lineN = lineNo(line)
        var view = cm.display.externalMeasured = new LineView(cm.doc, line, lineN)
        view.lineN = lineN
        var built = view.built = buildLineContent(cm, view)
        view.text = built.pre
        removeChildrenAndAdd(cm.display.lineMeasure, built.pre)
        return view
    }

// Get a {top, bottom, left, right} box (in line-local coordinates)
// for a given character.
    function measureChar(cm, line, ch, bias) {
        return measureCharPrepared(cm, prepareMeasureForLine(cm, line), ch, bias)
    }

// Find a line view that corresponds to the given line number.
    function findViewForLine(cm, lineN) {
        if (lineN >= cm.display.viewFrom && lineN < cm.display.viewTo)
        { return cm.display.view[findViewIndex(cm, lineN)] }
        var ext = cm.display.externalMeasured
        if (ext && lineN >= ext.lineN && lineN < ext.lineN + ext.size)
        { return ext }
    }

// Measurement can be split in two steps, the set-up work that
// applies to the whole line, and the measurement of the actual
// character. Functions like coordsChar, that need to do a lot of
// measurements in a row, can thus ensure that the set-up work is
// only done once.
    function prepareMeasureForLine(cm, line) {
        var lineN = lineNo(line)
        var view = findViewForLine(cm, lineN)
        if (view && !view.text) {
            view = null
        } else if (view && view.changes) {
            updateLineForChanges(cm, view, lineN, getDimensions(cm))
            cm.curOp.forceUpdate = true
        }
        if (!view)
        { view = updateExternalMeasurement(cm, line) }

        var info = mapFromLineView(view, line, lineN)
        return {
            line: line, view: view, rect: null,
            map: info.map, cache: info.cache, before: info.before,
            hasHeights: false
        }
    }

// Given a prepared measurement object, measures the position of an
// actual character (or fetches it from the cache).
    function measureCharPrepared(cm, prepared, ch, bias, varHeight) {
        if (prepared.before) { ch = -1 }
        var key = ch + (bias || ""), found
        if (prepared.cache.hasOwnProperty(key)) {
            found = prepared.cache[key]
        } else {
            if (!prepared.rect)
            { prepared.rect = prepared.view.text.getBoundingClientRect() }
            if (!prepared.hasHeights) {
                ensureLineHeights(cm, prepared.view, prepared.rect)
                prepared.hasHeights = true
            }
            found = measureCharInner(cm, prepared, ch, bias)
            if (!found.bogus) { prepared.cache[key] = found }
        }
        return {left: found.left, right: found.right,
            top: varHeight ? found.rtop : found.top,
            bottom: varHeight ? found.rbottom : found.bottom}
    }

    var nullRect = {left: 0, right: 0, top: 0, bottom: 0}

    function nodeAndOffsetInLineMap(map, ch, bias) {
        var node, start, end, collapse, mStart, mEnd
        // First, search the line map for the text node corresponding to,
        // or closest to, the target character.
        for (var i = 0; i < map.length; i += 3) {
            mStart = map[i]
            mEnd = map[i + 1]
            if (ch < mStart) {
                start = 0; end = 1
                collapse = "left"
            } else if (ch < mEnd) {
                start = ch - mStart
                end = start + 1
            } else if (i == map.length - 3 || ch == mEnd && map[i + 3] > ch) {
                end = mEnd - mStart
                start = end - 1
                if (ch >= mEnd) { collapse = "right" }
            }
            if (start != null) {
                node = map[i + 2]
                if (mStart == mEnd && bias == (node.insertLeft ? "left" : "right"))
                { collapse = bias }
                if (bias == "left" && start == 0)
                { while (i && map[i - 2] == map[i - 3] && map[i - 1].insertLeft) {
                    node = map[(i -= 3) + 2]
                    collapse = "left"
                } }
                if (bias == "right" && start == mEnd - mStart)
                { while (i < map.length - 3 && map[i + 3] == map[i + 4] && !map[i + 5].insertLeft) {
                    node = map[(i += 3) + 2]
                    collapse = "right"
                } }
                break
            }
        }
        return {node: node, start: start, end: end, collapse: collapse, coverStart: mStart, coverEnd: mEnd}
    }

    function getUsefulRect(rects, bias) {
        var rect = nullRect
        if (bias == "left") { for (var i = 0; i < rects.length; i++) {
            if ((rect = rects[i]).left != rect.right) { break }
        } } else { for (var i$1 = rects.length - 1; i$1 >= 0; i$1--) {
            if ((rect = rects[i$1]).left != rect.right) { break }
        } }
        return rect
    }

    function measureCharInner(cm, prepared, ch, bias) {
        var place = nodeAndOffsetInLineMap(prepared.map, ch, bias)
        var node = place.node, start = place.start, end = place.end, collapse = place.collapse

        var rect
        if (node.nodeType == 3) { // If it is a text node, use a range to retrieve the coordinates.
            for (var i$1 = 0; i$1 < 4; i$1++) { // Retry a maximum of 4 times when nonsense rectangles are returned
                while (start && isExtendingChar(prepared.line.text.charAt(place.coverStart + start))) { --start }
                while (place.coverStart + end < place.coverEnd && isExtendingChar(prepared.line.text.charAt(place.coverStart + end))) { ++end }
                if (ie && ie_version < 9 && start == 0 && end == place.coverEnd - place.coverStart)
                { rect = node.parentNode.getBoundingClientRect() }
                else
                { rect = getUsefulRect(range(node, start, end).getClientRects(), bias) }
                if (rect.left || rect.right || start == 0) { break }
                end = start
                start = start - 1
                collapse = "right"
            }
            if (ie && ie_version < 11) { rect = maybeUpdateRectForZooming(cm.display.measure, rect) }
        } else { // If it is a widget, simply get the box for the whole widget.
            if (start > 0) { collapse = bias = "right" }
            var rects
            if (cm.options.lineWrapping && (rects = node.getClientRects()).length > 1)
            { rect = rects[bias == "right" ? rects.length - 1 : 0] }
            else
            { rect = node.getBoundingClientRect() }
        }
        if (ie && ie_version < 9 && !start && (!rect || !rect.left && !rect.right)) {
            var rSpan = node.parentNode.getClientRects()[0]
            if (rSpan)
            { rect = {left: rSpan.left, right: rSpan.left + charWidth(cm.display), top: rSpan.top, bottom: rSpan.bottom} }
            else
            { rect = nullRect }
        }

        var rtop = rect.top - prepared.rect.top, rbot = rect.bottom - prepared.rect.top
        var mid = (rtop + rbot) / 2
        var heights = prepared.view.measure.heights
        var i = 0
        for (; i < heights.length - 1; i++)
        { if (mid < heights[i]) { break } }
        var top = i ? heights[i - 1] : 0, bot = heights[i]
        var result = {left: (collapse == "right" ? rect.right : rect.left) - prepared.rect.left,
            right: (collapse == "left" ? rect.left : rect.right) - prepared.rect.left,
            top: top, bottom: bot}
        if (!rect.left && !rect.right) { result.bogus = true }
        if (!cm.options.singleCursorHeightPerLine) { result.rtop = rtop; result.rbottom = rbot }

        return result
    }

// Work around problem with bounding client rects on ranges being
// returned incorrectly when zoomed on IE10 and below.
    function maybeUpdateRectForZooming(measure, rect) {
        if (!window.screen || screen.logicalXDPI == null ||
            screen.logicalXDPI == screen.deviceXDPI || !hasBadZoomedRects(measure))
        { return rect }
        var scaleX = screen.logicalXDPI / screen.deviceXDPI
        var scaleY = screen.logicalYDPI / screen.deviceYDPI
        return {left: rect.left * scaleX, right: rect.right * scaleX,
            top: rect.top * scaleY, bottom: rect.bottom * scaleY}
    }

    function clearLineMeasurementCacheFor(lineView) {
        if (lineView.measure) {
            lineView.measure.cache = {}
            lineView.measure.heights = null
            if (lineView.rest) { for (var i = 0; i < lineView.rest.length; i++)
            { lineView.measure.caches[i] = {} } }
        }
    }

    function clearLineMeasurementCache(cm) {
        cm.display.externalMeasure = null
        removeChildren(cm.display.lineMeasure)
        for (var i = 0; i < cm.display.view.length; i++)
        { clearLineMeasurementCacheFor(cm.display.view[i]) }
    }

    function clearCaches(cm) {
        clearLineMeasurementCache(cm)
        cm.display.cachedCharWidth = cm.display.cachedTextHeight = cm.display.cachedPaddingH = null
        if (!cm.options.lineWrapping) { cm.display.maxLineChanged = true }
        cm.display.lineNumChars = null
    }

    function pageScrollX() {
        // Work around https://bugs.chromium.org/p/chromium/issues/detail?id=489206
        // which causes page_Offset and bounding client rects to use
        // different reference viewports and invalidate our calculations.
        if (chrome && android) { return -(document.body.getBoundingClientRect().left - parseInt(getComputedStyle(document.body).marginLeft)) }
        return window.pageXOffset || (document.documentElement || document.body).scrollLeft
    }
    function pageScrollY() {
        if (chrome && android) { return -(document.body.getBoundingClientRect().top - parseInt(getComputedStyle(document.body).marginTop)) }
        return window.pageYOffset || (document.documentElement || document.body).scrollTop
    }

    function widgetTopHeight(lineObj) {
        var height = 0
        if (lineObj.widgets) { for (var i = 0; i < lineObj.widgets.length; ++i) { if (lineObj.widgets[i].above)
        { height += widgetHeight(lineObj.widgets[i]) } } }
        return height
    }

// Converts a {top, bottom, left, right} box from line-local
// coordinates into another coordinate system. Context may be one of
// "line", "div" (display.lineDiv), "local"./null (editor), "window",
// or "page".
    function intoCoordSystem(cm, lineObj, rect, context, includeWidgets) {
        if (!includeWidgets) {
            var height = widgetTopHeight(lineObj)
            rect.top += height; rect.bottom += height
        }
        if (context == "line") { return rect }
        if (!context) { context = "local" }
        var yOff = heightAtLine(lineObj)
        if (context == "local") { yOff += paddingTop(cm.display) }
        else { yOff -= cm.display.viewOffset }
        if (context == "page" || context == "window") {
            var lOff = cm.display.lineSpace.getBoundingClientRect()
            yOff += lOff.top + (context == "window" ? 0 : pageScrollY())
            var xOff = lOff.left + (context == "window" ? 0 : pageScrollX())
            rect.left += xOff; rect.right += xOff
        }
        rect.top += yOff; rect.bottom += yOff
        return rect
    }

// Coverts a box from "div" coords to another coordinate system.
// Context may be "window", "page", "div", or "local"./null.
    function fromCoordSystem(cm, coords, context) {
        if (context == "div") { return coords }
        var left = coords.left, top = coords.top
        // First move into "page" coordinate system
        if (context == "page") {
            left -= pageScrollX()
            top -= pageScrollY()
        } else if (context == "local" || !context) {
            var localBox = cm.display.sizer.getBoundingClientRect()
            left += localBox.left
            top += localBox.top
        }

        var lineSpaceBox = cm.display.lineSpace.getBoundingClientRect()
        return {left: left - lineSpaceBox.left, top: top - lineSpaceBox.top}
    }

    function charCoords(cm, pos, context, lineObj, bias) {
        if (!lineObj) { lineObj = getLine(cm.doc, pos.line) }
        return intoCoordSystem(cm, lineObj, measureChar(cm, lineObj, pos.ch, bias), context)
    }

// Returns a box for a given cursor position, which may have an
// 'other' property containing the position of the secondary cursor
// on a bidi boundary.
// A cursor Pos(line, char, "before") is on the same visual line as `char - 1`
// and after `char - 1` in writing order of `char - 1`
// A cursor Pos(line, char, "after") is on the same visual line as `char`
// and before `char` in writing order of `char`
// Examples (upper-case letters are RTL, lower-case are LTR):
//     Pos(0, 1, ...)
//     before   after
// ab     a|b     a|b
// aB     a|B     aB|
// Ab     |Ab     A|b
// AB     B|A     B|A
// Every position after the last character on a line is considered to stick
// to the last character on the line.
    function cursorCoords(cm, pos, context, lineObj, preparedMeasure, varHeight) {
        lineObj = lineObj || getLine(cm.doc, pos.line)
        if (!preparedMeasure) { preparedMeasure = prepareMeasureForLine(cm, lineObj) }
        function get(ch, right) {
            var m = measureCharPrepared(cm, preparedMeasure, ch, right ? "right" : "left", varHeight)
            if (right) { m.left = m.right; } else { m.right = m.left }
            return intoCoordSystem(cm, lineObj, m, context)
        }
        var order = getOrder(lineObj, cm.doc.direction), ch = pos.ch, sticky = pos.sticky
        if (ch >= lineObj.text.length) {
            ch = lineObj.text.length
            sticky = "before"
        } else if (ch <= 0) {
            ch = 0
            sticky = "after"
        }
        if (!order) { return get(sticky == "before" ? ch - 1 : ch, sticky == "before") }

        function getBidi(ch, partPos, invert) {
            var part = order[partPos], right = part.level == 1
            return get(invert ? ch - 1 : ch, right != invert)
        }
        var partPos = getBidiPartAt(order, ch, sticky)
        var other = bidiOther
        var val = getBidi(ch, partPos, sticky == "before")
        if (other != null) { val.other = getBidi(ch, other, sticky != "before") }
        return val
    }

// Used to cheaply estimate the coordinates for a position. Used for
// intermediate scroll updates.
    function estimateCoords(cm, pos) {
        var left = 0
        pos = clipPos(cm.doc, pos)
        if (!cm.options.lineWrapping) { left = charWidth(cm.display) * pos.ch }
        var lineObj = getLine(cm.doc, pos.line)
        var top = heightAtLine(lineObj) + paddingTop(cm.display)
        return {left: left, right: left, top: top, bottom: top + lineObj.height}
    }

// Positions returned by coordsChar contain some extra information.
// xRel is the relative x position of the input coordinates compared
// to the found position (so xRel > 0 means the coordinates are to
// the right of the character position, for example). When outside
// is true, that means the coordinates lie outside the line's
// vertical range.
    function PosWithInfo(line, ch, sticky, outside, xRel) {
        var pos = Pos(line, ch, sticky)
        pos.xRel = xRel
        if (outside) { pos.outside = true }
        return pos
    }

// Compute the character position closest to the given coordinates.
// Input must be lineSpace-local ("div" coordinate system).
    function coordsChar(cm, x, y) {
        var doc = cm.doc
        y += cm.display.viewOffset
        if (y < 0) { return PosWithInfo(doc.first, 0, null, true, -1) }
        var lineN = lineAtHeight(doc, y), last = doc.first + doc.size - 1
        if (lineN > last)
        { return PosWithInfo(doc.first + doc.size - 1, getLine(doc, last).text.length, null, true, 1) }
        if (x < 0) { x = 0 }

        var lineObj = getLine(doc, lineN)
        for (;;) {
            var found = coordsCharInner(cm, lineObj, lineN, x, y)
            var merged = collapsedSpanAtEnd(lineObj)
            var mergedPos = merged && merged.find(0, true)
            if (merged && (found.ch > mergedPos.from.ch || found.ch == mergedPos.from.ch && found.xRel > 0))
            { lineN = lineNo(lineObj = mergedPos.to.line) }
            else
            { return found }
        }
    }

    function wrappedLineExtent(cm, lineObj, preparedMeasure, y) {
        y -= widgetTopHeight(lineObj)
        var end = lineObj.text.length
        var begin = findFirst(function (ch) { return measureCharPrepared(cm, preparedMeasure, ch - 1).bottom <= y; }, end, 0)
        end = findFirst(function (ch) { return measureCharPrepared(cm, preparedMeasure, ch).top > y; }, begin, end)
        return {begin: begin, end: end}
    }

    function wrappedLineExtentChar(cm, lineObj, preparedMeasure, target) {
        if (!preparedMeasure) { preparedMeasure = prepareMeasureForLine(cm, lineObj) }
        var targetTop = intoCoordSystem(cm, lineObj, measureCharPrepared(cm, preparedMeasure, target), "line").top
        return wrappedLineExtent(cm, lineObj, preparedMeasure, targetTop)
    }

// Returns true if the given side of a box is after the given
// coordinates, in top-to-bottom, left-to-right order.
    function boxIsAfter(box, x, y, left) {
        return box.bottom <= y ? false : box.top > y ? true : (left ? box.left : box.right) > x
    }

    function coordsCharInner(cm, lineObj, lineNo, x, y) {
        // Move y into line-local coordinate space
        y -= heightAtLine(lineObj)
        var preparedMeasure = prepareMeasureForLine(cm, lineObj)
        // When directly calling `measureCharPrepared`, we have to adjust
        // for the widgets at this line.
        var widgetHeight = widgetTopHeight(lineObj)
        var begin = 0, end = lineObj.text.length, ltr = true

        var order = getOrder(lineObj, cm.doc.direction)
        // If the line isn't plain left-to-right text, first figure out
        // which bidi section the coordinates fall into.
        if (order) {
            var part = (cm.options.lineWrapping ? coordsBidiPartWrapped : coordsBidiPart)
            (cm, lineObj, lineNo, preparedMeasure, order, x, y)
            ltr = part.level != 1
            // The awkward -1 offsets are needed because findFirst (called
            // on these below) will treat its first bound as inclusive,
            // second as exclusive, but we want to actually address the
            // characters in the part's range
            begin = ltr ? part.from : part.to - 1
            end = ltr ? part.to : part.from - 1
        }

        // A binary search to find the first character whose bounding box
        // starts after the coordinates. If we run across any whose box wrap
        // the coordinates, store that.
        var chAround = null, boxAround = null
        var ch = findFirst(function (ch) {
            var box = measureCharPrepared(cm, preparedMeasure, ch)
            box.top += widgetHeight; box.bottom += widgetHeight
            if (!boxIsAfter(box, x, y, false)) { return false }
            if (box.top <= y && box.left <= x) {
                chAround = ch
                boxAround = box
            }
            return true
        }, begin, end)

        var baseX, sticky, outside = false
        // If a box around the coordinates was found, use that
        if (boxAround) {
            // Distinguish coordinates nearer to the left or right side of the box
            var atLeft = x - boxAround.left < boxAround.right - x, atStart = atLeft == ltr
            ch = chAround + (atStart ? 0 : 1)
            sticky = atStart ? "after" : "before"
            baseX = atLeft ? boxAround.left : boxAround.right
        } else {
            // (Adjust for extended bound, if necessary.)
            if (!ltr && (ch == end || ch == begin)) { ch++ }
            // To determine which side to associate with, get the box to the
            // left of the character and compare it's vertical position to the
            // coordinates
            sticky = ch == 0 ? "after" : ch == lineObj.text.length ? "before" :
                (measureCharPrepared(cm, preparedMeasure, ch - (ltr ? 1 : 0)).bottom + widgetHeight <= y) == ltr ?
                    "after" : "before"
            // Now get accurate coordinates for this place, in order to get a
            // base X position
            var coords = cursorCoords(cm, Pos(lineNo, ch, sticky), "line", lineObj, preparedMeasure)
            baseX = coords.left
            outside = y < coords.top || y >= coords.bottom
        }

        ch = skipExtendingChars(lineObj.text, ch, 1)
        return PosWithInfo(lineNo, ch, sticky, outside, x - baseX)
    }

    function coordsBidiPart(cm, lineObj, lineNo, preparedMeasure, order, x, y) {
        // Bidi parts are sorted left-to-right, and in a non-line-wrapping
        // situation, we can take this ordering to correspond to the visual
        // ordering. This finds the first part whose end is after the given
        // coordinates.
        var index = findFirst(function (i) {
            var part = order[i], ltr = part.level != 1
            return boxIsAfter(cursorCoords(cm, Pos(lineNo, ltr ? part.to : part.from, ltr ? "before" : "after"),
                "line", lineObj, preparedMeasure), x, y, true)
        }, 0, order.length - 1)
        var part = order[index]
        // If this isn't the first part, the part's start is also after
        // the coordinates, and the coordinates aren't on the same line as
        // that start, move one part back.
        if (index > 0) {
            var ltr = part.level != 1
            var start = cursorCoords(cm, Pos(lineNo, ltr ? part.from : part.to, ltr ? "after" : "before"),
                "line", lineObj, preparedMeasure)
            if (boxIsAfter(start, x, y, true) && start.top > y)
            { part = order[index - 1] }
        }
        return part
    }

    function coordsBidiPartWrapped(cm, lineObj, _lineNo, preparedMeasure, order, x, y) {
        // In a wrapped line, rtl text on wrapping boundaries can do things
        // that don't correspond to the ordering in our `order` array at
        // all, so a binary search doesn't work, and we want to return a
        // part that only spans one line so that the binary search in
        // coordsCharInner is safe. As such, we first find the extent of the
        // wrapped line, and then do a flat search in which we discard any
        // spans that aren't on the line.
        var ref = wrappedLineExtent(cm, lineObj, preparedMeasure, y);
        var begin = ref.begin;
        var end = ref.end;
        if (/\s/.test(lineObj.text.charAt(end - 1))) { end-- }
        var part = null, closestDist = null
        for (var i = 0; i < order.length; i++) {
            var p = order[i]
            if (p.from >= end || p.to <= begin) { continue }
            var ltr = p.level != 1
            var endX = measureCharPrepared(cm, preparedMeasure, ltr ? Math.min(end, p.to) - 1 : Math.max(begin, p.from)).right
            // Weigh against spans ending before this, so that they are only
            // picked if nothing ends after
            var dist = endX < x ? x - endX + 1e9 : endX - x
            if (!part || closestDist > dist) {
                part = p
                closestDist = dist
            }
        }
        if (!part) { part = order[order.length - 1] }
        // Clip the part to the wrapped line.
        if (part.from < begin) { part = {from: begin, to: part.to, level: part.level} }
        if (part.to > end) { part = {from: part.from, to: end, level: part.level} }
        return part
    }

    var measureText
// Compute the default text height.
    function textHeight(display) {
        if (display.cachedTextHeight != null) { return display.cachedTextHeight }
        if (measureText == null) {
            measureText = elt("pre")
            // Measure a bunch of lines, for browsers that compute
            // fractional heights.
            for (var i = 0; i < 49; ++i) {
                measureText.appendChild(document.createTextNode("x"))
                measureText.appendChild(elt("br"))
            }
            measureText.appendChild(document.createTextNode("x"))
        }
        removeChildrenAndAdd(display.measure, measureText)
        var height = measureText.offsetHeight / 50
        if (height > 3) { display.cachedTextHeight = height }
        removeChildren(display.measure)
        return height || 1
    }

// Compute the default character width.
    function charWidth(display) {
        if (display.cachedCharWidth != null) { return display.cachedCharWidth }
        var anchor = elt("span", "xxxxxxxxxx")
        var pre = elt("pre", [anchor])
        removeChildrenAndAdd(display.measure, pre)
        var rect = anchor.getBoundingClientRect(), width = (rect.right - rect.left) / 10
        if (width > 2) { display.cachedCharWidth = width }
        return width || 10
    }

// Do a bulk-read of the DOM positions and sizes needed to draw the
// view, so that we don't interleave reading and writing to the DOM.
    function getDimensions(cm) {
        var d = cm.display, left = {}, width = {}
        var gutterLeft = d.gutters.clientLeft
        for (var n = d.gutters.firstChild, i = 0; n; n = n.nextSibling, ++i) {
            left[cm.options.gutters[i]] = n.offsetLeft + n.clientLeft + gutterLeft
            width[cm.options.gutters[i]] = n.clientWidth
        }
        return {fixedPos: compensateForHScroll(d),
            gutterTotalWidth: d.gutters.offsetWidth,
            gutterLeft: left,
            gutterWidth: width,
            wrapperWidth: d.wrapper.clientWidth}
    }

// Computes display.scroller.scrollLeft + display.gutters.offsetWidth,
// but using getBoundingClientRect to get a sub-pixel-accurate
// result.
    function compensateForHScroll(display) {
        return display.scroller.getBoundingClientRect().left - display.sizer.getBoundingClientRect().left
    }

// Returns a function that estimates the height of a line, to use as
// first approximation until the line becomes visible (and is thus
// properly measurable).
    function estimateHeight(cm) {
        var th = textHeight(cm.display), wrapping = cm.options.lineWrapping
        var perLine = wrapping && Math.max(5, cm.display.scroller.clientWidth / charWidth(cm.display) - 3)
        return function (line) {
            if (lineIsHidden(cm.doc, line)) { return 0 }

            var widgetsHeight = 0
            if (line.widgets) { for (var i = 0; i < line.widgets.length; i++) {
                if (line.widgets[i].height) { widgetsHeight += line.widgets[i].height }
            } }

            if (wrapping)
            { return widgetsHeight + (Math.ceil(line.text.length / perLine) || 1) * th }
            else
            { return widgetsHeight + th }
        }
    }

    function estimateLineHeights(cm) {
        var doc = cm.doc, est = estimateHeight(cm)
        doc.iter(function (line) {
            var estHeight = est(line)
            if (estHeight != line.height) { updateLineHeight(line, estHeight) }
        })
    }

// Given a mouse event, find the corresponding position. If liberal
// is false, it checks whether a gutter or scrollbar was clicked,
// and returns null if it was. forRect is used by rectangular
// selections, and tries to estimate a character position even for
// coordinates beyond the right of the text.
    function posFromMouse(cm, e, liberal, forRect) {
        var display = cm.display
        if (!liberal && e_target(e).getAttribute("cm-not-content") == "true") { return null }

        var x, y, space = display.lineSpace.getBoundingClientRect()
        // Fails unpredictably on IE[67] when mouse is dragged around quickly.
        try { x = e.clientX - space.left; y = e.clientY - space.top }
        catch (e) { return null }
        var coords = coordsChar(cm, x, y), line
        if (forRect && coords.xRel == 1 && (line = getLine(cm.doc, coords.line).text).length == coords.ch) {
            var colDiff = countColumn(line, line.length, cm.options.tabSize) - line.length
            coords = Pos(coords.line, Math.max(0, Math.round((x - paddingH(cm.display).left) / charWidth(cm.display)) - colDiff))
        }
        return coords
    }

// Find the view element corresponding to a given line. Return null
// when the line isn't visible.
    function findViewIndex(cm, n) {
        if (n >= cm.display.viewTo) { return null }
        n -= cm.display.viewFrom
        if (n < 0) { return null }
        var view = cm.display.view
        for (var i = 0; i < view.length; i++) {
            n -= view[i].size
            if (n < 0) { return i }
        }
    }

    function updateSelection(cm) {
        cm.display.input.showSelection(cm.display.input.prepareSelection())
    }

    function prepareSelection(cm, primary) {
        if ( primary === void 0 ) primary = true;

        var doc = cm.doc, result = {}
        var curFragment = result.cursors = document.createDocumentFragment()
        var selFragment = result.selection = document.createDocumentFragment()

        for (var i = 0; i < doc.sel.ranges.length; i++) {
            if (!primary && i == doc.sel.primIndex) { continue }
            var range = doc.sel.ranges[i]
            if (range.from().line >= cm.display.viewTo || range.to().line < cm.display.viewFrom) { continue }
            var collapsed = range.empty()
            if (collapsed || cm.options.showCursorWhenSelecting)
            { drawSelectionCursor(cm, range.head, curFragment) }
            if (!collapsed)
            { drawSelectionRange(cm, range, selFragment) }
        }
        return result
    }

// Draws a cursor for the given range
    function drawSelectionCursor(cm, head, output) {
        var pos = cursorCoords(cm, head, "div", null, null, !cm.options.singleCursorHeightPerLine)

        var cursor = output.appendChild(elt("div", "\u00a0", "CodeMirror-cursor"))
        cursor.style.left = pos.left + "px"
        cursor.style.top = pos.top + "px"
        cursor.style.height = Math.max(0, pos.bottom - pos.top) * cm.options.cursorHeight + "px"

        if (pos.other) {
            // Secondary cursor, shown when on a 'jump' in bi-directional text
            var otherCursor = output.appendChild(elt("div", "\u00a0", "CodeMirror-cursor CodeMirror-secondarycursor"))
            otherCursor.style.display = ""
            otherCursor.style.left = pos.other.left + "px"
            otherCursor.style.top = pos.other.top + "px"
            otherCursor.style.height = (pos.other.bottom - pos.other.top) * .85 + "px"
        }
    }

    function cmpCoords(a, b) { return a.top - b.top || a.left - b.left }

// Draws the given range as a highlighted selection
    function drawSelectionRange(cm, range, output) {
        var display = cm.display, doc = cm.doc
        var fragment = document.createDocumentFragment()
        var padding = paddingH(cm.display), leftSide = padding.left
        var rightSide = Math.max(display.sizerWidth, displayWidth(cm) - display.sizer.offsetLeft) - padding.right
        var docLTR = doc.direction == "ltr"

        function add(left, top, width, bottom) {
            if (top < 0) { top = 0 }
            top = Math.round(top)
            bottom = Math.round(bottom)
            fragment.appendChild(elt("div", null, "CodeMirror-selected", ("position: absolute; left: " + left + "px;\n                             top: " + top + "px; width: " + (width == null ? rightSide - left : width) + "px;\n                             height: " + (bottom - top) + "px")))
        }

        function drawForLine(line, fromArg, toArg) {
            var lineObj = getLine(doc, line)
            var lineLen = lineObj.text.length
            var start, end
            function coords(ch, bias) {
                return charCoords(cm, Pos(line, ch), "div", lineObj, bias)
            }

            function wrapX(pos, dir, side) {
                var extent = wrappedLineExtentChar(cm, lineObj, null, pos)
                var prop = (dir == "ltr") == (side == "after") ? "left" : "right"
                var ch = side == "after" ? extent.begin : extent.end - (/\s/.test(lineObj.text.charAt(extent.end - 1)) ? 2 : 1)
                return coords(ch, prop)[prop]
            }

            var order = getOrder(lineObj, doc.direction)
            iterateBidiSections(order, fromArg || 0, toArg == null ? lineLen : toArg, function (from, to, dir, i) {
                var ltr = dir == "ltr"
                var fromPos = coords(from, ltr ? "left" : "right")
                var toPos = coords(to - 1, ltr ? "right" : "left")

                var openStart = fromArg == null && from == 0, openEnd = toArg == null && to == lineLen
                var first = i == 0, last = !order || i == order.length - 1
                if (toPos.top - fromPos.top <= 3) { // Single line
                    var openLeft = (docLTR ? openStart : openEnd) && first
                    var openRight = (docLTR ? openEnd : openStart) && last
                    var left = openLeft ? leftSide : (ltr ? fromPos : toPos).left
                    var right = openRight ? rightSide : (ltr ? toPos : fromPos).right
                    add(left, fromPos.top, right - left, fromPos.bottom)
                } else { // Multiple lines
                    var topLeft, topRight, botLeft, botRight
                    if (ltr) {
                        topLeft = docLTR && openStart && first ? leftSide : fromPos.left
                        topRight = docLTR ? rightSide : wrapX(from, dir, "before")
                        botLeft = docLTR ? leftSide : wrapX(to, dir, "after")
                        botRight = docLTR && openEnd && last ? rightSide : toPos.right
                    } else {
                        topLeft = !docLTR ? leftSide : wrapX(from, dir, "before")
                        topRight = !docLTR && openStart && first ? rightSide : fromPos.right
                        botLeft = !docLTR && openEnd && last ? leftSide : toPos.left
                        botRight = !docLTR ? rightSide : wrapX(to, dir, "after")
                    }
                    add(topLeft, fromPos.top, topRight - topLeft, fromPos.bottom)
                    if (fromPos.bottom < toPos.top) { add(leftSide, fromPos.bottom, null, toPos.top) }
                    add(botLeft, toPos.top, botRight - botLeft, toPos.bottom)
                }

                if (!start || cmpCoords(fromPos, start) < 0) { start = fromPos }
                if (cmpCoords(toPos, start) < 0) { start = toPos }
                if (!end || cmpCoords(fromPos, end) < 0) { end = fromPos }
                if (cmpCoords(toPos, end) < 0) { end = toPos }
            })
            return {start: start, end: end}
        }

        var sFrom = range.from(), sTo = range.to()
        if (sFrom.line == sTo.line) {
            drawForLine(sFrom.line, sFrom.ch, sTo.ch)
        } else {
            var fromLine = getLine(doc, sFrom.line), toLine = getLine(doc, sTo.line)
            var singleVLine = visualLine(fromLine) == visualLine(toLine)
            var leftEnd = drawForLine(sFrom.line, sFrom.ch, singleVLine ? fromLine.text.length + 1 : null).end
            var rightStart = drawForLine(sTo.line, singleVLine ? 0 : null, sTo.ch).start
            if (singleVLine) {
                if (leftEnd.top < rightStart.top - 2) {
                    add(leftEnd.right, leftEnd.top, null, leftEnd.bottom)
                    add(leftSide, rightStart.top, rightStart.left, rightStart.bottom)
                } else {
                    add(leftEnd.right, leftEnd.top, rightStart.left - leftEnd.right, leftEnd.bottom)
                }
            }
            if (leftEnd.bottom < rightStart.top)
            { add(leftSide, leftEnd.bottom, null, rightStart.top) }
        }

        output.appendChild(fragment)
    }

// Cursor-blinking
    function restartBlink(cm) {
        if (!cm.state.focused) { return }
        var display = cm.display
        clearInterval(display.blinker)
        var on = true
        display.cursorDiv.style.visibility = ""
        if (cm.options.cursorBlinkRate > 0)
        { display.blinker = setInterval(function () { return display.cursorDiv.style.visibility = (on = !on) ? "" : "hidden"; },
            cm.options.cursorBlinkRate) }
        else if (cm.options.cursorBlinkRate < 0)
        { display.cursorDiv.style.visibility = "hidden" }
    }

    function ensureFocus(cm) {
        if (!cm.state.focused) { cm.display.input.focus(); onFocus(cm) }
    }

    function delayBlurEvent(cm) {
        cm.state.delayingBlurEvent = true
        setTimeout(function () { if (cm.state.delayingBlurEvent) {
            cm.state.delayingBlurEvent = false
            onBlur(cm)
        } }, 100)
    }

    function onFocus(cm, e) {
        if (cm.state.delayingBlurEvent) { cm.state.delayingBlurEvent = false }

        if (cm.options.readOnly == "nocursor") { return }
        if (!cm.state.focused) {
            signal(cm, "focus", cm, e)
            cm.state.focused = true
            addClass(cm.display.wrapper, "CodeMirror-focused")
            // This test prevents this from firing when a context
            // menu is closed (since the input reset would kill the
            // select-all detection hack)
            if (!cm.curOp && cm.display.selForContextMenu != cm.doc.sel) {
                cm.display.input.reset()
                if (webkit) { setTimeout(function () { return cm.display.input.reset(true); }, 20) } // Issue #1730
            }
            cm.display.input.receivedFocus()
        }
        restartBlink(cm)
    }
    function onBlur(cm, e) {
        if (cm.state.delayingBlurEvent) { return }

        if (cm.state.focused) {
            signal(cm, "blur", cm, e)
            cm.state.focused = false
            rmClass(cm.display.wrapper, "CodeMirror-focused")
        }
        clearInterval(cm.display.blinker)
        setTimeout(function () { if (!cm.state.focused) { cm.display.shift = false } }, 150)
    }

// Read the actual heights of the rendered lines, and update their
// stored heights to match.
    function updateHeightsInViewport(cm) {
        var display = cm.display
        var prevBottom = display.lineDiv.offsetTop
        for (var i = 0; i < display.view.length; i++) {
            var cur = display.view[i], height = (void 0)
            if (cur.hidden) { continue }
            if (ie && ie_version < 8) {
                var bot = cur.node.offsetTop + cur.node.offsetHeight
                height = bot - prevBottom
                prevBottom = bot
            } else {
                var box = cur.node.getBoundingClientRect()
                height = box.bottom - box.top
            }
            var diff = cur.line.height - height
            if (height < 2) { height = textHeight(display) }
            if (diff > .005 || diff < -.005) {
                updateLineHeight(cur.line, height)
                updateWidgetHeight(cur.line)
                if (cur.rest) { for (var j = 0; j < cur.rest.length; j++)
                { updateWidgetHeight(cur.rest[j]) } }
            }
        }
    }

// Read and store the height of line widgets associated with the
// given line.
    function updateWidgetHeight(line) {
        if (line.widgets) { for (var i = 0; i < line.widgets.length; ++i) {
            var w = line.widgets[i], parent = w.node.parentNode
            if (parent) { w.height = parent.offsetHeight }
        } }
    }

// Compute the lines that are visible in a given viewport (defaults
// the the current scroll position). viewport may contain top,
// height, and ensure (see op.scrollToPos) properties.
    function visibleLines(display, doc, viewport) {
        var top = viewport && viewport.top != null ? Math.max(0, viewport.top) : display.scroller.scrollTop
        top = Math.floor(top - paddingTop(display))
        var bottom = viewport && viewport.bottom != null ? viewport.bottom : top + display.wrapper.clientHeight

        var from = lineAtHeight(doc, top), to = lineAtHeight(doc, bottom)
        // Ensure is a {from: {line, ch}, to: {line, ch}} object, and
        // forces those lines into the viewport (if possible).
        if (viewport && viewport.ensure) {
            var ensureFrom = viewport.ensure.from.line, ensureTo = viewport.ensure.to.line
            if (ensureFrom < from) {
                from = ensureFrom
                to = lineAtHeight(doc, heightAtLine(getLine(doc, ensureFrom)) + display.wrapper.clientHeight)
            } else if (Math.min(ensureTo, doc.lastLine()) >= to) {
                from = lineAtHeight(doc, heightAtLine(getLine(doc, ensureTo)) - display.wrapper.clientHeight)
                to = ensureTo
            }
        }
        return {from: from, to: Math.max(to, from + 1)}
    }

// Re-align line numbers and gutter marks to compensate for
// horizontal scrolling.
    function alignHorizontally(cm) {
        var display = cm.display, view = display.view
        if (!display.alignWidgets && (!display.gutters.firstChild || !cm.options.fixedGutter)) { return }
        var comp = compensateForHScroll(display) - display.scroller.scrollLeft + cm.doc.scrollLeft
        var gutterW = display.gutters.offsetWidth, left = comp + "px"
        for (var i = 0; i < view.length; i++) { if (!view[i].hidden) {
            if (cm.options.fixedGutter) {
                if (view[i].gutter)
                { view[i].gutter.style.left = left }
                if (view[i].gutterBackground)
                { view[i].gutterBackground.style.left = left }
            }
            var align = view[i].alignable
            if (align) { for (var j = 0; j < align.length; j++)
            { align[j].style.left = left } }
        } }
        if (cm.options.fixedGutter)
        { display.gutters.style.left = (comp + gutterW) + "px" }
    }

// Used to ensure that the line number gutter is still the right
// size for the current document size. Returns true when an update
// is needed.
    function maybeUpdateLineNumberWidth(cm) {
        if (!cm.options.lineNumbers) { return false }
        var doc = cm.doc, last = lineNumberFor(cm.options, doc.first + doc.size - 1), display = cm.display
        if (last.length != display.lineNumChars) {
            var test = display.measure.appendChild(elt("div", [elt("div", last)],
                "CodeMirror-linenumber CodeMirror-gutter-elt"))
            var innerW = test.firstChild.offsetWidth, padding = test.offsetWidth - innerW
            display.lineGutter.style.width = ""
            display.lineNumInnerWidth = Math.max(innerW, display.lineGutter.offsetWidth - padding) + 1
            display.lineNumWidth = display.lineNumInnerWidth + padding
            display.lineNumChars = display.lineNumInnerWidth ? last.length : -1
            display.lineGutter.style.width = display.lineNumWidth + "px"
            updateGutterSpace(cm)
            return true
        }
        return false
    }

// SCROLLING THINGS INTO VIEW

// If an editor sits on the top or bottom of the window, partially
// scrolled out of view, this ensures that the cursor is visible.
    function maybeScrollWindow(cm, rect) {
        if (signalDOMEvent(cm, "scrollCursorIntoView")) { return }

        var display = cm.display, box = display.sizer.getBoundingClientRect(), doScroll = null
        if (rect.top + box.top < 0) { doScroll = true }
        else if (rect.bottom + box.top > (window.innerHeight || document.documentElement.clientHeight)) { doScroll = false }
        if (doScroll != null && !phantom) {
            var scrollNode = elt("div", "\u200b", null, ("position: absolute;\n                         top: " + (rect.top - display.viewOffset - paddingTop(cm.display)) + "px;\n                         height: " + (rect.bottom - rect.top + scrollGap(cm) + display.barHeight) + "px;\n                         left: " + (rect.left) + "px; width: " + (Math.max(2, rect.right - rect.left)) + "px;"))
            cm.display.lineSpace.appendChild(scrollNode)
            scrollNode.scrollIntoView(doScroll)
            cm.display.lineSpace.removeChild(scrollNode)
        }
    }

// Scroll a given position into view (immediately), verifying that
// it actually became visible (as line heights are accurately
// measured, the position of something may 'drift' during drawing).
    function scrollPosIntoView(cm, pos, end, margin) {
        if (margin == null) { margin = 0 }
        var rect
        if (!cm.options.lineWrapping && pos == end) {
            // Set pos and end to the cursor positions around the character pos sticks to
            // If pos.sticky == "before", that is around pos.ch - 1, otherwise around pos.ch
            // If pos == Pos(_, 0, "before"), pos and end are unchanged
            pos = pos.ch ? Pos(pos.line, pos.sticky == "before" ? pos.ch - 1 : pos.ch, "after") : pos
            end = pos.sticky == "before" ? Pos(pos.line, pos.ch + 1, "before") : pos
        }
        for (var limit = 0; limit < 5; limit++) {
            var changed = false
            var coords = cursorCoords(cm, pos)
            var endCoords = !end || end == pos ? coords : cursorCoords(cm, end)
            rect = {left: Math.min(coords.left, endCoords.left),
                top: Math.min(coords.top, endCoords.top) - margin,
                right: Math.max(coords.left, endCoords.left),
                bottom: Math.max(coords.bottom, endCoords.bottom) + margin}
            var scrollPos = calculateScrollPos(cm, rect)
            var startTop = cm.doc.scrollTop, startLeft = cm.doc.scrollLeft
            if (scrollPos.scrollTop != null) {
                updateScrollTop(cm, scrollPos.scrollTop)
                if (Math.abs(cm.doc.scrollTop - startTop) > 1) { changed = true }
            }
            if (scrollPos.scrollLeft != null) {
                setScrollLeft(cm, scrollPos.scrollLeft)
                if (Math.abs(cm.doc.scrollLeft - startLeft) > 1) { changed = true }
            }
            if (!changed) { break }
        }
        return rect
    }

// Scroll a given set of coordinates into view (immediately).
    function scrollIntoView(cm, rect) {
        var scrollPos = calculateScrollPos(cm, rect)
        if (scrollPos.scrollTop != null) { updateScrollTop(cm, scrollPos.scrollTop) }
        if (scrollPos.scrollLeft != null) { setScrollLeft(cm, scrollPos.scrollLeft) }
    }

// Calculate a new scroll position needed to scroll the given
// rectangle into view. Returns an object with scrollTop and
// scrollLeft properties. When these are undefined, the
// vertical/horizontal position does not need to be adjusted.
    function calculateScrollPos(cm, rect) {
        var display = cm.display, snapMargin = textHeight(cm.display)
        if (rect.top < 0) { rect.top = 0 }
        var screentop = cm.curOp && cm.curOp.scrollTop != null ? cm.curOp.scrollTop : display.scroller.scrollTop
        var screen = displayHeight(cm), result = {}
        if (rect.bottom - rect.top > screen) { rect.bottom = rect.top + screen }
        var docBottom = cm.doc.height + paddingVert(display)
        var atTop = rect.top < snapMargin, atBottom = rect.bottom > docBottom - snapMargin
        if (rect.top < screentop) {
            result.scrollTop = atTop ? 0 : rect.top
        } else if (rect.bottom > screentop + screen) {
            var newTop = Math.min(rect.top, (atBottom ? docBottom : rect.bottom) - screen)
            if (newTop != screentop) { result.scrollTop = newTop }
        }

        var screenleft = cm.curOp && cm.curOp.scrollLeft != null ? cm.curOp.scrollLeft : display.scroller.scrollLeft
        var screenw = displayWidth(cm) - (cm.options.fixedGutter ? display.gutters.offsetWidth : 0)
        var tooWide = rect.right - rect.left > screenw
        if (tooWide) { rect.right = rect.left + screenw }
        if (rect.left < 10)
        { result.scrollLeft = 0 }
        else if (rect.left < screenleft)
        { result.scrollLeft = Math.max(0, rect.left - (tooWide ? 0 : 10)) }
        else if (rect.right > screenw + screenleft - 3)
        { result.scrollLeft = rect.right + (tooWide ? 0 : 10) - screenw }
        return result
    }

// Store a relative adjustment to the scroll position in the current
// operation (to be applied when the operation finishes).
    function addToScrollTop(cm, top) {
        if (top == null) { return }
        resolveScrollToPos(cm)
        cm.curOp.scrollTop = (cm.curOp.scrollTop == null ? cm.doc.scrollTop : cm.curOp.scrollTop) + top
    }

// Make sure that at the end of the operation the current cursor is
// shown.
    function ensureCursorVisible(cm) {
        resolveScrollToPos(cm)
        var cur = cm.getCursor()
        cm.curOp.scrollToPos = {from: cur, to: cur, margin: cm.options.cursorScrollMargin}
    }

    function scrollToCoords(cm, x, y) {
        if (x != null || y != null) { resolveScrollToPos(cm) }
        if (x != null) { cm.curOp.scrollLeft = x }
        if (y != null) { cm.curOp.scrollTop = y }
    }

    function scrollToRange(cm, range) {
        resolveScrollToPos(cm)
        cm.curOp.scrollToPos = range
    }

// When an operation has its scrollToPos property set, and another
// scroll action is applied before the end of the operation, this
// 'simulates' scrolling that position into view in a cheap way, so
// that the effect of intermediate scroll commands is not ignored.
    function resolveScrollToPos(cm) {
        var range = cm.curOp.scrollToPos
        if (range) {
            cm.curOp.scrollToPos = null
            var from = estimateCoords(cm, range.from), to = estimateCoords(cm, range.to)
            scrollToCoordsRange(cm, from, to, range.margin)
        }
    }

    function scrollToCoordsRange(cm, from, to, margin) {
        var sPos = calculateScrollPos(cm, {
            left: Math.min(from.left, to.left),
            top: Math.min(from.top, to.top) - margin,
            right: Math.max(from.right, to.right),
            bottom: Math.max(from.bottom, to.bottom) + margin
        })
        scrollToCoords(cm, sPos.scrollLeft, sPos.scrollTop)
    }

// Sync the scrollable area and scrollbars, ensure the viewport
// covers the visible area.
    function updateScrollTop(cm, val) {
        if (Math.abs(cm.doc.scrollTop - val) < 2) { return }
        if (!gecko) { updateDisplaySimple(cm, {top: val}) }
        setScrollTop(cm, val, true)
        if (gecko) { updateDisplaySimple(cm) }
        startWorker(cm, 100)
    }

    function setScrollTop(cm, val, forceScroll) {
        val = Math.min(cm.display.scroller.scrollHeight - cm.display.scroller.clientHeight, val)
        if (cm.display.scroller.scrollTop == val && !forceScroll) { return }
        cm.doc.scrollTop = val
        cm.display.scrollbars.setScrollTop(val)
        if (cm.display.scroller.scrollTop != val) { cm.display.scroller.scrollTop = val }
    }

// Sync scroller and scrollbar, ensure the gutter elements are
// aligned.
    function setScrollLeft(cm, val, isScroller, forceScroll) {
        val = Math.min(val, cm.display.scroller.scrollWidth - cm.display.scroller.clientWidth)
        if ((isScroller ? val == cm.doc.scrollLeft : Math.abs(cm.doc.scrollLeft - val) < 2) && !forceScroll) { return }
        cm.doc.scrollLeft = val
        alignHorizontally(cm)
        if (cm.display.scroller.scrollLeft != val) { cm.display.scroller.scrollLeft = val }
        cm.display.scrollbars.setScrollLeft(val)
    }

// SCROLLBARS

// Prepare DOM reads needed to update the scrollbars. Done in one
// shot to minimize update/measure roundtrips.
    function measureForScrollbars(cm) {
        var d = cm.display, gutterW = d.gutters.offsetWidth
        var docH = Math.round(cm.doc.height + paddingVert(cm.display))
        return {
            clientHeight: d.scroller.clientHeight,
            viewHeight: d.wrapper.clientHeight,
            scrollWidth: d.scroller.scrollWidth, clientWidth: d.scroller.clientWidth,
            viewWidth: d.wrapper.clientWidth,
            barLeft: cm.options.fixedGutter ? gutterW : 0,
            docHeight: docH,
            scrollHeight: docH + scrollGap(cm) + d.barHeight,
            nativeBarWidth: d.nativeBarWidth,
            gutterWidth: gutterW
        }
    }

    var NativeScrollbars = function(place, scroll, cm) {
        this.cm = cm
        var vert = this.vert = elt("div", [elt("div", null, null, "min-width: 1px")], "CodeMirror-vscrollbar")
        var horiz = this.horiz = elt("div", [elt("div", null, null, "height: 100%; min-height: 1px")], "CodeMirror-hscrollbar")
        place(vert); place(horiz)

        on(vert, "scroll", function () {
            if (vert.clientHeight) { scroll(vert.scrollTop, "vertical") }
        })
        on(horiz, "scroll", function () {
            if (horiz.clientWidth) { scroll(horiz.scrollLeft, "horizontal") }
        })

        this.checkedZeroWidth = false
        // Need to set a minimum width to see the scrollbar on IE7 (but must not set it on IE8).
        if (ie && ie_version < 8) { this.horiz.style.minHeight = this.vert.style.minWidth = "18px" }
    };

    NativeScrollbars.prototype.update = function (measure) {
        var needsH = measure.scrollWidth > measure.clientWidth + 1
        var needsV = measure.scrollHeight > measure.clientHeight + 1
        var sWidth = measure.nativeBarWidth

        if (needsV) {
            this.vert.style.display = "block"
            this.vert.style.bottom = needsH ? sWidth + "px" : "0"
            var totalHeight = measure.viewHeight - (needsH ? sWidth : 0)
            // A bug in IE8 can cause this value to be negative, so guard it.
            this.vert.firstChild.style.height =
                Math.max(0, measure.scrollHeight - measure.clientHeight + totalHeight) + "px"
        } else {
            this.vert.style.display = ""
            this.vert.firstChild.style.height = "0"
        }

        if (needsH) {
            this.horiz.style.display = "block"
            this.horiz.style.right = needsV ? sWidth + "px" : "0"
            this.horiz.style.left = measure.barLeft + "px"
            var totalWidth = measure.viewWidth - measure.barLeft - (needsV ? sWidth : 0)
            this.horiz.firstChild.style.width =
                Math.max(0, measure.scrollWidth - measure.clientWidth + totalWidth) + "px"
        } else {
            this.horiz.style.display = ""
            this.horiz.firstChild.style.width = "0"
        }

        if (!this.checkedZeroWidth && measure.clientHeight > 0) {
            if (sWidth == 0) { this.zeroWidthHack() }
            this.checkedZeroWidth = true
        }

        return {right: needsV ? sWidth : 0, bottom: needsH ? sWidth : 0}
    };

    NativeScrollbars.prototype.setScrollLeft = function (pos) {
        if (this.horiz.scrollLeft != pos) { this.horiz.scrollLeft = pos }
        if (this.disableHoriz) { this.enableZeroWidthBar(this.horiz, this.disableHoriz, "horiz") }
    };

    NativeScrollbars.prototype.setScrollTop = function (pos) {
        if (this.vert.scrollTop != pos) { this.vert.scrollTop = pos }
        if (this.disableVert) { this.enableZeroWidthBar(this.vert, this.disableVert, "vert") }
    };

    NativeScrollbars.prototype.zeroWidthHack = function () {
        var w = mac && !mac_geMountainLion ? "12px" : "18px"
        this.horiz.style.height = this.vert.style.width = w
        this.horiz.style.pointerEvents = this.vert.style.pointerEvents = "none"
        this.disableHoriz = new Delayed
        this.disableVert = new Delayed
    };

    NativeScrollbars.prototype.enableZeroWidthBar = function (bar, delay, type) {
        bar.style.pointerEvents = "auto"
        function maybeDisable() {
            // To find out whether the scrollbar is still visible, we
            // check whether the element under the pixel in the bottom
            // right corner of the scrollbar box is the scrollbar box
            // itself (when the bar is still visible) or its filler child
            // (when the bar is hidden). If it is still visible, we keep
            // it enabled, if it's hidden, we disable pointer events.
            var box = bar.getBoundingClientRect()
            var elt = type == "vert" ? document.elementFromPoint(box.right - 1, (box.top + box.bottom) / 2)
                : document.elementFromPoint((box.right + box.left) / 2, box.bottom - 1)
            if (elt != bar) { bar.style.pointerEvents = "none" }
            else { delay.set(1000, maybeDisable) }
        }
        delay.set(1000, maybeDisable)
    };

    NativeScrollbars.prototype.clear = function () {
        var parent = this.horiz.parentNode
        parent.removeChild(this.horiz)
        parent.removeChild(this.vert)
    };

    var NullScrollbars = function () {};

    NullScrollbars.prototype.update = function () { return {bottom: 0, right: 0} };
    NullScrollbars.prototype.setScrollLeft = function () {};
    NullScrollbars.prototype.setScrollTop = function () {};
    NullScrollbars.prototype.clear = function () {};

    function updateScrollbars(cm, measure) {
        if (!measure) { measure = measureForScrollbars(cm) }
        var startWidth = cm.display.barWidth, startHeight = cm.display.barHeight
        updateScrollbarsInner(cm, measure)
        for (var i = 0; i < 4 && startWidth != cm.display.barWidth || startHeight != cm.display.barHeight; i++) {
            if (startWidth != cm.display.barWidth && cm.options.lineWrapping)
            { updateHeightsInViewport(cm) }
            updateScrollbarsInner(cm, measureForScrollbars(cm))
            startWidth = cm.display.barWidth; startHeight = cm.display.barHeight
        }
    }

// Re-synchronize the fake scrollbars with the actual size of the
// content.
    function updateScrollbarsInner(cm, measure) {
        var d = cm.display
        var sizes = d.scrollbars.update(measure)

        d.sizer.style.paddingRight = (d.barWidth = sizes.right) + "px"
        d.sizer.style.paddingBottom = (d.barHeight = sizes.bottom) + "px"
        d.heightForcer.style.borderBottom = sizes.bottom + "px solid transparent"

        if (sizes.right && sizes.bottom) {
            d.scrollbarFiller.style.display = "block"
            d.scrollbarFiller.style.height = sizes.bottom + "px"
            d.scrollbarFiller.style.width = sizes.right + "px"
        } else { d.scrollbarFiller.style.display = "" }
        if (sizes.bottom && cm.options.coverGutterNextToScrollbar && cm.options.fixedGutter) {
            d.gutterFiller.style.display = "block"
            d.gutterFiller.style.height = sizes.bottom + "px"
            d.gutterFiller.style.width = measure.gutterWidth + "px"
        } else { d.gutterFiller.style.display = "" }
    }

    var scrollbarModel = {"native": NativeScrollbars, "null": NullScrollbars}

    function initScrollbars(cm) {
        if (cm.display.scrollbars) {
            cm.display.scrollbars.clear()
            if (cm.display.scrollbars.addClass)
            { rmClass(cm.display.wrapper, cm.display.scrollbars.addClass) }
        }

        cm.display.scrollbars = new scrollbarModel[cm.options.scrollbarStyle](function (node) {
            cm.display.wrapper.insertBefore(node, cm.display.scrollbarFiller)
            // Prevent clicks in the scrollbars from killing focus
            on(node, "mousedown", function () {
                if (cm.state.focused) { setTimeout(function () { return cm.display.input.focus(); }, 0) }
            })
            node.setAttribute("cm-not-content", "true")
        }, function (pos, axis) {
            if (axis == "horizontal") { setScrollLeft(cm, pos) }
            else { updateScrollTop(cm, pos) }
        }, cm)
        if (cm.display.scrollbars.addClass)
        { addClass(cm.display.wrapper, cm.display.scrollbars.addClass) }
    }

// Operations are used to wrap a series of changes to the editor
// state in such a way that each change won't have to update the
// cursor and display (which would be awkward, slow, and
// error-prone). Instead, display updates are batched and then all
// combined and executed at once.

    var nextOpId = 0
// Start a new operation.
    function startOperation(cm) {
        cm.curOp = {
            cm: cm,
            viewChanged: false,      // Flag that indicates that lines might need to be redrawn
            startHeight: cm.doc.height, // Used to detect need to update scrollbar
            forceUpdate: false,      // Used to force a redraw
            updateInput: null,       // Whether to reset the input textarea
            typing: false,           // Whether this reset should be careful to leave existing text (for compositing)
            changeObjs: null,        // Accumulated changes, for firing change events
            cursorActivityHandlers: null, // Set of handlers to fire cursorActivity on
            cursorActivityCalled: 0, // Tracks which cursorActivity handlers have been called already
            selectionChanged: false, // Whether the selection needs to be redrawn
            updateMaxLine: false,    // Set when the widest line needs to be determined anew
            scrollLeft: null, scrollTop: null, // Intermediate scroll position, not pushed to DOM yet
            scrollToPos: null,       // Used to scroll to a specific position
            focus: false,
            id: ++nextOpId           // Unique ID
        }
        pushOperation(cm.curOp)
    }

// Finish an operation, updating the display and signalling delayed events
    function endOperation(cm) {
        var op = cm.curOp
        finishOperation(op, function (group) {
            for (var i = 0; i < group.ops.length; i++)
            { group.ops[i].cm.curOp = null }
            endOperations(group)
        })
    }

// The DOM updates done when an operation finishes are batched so
// that the minimum number of relayouts are required.
    function endOperations(group) {
        var ops = group.ops
        for (var i = 0; i < ops.length; i++) // Read DOM
        { endOperation_R1(ops[i]) }
        for (var i$1 = 0; i$1 < ops.length; i$1++) // Write DOM (maybe)
        { endOperation_W1(ops[i$1]) }
        for (var i$2 = 0; i$2 < ops.length; i$2++) // Read DOM
        { endOperation_R2(ops[i$2]) }
        for (var i$3 = 0; i$3 < ops.length; i$3++) // Write DOM (maybe)
        { endOperation_W2(ops[i$3]) }
        for (var i$4 = 0; i$4 < ops.length; i$4++) // Read DOM
        { endOperation_finish(ops[i$4]) }
    }

    function endOperation_R1(op) {
        var cm = op.cm, display = cm.display
        maybeClipScrollbars(cm)
        if (op.updateMaxLine) { findMaxLine(cm) }

        op.mustUpdate = op.viewChanged || op.forceUpdate || op.scrollTop != null ||
            op.scrollToPos && (op.scrollToPos.from.line < display.viewFrom ||
                op.scrollToPos.to.line >= display.viewTo) ||
            display.maxLineChanged && cm.options.lineWrapping
        op.update = op.mustUpdate &&
            new DisplayUpdate(cm, op.mustUpdate && {top: op.scrollTop, ensure: op.scrollToPos}, op.forceUpdate)
    }

    function endOperation_W1(op) {
        op.updatedDisplay = op.mustUpdate && updateDisplayIfNeeded(op.cm, op.update)
    }

    function endOperation_R2(op) {
        var cm = op.cm, display = cm.display
        if (op.updatedDisplay) { updateHeightsInViewport(cm) }

        op.barMeasure = measureForScrollbars(cm)

        // If the max line changed since it was last measured, measure it,
        // and ensure the document's width matches it.
        // updateDisplay_W2 will use these properties to do the actual resizing
        if (display.maxLineChanged && !cm.options.lineWrapping) {
            op.adjustWidthTo = measureChar(cm, display.maxLine, display.maxLine.text.length).left + 3
            cm.display.sizerWidth = op.adjustWidthTo
            op.barMeasure.scrollWidth =
                Math.max(display.scroller.clientWidth, display.sizer.offsetLeft + op.adjustWidthTo + scrollGap(cm) + cm.display.barWidth)
            op.maxScrollLeft = Math.max(0, display.sizer.offsetLeft + op.adjustWidthTo - displayWidth(cm))
        }

        if (op.updatedDisplay || op.selectionChanged)
        { op.preparedSelection = display.input.prepareSelection() }
    }

    function endOperation_W2(op) {
        var cm = op.cm

        if (op.adjustWidthTo != null) {
            cm.display.sizer.style.minWidth = op.adjustWidthTo + "px"
            if (op.maxScrollLeft < cm.doc.scrollLeft)
            { setScrollLeft(cm, Math.min(cm.display.scroller.scrollLeft, op.maxScrollLeft), true) }
            cm.display.maxLineChanged = false
        }

        var takeFocus = op.focus && op.focus == activeElt()
        if (op.preparedSelection)
        { cm.display.input.showSelection(op.preparedSelection, takeFocus) }
        if (op.updatedDisplay || op.startHeight != cm.doc.height)
        { updateScrollbars(cm, op.barMeasure) }
        if (op.updatedDisplay)
        { setDocumentHeight(cm, op.barMeasure) }

        if (op.selectionChanged) { restartBlink(cm) }

        if (cm.state.focused && op.updateInput)
        { cm.display.input.reset(op.typing) }
        if (takeFocus) { ensureFocus(op.cm) }
    }

    function endOperation_finish(op) {
        var cm = op.cm, display = cm.display, doc = cm.doc

        if (op.updatedDisplay) { postUpdateDisplay(cm, op.update) }

        // Abort mouse wheel delta measurement, when scrolling explicitly
        if (display.wheelStartX != null && (op.scrollTop != null || op.scrollLeft != null || op.scrollToPos))
        { display.wheelStartX = display.wheelStartY = null }

        // Propagate the scroll position to the actual DOM scroller
        if (op.scrollTop != null) { setScrollTop(cm, op.scrollTop, op.forceScroll) }

        if (op.scrollLeft != null) { setScrollLeft(cm, op.scrollLeft, true, true) }
        // If we need to scroll a specific position into view, do so.
        if (op.scrollToPos) {
            var rect = scrollPosIntoView(cm, clipPos(doc, op.scrollToPos.from),
                clipPos(doc, op.scrollToPos.to), op.scrollToPos.margin)
            maybeScrollWindow(cm, rect)
        }

        // Fire events for markers that are hidden/unidden by editing or
        // undoing
        var hidden = op.maybeHiddenMarkers, unhidden = op.maybeUnhiddenMarkers
        if (hidden) { for (var i = 0; i < hidden.length; ++i)
        { if (!hidden[i].lines.length) { signal(hidden[i], "hide") } } }
        if (unhidden) { for (var i$1 = 0; i$1 < unhidden.length; ++i$1)
        { if (unhidden[i$1].lines.length) { signal(unhidden[i$1], "unhide") } } }

        if (display.wrapper.offsetHeight)
        { doc.scrollTop = cm.display.scroller.scrollTop }

        // Fire change events, and delayed event handlers
        if (op.changeObjs)
        { signal(cm, "changes", cm, op.changeObjs) }
        if (op.update)
        { op.update.finish() }
    }

// Run the given function in an operation
    function runInOp(cm, f) {
        if (cm.curOp) { return f() }
        startOperation(cm)
        try { return f() }
        finally { endOperation(cm) }
    }
// Wraps a function in an operation. Returns the wrapped function.
    function operation(cm, f) {
        return function() {
            if (cm.curOp) { return f.apply(cm, arguments) }
            startOperation(cm)
            try { return f.apply(cm, arguments) }
            finally { endOperation(cm) }
        }
    }
// Used to add methods to editor and doc instances, wrapping them in
// operations.
    function methodOp(f) {
        return function() {
            if (this.curOp) { return f.apply(this, arguments) }
            startOperation(this)
            try { return f.apply(this, arguments) }
            finally { endOperation(this) }
        }
    }
    function docMethodOp(f) {
        return function() {
            var cm = this.cm
            if (!cm || cm.curOp) { return f.apply(this, arguments) }
            startOperation(cm)
            try { return f.apply(this, arguments) }
            finally { endOperation(cm) }
        }
    }

// Updates the display.view data structure for a given change to the
// document. From and to are in pre-change coordinates. Lendiff is
// the amount of lines added or subtracted by the change. This is
// used for changes that span multiple lines, or change the way
// lines are divided into visual lines. regLineChange (below)
// registers single-line changes.
    function regChange(cm, from, to, lendiff) {
        if (from == null) { from = cm.doc.first }
        if (to == null) { to = cm.doc.first + cm.doc.size }
        if (!lendiff) { lendiff = 0 }

        var display = cm.display
        if (lendiff && to < display.viewTo &&
            (display.updateLineNumbers == null || display.updateLineNumbers > from))
        { display.updateLineNumbers = from }

        cm.curOp.viewChanged = true

        if (from >= display.viewTo) { // Change after
            if (sawCollapsedSpans && visualLineNo(cm.doc, from) < display.viewTo)
            { resetView(cm) }
        } else if (to <= display.viewFrom) { // Change before
            if (sawCollapsedSpans && visualLineEndNo(cm.doc, to + lendiff) > display.viewFrom) {
                resetView(cm)
            } else {
                display.viewFrom += lendiff
                display.viewTo += lendiff
            }
        } else if (from <= display.viewFrom && to >= display.viewTo) { // Full overlap
            resetView(cm)
        } else if (from <= display.viewFrom) { // Top overlap
            var cut = viewCuttingPoint(cm, to, to + lendiff, 1)
            if (cut) {
                display.view = display.view.slice(cut.index)
                display.viewFrom = cut.lineN
                display.viewTo += lendiff
            } else {
                resetView(cm)
            }
        } else if (to >= display.viewTo) { // Bottom overlap
            var cut$1 = viewCuttingPoint(cm, from, from, -1)
            if (cut$1) {
                display.view = display.view.slice(0, cut$1.index)
                display.viewTo = cut$1.lineN
            } else {
                resetView(cm)
            }
        } else { // Gap in the middle
            var cutTop = viewCuttingPoint(cm, from, from, -1)
            var cutBot = viewCuttingPoint(cm, to, to + lendiff, 1)
            if (cutTop && cutBot) {
                display.view = display.view.slice(0, cutTop.index)
                    .concat(buildViewArray(cm, cutTop.lineN, cutBot.lineN))
                    .concat(display.view.slice(cutBot.index))
                display.viewTo += lendiff
            } else {
                resetView(cm)
            }
        }

        var ext = display.externalMeasured
        if (ext) {
            if (to < ext.lineN)
            { ext.lineN += lendiff }
            else if (from < ext.lineN + ext.size)
            { display.externalMeasured = null }
        }
    }

// Register a change to a single line. Type must be one of "text",
// "gutter", "class", "widget"
    function regLineChange(cm, line, type) {
        cm.curOp.viewChanged = true
        var display = cm.display, ext = cm.display.externalMeasured
        if (ext && line >= ext.lineN && line < ext.lineN + ext.size)
        { display.externalMeasured = null }

        if (line < display.viewFrom || line >= display.viewTo) { return }
        var lineView = display.view[findViewIndex(cm, line)]
        if (lineView.node == null) { return }
        var arr = lineView.changes || (lineView.changes = [])
        if (indexOf(arr, type) == -1) { arr.push(type) }
    }

// Clear the view.
    function resetView(cm) {
        cm.display.viewFrom = cm.display.viewTo = cm.doc.first
        cm.display.view = []
        cm.display.viewOffset = 0
    }

    function viewCuttingPoint(cm, oldN, newN, dir) {
        var index = findViewIndex(cm, oldN), diff, view = cm.display.view
        if (!sawCollapsedSpans || newN == cm.doc.first + cm.doc.size)
        { return {index: index, lineN: newN} }
        var n = cm.display.viewFrom
        for (var i = 0; i < index; i++)
        { n += view[i].size }
        if (n != oldN) {
            if (dir > 0) {
                if (index == view.length - 1) { return null }
                diff = (n + view[index].size) - oldN
                index++
            } else {
                diff = n - oldN
            }
            oldN += diff; newN += diff
        }
        while (visualLineNo(cm.doc, newN) != newN) {
            if (index == (dir < 0 ? 0 : view.length - 1)) { return null }
            newN += dir * view[index - (dir < 0 ? 1 : 0)].size
            index += dir
        }
        return {index: index, lineN: newN}
    }

// Force the view to cover a given range, adding empty view element
// or clipping off existing ones as needed.
    function adjustView(cm, from, to) {
        var display = cm.display, view = display.view
        if (view.length == 0 || from >= display.viewTo || to <= display.viewFrom) {
            display.view = buildViewArray(cm, from, to)
            display.viewFrom = from
        } else {
            if (display.viewFrom > from)
            { display.view = buildViewArray(cm, from, display.viewFrom).concat(display.view) }
            else if (display.viewFrom < from)
            { display.view = display.view.slice(findViewIndex(cm, from)) }
            display.viewFrom = from
            if (display.viewTo < to)
            { display.view = display.view.concat(buildViewArray(cm, display.viewTo, to)) }
            else if (display.viewTo > to)
            { display.view = display.view.slice(0, findViewIndex(cm, to)) }
        }
        display.viewTo = to
    }

// Count the number of lines in the view whose DOM representation is
// out of date (or nonexistent).
    function countDirtyView(cm) {
        var view = cm.display.view, dirty = 0
        for (var i = 0; i < view.length; i++) {
            var lineView = view[i]
            if (!lineView.hidden && (!lineView.node || lineView.changes)) { ++dirty }
        }
        return dirty
    }

// HIGHLIGHT WORKER

    function startWorker(cm, time) {
        if (cm.doc.highlightFrontier < cm.display.viewTo)
        { cm.state.highlight.set(time, bind(highlightWorker, cm)) }
    }

    function highlightWorker(cm) {
        var doc = cm.doc
        if (doc.highlightFrontier >= cm.display.viewTo) { return }
        var end = +new Date + cm.options.workTime
        var context = getContextBefore(cm, doc.highlightFrontier)
        var changedLines = []

        doc.iter(context.line, Math.min(doc.first + doc.size, cm.display.viewTo + 500), function (line) {
            if (context.line >= cm.display.viewFrom) { // Visible
                var oldStyles = line.styles
                var resetState = line.text.length > cm.options.maxHighlightLength ? copyState(doc.mode, context.state) : null
                var highlighted = highlightLine(cm, line, context, true)
                if (resetState) { context.state = resetState }
                line.styles = highlighted.styles
                var oldCls = line.styleClasses, newCls = highlighted.classes
                if (newCls) { line.styleClasses = newCls }
                else if (oldCls) { line.styleClasses = null }
                var ischange = !oldStyles || oldStyles.length != line.styles.length ||
                    oldCls != newCls && (!oldCls || !newCls || oldCls.bgClass != newCls.bgClass || oldCls.textClass != newCls.textClass)
                for (var i = 0; !ischange && i < oldStyles.length; ++i) { ischange = oldStyles[i] != line.styles[i] }
                if (ischange) { changedLines.push(context.line) }
                line.stateAfter = context.save()
                context.nextLine()
            } else {
                if (line.text.length <= cm.options.maxHighlightLength)
                { processLine(cm, line.text, context) }
                line.stateAfter = context.line % 5 == 0 ? context.save() : null
                context.nextLine()
            }
            if (+new Date > end) {
                startWorker(cm, cm.options.workDelay)
                return true
            }
        })
        doc.highlightFrontier = context.line
        doc.modeFrontier = Math.max(doc.modeFrontier, context.line)
        if (changedLines.length) { runInOp(cm, function () {
            for (var i = 0; i < changedLines.length; i++)
            { regLineChange(cm, changedLines[i], "text") }
        }) }
    }

// DISPLAY DRAWING

    var DisplayUpdate = function(cm, viewport, force) {
        var display = cm.display

        this.viewport = viewport
        // Store some values that we'll need later (but don't want to force a relayout for)
        this.visible = visibleLines(display, cm.doc, viewport)
        this.editorIsHidden = !display.wrapper.offsetWidth
        this.wrapperHeight = display.wrapper.clientHeight
        this.wrapperWidth = display.wrapper.clientWidth
        this.oldDisplayWidth = displayWidth(cm)
        this.force = force
        this.dims = getDimensions(cm)
        this.events = []
    };

    DisplayUpdate.prototype.signal = function (emitter, type) {
        if (hasHandler(emitter, type))
        { this.events.push(arguments) }
    };
    DisplayUpdate.prototype.finish = function () {
        var this$1 = this;

        for (var i = 0; i < this.events.length; i++)
        { signal.apply(null, this$1.events[i]) }
    };

    function maybeClipScrollbars(cm) {
        var display = cm.display
        if (!display.scrollbarsClipped && display.scroller.offsetWidth) {
            display.nativeBarWidth = display.scroller.offsetWidth - display.scroller.clientWidth
            display.heightForcer.style.height = scrollGap(cm) + "px"
            display.sizer.style.marginBottom = -display.nativeBarWidth + "px"
            display.sizer.style.borderRightWidth = scrollGap(cm) + "px"
            display.scrollbarsClipped = true
        }
    }

    function selectionSnapshot(cm) {
        if (cm.hasFocus()) { return null }
        var active = activeElt()
        if (!active || !contains(cm.display.lineDiv, active)) { return null }
        var result = {activeElt: active}
        if (window.getSelection) {
            var sel = window.getSelection()
            if (sel.anchorNode && sel.extend && contains(cm.display.lineDiv, sel.anchorNode)) {
                result.anchorNode = sel.anchorNode
                result.anchorOffset = sel.anchorOffset
                result.focusNode = sel.focusNode
                result.focusOffset = sel.focusOffset
            }
        }
        return result
    }

    function restoreSelection(snapshot) {
        if (!snapshot || !snapshot.activeElt || snapshot.activeElt == activeElt()) { return }
        snapshot.activeElt.focus()
        if (snapshot.anchorNode && contains(document.body, snapshot.anchorNode) && contains(document.body, snapshot.focusNode)) {
            var sel = window.getSelection(), range = document.createRange()
            range.setEnd(snapshot.anchorNode, snapshot.anchorOffset)
            range.collapse(false)
            sel.removeAllRanges()
            sel.addRange(range)
            sel.extend(snapshot.focusNode, snapshot.focusOffset)
        }
    }

// Does the actual updating of the line display. Bails out
// (returning false) when there is nothing to be done and forced is
// false.
    function updateDisplayIfNeeded(cm, update) {
        var display = cm.display, doc = cm.doc

        if (update.editorIsHidden) {
            resetView(cm)
            return false
        }

        // Bail out if the visible area is already rendered and nothing changed.
        if (!update.force &&
            update.visible.from >= display.viewFrom && update.visible.to <= display.viewTo &&
            (display.updateLineNumbers == null || display.updateLineNumbers >= display.viewTo) &&
            display.renderedView == display.view && countDirtyView(cm) == 0)
        { return false }

        if (maybeUpdateLineNumberWidth(cm)) {
            resetView(cm)
            update.dims = getDimensions(cm)
        }

        // Compute a suitable new viewport (from & to)
        var end = doc.first + doc.size
        var from = Math.max(update.visible.from - cm.options.viewportMargin, doc.first)
        var to = Math.min(end, update.visible.to + cm.options.viewportMargin)
        if (display.viewFrom < from && from - display.viewFrom < 20) { from = Math.max(doc.first, display.viewFrom) }
        if (display.viewTo > to && display.viewTo - to < 20) { to = Math.min(end, display.viewTo) }
        if (sawCollapsedSpans) {
            from = visualLineNo(cm.doc, from)
            to = visualLineEndNo(cm.doc, to)
        }

        var different = from != display.viewFrom || to != display.viewTo ||
            display.lastWrapHeight != update.wrapperHeight || display.lastWrapWidth != update.wrapperWidth
        adjustView(cm, from, to)

        display.viewOffset = heightAtLine(getLine(cm.doc, display.viewFrom))
        // Position the mover div to align with the current scroll position
        cm.display.mover.style.top = display.viewOffset + "px"

        var toUpdate = countDirtyView(cm)
        if (!different && toUpdate == 0 && !update.force && display.renderedView == display.view &&
            (display.updateLineNumbers == null || display.updateLineNumbers >= display.viewTo))
        { return false }

        // For big changes, we hide the enclosing element during the
        // update, since that speeds up the operations on most browsers.
        var selSnapshot = selectionSnapshot(cm)
        if (toUpdate > 4) { display.lineDiv.style.display = "none" }
        patchDisplay(cm, display.updateLineNumbers, update.dims)
        if (toUpdate > 4) { display.lineDiv.style.display = "" }
        display.renderedView = display.view
        // There might have been a widget with a focused element that got
        // hidden or updated, if so re-focus it.
        restoreSelection(selSnapshot)

        // Prevent selection and cursors from interfering with the scroll
        // width and height.
        removeChildren(display.cursorDiv)
        removeChildren(display.selectionDiv)
        display.gutters.style.height = display.sizer.style.minHeight = 0

        if (different) {
            display.lastWrapHeight = update.wrapperHeight
            display.lastWrapWidth = update.wrapperWidth
            startWorker(cm, 400)
        }

        display.updateLineNumbers = null

        return true
    }

    function postUpdateDisplay(cm, update) {
        var viewport = update.viewport

        for (var first = true;; first = false) {
            if (!first || !cm.options.lineWrapping || update.oldDisplayWidth == displayWidth(cm)) {
                // Clip forced viewport to actual scrollable area.
                if (viewport && viewport.top != null)
                { viewport = {top: Math.min(cm.doc.height + paddingVert(cm.display) - displayHeight(cm), viewport.top)} }
                // Updated line heights might result in the drawn area not
                // actually covering the viewport. Keep looping until it does.
                update.visible = visibleLines(cm.display, cm.doc, viewport)
                if (update.visible.from >= cm.display.viewFrom && update.visible.to <= cm.display.viewTo)
                { break }
            }
            if (!updateDisplayIfNeeded(cm, update)) { break }
            updateHeightsInViewport(cm)
            var barMeasure = measureForScrollbars(cm)
            updateSelection(cm)
            updateScrollbars(cm, barMeasure)
            setDocumentHeight(cm, barMeasure)
            update.force = false
        }

        update.signal(cm, "update", cm)
        if (cm.display.viewFrom != cm.display.reportedViewFrom || cm.display.viewTo != cm.display.reportedViewTo) {
            update.signal(cm, "viewportChange", cm, cm.display.viewFrom, cm.display.viewTo)
            cm.display.reportedViewFrom = cm.display.viewFrom; cm.display.reportedViewTo = cm.display.viewTo
        }
    }

    function updateDisplaySimple(cm, viewport) {
        var update = new DisplayUpdate(cm, viewport)
        if (updateDisplayIfNeeded(cm, update)) {
            updateHeightsInViewport(cm)
            postUpdateDisplay(cm, update)
            var barMeasure = measureForScrollbars(cm)
            updateSelection(cm)
            updateScrollbars(cm, barMeasure)
            setDocumentHeight(cm, barMeasure)
            update.finish()
        }
    }

// Sync the actual display DOM structure with display.view, removing
// nodes for lines that are no longer in view, and creating the ones
// that are not there yet, and updating the ones that are out of
// date.
    function patchDisplay(cm, updateNumbersFrom, dims) {
        var display = cm.display, lineNumbers = cm.options.lineNumbers
        var container = display.lineDiv, cur = container.firstChild

        function rm(node) {
            var next = node.nextSibling
            // Works around a throw-scroll bug in OS X Webkit
            if (webkit && mac && cm.display.currentWheelTarget == node)
            { node.style.display = "none" }
            else
            { node.parentNode.removeChild(node) }
            return next
        }

        var view = display.view, lineN = display.viewFrom
        // Loop over the elements in the view, syncing cur (the DOM nodes
        // in display.lineDiv) with the view as we go.
        for (var i = 0; i < view.length; i++) {
            var lineView = view[i]
            if (lineView.hidden) {
            } else if (!lineView.node || lineView.node.parentNode != container) { // Not drawn yet
                var node = buildLineElement(cm, lineView, lineN, dims)
                container.insertBefore(node, cur)
            } else { // Already drawn
                while (cur != lineView.node) { cur = rm(cur) }
                var updateNumber = lineNumbers && updateNumbersFrom != null &&
                    updateNumbersFrom <= lineN && lineView.lineNumber
                if (lineView.changes) {
                    if (indexOf(lineView.changes, "gutter") > -1) { updateNumber = false }
                    updateLineForChanges(cm, lineView, lineN, dims)
                }
                if (updateNumber) {
                    removeChildren(lineView.lineNumber)
                    lineView.lineNumber.appendChild(document.createTextNode(lineNumberFor(cm.options, lineN)))
                }
                cur = lineView.node.nextSibling
            }
            lineN += lineView.size
        }
        while (cur) { cur = rm(cur) }
    }

    function updateGutterSpace(cm) {
        var width = cm.display.gutters.offsetWidth
        cm.display.sizer.style.marginLeft = width + "px"
    }

    function setDocumentHeight(cm, measure) {
        cm.display.sizer.style.minHeight = measure.docHeight + "px"
        cm.display.heightForcer.style.top = measure.docHeight + "px"
        cm.display.gutters.style.height = (measure.docHeight + cm.display.barHeight + scrollGap(cm)) + "px"
    }

// Rebuild the gutter elements, ensure the margin to the left of the
// code matches their width.
    function updateGutters(cm) {
        var gutters = cm.display.gutters, specs = cm.options.gutters
        removeChildren(gutters)
        var i = 0
        for (; i < specs.length; ++i) {
            var gutterClass = specs[i]
            var gElt = gutters.appendChild(elt("div", null, "CodeMirror-gutter " + gutterClass))
            if (gutterClass == "CodeMirror-linenumbers") {
                cm.display.lineGutter = gElt
                gElt.style.width = (cm.display.lineNumWidth || 1) + "px"
            }
        }
        gutters.style.display = i ? "" : "none"
        updateGutterSpace(cm)
    }

// Make sure the gutters options contains the element
// "CodeMirror-linenumbers" when the lineNumbers option is true.
    function setGuttersForLineNumbers(options) {
        var found = indexOf(options.gutters, "CodeMirror-linenumbers")
        if (found == -1 && options.lineNumbers) {
            options.gutters = options.gutters.concat(["CodeMirror-linenumbers"])
        } else if (found > -1 && !options.lineNumbers) {
            options.gutters = options.gutters.slice(0)
            options.gutters.splice(found, 1)
        }
    }

    var wheelSamples = 0;
    var wheelPixelsPerUnit = null;
// Fill in a browser-detected starting value on browsers where we
// know one. These don't have to be accurate -- the result of them
// being wrong would just be a slight flicker on the first wheel
// scroll (if it is large enough).
    if (ie) { wheelPixelsPerUnit = -.53 }
    else if (gecko) { wheelPixelsPerUnit = 15 }
    else if (chrome) { wheelPixelsPerUnit = -.7 }
    else if (safari) { wheelPixelsPerUnit = -1/3 }

    function wheelEventDelta(e) {
        var dx = e.wheelDeltaX, dy = e.wheelDeltaY
        if (dx == null && e.detail && e.axis == e.HORIZONTAL_AXIS) { dx = e.detail }
        if (dy == null && e.detail && e.axis == e.VERTICAL_AXIS) { dy = e.detail }
        else if (dy == null) { dy = e.wheelDelta }
        return {x: dx, y: dy}
    }
    function wheelEventPixels(e) {
        var delta = wheelEventDelta(e)
        delta.x *= wheelPixelsPerUnit
        delta.y *= wheelPixelsPerUnit
        return delta
    }

    function onScrollWheel(cm, e) {
        var delta = wheelEventDelta(e), dx = delta.x, dy = delta.y

        var display = cm.display, scroll = display.scroller
        // Quit if there's nothing to scroll here
        var canScrollX = scroll.scrollWidth > scroll.clientWidth
        var canScrollY = scroll.scrollHeight > scroll.clientHeight
        if (!(dx && canScrollX || dy && canScrollY)) { return }

        // Webkit browsers on OS X abort momentum scrolls when the target
        // of the scroll event is removed from the scrollable element.
        // This hack (see related code in patchDisplay) makes sure the
        // element is kept around.
        if (dy && mac && webkit) {
            outer: for (var cur = e.target, view = display.view; cur != scroll; cur = cur.parentNode) {
                for (var i = 0; i < view.length; i++) {
                    if (view[i].node == cur) {
                        cm.display.currentWheelTarget = cur
                        break outer
                    }
                }
            }
        }

        // On some browsers, horizontal scrolling will cause redraws to
        // happen before the gutter has been realigned, causing it to
        // wriggle around in a most unseemly way. When we have an
        // estimated pixels/delta value, we just handle horizontal
        // scrolling entirely here. It'll be slightly off from native, but
        // better than glitching out.
        if (dx && !gecko && !presto && wheelPixelsPerUnit != null) {
            if (dy && canScrollY)
            { updateScrollTop(cm, Math.max(0, scroll.scrollTop + dy * wheelPixelsPerUnit)) }
            setScrollLeft(cm, Math.max(0, scroll.scrollLeft + dx * wheelPixelsPerUnit))
            // Only prevent default scrolling if vertical scrolling is
            // actually possible. Otherwise, it causes vertical scroll
            // jitter on OSX trackpads when deltaX is small and deltaY
            // is large (issue #3579)
            if (!dy || (dy && canScrollY))
            { e_preventDefault(e) }
            display.wheelStartX = null // Abort measurement, if in progress
            return
        }

        // 'Project' the visible viewport to cover the area that is being
        // scrolled into view (if we know enough to estimate it).
        if (dy && wheelPixelsPerUnit != null) {
            var pixels = dy * wheelPixelsPerUnit
            var top = cm.doc.scrollTop, bot = top + display.wrapper.clientHeight
            if (pixels < 0) { top = Math.max(0, top + pixels - 50) }
            else { bot = Math.min(cm.doc.height, bot + pixels + 50) }
            updateDisplaySimple(cm, {top: top, bottom: bot})
        }

        if (wheelSamples < 20) {
            if (display.wheelStartX == null) {
                display.wheelStartX = scroll.scrollLeft; display.wheelStartY = scroll.scrollTop
                display.wheelDX = dx; display.wheelDY = dy
                setTimeout(function () {
                    if (display.wheelStartX == null) { return }
                    var movedX = scroll.scrollLeft - display.wheelStartX
                    var movedY = scroll.scrollTop - display.wheelStartY
                    var sample = (movedY && display.wheelDY && movedY / display.wheelDY) ||
                        (movedX && display.wheelDX && movedX / display.wheelDX)
                    display.wheelStartX = display.wheelStartY = null
                    if (!sample) { return }
                    wheelPixelsPerUnit = (wheelPixelsPerUnit * wheelSamples + sample) / (wheelSamples + 1)
                    ++wheelSamples
                }, 200)
            } else {
                display.wheelDX += dx; display.wheelDY += dy
            }
        }
    }

// Selection objects are immutable. A new one is created every time
// the selection changes. A selection is one or more non-overlapping
// (and non-touching) ranges, sorted, and an integer that indicates
// which one is the primary selection (the one that's scrolled into
// view, that getCursor returns, etc).
    var Selection = function(ranges, primIndex) {
        this.ranges = ranges
        this.primIndex = primIndex
    };

    Selection.prototype.primary = function () { return this.ranges[this.primIndex] };

    Selection.prototype.equals = function (other) {
        var this$1 = this;

        if (other == this) { return true }
        if (other.primIndex != this.primIndex || other.ranges.length != this.ranges.length) { return false }
        for (var i = 0; i < this.ranges.length; i++) {
            var here = this$1.ranges[i], there = other.ranges[i]
            if (!equalCursorPos(here.anchor, there.anchor) || !equalCursorPos(here.head, there.head)) { return false }
        }
        return true
    };

    Selection.prototype.deepCopy = function () {
        var this$1 = this;

        var out = []
        for (var i = 0; i < this.ranges.length; i++)
        { out[i] = new Range(copyPos(this$1.ranges[i].anchor), copyPos(this$1.ranges[i].head)) }
        return new Selection(out, this.primIndex)
    };

    Selection.prototype.somethingSelected = function () {
        var this$1 = this;

        for (var i = 0; i < this.ranges.length; i++)
        { if (!this$1.ranges[i].empty()) { return true } }
        return false
    };

    Selection.prototype.contains = function (pos, end) {
        var this$1 = this;

        if (!end) { end = pos }
        for (var i = 0; i < this.ranges.length; i++) {
            var range = this$1.ranges[i]
            if (cmp(end, range.from()) >= 0 && cmp(pos, range.to()) <= 0)
            { return i }
        }
        return -1
    };

    var Range = function(anchor, head) {
        this.anchor = anchor; this.head = head
    };

    Range.prototype.from = function () { return minPos(this.anchor, this.head) };
    Range.prototype.to = function () { return maxPos(this.anchor, this.head) };
    Range.prototype.empty = function () { return this.head.line == this.anchor.line && this.head.ch == this.anchor.ch };

// Take an unsorted, potentially overlapping set of ranges, and
// build a selection out of it. 'Consumes' ranges array (modifying
// it).
    function normalizeSelection(ranges, primIndex) {
        var prim = ranges[primIndex]
        ranges.sort(function (a, b) { return cmp(a.from(), b.from()); })
        primIndex = indexOf(ranges, prim)
        for (var i = 1; i < ranges.length; i++) {
            var cur = ranges[i], prev = ranges[i - 1]
            if (cmp(prev.to(), cur.from()) >= 0) {
                var from = minPos(prev.from(), cur.from()), to = maxPos(prev.to(), cur.to())
                var inv = prev.empty() ? cur.from() == cur.head : prev.from() == prev.head
                if (i <= primIndex) { --primIndex }
                ranges.splice(--i, 2, new Range(inv ? to : from, inv ? from : to))
            }
        }
        return new Selection(ranges, primIndex)
    }

    function simpleSelection(anchor, head) {
        return new Selection([new Range(anchor, head || anchor)], 0)
    }

// Compute the position of the end of a change (its 'to' property
// refers to the pre-change end).
    function changeEnd(change) {
        if (!change.text) { return change.to }
        return Pos(change.from.line + change.text.length - 1,
            lst(change.text).length + (change.text.length == 1 ? change.from.ch : 0))
    }

// Adjust a position to refer to the post-change position of the
// same text, or the end of the change if the change covers it.
    function adjustForChange(pos, change) {
        if (cmp(pos, change.from) < 0) { return pos }
        if (cmp(pos, change.to) <= 0) { return changeEnd(change) }

        var line = pos.line + change.text.length - (change.to.line - change.from.line) - 1, ch = pos.ch
        if (pos.line == change.to.line) { ch += changeEnd(change).ch - change.to.ch }
        return Pos(line, ch)
    }

    function computeSelAfterChange(doc, change) {
        var out = []
        for (var i = 0; i < doc.sel.ranges.length; i++) {
            var range = doc.sel.ranges[i]
            out.push(new Range(adjustForChange(range.anchor, change),
                adjustForChange(range.head, change)))
        }
        return normalizeSelection(out, doc.sel.primIndex)
    }

    function offsetPos(pos, old, nw) {
        if (pos.line == old.line)
        { return Pos(nw.line, pos.ch - old.ch + nw.ch) }
        else
        { return Pos(nw.line + (pos.line - old.line), pos.ch) }
    }

// Used by replaceSelections to allow moving the selection to the
// start or around the replaced test. Hint may be "start" or "around".
    function computeReplacedSel(doc, changes, hint) {
        var out = []
        var oldPrev = Pos(doc.first, 0), newPrev = oldPrev
        for (var i = 0; i < changes.length; i++) {
            var change = changes[i]
            var from = offsetPos(change.from, oldPrev, newPrev)
            var to = offsetPos(changeEnd(change), oldPrev, newPrev)
            oldPrev = change.to
            newPrev = to
            if (hint == "around") {
                var range = doc.sel.ranges[i], inv = cmp(range.head, range.anchor) < 0
                out[i] = new Range(inv ? to : from, inv ? from : to)
            } else {
                out[i] = new Range(from, from)
            }
        }
        return new Selection(out, doc.sel.primIndex)
    }

// Used to get the editor into a consistent state again when options change.

    function loadMode(cm) {
        cm.doc.mode = getMode(cm.options, cm.doc.modeOption)
        resetModeState(cm)
    }

    function resetModeState(cm) {
        cm.doc.iter(function (line) {
            if (line.stateAfter) { line.stateAfter = null }
            if (line.styles) { line.styles = null }
        })
        cm.doc.modeFrontier = cm.doc.highlightFrontier = cm.doc.first
        startWorker(cm, 100)
        cm.state.modeGen++
        if (cm.curOp) { regChange(cm) }
    }

// DOCUMENT DATA STRUCTURE

// By default, updates that start and end at the beginning of a line
// are treated specially, in order to make the association of line
// widgets and marker elements with the text behave more intuitive.
    function isWholeLineUpdate(doc, change) {
        return change.from.ch == 0 && change.to.ch == 0 && lst(change.text) == "" &&
            (!doc.cm || doc.cm.options.wholeLineUpdateBefore)
    }

// Perform a change on the document data structure.
    function updateDoc(doc, change, markedSpans, estimateHeight) {
        function spansFor(n) {return markedSpans ? markedSpans[n] : null}
        function update(line, text, spans) {
            updateLine(line, text, spans, estimateHeight)
            signalLater(line, "change", line, change)
        }
        function linesFor(start, end) {
            var result = []
            for (var i = start; i < end; ++i)
            { result.push(new Line(text[i], spansFor(i), estimateHeight)) }
            return result
        }

        var from = change.from, to = change.to, text = change.text
        var firstLine = getLine(doc, from.line), lastLine = getLine(doc, to.line)
        var lastText = lst(text), lastSpans = spansFor(text.length - 1), nlines = to.line - from.line

        // Adjust the line structure
        if (change.full) {
            doc.insert(0, linesFor(0, text.length))
            doc.remove(text.length, doc.size - text.length)
        } else if (isWholeLineUpdate(doc, change)) {
            // This is a whole-line replace. Treated specially to make
            // sure line objects move the way they are supposed to.
            var added = linesFor(0, text.length - 1)
            update(lastLine, lastLine.text, lastSpans)
            if (nlines) { doc.remove(from.line, nlines) }
            if (added.length) { doc.insert(from.line, added) }
        } else if (firstLine == lastLine) {
            if (text.length == 1) {
                update(firstLine, firstLine.text.slice(0, from.ch) + lastText + firstLine.text.slice(to.ch), lastSpans)
            } else {
                var added$1 = linesFor(1, text.length - 1)
                added$1.push(new Line(lastText + firstLine.text.slice(to.ch), lastSpans, estimateHeight))
                update(firstLine, firstLine.text.slice(0, from.ch) + text[0], spansFor(0))
                doc.insert(from.line + 1, added$1)
            }
        } else if (text.length == 1) {
            update(firstLine, firstLine.text.slice(0, from.ch) + text[0] + lastLine.text.slice(to.ch), spansFor(0))
            doc.remove(from.line + 1, nlines)
        } else {
            update(firstLine, firstLine.text.slice(0, from.ch) + text[0], spansFor(0))
            update(lastLine, lastText + lastLine.text.slice(to.ch), lastSpans)
            var added$2 = linesFor(1, text.length - 1)
            if (nlines > 1) { doc.remove(from.line + 1, nlines - 1) }
            doc.insert(from.line + 1, added$2)
        }

        signalLater(doc, "change", doc, change)
    }

// Call f for all linked documents.
    function linkedDocs(doc, f, sharedHistOnly) {
        function propagate(doc, skip, sharedHist) {
            if (doc.linked) { for (var i = 0; i < doc.linked.length; ++i) {
                var rel = doc.linked[i]
                if (rel.doc == skip) { continue }
                var shared = sharedHist && rel.sharedHist
                if (sharedHistOnly && !shared) { continue }
                f(rel.doc, shared)
                propagate(rel.doc, doc, shared)
            } }
        }
        propagate(doc, null, true)
    }

// Attach a document to an editor.
    function attachDoc(cm, doc) {
        if (doc.cm) { throw new Error("This document is already in use.") }
        cm.doc = doc
        doc.cm = cm
        estimateLineHeights(cm)
        loadMode(cm)
        setDirectionClass(cm)
        if (!cm.options.lineWrapping) { findMaxLine(cm) }
        cm.options.mode = doc.modeOption
        regChange(cm)
    }

    function setDirectionClass(cm) {
        ;(cm.doc.direction == "rtl" ? addClass : rmClass)(cm.display.lineDiv, "CodeMirror-rtl")
    }

    function directionChanged(cm) {
        runInOp(cm, function () {
            setDirectionClass(cm)
            regChange(cm)
        })
    }

    function History(startGen) {
        // Arrays of change events and selections. Doing something adds an
        // event to done and clears undo. Undoing moves events from done
        // to undone, redoing moves them in the other direction.
        this.done = []; this.undone = []
        this.undoDepth = Infinity
        // Used to track when changes can be merged into a single undo
        // event
        this.lastModTime = this.lastSelTime = 0
        this.lastOp = this.lastSelOp = null
        this.lastOrigin = this.lastSelOrigin = null
        // Used by the isClean() method
        this.generation = this.maxGeneration = startGen || 1
    }

// Create a history change event from an updateDoc-style change
// object.
    function historyChangeFromChange(doc, change) {
        var histChange = {from: copyPos(change.from), to: changeEnd(change), text: getBetween(doc, change.from, change.to)}
        attachLocalSpans(doc, histChange, change.from.line, change.to.line + 1)
        linkedDocs(doc, function (doc) { return attachLocalSpans(doc, histChange, change.from.line, change.to.line + 1); }, true)
        return histChange
    }

// Pop all selection events off the end of a history array. Stop at
// a change event.
    function clearSelectionEvents(array) {
        while (array.length) {
            var last = lst(array)
            if (last.ranges) { array.pop() }
            else { break }
        }
    }

// Find the top change event in the history. Pop off selection
// events that are in the way.
    function lastChangeEvent(hist, force) {
        if (force) {
            clearSelectionEvents(hist.done)
            return lst(hist.done)
        } else if (hist.done.length && !lst(hist.done).ranges) {
            return lst(hist.done)
        } else if (hist.done.length > 1 && !hist.done[hist.done.length - 2].ranges) {
            hist.done.pop()
            return lst(hist.done)
        }
    }

// Register a change in the history. Merges changes that are within
// a single operation, or are close together with an origin that
// allows merging (starting with "+") into a single event.
    function addChangeToHistory(doc, change, selAfter, opId) {
        var hist = doc.history
        hist.undone.length = 0
        var time = +new Date, cur
        var last

        if ((hist.lastOp == opId ||
                hist.lastOrigin == change.origin && change.origin &&
                ((change.origin.charAt(0) == "+" && doc.cm && hist.lastModTime > time - doc.cm.options.historyEventDelay) ||
                    change.origin.charAt(0) == "*")) &&
            (cur = lastChangeEvent(hist, hist.lastOp == opId))) {
            // Merge this change into the last event
            last = lst(cur.changes)
            if (cmp(change.from, change.to) == 0 && cmp(change.from, last.to) == 0) {
                // Optimized case for simple insertion -- don't want to add
                // new changesets for every character typed
                last.to = changeEnd(change)
            } else {
                // Add new sub-event
                cur.changes.push(historyChangeFromChange(doc, change))
            }
        } else {
            // Can not be merged, start a new event.
            var before = lst(hist.done)
            if (!before || !before.ranges)
            { pushSelectionToHistory(doc.sel, hist.done) }
            cur = {changes: [historyChangeFromChange(doc, change)],
                generation: hist.generation}
            hist.done.push(cur)
            while (hist.done.length > hist.undoDepth) {
                hist.done.shift()
                if (!hist.done[0].ranges) { hist.done.shift() }
            }
        }
        hist.done.push(selAfter)
        hist.generation = ++hist.maxGeneration
        hist.lastModTime = hist.lastSelTime = time
        hist.lastOp = hist.lastSelOp = opId
        hist.lastOrigin = hist.lastSelOrigin = change.origin

        if (!last) { signal(doc, "historyAdded") }
    }

    function selectionEventCanBeMerged(doc, origin, prev, sel) {
        var ch = origin.charAt(0)
        return ch == "*" ||
            ch == "+" &&
            prev.ranges.length == sel.ranges.length &&
            prev.somethingSelected() == sel.somethingSelected() &&
            new Date - doc.history.lastSelTime <= (doc.cm ? doc.cm.options.historyEventDelay : 500)
    }

// Called whenever the selection changes, sets the new selection as
// the pending selection in the history, and pushes the old pending
// selection into the 'done' array when it was significantly
// different (in number of selected ranges, emptiness, or time).
    function addSelectionToHistory(doc, sel, opId, options) {
        var hist = doc.history, origin = options && options.origin

        // A new event is started when the previous origin does not match
        // the current, or the origins don't allow matching. Origins
        // starting with * are always merged, those starting with + are
        // merged when similar and close together in time.
        if (opId == hist.lastSelOp ||
            (origin && hist.lastSelOrigin == origin &&
                (hist.lastModTime == hist.lastSelTime && hist.lastOrigin == origin ||
                    selectionEventCanBeMerged(doc, origin, lst(hist.done), sel))))
        { hist.done[hist.done.length - 1] = sel }
        else
        { pushSelectionToHistory(sel, hist.done) }

        hist.lastSelTime = +new Date
        hist.lastSelOrigin = origin
        hist.lastSelOp = opId
        if (options && options.clearRedo !== false)
        { clearSelectionEvents(hist.undone) }
    }

    function pushSelectionToHistory(sel, dest) {
        var top = lst(dest)
        if (!(top && top.ranges && top.equals(sel)))
        { dest.push(sel) }
    }

// Used to store marked span information in the history.
    function attachLocalSpans(doc, change, from, to) {
        var existing = change["spans_" + doc.id], n = 0
        doc.iter(Math.max(doc.first, from), Math.min(doc.first + doc.size, to), function (line) {
            if (line.markedSpans)
            { (existing || (existing = change["spans_" + doc.id] = {}))[n] = line.markedSpans }
            ++n
        })
    }

// When un/re-doing restores text containing marked spans, those
// that have been explicitly cleared should not be restored.
    function removeClearedSpans(spans) {
        if (!spans) { return null }
        var out
        for (var i = 0; i < spans.length; ++i) {
            if (spans[i].marker.explicitlyCleared) { if (!out) { out = spans.slice(0, i) } }
            else if (out) { out.push(spans[i]) }
        }
        return !out ? spans : out.length ? out : null
    }

// Retrieve and filter the old marked spans stored in a change event.
    function getOldSpans(doc, change) {
        var found = change["spans_" + doc.id]
        if (!found) { return null }
        var nw = []
        for (var i = 0; i < change.text.length; ++i)
        { nw.push(removeClearedSpans(found[i])) }
        return nw
    }

// Used for un/re-doing changes from the history. Combines the
// result of computing the existing spans with the set of spans that
// existed in the history (so that deleting around a span and then
// undoing brings back the span).
    function mergeOldSpans(doc, change) {
        var old = getOldSpans(doc, change)
        var stretched = stretchSpansOverChange(doc, change)
        if (!old) { return stretched }
        if (!stretched) { return old }

        for (var i = 0; i < old.length; ++i) {
            var oldCur = old[i], stretchCur = stretched[i]
            if (oldCur && stretchCur) {
                spans: for (var j = 0; j < stretchCur.length; ++j) {
                    var span = stretchCur[j]
                    for (var k = 0; k < oldCur.length; ++k)
                    { if (oldCur[k].marker == span.marker) { continue spans } }
                    oldCur.push(span)
                }
            } else if (stretchCur) {
                old[i] = stretchCur
            }
        }
        return old
    }

// Used both to provide a JSON-safe object in .getHistory, and, when
// detaching a document, to split the history in two
    function copyHistoryArray(events, newGroup, instantiateSel) {
        var copy = []
        for (var i = 0; i < events.length; ++i) {
            var event = events[i]
            if (event.ranges) {
                copy.push(instantiateSel ? Selection.prototype.deepCopy.call(event) : event)
                continue
            }
            var changes = event.changes, newChanges = []
            copy.push({changes: newChanges})
            for (var j = 0; j < changes.length; ++j) {
                var change = changes[j], m = (void 0)
                newChanges.push({from: change.from, to: change.to, text: change.text})
                if (newGroup) { for (var prop in change) { if (m = prop.match(/^spans_(\d+)$/)) {
                    if (indexOf(newGroup, Number(m[1])) > -1) {
                        lst(newChanges)[prop] = change[prop]
                        delete change[prop]
                    }
                } } }
            }
        }
        return copy
    }

// The 'scroll' parameter given to many of these indicated whether
// the new cursor position should be scrolled into view after
// modifying the selection.

// If shift is held or the extend flag is set, extends a range to
// include a given position (and optionally a second position).
// Otherwise, simply returns the range between the given positions.
// Used for cursor motion and such.
    function extendRange(range, head, other, extend) {
        if (extend) {
            var anchor = range.anchor
            if (other) {
                var posBefore = cmp(head, anchor) < 0
                if (posBefore != (cmp(other, anchor) < 0)) {
                    anchor = head
                    head = other
                } else if (posBefore != (cmp(head, other) < 0)) {
                    head = other
                }
            }
            return new Range(anchor, head)
        } else {
            return new Range(other || head, head)
        }
    }

// Extend the primary selection range, discard the rest.
    function extendSelection(doc, head, other, options, extend) {
        if (extend == null) { extend = doc.cm && (doc.cm.display.shift || doc.extend) }
        setSelection(doc, new Selection([extendRange(doc.sel.primary(), head, other, extend)], 0), options)
    }

// Extend all selections (pos is an array of selections with length
// equal the number of selections)
    function extendSelections(doc, heads, options) {
        var out = []
        var extend = doc.cm && (doc.cm.display.shift || doc.extend)
        for (var i = 0; i < doc.sel.ranges.length; i++)
        { out[i] = extendRange(doc.sel.ranges[i], heads[i], null, extend) }
        var newSel = normalizeSelection(out, doc.sel.primIndex)
        setSelection(doc, newSel, options)
    }

// Updates a single range in the selection.
    function replaceOneSelection(doc, i, range, options) {
        var ranges = doc.sel.ranges.slice(0)
        ranges[i] = range
        setSelection(doc, normalizeSelection(ranges, doc.sel.primIndex), options)
    }

// Reset the selection to a single range.
    function setSimpleSelection(doc, anchor, head, options) {
        setSelection(doc, simpleSelection(anchor, head), options)
    }

// Give beforeSelectionChange handlers a change to influence a
// selection update.
    function filterSelectionChange(doc, sel, options) {
        var obj = {
            ranges: sel.ranges,
            update: function(ranges) {
                var this$1 = this;

                this.ranges = []
                for (var i = 0; i < ranges.length; i++)
                { this$1.ranges[i] = new Range(clipPos(doc, ranges[i].anchor),
                    clipPos(doc, ranges[i].head)) }
            },
            origin: options && options.origin
        }
        signal(doc, "beforeSelectionChange", doc, obj)
        if (doc.cm) { signal(doc.cm, "beforeSelectionChange", doc.cm, obj) }
        if (obj.ranges != sel.ranges) { return normalizeSelection(obj.ranges, obj.ranges.length - 1) }
        else { return sel }
    }

    function setSelectionReplaceHistory(doc, sel, options) {
        var done = doc.history.done, last = lst(done)
        if (last && last.ranges) {
            done[done.length - 1] = sel
            setSelectionNoUndo(doc, sel, options)
        } else {
            setSelection(doc, sel, options)
        }
    }

// Set a new selection.
    function setSelection(doc, sel, options) {
        setSelectionNoUndo(doc, sel, options)
        addSelectionToHistory(doc, doc.sel, doc.cm ? doc.cm.curOp.id : NaN, options)
    }

    function setSelectionNoUndo(doc, sel, options) {
        if (hasHandler(doc, "beforeSelectionChange") || doc.cm && hasHandler(doc.cm, "beforeSelectionChange"))
        { sel = filterSelectionChange(doc, sel, options) }

        var bias = options && options.bias ||
            (cmp(sel.primary().head, doc.sel.primary().head) < 0 ? -1 : 1)
        setSelectionInner(doc, skipAtomicInSelection(doc, sel, bias, true))

        if (!(options && options.scroll === false) && doc.cm)
        { ensureCursorVisible(doc.cm) }
    }

    function setSelectionInner(doc, sel) {
        if (sel.equals(doc.sel)) { return }

        doc.sel = sel

        if (doc.cm) {
            doc.cm.curOp.updateInput = doc.cm.curOp.selectionChanged = true
            signalCursorActivity(doc.cm)
        }
        signalLater(doc, "cursorActivity", doc)
    }

// Verify that the selection does not partially select any atomic
// marked ranges.
    function reCheckSelection(doc) {
        setSelectionInner(doc, skipAtomicInSelection(doc, doc.sel, null, false))
    }

// Return a selection that does not partially select any atomic
// ranges.
    function skipAtomicInSelection(doc, sel, bias, mayClear) {
        var out
        for (var i = 0; i < sel.ranges.length; i++) {
            var range = sel.ranges[i]
            var old = sel.ranges.length == doc.sel.ranges.length && doc.sel.ranges[i]
            var newAnchor = skipAtomic(doc, range.anchor, old && old.anchor, bias, mayClear)
            var newHead = skipAtomic(doc, range.head, old && old.head, bias, mayClear)
            if (out || newAnchor != range.anchor || newHead != range.head) {
                if (!out) { out = sel.ranges.slice(0, i) }
                out[i] = new Range(newAnchor, newHead)
            }
        }
        return out ? normalizeSelection(out, sel.primIndex) : sel
    }

    function skipAtomicInner(doc, pos, oldPos, dir, mayClear) {
        var line = getLine(doc, pos.line)
        if (line.markedSpans) { for (var i = 0; i < line.markedSpans.length; ++i) {
            var sp = line.markedSpans[i], m = sp.marker
            if ((sp.from == null || (m.inclusiveLeft ? sp.from <= pos.ch : sp.from < pos.ch)) &&
                (sp.to == null || (m.inclusiveRight ? sp.to >= pos.ch : sp.to > pos.ch))) {
                if (mayClear) {
                    signal(m, "beforeCursorEnter")
                    if (m.explicitlyCleared) {
                        if (!line.markedSpans) { break }
                        else {--i; continue}
                    }
                }
                if (!m.atomic) { continue }

                if (oldPos) {
                    var near = m.find(dir < 0 ? 1 : -1), diff = (void 0)
                    if (dir < 0 ? m.inclusiveRight : m.inclusiveLeft)
                    { near = movePos(doc, near, -dir, near && near.line == pos.line ? line : null) }
                    if (near && near.line == pos.line && (diff = cmp(near, oldPos)) && (dir < 0 ? diff < 0 : diff > 0))
                    { return skipAtomicInner(doc, near, pos, dir, mayClear) }
                }

                var far = m.find(dir < 0 ? -1 : 1)
                if (dir < 0 ? m.inclusiveLeft : m.inclusiveRight)
                { far = movePos(doc, far, dir, far.line == pos.line ? line : null) }
                return far ? skipAtomicInner(doc, far, pos, dir, mayClear) : null
            }
        } }
        return pos
    }

// Ensure a given position is not inside an atomic range.
    function skipAtomic(doc, pos, oldPos, bias, mayClear) {
        var dir = bias || 1
        var found = skipAtomicInner(doc, pos, oldPos, dir, mayClear) ||
            (!mayClear && skipAtomicInner(doc, pos, oldPos, dir, true)) ||
            skipAtomicInner(doc, pos, oldPos, -dir, mayClear) ||
            (!mayClear && skipAtomicInner(doc, pos, oldPos, -dir, true))
        if (!found) {
            doc.cantEdit = true
            return Pos(doc.first, 0)
        }
        return found
    }

    function movePos(doc, pos, dir, line) {
        if (dir < 0 && pos.ch == 0) {
            if (pos.line > doc.first) { return clipPos(doc, Pos(pos.line - 1)) }
            else { return null }
        } else if (dir > 0 && pos.ch == (line || getLine(doc, pos.line)).text.length) {
            if (pos.line < doc.first + doc.size - 1) { return Pos(pos.line + 1, 0) }
            else { return null }
        } else {
            return new Pos(pos.line, pos.ch + dir)
        }
    }

    function selectAll(cm) {
        cm.setSelection(Pos(cm.firstLine(), 0), Pos(cm.lastLine()), sel_dontScroll)
    }

// UPDATING

// Allow "beforeChange" event handlers to influence a change
    function filterChange(doc, change, update) {
        var obj = {
            canceled: false,
            from: change.from,
            to: change.to,
            text: change.text,
            origin: change.origin,
            cancel: function () { return obj.canceled = true; }
        }
        if (update) { obj.update = function (from, to, text, origin) {
            if (from) { obj.from = clipPos(doc, from) }
            if (to) { obj.to = clipPos(doc, to) }
            if (text) { obj.text = text }
            if (origin !== undefined) { obj.origin = origin }
        } }
        signal(doc, "beforeChange", doc, obj)
        if (doc.cm) { signal(doc.cm, "beforeChange", doc.cm, obj) }

        if (obj.canceled) { return null }
        return {from: obj.from, to: obj.to, text: obj.text, origin: obj.origin}
    }

// Apply a change to a document, and add it to the document's
// history, and propagating it to all linked documents.
    function makeChange(doc, change, ignoreReadOnly) {
        if (doc.cm) {
            if (!doc.cm.curOp) { return operation(doc.cm, makeChange)(doc, change, ignoreReadOnly) }
            if (doc.cm.state.suppressEdits) { return }
        }

        if (hasHandler(doc, "beforeChange") || doc.cm && hasHandler(doc.cm, "beforeChange")) {
            change = filterChange(doc, change, true)
            if (!change) { return }
        }

        // Possibly split or suppress the update based on the presence
        // of read-only spans in its range.
        var split = sawReadOnlySpans && !ignoreReadOnly && removeReadOnlyRanges(doc, change.from, change.to)
        if (split) {
            for (var i = split.length - 1; i >= 0; --i)
            { makeChangeInner(doc, {from: split[i].from, to: split[i].to, text: i ? [""] : change.text, origin: change.origin}) }
        } else {
            makeChangeInner(doc, change)
        }
    }

    function makeChangeInner(doc, change) {
        if (change.text.length == 1 && change.text[0] == "" && cmp(change.from, change.to) == 0) { return }
        var selAfter = computeSelAfterChange(doc, change)
        addChangeToHistory(doc, change, selAfter, doc.cm ? doc.cm.curOp.id : NaN)

        makeChangeSingleDoc(doc, change, selAfter, stretchSpansOverChange(doc, change))
        var rebased = []

        linkedDocs(doc, function (doc, sharedHist) {
            if (!sharedHist && indexOf(rebased, doc.history) == -1) {
                rebaseHist(doc.history, change)
                rebased.push(doc.history)
            }
            makeChangeSingleDoc(doc, change, null, stretchSpansOverChange(doc, change))
        })
    }

// Revert a change stored in a document's history.
    function makeChangeFromHistory(doc, type, allowSelectionOnly) {
        if (doc.cm && doc.cm.state.suppressEdits && !allowSelectionOnly) { return }

        var hist = doc.history, event, selAfter = doc.sel
        var source = type == "undo" ? hist.done : hist.undone, dest = type == "undo" ? hist.undone : hist.done

        // Verify that there is a useable event (so that ctrl-z won't
        // needlessly clear selection events)
        var i = 0
        for (; i < source.length; i++) {
            event = source[i]
            if (allowSelectionOnly ? event.ranges && !event.equals(doc.sel) : !event.ranges)
            { break }
        }
        if (i == source.length) { return }
        hist.lastOrigin = hist.lastSelOrigin = null

        for (;;) {
            event = source.pop()
            if (event.ranges) {
                pushSelectionToHistory(event, dest)
                if (allowSelectionOnly && !event.equals(doc.sel)) {
                    setSelection(doc, event, {clearRedo: false})
                    return
                }
                selAfter = event
            }
            else { break }
        }

        // Build up a reverse change object to add to the opposite history
        // stack (redo when undoing, and vice versa).
        var antiChanges = []
        pushSelectionToHistory(selAfter, dest)
        dest.push({changes: antiChanges, generation: hist.generation})
        hist.generation = event.generation || ++hist.maxGeneration

        var filter = hasHandler(doc, "beforeChange") || doc.cm && hasHandler(doc.cm, "beforeChange")

        var loop = function ( i ) {
            var change = event.changes[i]
            change.origin = type
            if (filter && !filterChange(doc, change, false)) {
                source.length = 0
                return {}
            }

            antiChanges.push(historyChangeFromChange(doc, change))

            var after = i ? computeSelAfterChange(doc, change) : lst(source)
            makeChangeSingleDoc(doc, change, after, mergeOldSpans(doc, change))
            if (!i && doc.cm) { doc.cm.scrollIntoView({from: change.from, to: changeEnd(change)}) }
            var rebased = []

            // Propagate to the linked documents
            linkedDocs(doc, function (doc, sharedHist) {
                if (!sharedHist && indexOf(rebased, doc.history) == -1) {
                    rebaseHist(doc.history, change)
                    rebased.push(doc.history)
                }
                makeChangeSingleDoc(doc, change, null, mergeOldSpans(doc, change))
            })
        };

        for (var i$1 = event.changes.length - 1; i$1 >= 0; --i$1) {
            var returned = loop( i$1 );

            if ( returned ) return returned.v;
        }
    }

// Sub-views need their line numbers shifted when text is added
// above or below them in the parent document.
    function shiftDoc(doc, distance) {
        if (distance == 0) { return }
        doc.first += distance
        doc.sel = new Selection(map(doc.sel.ranges, function (range) { return new Range(
            Pos(range.anchor.line + distance, range.anchor.ch),
            Pos(range.head.line + distance, range.head.ch)
        ); }), doc.sel.primIndex)
        if (doc.cm) {
            regChange(doc.cm, doc.first, doc.first - distance, distance)
            for (var d = doc.cm.display, l = d.viewFrom; l < d.viewTo; l++)
            { regLineChange(doc.cm, l, "gutter") }
        }
    }

// More lower-level change function, handling only a single document
// (not linked ones).
    function makeChangeSingleDoc(doc, change, selAfter, spans) {
        if (doc.cm && !doc.cm.curOp)
        { return operation(doc.cm, makeChangeSingleDoc)(doc, change, selAfter, spans) }

        if (change.to.line < doc.first) {
            shiftDoc(doc, change.text.length - 1 - (change.to.line - change.from.line))
            return
        }
        if (change.from.line > doc.lastLine()) { return }

        // Clip the change to the size of this doc
        if (change.from.line < doc.first) {
            var shift = change.text.length - 1 - (doc.first - change.from.line)
            shiftDoc(doc, shift)
            change = {from: Pos(doc.first, 0), to: Pos(change.to.line + shift, change.to.ch),
                text: [lst(change.text)], origin: change.origin}
        }
        var last = doc.lastLine()
        if (change.to.line > last) {
            change = {from: change.from, to: Pos(last, getLine(doc, last).text.length),
                text: [change.text[0]], origin: change.origin}
        }

        change.removed = getBetween(doc, change.from, change.to)

        if (!selAfter) { selAfter = computeSelAfterChange(doc, change) }
        if (doc.cm) { makeChangeSingleDocInEditor(doc.cm, change, spans) }
        else { updateDoc(doc, change, spans) }
        setSelectionNoUndo(doc, selAfter, sel_dontScroll)
    }

// Handle the interaction of a change to a document with the editor
// that this document is part of.
    function makeChangeSingleDocInEditor(cm, change, spans) {
        var doc = cm.doc, display = cm.display, from = change.from, to = change.to

        var recomputeMaxLength = false, checkWidthStart = from.line
        if (!cm.options.lineWrapping) {
            checkWidthStart = lineNo(visualLine(getLine(doc, from.line)))
            doc.iter(checkWidthStart, to.line + 1, function (line) {
                if (line == display.maxLine) {
                    recomputeMaxLength = true
                    return true
                }
            })
        }

        if (doc.sel.contains(change.from, change.to) > -1)
        { signalCursorActivity(cm) }

        updateDoc(doc, change, spans, estimateHeight(cm))

        if (!cm.options.lineWrapping) {
            doc.iter(checkWidthStart, from.line + change.text.length, function (line) {
                var len = lineLength(line)
                if (len > display.maxLineLength) {
                    display.maxLine = line
                    display.maxLineLength = len
                    display.maxLineChanged = true
                    recomputeMaxLength = false
                }
            })
            if (recomputeMaxLength) { cm.curOp.updateMaxLine = true }
        }

        retreatFrontier(doc, from.line)
        startWorker(cm, 400)

        var lendiff = change.text.length - (to.line - from.line) - 1
        // Remember that these lines changed, for updating the display
        if (change.full)
        { regChange(cm) }
        else if (from.line == to.line && change.text.length == 1 && !isWholeLineUpdate(cm.doc, change))
        { regLineChange(cm, from.line, "text") }
        else
        { regChange(cm, from.line, to.line + 1, lendiff) }

        var changesHandler = hasHandler(cm, "changes"), changeHandler = hasHandler(cm, "change")
        if (changeHandler || changesHandler) {
            var obj = {
                from: from, to: to,
                text: change.text,
                removed: change.removed,
                origin: change.origin
            }
            if (changeHandler) { signalLater(cm, "change", cm, obj) }
            if (changesHandler) { (cm.curOp.changeObjs || (cm.curOp.changeObjs = [])).push(obj) }
        }
        cm.display.selForContextMenu = null
    }

    function replaceRange(doc, code, from, to, origin) {
        if (!to) { to = from }
        if (cmp(to, from) < 0) { var assign;
            (assign = [to, from], from = assign[0], to = assign[1], assign) }
        if (typeof code == "string") { code = doc.splitLines(code) }
        makeChange(doc, {from: from, to: to, text: code, origin: origin})
    }

// Rebasing/resetting history to deal with externally-sourced changes

    function rebaseHistSelSingle(pos, from, to, diff) {
        if (to < pos.line) {
            pos.line += diff
        } else if (from < pos.line) {
            pos.line = from
            pos.ch = 0
        }
    }

// Tries to rebase an array of history events given a change in the
// document. If the change touches the same lines as the event, the
// event, and everything 'behind' it, is discarded. If the change is
// before the event, the event's positions are updated. Uses a
// copy-on-write scheme for the positions, to avoid having to
// reallocate them all on every rebase, but also avoid problems with
// shared position objects being unsafely updated.
    function rebaseHistArray(array, from, to, diff) {
        for (var i = 0; i < array.length; ++i) {
            var sub = array[i], ok = true
            if (sub.ranges) {
                if (!sub.copied) { sub = array[i] = sub.deepCopy(); sub.copied = true }
                for (var j = 0; j < sub.ranges.length; j++) {
                    rebaseHistSelSingle(sub.ranges[j].anchor, from, to, diff)
                    rebaseHistSelSingle(sub.ranges[j].head, from, to, diff)
                }
                continue
            }
            for (var j$1 = 0; j$1 < sub.changes.length; ++j$1) {
                var cur = sub.changes[j$1]
                if (to < cur.from.line) {
                    cur.from = Pos(cur.from.line + diff, cur.from.ch)
                    cur.to = Pos(cur.to.line + diff, cur.to.ch)
                } else if (from <= cur.to.line) {
                    ok = false
                    break
                }
            }
            if (!ok) {
                array.splice(0, i + 1)
                i = 0
            }
        }
    }

    function rebaseHist(hist, change) {
        var from = change.from.line, to = change.to.line, diff = change.text.length - (to - from) - 1
        rebaseHistArray(hist.done, from, to, diff)
        rebaseHistArray(hist.undone, from, to, diff)
    }

// Utility for applying a change to a line by handle or number,
// returning the number and optionally registering the line as
// changed.
    function changeLine(doc, handle, changeType, op) {
        var no = handle, line = handle
        if (typeof handle == "number") { line = getLine(doc, clipLine(doc, handle)) }
        else { no = lineNo(handle) }
        if (no == null) { return null }
        if (op(line, no) && doc.cm) { regLineChange(doc.cm, no, changeType) }
        return line
    }

// The document is represented as a BTree consisting of leaves, with
// chunk of lines in them, and branches, with up to ten leaves or
// other branch nodes below them. The top node is always a branch
// node, and is the document object itself (meaning it has
// additional methods and properties).
//
// All nodes have parent links. The tree is used both to go from
// line numbers to line objects, and to go from objects to numbers.
// It also indexes by height, and is used to convert between height
// and line object, and to find the total height of the document.
//
// See also http://marijnhaverbeke.nl/blog/codemirror-line-tree.html

    function LeafChunk(lines) {
        var this$1 = this;

        this.lines = lines
        this.parent = null
        var height = 0
        for (var i = 0; i < lines.length; ++i) {
            lines[i].parent = this$1
            height += lines[i].height
        }
        this.height = height
    }

    LeafChunk.prototype = {
        chunkSize: function chunkSize() { return this.lines.length },

        // Remove the n lines at offset 'at'.
        removeInner: function removeInner(at, n) {
            var this$1 = this;

            for (var i = at, e = at + n; i < e; ++i) {
                var line = this$1.lines[i]
                this$1.height -= line.height
                cleanUpLine(line)
                signalLater(line, "delete")
            }
            this.lines.splice(at, n)
        },

        // Helper used to collapse a small branch into a single leaf.
        collapse: function collapse(lines) {
            lines.push.apply(lines, this.lines)
        },

        // Insert the given array of lines at offset 'at', count them as
        // having the given height.
        insertInner: function insertInner(at, lines, height) {
            var this$1 = this;

            this.height += height
            this.lines = this.lines.slice(0, at).concat(lines).concat(this.lines.slice(at))
            for (var i = 0; i < lines.length; ++i) { lines[i].parent = this$1 }
        },

        // Used to iterate over a part of the tree.
        iterN: function iterN(at, n, op) {
            var this$1 = this;

            for (var e = at + n; at < e; ++at)
            { if (op(this$1.lines[at])) { return true } }
        }
    }

    function BranchChunk(children) {
        var this$1 = this;

        this.children = children
        var size = 0, height = 0
        for (var i = 0; i < children.length; ++i) {
            var ch = children[i]
            size += ch.chunkSize(); height += ch.height
            ch.parent = this$1
        }
        this.size = size
        this.height = height
        this.parent = null
    }

    BranchChunk.prototype = {
        chunkSize: function chunkSize() { return this.size },

        removeInner: function removeInner(at, n) {
            var this$1 = this;

            this.size -= n
            for (var i = 0; i < this.children.length; ++i) {
                var child = this$1.children[i], sz = child.chunkSize()
                if (at < sz) {
                    var rm = Math.min(n, sz - at), oldHeight = child.height
                    child.removeInner(at, rm)
                    this$1.height -= oldHeight - child.height
                    if (sz == rm) { this$1.children.splice(i--, 1); child.parent = null }
                    if ((n -= rm) == 0) { break }
                    at = 0
                } else { at -= sz }
            }
            // If the result is smaller than 25 lines, ensure that it is a
            // single leaf node.
            if (this.size - n < 25 &&
                (this.children.length > 1 || !(this.children[0] instanceof LeafChunk))) {
                var lines = []
                this.collapse(lines)
                this.children = [new LeafChunk(lines)]
                this.children[0].parent = this
            }
        },

        collapse: function collapse(lines) {
            var this$1 = this;

            for (var i = 0; i < this.children.length; ++i) { this$1.children[i].collapse(lines) }
        },

        insertInner: function insertInner(at, lines, height) {
            var this$1 = this;

            this.size += lines.length
            this.height += height
            for (var i = 0; i < this.children.length; ++i) {
                var child = this$1.children[i], sz = child.chunkSize()
                if (at <= sz) {
                    child.insertInner(at, lines, height)
                    if (child.lines && child.lines.length > 50) {
                        // To avoid memory thrashing when child.lines is huge (e.g. first view of a large file), it's never spliced.
                        // Instead, small slices are taken. They're taken in order because sequential memory accesses are fastest.
                        var remaining = child.lines.length % 25 + 25
                        for (var pos = remaining; pos < child.lines.length;) {
                            var leaf = new LeafChunk(child.lines.slice(pos, pos += 25))
                            child.height -= leaf.height
                            this$1.children.splice(++i, 0, leaf)
                            leaf.parent = this$1
                        }
                        child.lines = child.lines.slice(0, remaining)
                        this$1.maybeSpill()
                    }
                    break
                }
                at -= sz
            }
        },

        // When a node has grown, check whether it should be split.
        maybeSpill: function maybeSpill() {
            if (this.children.length <= 10) { return }
            var me = this
            do {
                var spilled = me.children.splice(me.children.length - 5, 5)
                var sibling = new BranchChunk(spilled)
                if (!me.parent) { // Become the parent node
                    var copy = new BranchChunk(me.children)
                    copy.parent = me
                    me.children = [copy, sibling]
                    me = copy
                } else {
                    me.size -= sibling.size
                    me.height -= sibling.height
                    var myIndex = indexOf(me.parent.children, me)
                    me.parent.children.splice(myIndex + 1, 0, sibling)
                }
                sibling.parent = me.parent
            } while (me.children.length > 10)
            me.parent.maybeSpill()
        },

        iterN: function iterN(at, n, op) {
            var this$1 = this;

            for (var i = 0; i < this.children.length; ++i) {
                var child = this$1.children[i], sz = child.chunkSize()
                if (at < sz) {
                    var used = Math.min(n, sz - at)
                    if (child.iterN(at, used, op)) { return true }
                    if ((n -= used) == 0) { break }
                    at = 0
                } else { at -= sz }
            }
        }
    }

// Line widgets are block elements displayed above or below a line.

    var LineWidget = function(doc, node, options) {
        var this$1 = this;

        if (options) { for (var opt in options) { if (options.hasOwnProperty(opt))
        { this$1[opt] = options[opt] } } }
        this.doc = doc
        this.node = node
    };

    LineWidget.prototype.clear = function () {
        var this$1 = this;

        var cm = this.doc.cm, ws = this.line.widgets, line = this.line, no = lineNo(line)
        if (no == null || !ws) { return }
        for (var i = 0; i < ws.length; ++i) { if (ws[i] == this$1) { ws.splice(i--, 1) } }
        if (!ws.length) { line.widgets = null }
        var height = widgetHeight(this)
        updateLineHeight(line, Math.max(0, line.height - height))
        if (cm) {
            runInOp(cm, function () {
                adjustScrollWhenAboveVisible(cm, line, -height)
                regLineChange(cm, no, "widget")
            })
            signalLater(cm, "lineWidgetCleared", cm, this, no)
        }
    };

    LineWidget.prototype.changed = function () {
        var this$1 = this;

        var oldH = this.height, cm = this.doc.cm, line = this.line
        this.height = null
        var diff = widgetHeight(this) - oldH
        if (!diff) { return }
        updateLineHeight(line, line.height + diff)
        if (cm) {
            runInOp(cm, function () {
                cm.curOp.forceUpdate = true
                adjustScrollWhenAboveVisible(cm, line, diff)
                signalLater(cm, "lineWidgetChanged", cm, this$1, lineNo(line))
            })
        }
    };
    eventMixin(LineWidget)

    function adjustScrollWhenAboveVisible(cm, line, diff) {
        if (heightAtLine(line) < ((cm.curOp && cm.curOp.scrollTop) || cm.doc.scrollTop))
        { addToScrollTop(cm, diff) }
    }

    function addLineWidget(doc, handle, node, options) {
        var widget = new LineWidget(doc, node, options)
        var cm = doc.cm
        if (cm && widget.noHScroll) { cm.display.alignWidgets = true }
        changeLine(doc, handle, "widget", function (line) {
            var widgets = line.widgets || (line.widgets = [])
            if (widget.insertAt == null) { widgets.push(widget) }
            else { widgets.splice(Math.min(widgets.length - 1, Math.max(0, widget.insertAt)), 0, widget) }
            widget.line = line
            if (cm && !lineIsHidden(doc, line)) {
                var aboveVisible = heightAtLine(line) < doc.scrollTop
                updateLineHeight(line, line.height + widgetHeight(widget))
                if (aboveVisible) { addToScrollTop(cm, widget.height) }
                cm.curOp.forceUpdate = true
            }
            return true
        })
        signalLater(cm, "lineWidgetAdded", cm, widget, typeof handle == "number" ? handle : lineNo(handle))
        return widget
    }

// TEXTMARKERS

// Created with markText and setBookmark methods. A TextMarker is a
// handle that can be used to clear or find a marked position in the
// document. Line objects hold arrays (markedSpans) containing
// {from, to, marker} object pointing to such marker objects, and
// indicating that such a marker is present on that line. Multiple
// lines may point to the same marker when it spans across lines.
// The spans will have null for their from/to properties when the
// marker continues beyond the start/end of the line. Markers have
// links back to the lines they currently touch.

// Collapsed markers have unique ids, in order to be able to order
// them, which is needed for uniquely determining an outer marker
// when they overlap (they may nest, but not partially overlap).
    var nextMarkerId = 0

    var TextMarker = function(doc, type) {
        this.lines = []
        this.type = type
        this.doc = doc
        this.id = ++nextMarkerId
    };

// Clear the marker.
    TextMarker.prototype.clear = function () {
        var this$1 = this;

        if (this.explicitlyCleared) { return }
        var cm = this.doc.cm, withOp = cm && !cm.curOp
        if (withOp) { startOperation(cm) }
        if (hasHandler(this, "clear")) {
            var found = this.find()
            if (found) { signalLater(this, "clear", found.from, found.to) }
        }
        var min = null, max = null
        for (var i = 0; i < this.lines.length; ++i) {
            var line = this$1.lines[i]
            var span = getMarkedSpanFor(line.markedSpans, this$1)
            if (cm && !this$1.collapsed) { regLineChange(cm, lineNo(line), "text") }
            else if (cm) {
                if (span.to != null) { max = lineNo(line) }
                if (span.from != null) { min = lineNo(line) }
            }
            line.markedSpans = removeMarkedSpan(line.markedSpans, span)
            if (span.from == null && this$1.collapsed && !lineIsHidden(this$1.doc, line) && cm)
            { updateLineHeight(line, textHeight(cm.display)) }
        }
        if (cm && this.collapsed && !cm.options.lineWrapping) { for (var i$1 = 0; i$1 < this.lines.length; ++i$1) {
            var visual = visualLine(this$1.lines[i$1]), len = lineLength(visual)
            if (len > cm.display.maxLineLength) {
                cm.display.maxLine = visual
                cm.display.maxLineLength = len
                cm.display.maxLineChanged = true
            }
        } }

        if (min != null && cm && this.collapsed) { regChange(cm, min, max + 1) }
        this.lines.length = 0
        this.explicitlyCleared = true
        if (this.atomic && this.doc.cantEdit) {
            this.doc.cantEdit = false
            if (cm) { reCheckSelection(cm.doc) }
        }
        if (cm) { signalLater(cm, "markerCleared", cm, this, min, max) }
        if (withOp) { endOperation(cm) }
        if (this.parent) { this.parent.clear() }
    };

// Find the position of the marker in the document. Returns a {from,
// to} object by default. Side can be passed to get a specific side
// -- 0 (both), -1 (left), or 1 (right). When lineObj is true, the
// Pos objects returned contain a line object, rather than a line
// number (used to prevent looking up the same line twice).
    TextMarker.prototype.find = function (side, lineObj) {
        var this$1 = this;

        if (side == null && this.type == "bookmark") { side = 1 }
        var from, to
        for (var i = 0; i < this.lines.length; ++i) {
            var line = this$1.lines[i]
            var span = getMarkedSpanFor(line.markedSpans, this$1)
            if (span.from != null) {
                from = Pos(lineObj ? line : lineNo(line), span.from)
                if (side == -1) { return from }
            }
            if (span.to != null) {
                to = Pos(lineObj ? line : lineNo(line), span.to)
                if (side == 1) { return to }
            }
        }
        return from && {from: from, to: to}
    };

// Signals that the marker's widget changed, and surrounding layout
// should be recomputed.
    TextMarker.prototype.changed = function () {
        var this$1 = this;

        var pos = this.find(-1, true), widget = this, cm = this.doc.cm
        if (!pos || !cm) { return }
        runInOp(cm, function () {
            var line = pos.line, lineN = lineNo(pos.line)
            var view = findViewForLine(cm, lineN)
            if (view) {
                clearLineMeasurementCacheFor(view)
                cm.curOp.selectionChanged = cm.curOp.forceUpdate = true
            }
            cm.curOp.updateMaxLine = true
            if (!lineIsHidden(widget.doc, line) && widget.height != null) {
                var oldHeight = widget.height
                widget.height = null
                var dHeight = widgetHeight(widget) - oldHeight
                if (dHeight)
                { updateLineHeight(line, line.height + dHeight) }
            }
            signalLater(cm, "markerChanged", cm, this$1)
        })
    };

    TextMarker.prototype.attachLine = function (line) {
        if (!this.lines.length && this.doc.cm) {
            var op = this.doc.cm.curOp
            if (!op.maybeHiddenMarkers || indexOf(op.maybeHiddenMarkers, this) == -1)
            { (op.maybeUnhiddenMarkers || (op.maybeUnhiddenMarkers = [])).push(this) }
        }
        this.lines.push(line)
    };

    TextMarker.prototype.detachLine = function (line) {
        this.lines.splice(indexOf(this.lines, line), 1)
        if (!this.lines.length && this.doc.cm) {
            var op = this.doc.cm.curOp
            ;(op.maybeHiddenMarkers || (op.maybeHiddenMarkers = [])).push(this)
        }
    };
    eventMixin(TextMarker)

// Create a marker, wire it up to the right lines, and
    function markText(doc, from, to, options, type) {
        // Shared markers (across linked documents) are handled separately
        // (markTextShared will call out to this again, once per
        // document).
        if (options && options.shared) { return markTextShared(doc, from, to, options, type) }
        // Ensure we are in an operation.
        if (doc.cm && !doc.cm.curOp) { return operation(doc.cm, markText)(doc, from, to, options, type) }

        var marker = new TextMarker(doc, type), diff = cmp(from, to)
        if (options) { copyObj(options, marker, false) }
        // Don't connect empty markers unless clearWhenEmpty is false
        if (diff > 0 || diff == 0 && marker.clearWhenEmpty !== false)
        { return marker }
        if (marker.replacedWith) {
            // Showing up as a widget implies collapsed (widget replaces text)
            marker.collapsed = true
            marker.widgetNode = eltP("span", [marker.replacedWith], "CodeMirror-widget")
            if (!options.handleMouseEvents) { marker.widgetNode.setAttribute("cm-ignore-events", "true") }
            if (options.insertLeft) { marker.widgetNode.insertLeft = true }
        }
        if (marker.collapsed) {
            if (conflictingCollapsedRange(doc, from.line, from, to, marker) ||
                from.line != to.line && conflictingCollapsedRange(doc, to.line, from, to, marker))
            { throw new Error("Inserting collapsed marker partially overlapping an existing one") }
            seeCollapsedSpans()
        }

        if (marker.addToHistory)
        { addChangeToHistory(doc, {from: from, to: to, origin: "markText"}, doc.sel, NaN) }

        var curLine = from.line, cm = doc.cm, updateMaxLine
        doc.iter(curLine, to.line + 1, function (line) {
            if (cm && marker.collapsed && !cm.options.lineWrapping && visualLine(line) == cm.display.maxLine)
            { updateMaxLine = true }
            if (marker.collapsed && curLine != from.line) { updateLineHeight(line, 0) }
            addMarkedSpan(line, new MarkedSpan(marker,
                curLine == from.line ? from.ch : null,
                curLine == to.line ? to.ch : null))
            ++curLine
        })
        // lineIsHidden depends on the presence of the spans, so needs a second pass
        if (marker.collapsed) { doc.iter(from.line, to.line + 1, function (line) {
            if (lineIsHidden(doc, line)) { updateLineHeight(line, 0) }
        }) }

        if (marker.clearOnEnter) { on(marker, "beforeCursorEnter", function () { return marker.clear(); }) }

        if (marker.readOnly) {
            seeReadOnlySpans()
            if (doc.history.done.length || doc.history.undone.length)
            { doc.clearHistory() }
        }
        if (marker.collapsed) {
            marker.id = ++nextMarkerId
            marker.atomic = true
        }
        if (cm) {
            // Sync editor state
            if (updateMaxLine) { cm.curOp.updateMaxLine = true }
            if (marker.collapsed)
            { regChange(cm, from.line, to.line + 1) }
            else if (marker.className || marker.title || marker.startStyle || marker.endStyle || marker.css)
            { for (var i = from.line; i <= to.line; i++) { regLineChange(cm, i, "text") } }
            if (marker.atomic) { reCheckSelection(cm.doc) }
            signalLater(cm, "markerAdded", cm, marker)
        }
        return marker
    }

// SHARED TEXTMARKERS

// A shared marker spans multiple linked documents. It is
// implemented as a meta-marker-object controlling multiple normal
// markers.
    var SharedTextMarker = function(markers, primary) {
        var this$1 = this;

        this.markers = markers
        this.primary = primary
        for (var i = 0; i < markers.length; ++i)
        { markers[i].parent = this$1 }
    };

    SharedTextMarker.prototype.clear = function () {
        var this$1 = this;

        if (this.explicitlyCleared) { return }
        this.explicitlyCleared = true
        for (var i = 0; i < this.markers.length; ++i)
        { this$1.markers[i].clear() }
        signalLater(this, "clear")
    };

    SharedTextMarker.prototype.find = function (side, lineObj) {
        return this.primary.find(side, lineObj)
    };
    eventMixin(SharedTextMarker)

    function markTextShared(doc, from, to, options, type) {
        options = copyObj(options)
        options.shared = false
        var markers = [markText(doc, from, to, options, type)], primary = markers[0]
        var widget = options.widgetNode
        linkedDocs(doc, function (doc) {
            if (widget) { options.widgetNode = widget.cloneNode(true) }
            markers.push(markText(doc, clipPos(doc, from), clipPos(doc, to), options, type))
            for (var i = 0; i < doc.linked.length; ++i)
            { if (doc.linked[i].isParent) { return } }
            primary = lst(markers)
        })
        return new SharedTextMarker(markers, primary)
    }

    function findSharedMarkers(doc) {
        return doc.findMarks(Pos(doc.first, 0), doc.clipPos(Pos(doc.lastLine())), function (m) { return m.parent; })
    }

    function copySharedMarkers(doc, markers) {
        for (var i = 0; i < markers.length; i++) {
            var marker = markers[i], pos = marker.find()
            var mFrom = doc.clipPos(pos.from), mTo = doc.clipPos(pos.to)
            if (cmp(mFrom, mTo)) {
                var subMark = markText(doc, mFrom, mTo, marker.primary, marker.primary.type)
                marker.markers.push(subMark)
                subMark.parent = marker
            }
        }
    }

    function detachSharedMarkers(markers) {
        var loop = function ( i ) {
            var marker = markers[i], linked = [marker.primary.doc]
            linkedDocs(marker.primary.doc, function (d) { return linked.push(d); })
            for (var j = 0; j < marker.markers.length; j++) {
                var subMarker = marker.markers[j]
                if (indexOf(linked, subMarker.doc) == -1) {
                    subMarker.parent = null
                    marker.markers.splice(j--, 1)
                }
            }
        };

        for (var i = 0; i < markers.length; i++) loop( i );
    }

    var nextDocId = 0
    var Doc = function(text, mode, firstLine, lineSep, direction) {
        if (!(this instanceof Doc)) { return new Doc(text, mode, firstLine, lineSep, direction) }
        if (firstLine == null) { firstLine = 0 }

        BranchChunk.call(this, [new LeafChunk([new Line("", null)])])
        this.first = firstLine
        this.scrollTop = this.scrollLeft = 0
        this.cantEdit = false
        this.cleanGeneration = 1
        this.modeFrontier = this.highlightFrontier = firstLine
        var start = Pos(firstLine, 0)
        this.sel = simpleSelection(start)
        this.history = new History(null)
        this.id = ++nextDocId
        this.modeOption = mode
        this.lineSep = lineSep
        this.direction = (direction == "rtl") ? "rtl" : "ltr"
        this.extend = false

        if (typeof text == "string") { text = this.splitLines(text) }
        updateDoc(this, {from: start, to: start, text: text})
        setSelection(this, simpleSelection(start), sel_dontScroll)
    }

    Doc.prototype = createObj(BranchChunk.prototype, {
        constructor: Doc,
        // Iterate over the document. Supports two forms -- with only one
        // argument, it calls that for each line in the document. With
        // three, it iterates over the range given by the first two (with
        // the second being non-inclusive).
        iter: function(from, to, op) {
            if (op) { this.iterN(from - this.first, to - from, op) }
            else { this.iterN(this.first, this.first + this.size, from) }
        },

        // Non-public interface for adding and removing lines.
        insert: function(at, lines) {
            var height = 0
            for (var i = 0; i < lines.length; ++i) { height += lines[i].height }
            this.insertInner(at - this.first, lines, height)
        },
        remove: function(at, n) { this.removeInner(at - this.first, n) },

        // From here, the methods are part of the public interface. Most
        // are also available from CodeMirror (editor) instances.

        getValue: function(lineSep) {
            var lines = getLines(this, this.first, this.first + this.size)
            if (lineSep === false) { return lines }
            return lines.join(lineSep || this.lineSeparator())
        },
        setValue: docMethodOp(function(code) {
            var top = Pos(this.first, 0), last = this.first + this.size - 1
            makeChange(this, {from: top, to: Pos(last, getLine(this, last).text.length),
                text: this.splitLines(code), origin: "setValue", full: true}, true)
            if (this.cm) { scrollToCoords(this.cm, 0, 0) }
            setSelection(this, simpleSelection(top), sel_dontScroll)
        }),
        replaceRange: function(code, from, to, origin) {
            from = clipPos(this, from)
            to = to ? clipPos(this, to) : from
            replaceRange(this, code, from, to, origin)
        },
        getRange: function(from, to, lineSep) {
            var lines = getBetween(this, clipPos(this, from), clipPos(this, to))
            if (lineSep === false) { return lines }
            return lines.join(lineSep || this.lineSeparator())
        },

        getLine: function(line) {var l = this.getLineHandle(line); return l && l.text},

        getLineHandle: function(line) {if (isLine(this, line)) { return getLine(this, line) }},
        getLineNumber: function(line) {return lineNo(line)},

        getLineHandleVisualStart: function(line) {
            if (typeof line == "number") { line = getLine(this, line) }
            return visualLine(line)
        },

        lineCount: function() {return this.size},
        firstLine: function() {return this.first},
        lastLine: function() {return this.first + this.size - 1},

        clipPos: function(pos) {return clipPos(this, pos)},

        getCursor: function(start) {
            var range = this.sel.primary(), pos
            if (start == null || start == "head") { pos = range.head }
            else if (start == "anchor") { pos = range.anchor }
            else if (start == "end" || start == "to" || start === false) { pos = range.to() }
            else { pos = range.from() }
            return pos
        },
        listSelections: function() { return this.sel.ranges },
        somethingSelected: function() {return this.sel.somethingSelected()},

        setCursor: docMethodOp(function(line, ch, options) {
            setSimpleSelection(this, clipPos(this, typeof line == "number" ? Pos(line, ch || 0) : line), null, options)
        }),
        setSelection: docMethodOp(function(anchor, head, options) {
            setSimpleSelection(this, clipPos(this, anchor), clipPos(this, head || anchor), options)
        }),
        extendSelection: docMethodOp(function(head, other, options) {
            extendSelection(this, clipPos(this, head), other && clipPos(this, other), options)
        }),
        extendSelections: docMethodOp(function(heads, options) {
            extendSelections(this, clipPosArray(this, heads), options)
        }),
        extendSelectionsBy: docMethodOp(function(f, options) {
            var heads = map(this.sel.ranges, f)
            extendSelections(this, clipPosArray(this, heads), options)
        }),
        setSelections: docMethodOp(function(ranges, primary, options) {
            var this$1 = this;

            if (!ranges.length) { return }
            var out = []
            for (var i = 0; i < ranges.length; i++)
            { out[i] = new Range(clipPos(this$1, ranges[i].anchor),
                clipPos(this$1, ranges[i].head)) }
            if (primary == null) { primary = Math.min(ranges.length - 1, this.sel.primIndex) }
            setSelection(this, normalizeSelection(out, primary), options)
        }),
        addSelection: docMethodOp(function(anchor, head, options) {
            var ranges = this.sel.ranges.slice(0)
            ranges.push(new Range(clipPos(this, anchor), clipPos(this, head || anchor)))
            setSelection(this, normalizeSelection(ranges, ranges.length - 1), options)
        }),

        getSelection: function(lineSep) {
            var this$1 = this;

            var ranges = this.sel.ranges, lines
            for (var i = 0; i < ranges.length; i++) {
                var sel = getBetween(this$1, ranges[i].from(), ranges[i].to())
                lines = lines ? lines.concat(sel) : sel
            }
            if (lineSep === false) { return lines }
            else { return lines.join(lineSep || this.lineSeparator()) }
        },
        getSelections: function(lineSep) {
            var this$1 = this;

            var parts = [], ranges = this.sel.ranges
            for (var i = 0; i < ranges.length; i++) {
                var sel = getBetween(this$1, ranges[i].from(), ranges[i].to())
                if (lineSep !== false) { sel = sel.join(lineSep || this$1.lineSeparator()) }
                parts[i] = sel
            }
            return parts
        },
        replaceSelection: function(code, collapse, origin) {
            var dup = []
            for (var i = 0; i < this.sel.ranges.length; i++)
            { dup[i] = code }
            this.replaceSelections(dup, collapse, origin || "+input")
        },
        replaceSelections: docMethodOp(function(code, collapse, origin) {
            var this$1 = this;

            var changes = [], sel = this.sel
            for (var i = 0; i < sel.ranges.length; i++) {
                var range = sel.ranges[i]
                changes[i] = {from: range.from(), to: range.to(), text: this$1.splitLines(code[i]), origin: origin}
            }
            var newSel = collapse && collapse != "end" && computeReplacedSel(this, changes, collapse)
            for (var i$1 = changes.length - 1; i$1 >= 0; i$1--)
            { makeChange(this$1, changes[i$1]) }
            if (newSel) { setSelectionReplaceHistory(this, newSel) }
            else if (this.cm) { ensureCursorVisible(this.cm) }
        }),
        undo: docMethodOp(function() {makeChangeFromHistory(this, "undo")}),
        redo: docMethodOp(function() {makeChangeFromHistory(this, "redo")}),
        undoSelection: docMethodOp(function() {makeChangeFromHistory(this, "undo", true)}),
        redoSelection: docMethodOp(function() {makeChangeFromHistory(this, "redo", true)}),

        setExtending: function(val) {this.extend = val},
        getExtending: function() {return this.extend},

        historySize: function() {
            var hist = this.history, done = 0, undone = 0
            for (var i = 0; i < hist.done.length; i++) { if (!hist.done[i].ranges) { ++done } }
            for (var i$1 = 0; i$1 < hist.undone.length; i$1++) { if (!hist.undone[i$1].ranges) { ++undone } }
            return {undo: done, redo: undone}
        },
        clearHistory: function() {this.history = new History(this.history.maxGeneration)},

        markClean: function() {
            this.cleanGeneration = this.changeGeneration(true)
        },
        changeGeneration: function(forceSplit) {
            if (forceSplit)
            { this.history.lastOp = this.history.lastSelOp = this.history.lastOrigin = null }
            return this.history.generation
        },
        isClean: function (gen) {
            return this.history.generation == (gen || this.cleanGeneration)
        },

        getHistory: function() {
            return {done: copyHistoryArray(this.history.done),
                undone: copyHistoryArray(this.history.undone)}
        },
        setHistory: function(histData) {
            var hist = this.history = new History(this.history.maxGeneration)
            hist.done = copyHistoryArray(histData.done.slice(0), null, true)
            hist.undone = copyHistoryArray(histData.undone.slice(0), null, true)
        },

        setGutterMarker: docMethodOp(function(line, gutterID, value) {
            return changeLine(this, line, "gutter", function (line) {
                var markers = line.gutterMarkers || (line.gutterMarkers = {})
                markers[gutterID] = value
                if (!value && isEmpty(markers)) { line.gutterMarkers = null }
                return true
            })
        }),

        clearGutter: docMethodOp(function(gutterID) {
            var this$1 = this;

            this.iter(function (line) {
                if (line.gutterMarkers && line.gutterMarkers[gutterID]) {
                    changeLine(this$1, line, "gutter", function () {
                        line.gutterMarkers[gutterID] = null
                        if (isEmpty(line.gutterMarkers)) { line.gutterMarkers = null }
                        return true
                    })
                }
            })
        }),

        lineInfo: function(line) {
            var n
            if (typeof line == "number") {
                if (!isLine(this, line)) { return null }
                n = line
                line = getLine(this, line)
                if (!line) { return null }
            } else {
                n = lineNo(line)
                if (n == null) { return null }
            }
            return {line: n, handle: line, text: line.text, gutterMarkers: line.gutterMarkers,
                textClass: line.textClass, bgClass: line.bgClass, wrapClass: line.wrapClass,
                widgets: line.widgets}
        },

        addLineClass: docMethodOp(function(handle, where, cls) {
            return changeLine(this, handle, where == "gutter" ? "gutter" : "class", function (line) {
                var prop = where == "text" ? "textClass"
                    : where == "background" ? "bgClass"
                        : where == "gutter" ? "gutterClass" : "wrapClass"
                if (!line[prop]) { line[prop] = cls }
                else if (classTest(cls).test(line[prop])) { return false }
                else { line[prop] += " " + cls }
                return true
            })
        }),
        removeLineClass: docMethodOp(function(handle, where, cls) {
            return changeLine(this, handle, where == "gutter" ? "gutter" : "class", function (line) {
                var prop = where == "text" ? "textClass"
                    : where == "background" ? "bgClass"
                        : where == "gutter" ? "gutterClass" : "wrapClass"
                var cur = line[prop]
                if (!cur) { return false }
                else if (cls == null) { line[prop] = null }
                else {
                    var found = cur.match(classTest(cls))
                    if (!found) { return false }
                    var end = found.index + found[0].length
                    line[prop] = cur.slice(0, found.index) + (!found.index || end == cur.length ? "" : " ") + cur.slice(end) || null
                }
                return true
            })
        }),

        addLineWidget: docMethodOp(function(handle, node, options) {
            return addLineWidget(this, handle, node, options)
        }),
        removeLineWidget: function(widget) { widget.clear() },

        markText: function(from, to, options) {
            return markText(this, clipPos(this, from), clipPos(this, to), options, options && options.type || "range")
        },
        setBookmark: function(pos, options) {
            var realOpts = {replacedWith: options && (options.nodeType == null ? options.widget : options),
                insertLeft: options && options.insertLeft,
                clearWhenEmpty: false, shared: options && options.shared,
                handleMouseEvents: options && options.handleMouseEvents}
            pos = clipPos(this, pos)
            return markText(this, pos, pos, realOpts, "bookmark")
        },
        findMarksAt: function(pos) {
            pos = clipPos(this, pos)
            var markers = [], spans = getLine(this, pos.line).markedSpans
            if (spans) { for (var i = 0; i < spans.length; ++i) {
                var span = spans[i]
                if ((span.from == null || span.from <= pos.ch) &&
                    (span.to == null || span.to >= pos.ch))
                { markers.push(span.marker.parent || span.marker) }
            } }
            return markers
        },
        findMarks: function(from, to, filter) {
            from = clipPos(this, from); to = clipPos(this, to)
            var found = [], lineNo = from.line
            this.iter(from.line, to.line + 1, function (line) {
                var spans = line.markedSpans
                if (spans) { for (var i = 0; i < spans.length; i++) {
                    var span = spans[i]
                    if (!(span.to != null && lineNo == from.line && from.ch >= span.to ||
                            span.from == null && lineNo != from.line ||
                            span.from != null && lineNo == to.line && span.from >= to.ch) &&
                        (!filter || filter(span.marker)))
                    { found.push(span.marker.parent || span.marker) }
                } }
                ++lineNo
            })
            return found
        },
        getAllMarks: function() {
            var markers = []
            this.iter(function (line) {
                var sps = line.markedSpans
                if (sps) { for (var i = 0; i < sps.length; ++i)
                { if (sps[i].from != null) { markers.push(sps[i].marker) } } }
            })
            return markers
        },

        posFromIndex: function(off) {
            var ch, lineNo = this.first, sepSize = this.lineSeparator().length
            this.iter(function (line) {
                var sz = line.text.length + sepSize
                if (sz > off) { ch = off; return true }
                off -= sz
                ++lineNo
            })
            return clipPos(this, Pos(lineNo, ch))
        },
        indexFromPos: function (coords) {
            coords = clipPos(this, coords)
            var index = coords.ch
            if (coords.line < this.first || coords.ch < 0) { return 0 }
            var sepSize = this.lineSeparator().length
            this.iter(this.first, coords.line, function (line) { // iter aborts when callback returns a truthy value
                index += line.text.length + sepSize
            })
            return index
        },

        copy: function(copyHistory) {
            var doc = new Doc(getLines(this, this.first, this.first + this.size),
                this.modeOption, this.first, this.lineSep, this.direction)
            doc.scrollTop = this.scrollTop; doc.scrollLeft = this.scrollLeft
            doc.sel = this.sel
            doc.extend = false
            if (copyHistory) {
                doc.history.undoDepth = this.history.undoDepth
                doc.setHistory(this.getHistory())
            }
            return doc
        },

        linkedDoc: function(options) {
            if (!options) { options = {} }
            var from = this.first, to = this.first + this.size
            if (options.from != null && options.from > from) { from = options.from }
            if (options.to != null && options.to < to) { to = options.to }
            var copy = new Doc(getLines(this, from, to), options.mode || this.modeOption, from, this.lineSep, this.direction)
            if (options.sharedHist) { copy.history = this.history
            ; }(this.linked || (this.linked = [])).push({doc: copy, sharedHist: options.sharedHist})
            copy.linked = [{doc: this, isParent: true, sharedHist: options.sharedHist}]
            copySharedMarkers(copy, findSharedMarkers(this))
            return copy
        },
        unlinkDoc: function(other) {
            var this$1 = this;

            if (other instanceof CodeMirror) { other = other.doc }
            if (this.linked) { for (var i = 0; i < this.linked.length; ++i) {
                var link = this$1.linked[i]
                if (link.doc != other) { continue }
                this$1.linked.splice(i, 1)
                other.unlinkDoc(this$1)
                detachSharedMarkers(findSharedMarkers(this$1))
                break
            } }
            // If the histories were shared, split them again
            if (other.history == this.history) {
                var splitIds = [other.id]
                linkedDocs(other, function (doc) { return splitIds.push(doc.id); }, true)
                other.history = new History(null)
                other.history.done = copyHistoryArray(this.history.done, splitIds)
                other.history.undone = copyHistoryArray(this.history.undone, splitIds)
            }
        },
        iterLinkedDocs: function(f) {linkedDocs(this, f)},

        getMode: function() {return this.mode},
        getEditor: function() {return this.cm},

        splitLines: function(str) {
            if (this.lineSep) { return str.split(this.lineSep) }
            return splitLinesAuto(str)
        },
        lineSeparator: function() { return this.lineSep || "\n" },

        setDirection: docMethodOp(function (dir) {
            if (dir != "rtl") { dir = "ltr" }
            if (dir == this.direction) { return }
            this.direction = dir
            this.iter(function (line) { return line.order = null; })
            if (this.cm) { directionChanged(this.cm) }
        })
    })

// Public alias.
    Doc.prototype.eachLine = Doc.prototype.iter

// Kludge to work around strange IE behavior where it'll sometimes
// re-fire a series of drag-related events right after the drop (#1551)
    var lastDrop = 0

    function onDrop(e) {
        var cm = this
        clearDragCursor(cm)
        if (signalDOMEvent(cm, e) || eventInWidget(cm.display, e))
        { return }
        e_preventDefault(e)
        if (ie) { lastDrop = +new Date }
        var pos = posFromMouse(cm, e, true), files = e.dataTransfer.files
        if (!pos || cm.isReadOnly()) { return }
        // Might be a file drop, in which case we simply extract the text
        // and insert it.
        if (files && files.length && window.FileReader && window.File) {
            var n = files.length, text = Array(n), read = 0
            var loadFile = function (file, i) {
                if (cm.options.allowDropFileTypes &&
                    indexOf(cm.options.allowDropFileTypes, file.type) == -1)
                { return }

                var reader = new FileReader
                reader.onload = operation(cm, function () {
                    var content = reader.result
                    if (/[\x00-\x08\x0e-\x1f]{2}/.test(content)) { content = "" }
                    text[i] = content
                    if (++read == n) {
                        pos = clipPos(cm.doc, pos)
                        var change = {from: pos, to: pos,
                            text: cm.doc.splitLines(text.join(cm.doc.lineSeparator())),
                            origin: "paste"}
                        makeChange(cm.doc, change)
                        setSelectionReplaceHistory(cm.doc, simpleSelection(pos, changeEnd(change)))
                    }
                })
                reader.readAsText(file)
            }
            for (var i = 0; i < n; ++i) { loadFile(files[i], i) }
        } else { // Normal drop
            // Don't do a replace if the drop happened inside of the selected text.
            if (cm.state.draggingText && cm.doc.sel.contains(pos) > -1) {
                cm.state.draggingText(e)
                // Ensure the editor is re-focused
                setTimeout(function () { return cm.display.input.focus(); }, 20)
                return
            }
            try {
                var text$1 = e.dataTransfer.getData("Text")
                if (text$1) {
                    var selected
                    if (cm.state.draggingText && !cm.state.draggingText.copy)
                    { selected = cm.listSelections() }
                    setSelectionNoUndo(cm.doc, simpleSelection(pos, pos))
                    if (selected) { for (var i$1 = 0; i$1 < selected.length; ++i$1)
                    { replaceRange(cm.doc, "", selected[i$1].anchor, selected[i$1].head, "drag") } }
                    cm.replaceSelection(text$1, "around", "paste")
                    cm.display.input.focus()
                }
            }
            catch(e){}
        }
    }

    function onDragStart(cm, e) {
        if (ie && (!cm.state.draggingText || +new Date - lastDrop < 100)) { e_stop(e); return }
        if (signalDOMEvent(cm, e) || eventInWidget(cm.display, e)) { return }

        e.dataTransfer.setData("Text", cm.getSelection())
        e.dataTransfer.effectAllowed = "copyMove"

        // Use dummy image instead of default browsers image.
        // Recent Safari (~6.0.2) have a tendency to segfault when this happens, so we don't do it there.
        if (e.dataTransfer.setDragImage && !safari) {
            var img = elt("img", null, null, "position: fixed; left: 0; top: 0;")
            img.src = "data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw=="
            if (presto) {
                img.width = img.height = 1
                cm.display.wrapper.appendChild(img)
                // Force a relayout, or Opera won't use our image for some obscure reason
                img._top = img.offsetTop
            }
            e.dataTransfer.setDragImage(img, 0, 0)
            if (presto) { img.parentNode.removeChild(img) }
        }
    }

    function onDragOver(cm, e) {
        var pos = posFromMouse(cm, e)
        if (!pos) { return }
        var frag = document.createDocumentFragment()
        drawSelectionCursor(cm, pos, frag)
        if (!cm.display.dragCursor) {
            cm.display.dragCursor = elt("div", null, "CodeMirror-cursors CodeMirror-dragcursors")
            cm.display.lineSpace.insertBefore(cm.display.dragCursor, cm.display.cursorDiv)
        }
        removeChildrenAndAdd(cm.display.dragCursor, frag)
    }

    function clearDragCursor(cm) {
        if (cm.display.dragCursor) {
            cm.display.lineSpace.removeChild(cm.display.dragCursor)
            cm.display.dragCursor = null
        }
    }

// These must be handled carefully, because naively registering a
// handler for each editor will cause the editors to never be
// garbage collected.

    function forEachCodeMirror(f) {
        if (!document.getElementsByClassName) { return }
        var byClass = document.getElementsByClassName("CodeMirror")
        for (var i = 0; i < byClass.length; i++) {
            var cm = byClass[i].CodeMirror
            if (cm) { f(cm) }
        }
    }

    var globalsRegistered = false
    function ensureGlobalHandlers() {
        if (globalsRegistered) { return }
        registerGlobalHandlers()
        globalsRegistered = true
    }
    function registerGlobalHandlers() {
        // When the window resizes, we need to refresh active editors.
        var resizeTimer
        on(window, "resize", function () {
            if (resizeTimer == null) { resizeTimer = setTimeout(function () {
                resizeTimer = null
                forEachCodeMirror(onResize)
            }, 100) }
        })
        // When the window loses focus, we want to show the editor as blurred
        on(window, "blur", function () { return forEachCodeMirror(onBlur); })
    }
// Called when the window resizes
    function onResize(cm) {
        var d = cm.display
        if (d.lastWrapHeight == d.wrapper.clientHeight && d.lastWrapWidth == d.wrapper.clientWidth)
        { return }
        // Might be a text scaling operation, clear size caches.
        d.cachedCharWidth = d.cachedTextHeight = d.cachedPaddingH = null
        d.scrollbarsClipped = false
        cm.setSize()
    }

    var keyNames = {
        3: "Enter", 8: "Backspace", 9: "Tab", 13: "Enter", 16: "Shift", 17: "Ctrl", 18: "Alt",
        19: "Pause", 20: "CapsLock", 27: "Esc", 32: "Space", 33: "PageUp", 34: "PageDown", 35: "End",
        36: "Home", 37: "Left", 38: "Up", 39: "Right", 40: "Down", 44: "PrintScrn", 45: "Insert",
        46: "Delete", 59: ";", 61: "=", 91: "Mod", 92: "Mod", 93: "Mod",
        106: "*", 107: "=", 109: "-", 110: ".", 111: "/", 127: "Delete",
        173: "-", 186: ";", 187: "=", 188: ",", 189: "-", 190: ".", 191: "/", 192: "`", 219: "[", 220: "\\",
        221: "]", 222: "'", 63232: "Up", 63233: "Down", 63234: "Left", 63235: "Right", 63272: "Delete",
        63273: "Home", 63275: "End", 63276: "PageUp", 63277: "PageDown", 63302: "Insert"
    }

// Number keys
    for (var i = 0; i < 10; i++) { keyNames[i + 48] = keyNames[i + 96] = String(i) }
// Alphabetic keys
    for (var i$1 = 65; i$1 <= 90; i$1++) { keyNames[i$1] = String.fromCharCode(i$1) }
// Function keys
    for (var i$2 = 1; i$2 <= 12; i$2++) { keyNames[i$2 + 111] = keyNames[i$2 + 63235] = "F" + i$2 }

    var keyMap = {}

    keyMap.basic = {
        "Left": "goCharLeft", "Right": "goCharRight", "Up": "goLineUp", "Down": "goLineDown",
        "End": "goLineEnd", "Home": "goLineStartSmart", "PageUp": "goPageUp", "PageDown": "goPageDown",
        "Delete": "delCharAfter", "Backspace": "delCharBefore", "Shift-Backspace": "delCharBefore",
        "Tab": "defaultTab", "Shift-Tab": "indentAuto",
        "Enter": "newlineAndIndent", "Insert": "toggleOverwrite",
        "Esc": "singleSelection"
    }
// Note that the save and find-related commands aren't defined by
// default. User code or addons can define them. Unknown commands
// are simply ignored.
    keyMap.pcDefault = {
        "Ctrl-A": "selectAll", "Ctrl-D": "deleteLine", "Ctrl-Z": "undo", "Shift-Ctrl-Z": "redo", "Ctrl-Y": "redo",
        "Ctrl-Home": "goDocStart", "Ctrl-End": "goDocEnd", "Ctrl-Up": "goLineUp", "Ctrl-Down": "goLineDown",
        "Ctrl-Left": "goGroupLeft", "Ctrl-Right": "goGroupRight", "Alt-Left": "goLineStart", "Alt-Right": "goLineEnd",
        "Ctrl-Backspace": "delGroupBefore", "Ctrl-Delete": "delGroupAfter", "Ctrl-S": "save", "Ctrl-F": "find",
        "Ctrl-G": "findNext", "Shift-Ctrl-G": "findPrev", "Shift-Ctrl-F": "replace", "Shift-Ctrl-R": "replaceAll",
        "Ctrl-[": "indentLess", "Ctrl-]": "indentMore",
        "Ctrl-U": "undoSelection", "Shift-Ctrl-U": "redoSelection", "Alt-U": "redoSelection",
        fallthrough: "basic"
    }
// Very basic readline/emacs-style bindings, which are standard on Mac.
    keyMap.emacsy = {
        "Ctrl-F": "goCharRight", "Ctrl-B": "goCharLeft", "Ctrl-P": "goLineUp", "Ctrl-N": "goLineDown",
        "Alt-F": "goWordRight", "Alt-B": "goWordLeft", "Ctrl-A": "goLineStart", "Ctrl-E": "goLineEnd",
        "Ctrl-V": "goPageDown", "Shift-Ctrl-V": "goPageUp", "Ctrl-D": "delCharAfter", "Ctrl-H": "delCharBefore",
        "Alt-D": "delWordAfter", "Alt-Backspace": "delWordBefore", "Ctrl-K": "killLine", "Ctrl-T": "transposeChars",
        "Ctrl-O": "openLine"
    }
    keyMap.macDefault = {
        "Cmd-A": "selectAll", "Cmd-D": "deleteLine", "Cmd-Z": "undo", "Shift-Cmd-Z": "redo", "Cmd-Y": "redo",
        "Cmd-Home": "goDocStart", "Cmd-Up": "goDocStart", "Cmd-End": "goDocEnd", "Cmd-Down": "goDocEnd", "Alt-Left": "goGroupLeft",
        "Alt-Right": "goGroupRight", "Cmd-Left": "goLineLeft", "Cmd-Right": "goLineRight", "Alt-Backspace": "delGroupBefore",
        "Ctrl-Alt-Backspace": "delGroupAfter", "Alt-Delete": "delGroupAfter", "Cmd-S": "save", "Cmd-F": "find",
        "Cmd-G": "findNext", "Shift-Cmd-G": "findPrev", "Cmd-Alt-F": "replace", "Shift-Cmd-Alt-F": "replaceAll",
        "Cmd-[": "indentLess", "Cmd-]": "indentMore", "Cmd-Backspace": "delWrappedLineLeft", "Cmd-Delete": "delWrappedLineRight",
        "Cmd-U": "undoSelection", "Shift-Cmd-U": "redoSelection", "Ctrl-Up": "goDocStart", "Ctrl-Down": "goDocEnd",
        fallthrough: ["basic", "emacsy"]
    }
    keyMap["default"] = mac ? keyMap.macDefault : keyMap.pcDefault

// KEYMAP DISPATCH

    function normalizeKeyName(name) {
        var parts = name.split(/-(?!$)/)
        name = parts[parts.length - 1]
        var alt, ctrl, shift, cmd
        for (var i = 0; i < parts.length - 1; i++) {
            var mod = parts[i]
            if (/^(cmd|meta|m)$/i.test(mod)) { cmd = true }
            else if (/^a(lt)?$/i.test(mod)) { alt = true }
            else if (/^(c|ctrl|control)$/i.test(mod)) { ctrl = true }
            else if (/^s(hift)?$/i.test(mod)) { shift = true }
            else { throw new Error("Unrecognized modifier name: " + mod) }
        }
        if (alt) { name = "Alt-" + name }
        if (ctrl) { name = "Ctrl-" + name }
        if (cmd) { name = "Cmd-" + name }
        if (shift) { name = "Shift-" + name }
        return name
    }

// This is a kludge to keep keymaps mostly working as raw objects
// (backwards compatibility) while at the same time support features
// like normalization and multi-stroke key bindings. It compiles a
// new normalized keymap, and then updates the old object to reflect
// this.
    function normalizeKeyMap(keymap) {
        var copy = {}
        for (var keyname in keymap) { if (keymap.hasOwnProperty(keyname)) {
            var value = keymap[keyname]
            if (/^(name|fallthrough|(de|at)tach)$/.test(keyname)) { continue }
            if (value == "...") { delete keymap[keyname]; continue }

            var keys = map(keyname.split(" "), normalizeKeyName)
            for (var i = 0; i < keys.length; i++) {
                var val = (void 0), name = (void 0)
                if (i == keys.length - 1) {
                    name = keys.join(" ")
                    val = value
                } else {
                    name = keys.slice(0, i + 1).join(" ")
                    val = "..."
                }
                var prev = copy[name]
                if (!prev) { copy[name] = val }
                else if (prev != val) { throw new Error("Inconsistent bindings for " + name) }
            }
            delete keymap[keyname]
        } }
        for (var prop in copy) { keymap[prop] = copy[prop] }
        return keymap
    }

    function lookupKey(key, map, handle, context) {
        map = getKeyMap(map)
        var found = map.call ? map.call(key, context) : map[key]
        if (found === false) { return "nothing" }
        if (found === "...") { return "multi" }
        if (found != null && handle(found)) { return "handled" }

        if (map.fallthrough) {
            if (Object.prototype.toString.call(map.fallthrough) != "[object Array]")
            { return lookupKey(key, map.fallthrough, handle, context) }
            for (var i = 0; i < map.fallthrough.length; i++) {
                var result = lookupKey(key, map.fallthrough[i], handle, context)
                if (result) { return result }
            }
        }
    }

// Modifier key presses don't count as 'real' key presses for the
// purpose of keymap fallthrough.
    function isModifierKey(value) {
        var name = typeof value == "string" ? value : keyNames[value.keyCode]
        return name == "Ctrl" || name == "Alt" || name == "Shift" || name == "Mod"
    }

    function addModifierNames(name, event, noShift) {
        var base = name
        if (event.altKey && base != "Alt") { name = "Alt-" + name }
        if ((flipCtrlCmd ? event.metaKey : event.ctrlKey) && base != "Ctrl") { name = "Ctrl-" + name }
        if ((flipCtrlCmd ? event.ctrlKey : event.metaKey) && base != "Cmd") { name = "Cmd-" + name }
        if (!noShift && event.shiftKey && base != "Shift") { name = "Shift-" + name }
        return name
    }

// Look up the name of a key as indicated by an event object.
    function keyName(event, noShift) {
        if (presto && event.keyCode == 34 && event["char"]) { return false }
        var name = keyNames[event.keyCode]
        if (name == null || event.altGraphKey) { return false }
        return addModifierNames(name, event, noShift)
    }

    function getKeyMap(val) {
        return typeof val == "string" ? keyMap[val] : val
    }

// Helper for deleting text near the selection(s), used to implement
// backspace, delete, and similar functionality.
    function deleteNearSelection(cm, compute) {
        var ranges = cm.doc.sel.ranges, kill = []
        // Build up a set of ranges to kill first, merging overlapping
        // ranges.
        for (var i = 0; i < ranges.length; i++) {
            var toKill = compute(ranges[i])
            while (kill.length && cmp(toKill.from, lst(kill).to) <= 0) {
                var replaced = kill.pop()
                if (cmp(replaced.from, toKill.from) < 0) {
                    toKill.from = replaced.from
                    break
                }
            }
            kill.push(toKill)
        }
        // Next, remove those actual ranges.
        runInOp(cm, function () {
            for (var i = kill.length - 1; i >= 0; i--)
            { replaceRange(cm.doc, "", kill[i].from, kill[i].to, "+delete") }
            ensureCursorVisible(cm)
        })
    }

    function moveCharLogically(line, ch, dir) {
        var target = skipExtendingChars(line.text, ch + dir, dir)
        return target < 0 || target > line.text.length ? null : target
    }

    function moveLogically(line, start, dir) {
        var ch = moveCharLogically(line, start.ch, dir)
        return ch == null ? null : new Pos(start.line, ch, dir < 0 ? "after" : "before")
    }

    function endOfLine(visually, cm, lineObj, lineNo, dir) {
        if (visually) {
            var order = getOrder(lineObj, cm.doc.direction)
            if (order) {
                var part = dir < 0 ? lst(order) : order[0]
                var moveInStorageOrder = (dir < 0) == (part.level == 1)
                var sticky = moveInStorageOrder ? "after" : "before"
                var ch
                // With a wrapped rtl chunk (possibly spanning multiple bidi parts),
                // it could be that the last bidi part is not on the last visual line,
                // since visual lines contain content order-consecutive chunks.
                // Thus, in rtl, we are looking for the first (content-order) character
                // in the rtl chunk that is on the last line (that is, the same line
                // as the last (content-order) character).
                if (part.level > 0 || cm.doc.direction == "rtl") {
                    var prep = prepareMeasureForLine(cm, lineObj)
                    ch = dir < 0 ? lineObj.text.length - 1 : 0
                    var targetTop = measureCharPrepared(cm, prep, ch).top
                    ch = findFirst(function (ch) { return measureCharPrepared(cm, prep, ch).top == targetTop; }, (dir < 0) == (part.level == 1) ? part.from : part.to - 1, ch)
                    if (sticky == "before") { ch = moveCharLogically(lineObj, ch, 1) }
                } else { ch = dir < 0 ? part.to : part.from }
                return new Pos(lineNo, ch, sticky)
            }
        }
        return new Pos(lineNo, dir < 0 ? lineObj.text.length : 0, dir < 0 ? "before" : "after")
    }

    function moveVisually(cm, line, start, dir) {
        var bidi = getOrder(line, cm.doc.direction)
        if (!bidi) { return moveLogically(line, start, dir) }
        if (start.ch >= line.text.length) {
            start.ch = line.text.length
            start.sticky = "before"
        } else if (start.ch <= 0) {
            start.ch = 0
            start.sticky = "after"
        }
        var partPos = getBidiPartAt(bidi, start.ch, start.sticky), part = bidi[partPos]
        if (cm.doc.direction == "ltr" && part.level % 2 == 0 && (dir > 0 ? part.to > start.ch : part.from < start.ch)) {
            // Case 1: We move within an ltr part in an ltr editor. Even with wrapped lines,
            // nothing interesting happens.
            return moveLogically(line, start, dir)
        }

        var mv = function (pos, dir) { return moveCharLogically(line, pos instanceof Pos ? pos.ch : pos, dir); }
        var prep
        var getWrappedLineExtent = function (ch) {
            if (!cm.options.lineWrapping) { return {begin: 0, end: line.text.length} }
            prep = prep || prepareMeasureForLine(cm, line)
            return wrappedLineExtentChar(cm, line, prep, ch)
        }
        var wrappedLineExtent = getWrappedLineExtent(start.sticky == "before" ? mv(start, -1) : start.ch)

        if (cm.doc.direction == "rtl" || part.level == 1) {
            var moveInStorageOrder = (part.level == 1) == (dir < 0)
            var ch = mv(start, moveInStorageOrder ? 1 : -1)
            if (ch != null && (!moveInStorageOrder ? ch >= part.from && ch >= wrappedLineExtent.begin : ch <= part.to && ch <= wrappedLineExtent.end)) {
                // Case 2: We move within an rtl part or in an rtl editor on the same visual line
                var sticky = moveInStorageOrder ? "before" : "after"
                return new Pos(start.line, ch, sticky)
            }
        }

        // Case 3: Could not move within this bidi part in this visual line, so leave
        // the current bidi part

        var searchInVisualLine = function (partPos, dir, wrappedLineExtent) {
            var getRes = function (ch, moveInStorageOrder) { return moveInStorageOrder
                ? new Pos(start.line, mv(ch, 1), "before")
                : new Pos(start.line, ch, "after"); }

            for (; partPos >= 0 && partPos < bidi.length; partPos += dir) {
                var part = bidi[partPos]
                var moveInStorageOrder = (dir > 0) == (part.level != 1)
                var ch = moveInStorageOrder ? wrappedLineExtent.begin : mv(wrappedLineExtent.end, -1)
                if (part.from <= ch && ch < part.to) { return getRes(ch, moveInStorageOrder) }
                ch = moveInStorageOrder ? part.from : mv(part.to, -1)
                if (wrappedLineExtent.begin <= ch && ch < wrappedLineExtent.end) { return getRes(ch, moveInStorageOrder) }
            }
        }

        // Case 3a: Look for other bidi parts on the same visual line
        var res = searchInVisualLine(partPos + dir, dir, wrappedLineExtent)
        if (res) { return res }

        // Case 3b: Look for other bidi parts on the next visual line
        var nextCh = dir > 0 ? wrappedLineExtent.end : mv(wrappedLineExtent.begin, -1)
        if (nextCh != null && !(dir > 0 && nextCh == line.text.length)) {
            res = searchInVisualLine(dir > 0 ? 0 : bidi.length - 1, dir, getWrappedLineExtent(nextCh))
            if (res) { return res }
        }

        // Case 4: Nowhere to move
        return null
    }

// Commands are parameter-less actions that can be performed on an
// editor, mostly used for keybindings.
    var commands = {
        selectAll: selectAll,
        singleSelection: function (cm) { return cm.setSelection(cm.getCursor("anchor"), cm.getCursor("head"), sel_dontScroll); },
        killLine: function (cm) { return deleteNearSelection(cm, function (range) {
            if (range.empty()) {
                var len = getLine(cm.doc, range.head.line).text.length
                if (range.head.ch == len && range.head.line < cm.lastLine())
                { return {from: range.head, to: Pos(range.head.line + 1, 0)} }
                else
                { return {from: range.head, to: Pos(range.head.line, len)} }
            } else {
                return {from: range.from(), to: range.to()}
            }
        }); },
        deleteLine: function (cm) { return deleteNearSelection(cm, function (range) { return ({
            from: Pos(range.from().line, 0),
            to: clipPos(cm.doc, Pos(range.to().line + 1, 0))
        }); }); },
        delLineLeft: function (cm) { return deleteNearSelection(cm, function (range) { return ({
            from: Pos(range.from().line, 0), to: range.from()
        }); }); },
        delWrappedLineLeft: function (cm) { return deleteNearSelection(cm, function (range) {
            var top = cm.charCoords(range.head, "div").top + 5
            var leftPos = cm.coordsChar({left: 0, top: top}, "div")
            return {from: leftPos, to: range.from()}
        }); },
        delWrappedLineRight: function (cm) { return deleteNearSelection(cm, function (range) {
            var top = cm.charCoords(range.head, "div").top + 5
            var rightPos = cm.coordsChar({left: cm.display.lineDiv.offsetWidth + 100, top: top}, "div")
            return {from: range.from(), to: rightPos }
        }); },
        undo: function (cm) { return cm.undo(); },
        redo: function (cm) { return cm.redo(); },
        undoSelection: function (cm) { return cm.undoSelection(); },
        redoSelection: function (cm) { return cm.redoSelection(); },
        goDocStart: function (cm) { return cm.extendSelection(Pos(cm.firstLine(), 0)); },
        goDocEnd: function (cm) { return cm.extendSelection(Pos(cm.lastLine())); },
        goLineStart: function (cm) { return cm.extendSelectionsBy(function (range) { return lineStart(cm, range.head.line); },
            {origin: "+move", bias: 1}
        ); },
        goLineStartSmart: function (cm) { return cm.extendSelectionsBy(function (range) { return lineStartSmart(cm, range.head); },
            {origin: "+move", bias: 1}
        ); },
        goLineEnd: function (cm) { return cm.extendSelectionsBy(function (range) { return lineEnd(cm, range.head.line); },
            {origin: "+move", bias: -1}
        ); },
        goLineRight: function (cm) { return cm.extendSelectionsBy(function (range) {
            var top = cm.cursorCoords(range.head, "div").top + 5
            return cm.coordsChar({left: cm.display.lineDiv.offsetWidth + 100, top: top}, "div")
        }, sel_move); },
        goLineLeft: function (cm) { return cm.extendSelectionsBy(function (range) {
            var top = cm.cursorCoords(range.head, "div").top + 5
            return cm.coordsChar({left: 0, top: top}, "div")
        }, sel_move); },
        goLineLeftSmart: function (cm) { return cm.extendSelectionsBy(function (range) {
            var top = cm.cursorCoords(range.head, "div").top + 5
            var pos = cm.coordsChar({left: 0, top: top}, "div")
            if (pos.ch < cm.getLine(pos.line).search(/\S/)) { return lineStartSmart(cm, range.head) }
            return pos
        }, sel_move); },
        goLineUp: function (cm) { return cm.moveV(-1, "line"); },
        goLineDown: function (cm) { return cm.moveV(1, "line"); },
        goPageUp: function (cm) { return cm.moveV(-1, "page"); },
        goPageDown: function (cm) { return cm.moveV(1, "page"); },
        goCharLeft: function (cm) { return cm.moveH(-1, "char"); },
        goCharRight: function (cm) { return cm.moveH(1, "char"); },
        goColumnLeft: function (cm) { return cm.moveH(-1, "column"); },
        goColumnRight: function (cm) { return cm.moveH(1, "column"); },
        goWordLeft: function (cm) { return cm.moveH(-1, "word"); },
        goGroupRight: function (cm) { return cm.moveH(1, "group"); },
        goGroupLeft: function (cm) { return cm.moveH(-1, "group"); },
        goWordRight: function (cm) { return cm.moveH(1, "word"); },
        delCharBefore: function (cm) { return cm.deleteH(-1, "char"); },
        delCharAfter: function (cm) { return cm.deleteH(1, "char"); },
        delWordBefore: function (cm) { return cm.deleteH(-1, "word"); },
        delWordAfter: function (cm) { return cm.deleteH(1, "word"); },
        delGroupBefore: function (cm) { return cm.deleteH(-1, "group"); },
        delGroupAfter: function (cm) { return cm.deleteH(1, "group"); },
        indentAuto: function (cm) { return cm.indentSelection("smart"); },
        indentMore: function (cm) { return cm.indentSelection("add"); },
        indentLess: function (cm) { return cm.indentSelection("subtract"); },
        insertTab: function (cm) { return cm.replaceSelection("\t"); },
        insertSoftTab: function (cm) {
            var spaces = [], ranges = cm.listSelections(), tabSize = cm.options.tabSize
            for (var i = 0; i < ranges.length; i++) {
                var pos = ranges[i].from()
                var col = countColumn(cm.getLine(pos.line), pos.ch, tabSize)
                spaces.push(spaceStr(tabSize - col % tabSize))
            }
            cm.replaceSelections(spaces)
        },
        defaultTab: function (cm) {
            if (cm.somethingSelected()) { cm.indentSelection("add") }
            else { cm.execCommand("insertTab") }
        },
        // Swap the two chars left and right of each selection's head.
        // Move cursor behind the two swapped characters afterwards.
        //
        // Doesn't consider line feeds a character.
        // Doesn't scan more than one line above to find a character.
        // Doesn't do anything on an empty line.
        // Doesn't do anything with non-empty selections.
        transposeChars: function (cm) { return runInOp(cm, function () {
            var ranges = cm.listSelections(), newSel = []
            for (var i = 0; i < ranges.length; i++) {
                if (!ranges[i].empty()) { continue }
                var cur = ranges[i].head, line = getLine(cm.doc, cur.line).text
                if (line) {
                    if (cur.ch == line.length) { cur = new Pos(cur.line, cur.ch - 1) }
                    if (cur.ch > 0) {
                        cur = new Pos(cur.line, cur.ch + 1)
                        cm.replaceRange(line.charAt(cur.ch - 1) + line.charAt(cur.ch - 2),
                            Pos(cur.line, cur.ch - 2), cur, "+transpose")
                    } else if (cur.line > cm.doc.first) {
                        var prev = getLine(cm.doc, cur.line - 1).text
                        if (prev) {
                            cur = new Pos(cur.line, 1)
                            cm.replaceRange(line.charAt(0) + cm.doc.lineSeparator() +
                                prev.charAt(prev.length - 1),
                                Pos(cur.line - 1, prev.length - 1), cur, "+transpose")
                        }
                    }
                }
                newSel.push(new Range(cur, cur))
            }
            cm.setSelections(newSel)
        }); },
        newlineAndIndent: function (cm) { return runInOp(cm, function () {
            var sels = cm.listSelections()
            for (var i = sels.length - 1; i >= 0; i--)
            { cm.replaceRange(cm.doc.lineSeparator(), sels[i].anchor, sels[i].head, "+input") }
            sels = cm.listSelections()
            for (var i$1 = 0; i$1 < sels.length; i$1++)
            { cm.indentLine(sels[i$1].from().line, null, true) }
            ensureCursorVisible(cm)
        }); },
        openLine: function (cm) { return cm.replaceSelection("\n", "start"); },
        toggleOverwrite: function (cm) { return cm.toggleOverwrite(); }
    }


    function lineStart(cm, lineN) {
        var line = getLine(cm.doc, lineN)
        var visual = visualLine(line)
        if (visual != line) { lineN = lineNo(visual) }
        return endOfLine(true, cm, visual, lineN, 1)
    }
    function lineEnd(cm, lineN) {
        var line = getLine(cm.doc, lineN)
        var visual = visualLineEnd(line)
        if (visual != line) { lineN = lineNo(visual) }
        return endOfLine(true, cm, line, lineN, -1)
    }
    function lineStartSmart(cm, pos) {
        var start = lineStart(cm, pos.line)
        var line = getLine(cm.doc, start.line)
        var order = getOrder(line, cm.doc.direction)
        if (!order || order[0].level == 0) {
            var firstNonWS = Math.max(0, line.text.search(/\S/))
            var inWS = pos.line == start.line && pos.ch <= firstNonWS && pos.ch
            return Pos(start.line, inWS ? 0 : firstNonWS, start.sticky)
        }
        return start
    }

// Run a handler that was bound to a key.
    function doHandleBinding(cm, bound, dropShift) {
        if (typeof bound == "string") {
            bound = commands[bound]
            if (!bound) { return false }
        }
        // Ensure previous input has been read, so that the handler sees a
        // consistent view of the document
        cm.display.input.ensurePolled()
        var prevShift = cm.display.shift, done = false
        try {
            if (cm.isReadOnly()) { cm.state.suppressEdits = true }
            if (dropShift) { cm.display.shift = false }
            done = bound(cm) != Pass
        } finally {
            cm.display.shift = prevShift
            cm.state.suppressEdits = false
        }
        return done
    }

    function lookupKeyForEditor(cm, name, handle) {
        for (var i = 0; i < cm.state.keyMaps.length; i++) {
            var result = lookupKey(name, cm.state.keyMaps[i], handle, cm)
            if (result) { return result }
        }
        return (cm.options.extraKeys && lookupKey(name, cm.options.extraKeys, handle, cm))
            || lookupKey(name, cm.options.keyMap, handle, cm)
    }

// Note that, despite the name, this function is also used to check
// for bound mouse clicks.

    var stopSeq = new Delayed

    function dispatchKey(cm, name, e, handle) {
        var seq = cm.state.keySeq
        if (seq) {
            if (isModifierKey(name)) { return "handled" }
            if (/\'$/.test(name))
            { cm.state.keySeq = null }
            else
            { stopSeq.set(50, function () {
                if (cm.state.keySeq == seq) {
                    cm.state.keySeq = null
                    cm.display.input.reset()
                }
            }) }
            if (dispatchKeyInner(cm, seq + " " + name, e, handle)) { return true }
        }
        return dispatchKeyInner(cm, name, e, handle)
    }

    function dispatchKeyInner(cm, name, e, handle) {
        var result = lookupKeyForEditor(cm, name, handle)

        if (result == "multi")
        { cm.state.keySeq = name }
        if (result == "handled")
        { signalLater(cm, "keyHandled", cm, name, e) }

        if (result == "handled" || result == "multi") {
            e_preventDefault(e)
            restartBlink(cm)
        }

        return !!result
    }

// Handle a key from the keydown event.
    function handleKeyBinding(cm, e) {
        var name = keyName(e, true)
        if (!name) { return false }

        if (e.shiftKey && !cm.state.keySeq) {
            // First try to resolve full name (including 'Shift-'). Failing
            // that, see if there is a cursor-motion command (starting with
            // 'go') bound to the keyname without 'Shift-'.
            return dispatchKey(cm, "Shift-" + name, e, function (b) { return doHandleBinding(cm, b, true); })
                || dispatchKey(cm, name, e, function (b) {
                    if (typeof b == "string" ? /^go[A-Z]/.test(b) : b.motion)
                    { return doHandleBinding(cm, b) }
                })
        } else {
            return dispatchKey(cm, name, e, function (b) { return doHandleBinding(cm, b); })
        }
    }

// Handle a key from the keypress event
    function handleCharBinding(cm, e, ch) {
        return dispatchKey(cm, "'" + ch + "'", e, function (b) { return doHandleBinding(cm, b, true); })
    }

    var lastStoppedKey = null
    function onKeyDown(e) {
        var cm = this
        cm.curOp.focus = activeElt()
        if (signalDOMEvent(cm, e)) { return }
        // IE does strange things with escape.
        if (ie && ie_version < 11 && e.keyCode == 27) { e.returnValue = false }
        var code = e.keyCode
        cm.display.shift = code == 16 || e.shiftKey
        var handled = handleKeyBinding(cm, e)
        if (presto) {
            lastStoppedKey = handled ? code : null
            // Opera has no cut event... we try to at least catch the key combo
            if (!handled && code == 88 && !hasCopyEvent && (mac ? e.metaKey : e.ctrlKey))
            { cm.replaceSelection("", null, "cut") }
        }

        // Turn mouse into crosshair when Alt is held on Mac.
        if (code == 18 && !/\bCodeMirror-crosshair\b/.test(cm.display.lineDiv.className))
        { showCrossHair(cm) }
    }

    function showCrossHair(cm) {
        var lineDiv = cm.display.lineDiv
        addClass(lineDiv, "CodeMirror-crosshair")

        function up(e) {
            if (e.keyCode == 18 || !e.altKey) {
                rmClass(lineDiv, "CodeMirror-crosshair")
                off(document, "keyup", up)
                off(document, "mouseover", up)
            }
        }
        on(document, "keyup", up)
        on(document, "mouseover", up)
    }

    function onKeyUp(e) {
        if (e.keyCode == 16) { this.doc.sel.shift = false }
        signalDOMEvent(this, e)
    }

    function onKeyPress(e) {
        var cm = this
        if (eventInWidget(cm.display, e) || signalDOMEvent(cm, e) || e.ctrlKey && !e.altKey || mac && e.metaKey) { return }
        var keyCode = e.keyCode, charCode = e.charCode
        if (presto && keyCode == lastStoppedKey) {lastStoppedKey = null; e_preventDefault(e); return}
        if ((presto && (!e.which || e.which < 10)) && handleKeyBinding(cm, e)) { return }
        var ch = String.fromCharCode(charCode == null ? keyCode : charCode)
        // Some browsers fire keypress events for backspace
        if (ch == "\x08") { return }
        if (handleCharBinding(cm, e, ch)) { return }
        cm.display.input.onKeyPress(e)
    }

    var DOUBLECLICK_DELAY = 400

    var PastClick = function(time, pos, button) {
        this.time = time
        this.pos = pos
        this.button = button
    };

    PastClick.prototype.compare = function (time, pos, button) {
        return this.time + DOUBLECLICK_DELAY > time &&
            cmp(pos, this.pos) == 0 && button == this.button
    };

    var lastClick;
    var lastDoubleClick;
    function clickRepeat(pos, button) {
        var now = +new Date
        if (lastDoubleClick && lastDoubleClick.compare(now, pos, button)) {
            lastClick = lastDoubleClick = null
            return "triple"
        } else if (lastClick && lastClick.compare(now, pos, button)) {
            lastDoubleClick = new PastClick(now, pos, button)
            lastClick = null
            return "double"
        } else {
            lastClick = new PastClick(now, pos, button)
            lastDoubleClick = null
            return "single"
        }
    }

// A mouse down can be a single click, double click, triple click,
// start of selection drag, start of text drag, new cursor
// (ctrl-click), rectangle drag (alt-drag), or xwin
// middle-click-paste. Or it might be a click on something we should
// not interfere with, such as a scrollbar or widget.
    function onMouseDown(e) {
        var cm = this, display = cm.display
        if (signalDOMEvent(cm, e) || display.activeTouch && display.input.supportsTouch()) { return }
        display.input.ensurePolled()
        display.shift = e.shiftKey

        if (eventInWidget(display, e)) {
            if (!webkit) {
                // Briefly turn off draggability, to allow widgets to do
                // normal dragging things.
                display.scroller.draggable = false
                setTimeout(function () { return display.scroller.draggable = true; }, 100)
            }
            return
        }
        if (clickInGutter(cm, e)) { return }
        var pos = posFromMouse(cm, e), button = e_button(e), repeat = pos ? clickRepeat(pos, button) : "single"
        window.focus()

        // #3261: make sure, that we're not starting a second selection
        if (button == 1 && cm.state.selectingText)
        { cm.state.selectingText(e) }

        if (pos && handleMappedButton(cm, button, pos, repeat, e)) { return }

        if (button == 1) {
            if (pos) { leftButtonDown(cm, pos, repeat, e) }
            else if (e_target(e) == display.scroller) { e_preventDefault(e) }
        } else if (button == 2) {
            if (pos) { extendSelection(cm.doc, pos) }
            setTimeout(function () { return display.input.focus(); }, 20)
        } else if (button == 3) {
            if (captureRightClick) { onContextMenu(cm, e) }
            else { delayBlurEvent(cm) }
        }
    }

    function handleMappedButton(cm, button, pos, repeat, event) {
        var name = "Click"
        if (repeat == "double") { name = "Double" + name }
        else if (repeat == "triple") { name = "Triple" + name }
        name = (button == 1 ? "Left" : button == 2 ? "Middle" : "Right") + name

        return dispatchKey(cm,  addModifierNames(name, event), event, function (bound) {
            if (typeof bound == "string") { bound = commands[bound] }
            if (!bound) { return false }
            var done = false
            try {
                if (cm.isReadOnly()) { cm.state.suppressEdits = true }
                done = bound(cm, pos) != Pass
            } finally {
                cm.state.suppressEdits = false
            }
            return done
        })
    }

    function configureMouse(cm, repeat, event) {
        var option = cm.getOption("configureMouse")
        var value = option ? option(cm, repeat, event) : {}
        if (value.unit == null) {
            var rect = chromeOS ? event.shiftKey && event.metaKey : event.altKey
            value.unit = rect ? "rectangle" : repeat == "single" ? "char" : repeat == "double" ? "word" : "line"
        }
        if (value.extend == null || cm.doc.extend) { value.extend = cm.doc.extend || event.shiftKey }
        if (value.addNew == null) { value.addNew = mac ? event.metaKey : event.ctrlKey }
        if (value.moveOnDrag == null) { value.moveOnDrag = !(mac ? event.altKey : event.ctrlKey) }
        return value
    }

    function leftButtonDown(cm, pos, repeat, event) {
        if (ie) { setTimeout(bind(ensureFocus, cm), 0) }
        else { cm.curOp.focus = activeElt() }

        var behavior = configureMouse(cm, repeat, event)

        var sel = cm.doc.sel, contained
        if (cm.options.dragDrop && dragAndDrop && !cm.isReadOnly() &&
            repeat == "single" && (contained = sel.contains(pos)) > -1 &&
            (cmp((contained = sel.ranges[contained]).from(), pos) < 0 || pos.xRel > 0) &&
            (cmp(contained.to(), pos) > 0 || pos.xRel < 0))
        { leftButtonStartDrag(cm, event, pos, behavior) }
        else
        { leftButtonSelect(cm, event, pos, behavior) }
    }

// Start a text drag. When it ends, see if any dragging actually
// happen, and treat as a click if it didn't.
    function leftButtonStartDrag(cm, event, pos, behavior) {
        var display = cm.display, moved = false
        var dragEnd = operation(cm, function (e) {
            if (webkit) { display.scroller.draggable = false }
            cm.state.draggingText = false
            off(document, "mouseup", dragEnd)
            off(document, "mousemove", mouseMove)
            off(display.scroller, "dragstart", dragStart)
            off(display.scroller, "drop", dragEnd)
            if (!moved) {
                e_preventDefault(e)
                if (!behavior.addNew)
                { extendSelection(cm.doc, pos, null, null, behavior.extend) }
                // Work around unexplainable focus problem in IE9 (#2127) and Chrome (#3081)
                if (webkit || ie && ie_version == 9)
                { setTimeout(function () {document.body.focus(); display.input.focus()}, 20) }
                else
                { display.input.focus() }
            }
        })
        var mouseMove = function(e2) {
            moved = moved || Math.abs(event.clientX - e2.clientX) + Math.abs(event.clientY - e2.clientY) >= 10
        }
        var dragStart = function () { return moved = true; }
        // Let the drag handler handle this.
        if (webkit) { display.scroller.draggable = true }
        cm.state.draggingText = dragEnd
        dragEnd.copy = !behavior.moveOnDrag
        // IE's approach to draggable
        if (display.scroller.dragDrop) { display.scroller.dragDrop() }
        on(document, "mouseup", dragEnd)
        on(document, "mousemove", mouseMove)
        on(display.scroller, "dragstart", dragStart)
        on(display.scroller, "drop", dragEnd)

        delayBlurEvent(cm)
        setTimeout(function () { return display.input.focus(); }, 20)
    }

    function rangeForUnit(cm, pos, unit) {
        if (unit == "char") { return new Range(pos, pos) }
        if (unit == "word") { return cm.findWordAt(pos) }
        if (unit == "line") { return new Range(Pos(pos.line, 0), clipPos(cm.doc, Pos(pos.line + 1, 0))) }
        var result = unit(cm, pos)
        return new Range(result.from, result.to)
    }

// Normal selection, as opposed to text dragging.
    function leftButtonSelect(cm, event, start, behavior) {
        var display = cm.display, doc = cm.doc
        e_preventDefault(event)

        var ourRange, ourIndex, startSel = doc.sel, ranges = startSel.ranges
        if (behavior.addNew && !behavior.extend) {
            ourIndex = doc.sel.contains(start)
            if (ourIndex > -1)
            { ourRange = ranges[ourIndex] }
            else
            { ourRange = new Range(start, start) }
        } else {
            ourRange = doc.sel.primary()
            ourIndex = doc.sel.primIndex
        }

        if (behavior.unit == "rectangle") {
            if (!behavior.addNew) { ourRange = new Range(start, start) }
            start = posFromMouse(cm, event, true, true)
            ourIndex = -1
        } else {
            var range = rangeForUnit(cm, start, behavior.unit)
            if (behavior.extend)
            { ourRange = extendRange(ourRange, range.anchor, range.head, behavior.extend) }
            else
            { ourRange = range }
        }

        if (!behavior.addNew) {
            ourIndex = 0
            setSelection(doc, new Selection([ourRange], 0), sel_mouse)
            startSel = doc.sel
        } else if (ourIndex == -1) {
            ourIndex = ranges.length
            setSelection(doc, normalizeSelection(ranges.concat([ourRange]), ourIndex),
                {scroll: false, origin: "*mouse"})
        } else if (ranges.length > 1 && ranges[ourIndex].empty() && behavior.unit == "char" && !behavior.extend) {
            setSelection(doc, normalizeSelection(ranges.slice(0, ourIndex).concat(ranges.slice(ourIndex + 1)), 0),
                {scroll: false, origin: "*mouse"})
            startSel = doc.sel
        } else {
            replaceOneSelection(doc, ourIndex, ourRange, sel_mouse)
        }

        var lastPos = start
        function extendTo(pos) {
            if (cmp(lastPos, pos) == 0) { return }
            lastPos = pos

            if (behavior.unit == "rectangle") {
                var ranges = [], tabSize = cm.options.tabSize
                var startCol = countColumn(getLine(doc, start.line).text, start.ch, tabSize)
                var posCol = countColumn(getLine(doc, pos.line).text, pos.ch, tabSize)
                var left = Math.min(startCol, posCol), right = Math.max(startCol, posCol)
                for (var line = Math.min(start.line, pos.line), end = Math.min(cm.lastLine(), Math.max(start.line, pos.line));
                     line <= end; line++) {
                    var text = getLine(doc, line).text, leftPos = findColumn(text, left, tabSize)
                    if (left == right)
                    { ranges.push(new Range(Pos(line, leftPos), Pos(line, leftPos))) }
                    else if (text.length > leftPos)
                    { ranges.push(new Range(Pos(line, leftPos), Pos(line, findColumn(text, right, tabSize)))) }
                }
                if (!ranges.length) { ranges.push(new Range(start, start)) }
                setSelection(doc, normalizeSelection(startSel.ranges.slice(0, ourIndex).concat(ranges), ourIndex),
                    {origin: "*mouse", scroll: false})
                cm.scrollIntoView(pos)
            } else {
                var oldRange = ourRange
                var range = rangeForUnit(cm, pos, behavior.unit)
                var anchor = oldRange.anchor, head
                if (cmp(range.anchor, anchor) > 0) {
                    head = range.head
                    anchor = minPos(oldRange.from(), range.anchor)
                } else {
                    head = range.anchor
                    anchor = maxPos(oldRange.to(), range.head)
                }
                var ranges$1 = startSel.ranges.slice(0)
                ranges$1[ourIndex] = bidiSimplify(cm, new Range(clipPos(doc, anchor), head))
                setSelection(doc, normalizeSelection(ranges$1, ourIndex), sel_mouse)
            }
        }

        var editorSize = display.wrapper.getBoundingClientRect()
        // Used to ensure timeout re-tries don't fire when another extend
        // happened in the meantime (clearTimeout isn't reliable -- at
        // least on Chrome, the timeouts still happen even when cleared,
        // if the clear happens after their scheduled firing time).
        var counter = 0

        function extend(e) {
            var curCount = ++counter
            var cur = posFromMouse(cm, e, true, behavior.unit == "rectangle")
            if (!cur) { return }
            if (cmp(cur, lastPos) != 0) {
                cm.curOp.focus = activeElt()
                extendTo(cur)
                var visible = visibleLines(display, doc)
                if (cur.line >= visible.to || cur.line < visible.from)
                { setTimeout(operation(cm, function () {if (counter == curCount) { extend(e) }}), 150) }
            } else {
                var outside = e.clientY < editorSize.top ? -20 : e.clientY > editorSize.bottom ? 20 : 0
                if (outside) { setTimeout(operation(cm, function () {
                    if (counter != curCount) { return }
                    display.scroller.scrollTop += outside
                    extend(e)
                }), 50) }
            }
        }

        function done(e) {
            cm.state.selectingText = false
            counter = Infinity
            e_preventDefault(e)
            display.input.focus()
            off(document, "mousemove", move)
            off(document, "mouseup", up)
            doc.history.lastSelOrigin = null
        }

        var move = operation(cm, function (e) {
            if (!e_button(e)) { done(e) }
            else { extend(e) }
        })
        var up = operation(cm, done)
        cm.state.selectingText = up
        on(document, "mousemove", move)
        on(document, "mouseup", up)
    }

// Used when mouse-selecting to adjust the anchor to the proper side
// of a bidi jump depending on the visual position of the head.
    function bidiSimplify(cm, range) {
        var anchor = range.anchor;
        var head = range.head;
        var anchorLine = getLine(cm.doc, anchor.line)
        if (cmp(anchor, head) == 0 && anchor.sticky == head.sticky) { return range }
        var order = getOrder(anchorLine)
        if (!order) { return range }
        var index = getBidiPartAt(order, anchor.ch, anchor.sticky), part = order[index]
        if (part.from != anchor.ch && part.to != anchor.ch) { return range }
        var boundary = index + ((part.from == anchor.ch) == (part.level != 1) ? 0 : 1)
        if (boundary == 0 || boundary == order.length) { return range }

        // Compute the relative visual position of the head compared to the
        // anchor (<0 is to the left, >0 to the right)
        var leftSide
        if (head.line != anchor.line) {
            leftSide = (head.line - anchor.line) * (cm.doc.direction == "ltr" ? 1 : -1) > 0
        } else {
            var headIndex = getBidiPartAt(order, head.ch, head.sticky)
            var dir = headIndex - index || (head.ch - anchor.ch) * (part.level == 1 ? -1 : 1)
            if (headIndex == boundary - 1 || headIndex == boundary)
            { leftSide = dir < 0 }
            else
            { leftSide = dir > 0 }
        }

        var usePart = order[boundary + (leftSide ? -1 : 0)]
        var from = leftSide == (usePart.level == 1)
        var ch = from ? usePart.from : usePart.to, sticky = from ? "after" : "before"
        return anchor.ch == ch && anchor.sticky == sticky ? range : new Range(new Pos(anchor.line, ch, sticky), head)
    }


// Determines whether an event happened in the gutter, and fires the
// handlers for the corresponding event.
    function gutterEvent(cm, e, type, prevent) {
        var mX, mY
        if (e.touches) {
            mX = e.touches[0].clientX
            mY = e.touches[0].clientY
        } else {
            try { mX = e.clientX; mY = e.clientY }
            catch(e) { return false }
        }
        if (mX >= Math.floor(cm.display.gutters.getBoundingClientRect().right)) { return false }
        if (prevent) { e_preventDefault(e) }

        var display = cm.display
        var lineBox = display.lineDiv.getBoundingClientRect()

        if (mY > lineBox.bottom || !hasHandler(cm, type)) { return e_defaultPrevented(e) }
        mY -= lineBox.top - display.viewOffset

        for (var i = 0; i < cm.options.gutters.length; ++i) {
            var g = display.gutters.childNodes[i]
            if (g && g.getBoundingClientRect().right >= mX) {
                var line = lineAtHeight(cm.doc, mY)
                var gutter = cm.options.gutters[i]
                signal(cm, type, cm, line, gutter, e)
                return e_defaultPrevented(e)
            }
        }
    }

    function clickInGutter(cm, e) {
        return gutterEvent(cm, e, "gutterClick", true)
    }

// CONTEXT MENU HANDLING

// To make the context menu work, we need to briefly unhide the
// textarea (making it as unobtrusive as possible) to let the
// right-click take effect on it.
    function onContextMenu(cm, e) {
        if (eventInWidget(cm.display, e) || contextMenuInGutter(cm, e)) { return }
        if (signalDOMEvent(cm, e, "contextmenu")) { return }
        cm.display.input.onContextMenu(e)
    }

    function contextMenuInGutter(cm, e) {
        if (!hasHandler(cm, "gutterContextMenu")) { return false }
        return gutterEvent(cm, e, "gutterContextMenu", false)
    }

    function themeChanged(cm) {
        cm.display.wrapper.className = cm.display.wrapper.className.replace(/\s*cm-s-\S+/g, "") +
            cm.options.theme.replace(/(^|\s)\s*/g, " cm-s-")
        clearCaches(cm)
    }

    var Init = {toString: function(){return "CodeMirror.Init"}}

    var defaults = {}
    var optionHandlers = {}

    function defineOptions(CodeMirror) {
        var optionHandlers = CodeMirror.optionHandlers

        function option(name, deflt, handle, notOnInit) {
            CodeMirror.defaults[name] = deflt
            if (handle) { optionHandlers[name] =
                notOnInit ? function (cm, val, old) {if (old != Init) { handle(cm, val, old) }} : handle }
        }

        CodeMirror.defineOption = option

        // Passed to option handlers when there is no old value.
        CodeMirror.Init = Init

        // These two are, on init, called from the constructor because they
        // have to be initialized before the editor can start at all.
        option("value", "", function (cm, val) { return cm.setValue(val); }, true)
        option("mode", null, function (cm, val) {
            cm.doc.modeOption = val
            loadMode(cm)
        }, true)

        option("indentUnit", 2, loadMode, true)
        option("indentWithTabs", false)
        option("smartIndent", true)
        option("tabSize", 4, function (cm) {
            resetModeState(cm)
            clearCaches(cm)
            regChange(cm)
        }, true)
        option("lineSeparator", null, function (cm, val) {
            cm.doc.lineSep = val
            if (!val) { return }
            var newBreaks = [], lineNo = cm.doc.first
            cm.doc.iter(function (line) {
                for (var pos = 0;;) {
                    var found = line.text.indexOf(val, pos)
                    if (found == -1) { break }
                    pos = found + val.length
                    newBreaks.push(Pos(lineNo, found))
                }
                lineNo++
            })
            for (var i = newBreaks.length - 1; i >= 0; i--)
            { replaceRange(cm.doc, val, newBreaks[i], Pos(newBreaks[i].line, newBreaks[i].ch + val.length)) }
        })
        option("specialChars", /[\u0000-\u001f\u007f-\u009f\u00ad\u061c\u200b-\u200f\u2028\u2029\ufeff]/g, function (cm, val, old) {
            cm.state.specialChars = new RegExp(val.source + (val.test("\t") ? "" : "|\t"), "g")
            if (old != Init) { cm.refresh() }
        })
        option("specialCharPlaceholder", defaultSpecialCharPlaceholder, function (cm) { return cm.refresh(); }, true)
        option("electricChars", true)
        option("inputStyle", mobile ? "contenteditable" : "textarea", function () {
            throw new Error("inputStyle can not (yet) be changed in a running editor") // FIXME
        }, true)
        option("spellcheck", false, function (cm, val) { return cm.getInputField().spellcheck = val; }, true)
        option("rtlMoveVisually", !windows)
        option("wholeLineUpdateBefore", true)

        option("theme", "default", function (cm) {
            themeChanged(cm)
            guttersChanged(cm)
        }, true)
        option("keyMap", "default", function (cm, val, old) {
            var next = getKeyMap(val)
            var prev = old != Init && getKeyMap(old)
            if (prev && prev.detach) { prev.detach(cm, next) }
            if (next.attach) { next.attach(cm, prev || null) }
        })
        option("extraKeys", null)
        option("configureMouse", null)

        option("lineWrapping", false, wrappingChanged, true)
        option("gutters", [], function (cm) {
            setGuttersForLineNumbers(cm.options)
            guttersChanged(cm)
        }, true)
        option("fixedGutter", true, function (cm, val) {
            cm.display.gutters.style.left = val ? compensateForHScroll(cm.display) + "px" : "0"
            cm.refresh()
        }, true)
        option("coverGutterNextToScrollbar", false, function (cm) { return updateScrollbars(cm); }, true)
        option("scrollbarStyle", "native", function (cm) {
            initScrollbars(cm)
            updateScrollbars(cm)
            cm.display.scrollbars.setScrollTop(cm.doc.scrollTop)
            cm.display.scrollbars.setScrollLeft(cm.doc.scrollLeft)
        }, true)
        option("lineNumbers", false, function (cm) {
            setGuttersForLineNumbers(cm.options)
            guttersChanged(cm)
        }, true)
        option("firstLineNumber", 1, guttersChanged, true)
        option("lineNumberFormatter", function (integer) { return integer; }, guttersChanged, true)
        option("showCursorWhenSelecting", false, updateSelection, true)

        option("resetSelectionOnContextMenu", true)
        option("lineWiseCopyCut", true)
        option("pasteLinesPerSelection", true)

        option("readOnly", false, function (cm, val) {
            if (val == "nocursor") {
                onBlur(cm)
                cm.display.input.blur()
            }
            cm.display.input.readOnlyChanged(val)
        })
        option("disableInput", false, function (cm, val) {if (!val) { cm.display.input.reset() }}, true)
        option("dragDrop", true, dragDropChanged)
        option("allowDropFileTypes", null)

        option("cursorBlinkRate", 530)
        option("cursorScrollMargin", 0)
        option("cursorHeight", 1, updateSelection, true)
        option("singleCursorHeightPerLine", true, updateSelection, true)
        option("workTime", 100)
        option("workDelay", 100)
        option("flattenSpans", true, resetModeState, true)
        option("addModeClass", false, resetModeState, true)
        option("pollInterval", 100)
        option("undoDepth", 200, function (cm, val) { return cm.doc.history.undoDepth = val; })
        option("historyEventDelay", 1250)
        option("viewportMargin", 10, function (cm) { return cm.refresh(); }, true)
        option("maxHighlightLength", 10000, resetModeState, true)
        option("moveInputWithCursor", true, function (cm, val) {
            if (!val) { cm.display.input.resetPosition() }
        })

        option("tabindex", null, function (cm, val) { return cm.display.input.getField().tabIndex = val || ""; })
        option("autofocus", null)
        option("direction", "ltr", function (cm, val) { return cm.doc.setDirection(val); }, true)
    }

    function guttersChanged(cm) {
        updateGutters(cm)
        regChange(cm)
        alignHorizontally(cm)
    }

    function dragDropChanged(cm, value, old) {
        var wasOn = old && old != Init
        if (!value != !wasOn) {
            var funcs = cm.display.dragFunctions
            var toggle = value ? on : off
            toggle(cm.display.scroller, "dragstart", funcs.start)
            toggle(cm.display.scroller, "dragenter", funcs.enter)
            toggle(cm.display.scroller, "dragover", funcs.over)
            toggle(cm.display.scroller, "dragleave", funcs.leave)
            toggle(cm.display.scroller, "drop", funcs.drop)
        }
    }

    function wrappingChanged(cm) {
        if (cm.options.lineWrapping) {
            addClass(cm.display.wrapper, "CodeMirror-wrap")
            cm.display.sizer.style.minWidth = ""
            cm.display.sizerWidth = null
        } else {
            rmClass(cm.display.wrapper, "CodeMirror-wrap")
            findMaxLine(cm)
        }
        estimateLineHeights(cm)
        regChange(cm)
        clearCaches(cm)
        setTimeout(function () { return updateScrollbars(cm); }, 100)
    }

// A CodeMirror instance represents an editor. This is the object
// that user code is usually dealing with.

    function CodeMirror(place, options) {
        var this$1 = this;

        if (!(this instanceof CodeMirror)) { return new CodeMirror(place, options) }

        this.options = options = options ? copyObj(options) : {}
        // Determine effective options based on given values and defaults.
        copyObj(defaults, options, false)
        setGuttersForLineNumbers(options)

        var doc = options.value
        if (typeof doc == "string") { doc = new Doc(doc, options.mode, null, options.lineSeparator, options.direction) }
        this.doc = doc

        var input = new CodeMirror.inputStyles[options.inputStyle](this)
        var display = this.display = new Display(place, doc, input)
        display.wrapper.CodeMirror = this
        updateGutters(this)
        themeChanged(this)
        if (options.lineWrapping)
        { this.display.wrapper.className += " CodeMirror-wrap" }
        initScrollbars(this)

        this.state = {
            keyMaps: [],  // stores maps added by addKeyMap
            overlays: [], // highlighting overlays, as added by addOverlay
            modeGen: 0,   // bumped when mode/overlay changes, used to invalidate highlighting info
            overwrite: false,
            delayingBlurEvent: false,
            focused: false,
            suppressEdits: false, // used to disable editing during key handlers when in readOnly mode
            pasteIncoming: false, cutIncoming: false, // help recognize paste/cut edits in input.poll
            selectingText: false,
            draggingText: false,
            highlight: new Delayed(), // stores highlight worker timeout
            keySeq: null,  // Unfinished key sequence
            specialChars: null
        }

        if (options.autofocus && !mobile) { display.input.focus() }

        // Override magic textarea content restore that IE sometimes does
        // on our hidden textarea on reload
        if (ie && ie_version < 11) { setTimeout(function () { return this$1.display.input.reset(true); }, 20) }

        registerEventHandlers(this)
        ensureGlobalHandlers()

        startOperation(this)
        this.curOp.forceUpdate = true
        attachDoc(this, doc)

        if ((options.autofocus && !mobile) || this.hasFocus())
        { setTimeout(bind(onFocus, this), 20) }
        else
        { onBlur(this) }

        for (var opt in optionHandlers) { if (optionHandlers.hasOwnProperty(opt))
        { optionHandlers[opt](this$1, options[opt], Init) } }
        maybeUpdateLineNumberWidth(this)
        if (options.finishInit) { options.finishInit(this) }
        for (var i = 0; i < initHooks.length; ++i) { initHooks[i](this$1) }
        endOperation(this)
        // Suppress optimizelegibility in Webkit, since it breaks text
        // measuring on line wrapping boundaries.
        if (webkit && options.lineWrapping &&
            getComputedStyle(display.lineDiv).textRendering == "optimizelegibility")
        { display.lineDiv.style.textRendering = "auto" }
    }

// The default configuration options.
    CodeMirror.defaults = defaults
// Functions to run when options are changed.
    CodeMirror.optionHandlers = optionHandlers

// Attach the necessary event handlers when initializing the editor
    function registerEventHandlers(cm) {
        var d = cm.display
        on(d.scroller, "mousedown", operation(cm, onMouseDown))
        // Older IE's will not fire a second mousedown for a double click
        if (ie && ie_version < 11)
        { on(d.scroller, "dblclick", operation(cm, function (e) {
            if (signalDOMEvent(cm, e)) { return }
            var pos = posFromMouse(cm, e)
            if (!pos || clickInGutter(cm, e) || eventInWidget(cm.display, e)) { return }
            e_preventDefault(e)
            var word = cm.findWordAt(pos)
            extendSelection(cm.doc, word.anchor, word.head)
        })) }
        else
        { on(d.scroller, "dblclick", function (e) { return signalDOMEvent(cm, e) || e_preventDefault(e); }) }
        // Some browsers fire contextmenu *after* opening the menu, at
        // which point we can't mess with it anymore. Context menu is
        // handled in onMouseDown for these browsers.
        if (!captureRightClick) { on(d.scroller, "contextmenu", function (e) { return onContextMenu(cm, e); }) }

        // Used to suppress mouse event handling when a touch happens
        var touchFinished, prevTouch = {end: 0}
        function finishTouch() {
            if (d.activeTouch) {
                touchFinished = setTimeout(function () { return d.activeTouch = null; }, 1000)
                prevTouch = d.activeTouch
                prevTouch.end = +new Date
            }
        }
        function isMouseLikeTouchEvent(e) {
            if (e.touches.length != 1) { return false }
            var touch = e.touches[0]
            return touch.radiusX <= 1 && touch.radiusY <= 1
        }
        function farAway(touch, other) {
            if (other.left == null) { return true }
            var dx = other.left - touch.left, dy = other.top - touch.top
            return dx * dx + dy * dy > 20 * 20
        }
        on(d.scroller, "touchstart", function (e) {
            if (!signalDOMEvent(cm, e) && !isMouseLikeTouchEvent(e) && !clickInGutter(cm, e)) {
                d.input.ensurePolled()
                clearTimeout(touchFinished)
                var now = +new Date
                d.activeTouch = {start: now, moved: false,
                    prev: now - prevTouch.end <= 300 ? prevTouch : null}
                if (e.touches.length == 1) {
                    d.activeTouch.left = e.touches[0].pageX
                    d.activeTouch.top = e.touches[0].pageY
                }
            }
        })
        on(d.scroller, "touchmove", function () {
            if (d.activeTouch) { d.activeTouch.moved = true }
        })
        on(d.scroller, "touchend", function (e) {
            var touch = d.activeTouch
            if (touch && !eventInWidget(d, e) && touch.left != null &&
                !touch.moved && new Date - touch.start < 300) {
                var pos = cm.coordsChar(d.activeTouch, "page"), range
                if (!touch.prev || farAway(touch, touch.prev)) // Single tap
                { range = new Range(pos, pos) }
                else if (!touch.prev.prev || farAway(touch, touch.prev.prev)) // Double tap
                { range = cm.findWordAt(pos) }
                else // Triple tap
                { range = new Range(Pos(pos.line, 0), clipPos(cm.doc, Pos(pos.line + 1, 0))) }
                cm.setSelection(range.anchor, range.head)
                cm.focus()
                e_preventDefault(e)
            }
            finishTouch()
        })
        on(d.scroller, "touchcancel", finishTouch)

        // Sync scrolling between fake scrollbars and real scrollable
        // area, ensure viewport is updated when scrolling.
        on(d.scroller, "scroll", function () {
            if (d.scroller.clientHeight) {
                updateScrollTop(cm, d.scroller.scrollTop)
                setScrollLeft(cm, d.scroller.scrollLeft, true)
                signal(cm, "scroll", cm)
            }
        })

        // Listen to wheel events in order to try and update the viewport on time.
        on(d.scroller, "mousewheel", function (e) { return onScrollWheel(cm, e); })
        on(d.scroller, "DOMMouseScroll", function (e) { return onScrollWheel(cm, e); })

        // Prevent wrapper from ever scrolling
        on(d.wrapper, "scroll", function () { return d.wrapper.scrollTop = d.wrapper.scrollLeft = 0; })

        d.dragFunctions = {
            enter: function (e) {if (!signalDOMEvent(cm, e)) { e_stop(e) }},
            over: function (e) {if (!signalDOMEvent(cm, e)) { onDragOver(cm, e); e_stop(e) }},
            start: function (e) { return onDragStart(cm, e); },
            drop: operation(cm, onDrop),
            leave: function (e) {if (!signalDOMEvent(cm, e)) { clearDragCursor(cm) }}
        }

        var inp = d.input.getField()
        on(inp, "keyup", function (e) { return onKeyUp.call(cm, e); })
        on(inp, "keydown", operation(cm, onKeyDown))
        on(inp, "keypress", operation(cm, onKeyPress))
        on(inp, "focus", function (e) { return onFocus(cm, e); })
        on(inp, "blur", function (e) { return onBlur(cm, e); })
    }

    var initHooks = []
    CodeMirror.defineInitHook = function (f) { return initHooks.push(f); }

// Indent the given line. The how parameter can be "smart",
// "add"/null, "subtract", or "prev". When aggressive is false
// (typically set to true for forced single-line indents), empty
// lines are not indented, and places where the mode returns Pass
// are left alone.
    function indentLine(cm, n, how, aggressive) {
        var doc = cm.doc, state
        if (how == null) { how = "add" }
        if (how == "smart") {
            // Fall back to "prev" when the mode doesn't have an indentation
            // method.
            if (!doc.mode.indent) { how = "prev" }
            else { state = getContextBefore(cm, n).state }
        }

        var tabSize = cm.options.tabSize
        var line = getLine(doc, n), curSpace = countColumn(line.text, null, tabSize)
        if (line.stateAfter) { line.stateAfter = null }
        var curSpaceString = line.text.match(/^\s*/)[0], indentation
        if (!aggressive && !/\S/.test(line.text)) {
            indentation = 0
            how = "not"
        } else if (how == "smart") {
            indentation = doc.mode.indent(state, line.text.slice(curSpaceString.length), line.text)
            if (indentation == Pass || indentation > 150) {
                if (!aggressive) { return }
                how = "prev"
            }
        }
        if (how == "prev") {
            if (n > doc.first) { indentation = countColumn(getLine(doc, n-1).text, null, tabSize) }
            else { indentation = 0 }
        } else if (how == "add") {
            indentation = curSpace + cm.options.indentUnit
        } else if (how == "subtract") {
            indentation = curSpace - cm.options.indentUnit
        } else if (typeof how == "number") {
            indentation = curSpace + how
        }
        indentation = Math.max(0, indentation)

        var indentString = "", pos = 0
        if (cm.options.indentWithTabs)
        { for (var i = Math.floor(indentation / tabSize); i; --i) {pos += tabSize; indentString += "\t"} }
        if (pos < indentation) { indentString += spaceStr(indentation - pos) }

        if (indentString != curSpaceString) {
            replaceRange(doc, indentString, Pos(n, 0), Pos(n, curSpaceString.length), "+input")
            line.stateAfter = null
            return true
        } else {
            // Ensure that, if the cursor was in the whitespace at the start
            // of the line, it is moved to the end of that space.
            for (var i$1 = 0; i$1 < doc.sel.ranges.length; i$1++) {
                var range = doc.sel.ranges[i$1]
                if (range.head.line == n && range.head.ch < curSpaceString.length) {
                    var pos$1 = Pos(n, curSpaceString.length)
                    replaceOneSelection(doc, i$1, new Range(pos$1, pos$1))
                    break
                }
            }
        }
    }

// This will be set to a {lineWise: bool, text: [string]} object, so
// that, when pasting, we know what kind of selections the copied
// text was made out of.
    var lastCopied = null

    function setLastCopied(newLastCopied) {
        lastCopied = newLastCopied
    }

    function applyTextInput(cm, inserted, deleted, sel, origin) {
        var doc = cm.doc
        cm.display.shift = false
        if (!sel) { sel = doc.sel }

        var paste = cm.state.pasteIncoming || origin == "paste"
        var textLines = splitLinesAuto(inserted), multiPaste = null
        // When pasing N lines into N selections, insert one line per selection
        if (paste && sel.ranges.length > 1) {
            if (lastCopied && lastCopied.text.join("\n") == inserted) {
                if (sel.ranges.length % lastCopied.text.length == 0) {
                    multiPaste = []
                    for (var i = 0; i < lastCopied.text.length; i++)
                    { multiPaste.push(doc.splitLines(lastCopied.text[i])) }
                }
            } else if (textLines.length == sel.ranges.length && cm.options.pasteLinesPerSelection) {
                multiPaste = map(textLines, function (l) { return [l]; })
            }
        }

        var updateInput
        // Normal behavior is to insert the new text into every selection
        for (var i$1 = sel.ranges.length - 1; i$1 >= 0; i$1--) {
            var range = sel.ranges[i$1]
            var from = range.from(), to = range.to()
            if (range.empty()) {
                if (deleted && deleted > 0) // Handle deletion
                { from = Pos(from.line, from.ch - deleted) }
                else if (cm.state.overwrite && !paste) // Handle overwrite
                { to = Pos(to.line, Math.min(getLine(doc, to.line).text.length, to.ch + lst(textLines).length)) }
                else if (lastCopied && lastCopied.lineWise && lastCopied.text.join("\n") == inserted)
                { from = to = Pos(from.line, 0) }
            }
            updateInput = cm.curOp.updateInput
            var changeEvent = {from: from, to: to, text: multiPaste ? multiPaste[i$1 % multiPaste.length] : textLines,
                origin: origin || (paste ? "paste" : cm.state.cutIncoming ? "cut" : "+input")}
            makeChange(cm.doc, changeEvent)
            signalLater(cm, "inputRead", cm, changeEvent)
        }
        if (inserted && !paste)
        { triggerElectric(cm, inserted) }

        ensureCursorVisible(cm)
        cm.curOp.updateInput = updateInput
        cm.curOp.typing = true
        cm.state.pasteIncoming = cm.state.cutIncoming = false
    }

    function handlePaste(e, cm) {
        var pasted = e.clipboardData && e.clipboardData.getData("Text")
        if (pasted) {
            e.preventDefault()
            if (!cm.isReadOnly() && !cm.options.disableInput)
            { runInOp(cm, function () { return applyTextInput(cm, pasted, 0, null, "paste"); }) }
            return true
        }
    }

    function triggerElectric(cm, inserted) {
        // When an 'electric' character is inserted, immediately trigger a reindent
        if (!cm.options.electricChars || !cm.options.smartIndent) { return }
        var sel = cm.doc.sel

        for (var i = sel.ranges.length - 1; i >= 0; i--) {
            var range = sel.ranges[i]
            if (range.head.ch > 100 || (i && sel.ranges[i - 1].head.line == range.head.line)) { continue }
            var mode = cm.getModeAt(range.head)
            var indented = false
            if (mode.electricChars) {
                for (var j = 0; j < mode.electricChars.length; j++)
                { if (inserted.indexOf(mode.electricChars.charAt(j)) > -1) {
                    indented = indentLine(cm, range.head.line, "smart")
                    break
                } }
            } else if (mode.electricInput) {
                if (mode.electricInput.test(getLine(cm.doc, range.head.line).text.slice(0, range.head.ch)))
                { indented = indentLine(cm, range.head.line, "smart") }
            }
            if (indented) { signalLater(cm, "electricInput", cm, range.head.line) }
        }
    }

    function copyableRanges(cm) {
        var text = [], ranges = []
        for (var i = 0; i < cm.doc.sel.ranges.length; i++) {
            var line = cm.doc.sel.ranges[i].head.line
            var lineRange = {anchor: Pos(line, 0), head: Pos(line + 1, 0)}
            ranges.push(lineRange)
            text.push(cm.getRange(lineRange.anchor, lineRange.head))
        }
        return {text: text, ranges: ranges}
    }

    function disableBrowserMagic(field, spellcheck) {
        field.setAttribute("autocorrect", "off")
        field.setAttribute("autocapitalize", "off")
        field.setAttribute("spellcheck", !!spellcheck)
    }

    function hiddenTextarea() {
        var te = elt("textarea", null, null, "position: absolute; bottom: -1em; padding: 0; width: 1px; height: 1em; outline: none")
        var div = elt("div", [te], null, "overflow: hidden; position: relative; width: 3px; height: 0px;")
        // The textarea is kept positioned near the cursor to prevent the
        // fact that it'll be scrolled into view on input from scrolling
        // our fake cursor out of view. On webkit, when wrap=off, paste is
        // very slow. So make the area wide instead.
        if (webkit) { te.style.width = "1000px" }
        else { te.setAttribute("wrap", "off") }
        // If border: 0; -- iOS fails to open keyboard (issue #1287)
        if (ios) { te.style.border = "1px solid black" }
        disableBrowserMagic(te)
        return div
    }

// The publicly visible API. Note that methodOp(f) means
// 'wrap f in an operation, performed on its `this` parameter'.

// This is not the complete set of editor methods. Most of the
// methods defined on the Doc type are also injected into
// CodeMirror.prototype, for backwards compatibility and
// convenience.

    function addEditorMethods(CodeMirror) {
        var optionHandlers = CodeMirror.optionHandlers

        var helpers = CodeMirror.helpers = {}

        CodeMirror.prototype = {
            constructor: CodeMirror,
            focus: function(){window.focus(); this.display.input.focus()},

            setOption: function(option, value) {
                var options = this.options, old = options[option]
                if (options[option] == value && option != "mode") { return }
                options[option] = value
                if (optionHandlers.hasOwnProperty(option))
                { operation(this, optionHandlers[option])(this, value, old) }
                signal(this, "optionChange", this, option)
            },

            getOption: function(option) {return this.options[option]},
            getDoc: function() {return this.doc},

            addKeyMap: function(map, bottom) {
                this.state.keyMaps[bottom ? "push" : "unshift"](getKeyMap(map))
            },
            removeKeyMap: function(map) {
                var maps = this.state.keyMaps
                for (var i = 0; i < maps.length; ++i)
                { if (maps[i] == map || maps[i].name == map) {
                    maps.splice(i, 1)
                    return true
                } }
            },

            addOverlay: methodOp(function(spec, options) {
                var mode = spec.token ? spec : CodeMirror.getMode(this.options, spec)
                if (mode.startState) { throw new Error("Overlays may not be stateful.") }
                insertSorted(this.state.overlays,
                    {mode: mode, modeSpec: spec, opaque: options && options.opaque,
                        priority: (options && options.priority) || 0},
                    function (overlay) { return overlay.priority; })
                this.state.modeGen++
                regChange(this)
            }),
            removeOverlay: methodOp(function(spec) {
                var this$1 = this;

                var overlays = this.state.overlays
                for (var i = 0; i < overlays.length; ++i) {
                    var cur = overlays[i].modeSpec
                    if (cur == spec || typeof spec == "string" && cur.name == spec) {
                        overlays.splice(i, 1)
                        this$1.state.modeGen++
                        regChange(this$1)
                        return
                    }
                }
            }),

            indentLine: methodOp(function(n, dir, aggressive) {
                if (typeof dir != "string" && typeof dir != "number") {
                    if (dir == null) { dir = this.options.smartIndent ? "smart" : "prev" }
                    else { dir = dir ? "add" : "subtract" }
                }
                if (isLine(this.doc, n)) { indentLine(this, n, dir, aggressive) }
            }),
            indentSelection: methodOp(function(how) {
                var this$1 = this;

                var ranges = this.doc.sel.ranges, end = -1
                for (var i = 0; i < ranges.length; i++) {
                    var range = ranges[i]
                    if (!range.empty()) {
                        var from = range.from(), to = range.to()
                        var start = Math.max(end, from.line)
                        end = Math.min(this$1.lastLine(), to.line - (to.ch ? 0 : 1)) + 1
                        for (var j = start; j < end; ++j)
                        { indentLine(this$1, j, how) }
                        var newRanges = this$1.doc.sel.ranges
                        if (from.ch == 0 && ranges.length == newRanges.length && newRanges[i].from().ch > 0)
                        { replaceOneSelection(this$1.doc, i, new Range(from, newRanges[i].to()), sel_dontScroll) }
                    } else if (range.head.line > end) {
                        indentLine(this$1, range.head.line, how, true)
                        end = range.head.line
                        if (i == this$1.doc.sel.primIndex) { ensureCursorVisible(this$1) }
                    }
                }
            }),

            // Fetch the parser token for a given character. Useful for hacks
            // that want to inspect the mode state (say, for completion).
            getTokenAt: function(pos, precise) {
                return takeToken(this, pos, precise)
            },

            getLineTokens: function(line, precise) {
                return takeToken(this, Pos(line), precise, true)
            },

            getTokenTypeAt: function(pos) {
                pos = clipPos(this.doc, pos)
                var styles = getLineStyles(this, getLine(this.doc, pos.line))
                var before = 0, after = (styles.length - 1) / 2, ch = pos.ch
                var type
                if (ch == 0) { type = styles[2] }
                else { for (;;) {
                    var mid = (before + after) >> 1
                    if ((mid ? styles[mid * 2 - 1] : 0) >= ch) { after = mid }
                    else if (styles[mid * 2 + 1] < ch) { before = mid + 1 }
                    else { type = styles[mid * 2 + 2]; break }
                } }
                var cut = type ? type.indexOf("overlay ") : -1
                return cut < 0 ? type : cut == 0 ? null : type.slice(0, cut - 1)
            },

            getModeAt: function(pos) {
                var mode = this.doc.mode
                if (!mode.innerMode) { return mode }
                return CodeMirror.innerMode(mode, this.getTokenAt(pos).state).mode
            },

            getHelper: function(pos, type) {
                return this.getHelpers(pos, type)[0]
            },

            getHelpers: function(pos, type) {
                var this$1 = this;

                var found = []
                if (!helpers.hasOwnProperty(type)) { return found }
                var help = helpers[type], mode = this.getModeAt(pos)
                if (typeof mode[type] == "string") {
                    if (help[mode[type]]) { found.push(help[mode[type]]) }
                } else if (mode[type]) {
                    for (var i = 0; i < mode[type].length; i++) {
                        var val = help[mode[type][i]]
                        if (val) { found.push(val) }
                    }
                } else if (mode.helperType && help[mode.helperType]) {
                    found.push(help[mode.helperType])
                } else if (help[mode.name]) {
                    found.push(help[mode.name])
                }
                for (var i$1 = 0; i$1 < help._global.length; i$1++) {
                    var cur = help._global[i$1]
                    if (cur.pred(mode, this$1) && indexOf(found, cur.val) == -1)
                    { found.push(cur.val) }
                }
                return found
            },

            getStateAfter: function(line, precise) {
                var doc = this.doc
                line = clipLine(doc, line == null ? doc.first + doc.size - 1: line)
                return getContextBefore(this, line + 1, precise).state
            },

            cursorCoords: function(start, mode) {
                var pos, range = this.doc.sel.primary()
                if (start == null) { pos = range.head }
                else if (typeof start == "object") { pos = clipPos(this.doc, start) }
                else { pos = start ? range.from() : range.to() }
                return cursorCoords(this, pos, mode || "page")
            },

            charCoords: function(pos, mode) {
                return charCoords(this, clipPos(this.doc, pos), mode || "page")
            },

            coordsChar: function(coords, mode) {
                coords = fromCoordSystem(this, coords, mode || "page")
                return coordsChar(this, coords.left, coords.top)
            },

            lineAtHeight: function(height, mode) {
                height = fromCoordSystem(this, {top: height, left: 0}, mode || "page").top
                return lineAtHeight(this.doc, height + this.display.viewOffset)
            },
            heightAtLine: function(line, mode, includeWidgets) {
                var end = false, lineObj
                if (typeof line == "number") {
                    var last = this.doc.first + this.doc.size - 1
                    if (line < this.doc.first) { line = this.doc.first }
                    else if (line > last) { line = last; end = true }
                    lineObj = getLine(this.doc, line)
                } else {
                    lineObj = line
                }
                return intoCoordSystem(this, lineObj, {top: 0, left: 0}, mode || "page", includeWidgets || end).top +
                    (end ? this.doc.height - heightAtLine(lineObj) : 0)
            },

            defaultTextHeight: function() { return textHeight(this.display) },
            defaultCharWidth: function() { return charWidth(this.display) },

            getViewport: function() { return {from: this.display.viewFrom, to: this.display.viewTo}},

            addWidget: function(pos, node, scroll, vert, horiz) {
                var display = this.display
                pos = cursorCoords(this, clipPos(this.doc, pos))
                var top = pos.bottom, left = pos.left
                node.style.position = "absolute"
                node.setAttribute("cm-ignore-events", "true")
                this.display.input.setUneditable(node)
                display.sizer.appendChild(node)
                if (vert == "over") {
                    top = pos.top
                } else if (vert == "above" || vert == "near") {
                    var vspace = Math.max(display.wrapper.clientHeight, this.doc.height),
                        hspace = Math.max(display.sizer.clientWidth, display.lineSpace.clientWidth)
                    // Default to positioning above (if specified and possible); otherwise default to positioning below
                    if ((vert == 'above' || pos.bottom + node.offsetHeight > vspace) && pos.top > node.offsetHeight)
                    { top = pos.top - node.offsetHeight }
                    else if (pos.bottom + node.offsetHeight <= vspace)
                    { top = pos.bottom }
                    if (left + node.offsetWidth > hspace)
                    { left = hspace - node.offsetWidth }
                }
                node.style.top = top + "px"
                node.style.left = node.style.right = ""
                if (horiz == "right") {
                    left = display.sizer.clientWidth - node.offsetWidth
                    node.style.right = "0px"
                } else {
                    if (horiz == "left") { left = 0 }
                    else if (horiz == "middle") { left = (display.sizer.clientWidth - node.offsetWidth) / 2 }
                    node.style.left = left + "px"
                }
                if (scroll)
                { scrollIntoView(this, {left: left, top: top, right: left + node.offsetWidth, bottom: top + node.offsetHeight}) }
            },

            triggerOnKeyDown: methodOp(onKeyDown),
            triggerOnKeyPress: methodOp(onKeyPress),
            triggerOnKeyUp: onKeyUp,
            triggerOnMouseDown: methodOp(onMouseDown),

            execCommand: function(cmd) {
                if (commands.hasOwnProperty(cmd))
                { return commands[cmd].call(null, this) }
            },

            triggerElectric: methodOp(function(text) { triggerElectric(this, text) }),

            findPosH: function(from, amount, unit, visually) {
                var this$1 = this;

                var dir = 1
                if (amount < 0) { dir = -1; amount = -amount }
                var cur = clipPos(this.doc, from)
                for (var i = 0; i < amount; ++i) {
                    cur = findPosH(this$1.doc, cur, dir, unit, visually)
                    if (cur.hitSide) { break }
                }
                return cur
            },

            moveH: methodOp(function(dir, unit) {
                var this$1 = this;

                this.extendSelectionsBy(function (range) {
                    if (this$1.display.shift || this$1.doc.extend || range.empty())
                    { return findPosH(this$1.doc, range.head, dir, unit, this$1.options.rtlMoveVisually) }
                    else
                    { return dir < 0 ? range.from() : range.to() }
                }, sel_move)
            }),

            deleteH: methodOp(function(dir, unit) {
                var sel = this.doc.sel, doc = this.doc
                if (sel.somethingSelected())
                { doc.replaceSelection("", null, "+delete") }
                else
                { deleteNearSelection(this, function (range) {
                    var other = findPosH(doc, range.head, dir, unit, false)
                    return dir < 0 ? {from: other, to: range.head} : {from: range.head, to: other}
                }) }
            }),

            findPosV: function(from, amount, unit, goalColumn) {
                var this$1 = this;

                var dir = 1, x = goalColumn
                if (amount < 0) { dir = -1; amount = -amount }
                var cur = clipPos(this.doc, from)
                for (var i = 0; i < amount; ++i) {
                    var coords = cursorCoords(this$1, cur, "div")
                    if (x == null) { x = coords.left }
                    else { coords.left = x }
                    cur = findPosV(this$1, coords, dir, unit)
                    if (cur.hitSide) { break }
                }
                return cur
            },

            moveV: methodOp(function(dir, unit) {
                var this$1 = this;

                var doc = this.doc, goals = []
                var collapse = !this.display.shift && !doc.extend && doc.sel.somethingSelected()
                doc.extendSelectionsBy(function (range) {
                    if (collapse)
                    { return dir < 0 ? range.from() : range.to() }
                    var headPos = cursorCoords(this$1, range.head, "div")
                    if (range.goalColumn != null) { headPos.left = range.goalColumn }
                    goals.push(headPos.left)
                    var pos = findPosV(this$1, headPos, dir, unit)
                    if (unit == "page" && range == doc.sel.primary())
                    { addToScrollTop(this$1, charCoords(this$1, pos, "div").top - headPos.top) }
                    return pos
                }, sel_move)
                if (goals.length) { for (var i = 0; i < doc.sel.ranges.length; i++)
                { doc.sel.ranges[i].goalColumn = goals[i] } }
            }),

            // Find the word at the given position (as returned by coordsChar).
            findWordAt: function(pos) {
                var doc = this.doc, line = getLine(doc, pos.line).text
                var start = pos.ch, end = pos.ch
                if (line) {
                    var helper = this.getHelper(pos, "wordChars")
                    if ((pos.sticky == "before" || end == line.length) && start) { --start; } else { ++end }
                    var startChar = line.charAt(start)
                    var check = isWordChar(startChar, helper)
                        ? function (ch) { return isWordChar(ch, helper); }
                        : /\s/.test(startChar) ? function (ch) { return /\s/.test(ch); }
                            : function (ch) { return (!/\s/.test(ch) && !isWordChar(ch)); }
                    while (start > 0 && check(line.charAt(start - 1))) { --start }
                    while (end < line.length && check(line.charAt(end))) { ++end }
                }
                return new Range(Pos(pos.line, start), Pos(pos.line, end))
            },

            toggleOverwrite: function(value) {
                if (value != null && value == this.state.overwrite) { return }
                if (this.state.overwrite = !this.state.overwrite)
                { addClass(this.display.cursorDiv, "CodeMirror-overwrite") }
                else
                { rmClass(this.display.cursorDiv, "CodeMirror-overwrite") }

                signal(this, "overwriteToggle", this, this.state.overwrite)
            },
            hasFocus: function() { return this.display.input.getField() == activeElt() },
            isReadOnly: function() { return !!(this.options.readOnly || this.doc.cantEdit) },

            scrollTo: methodOp(function (x, y) { scrollToCoords(this, x, y) }),
            getScrollInfo: function() {
                var scroller = this.display.scroller
                return {left: scroller.scrollLeft, top: scroller.scrollTop,
                    height: scroller.scrollHeight - scrollGap(this) - this.display.barHeight,
                    width: scroller.scrollWidth - scrollGap(this) - this.display.barWidth,
                    clientHeight: displayHeight(this), clientWidth: displayWidth(this)}
            },

            scrollIntoView: methodOp(function(range, margin) {
                if (range == null) {
                    range = {from: this.doc.sel.primary().head, to: null}
                    if (margin == null) { margin = this.options.cursorScrollMargin }
                } else if (typeof range == "number") {
                    range = {from: Pos(range, 0), to: null}
                } else if (range.from == null) {
                    range = {from: range, to: null}
                }
                if (!range.to) { range.to = range.from }
                range.margin = margin || 0

                if (range.from.line != null) {
                    scrollToRange(this, range)
                } else {
                    scrollToCoordsRange(this, range.from, range.to, range.margin)
                }
            }),

            setSize: methodOp(function(width, height) {
                var this$1 = this;

                var interpret = function (val) { return typeof val == "number" || /^\d+$/.test(String(val)) ? val + "px" : val; }
                if (width != null) { this.display.wrapper.style.width = interpret(width) }
                if (height != null) { this.display.wrapper.style.height = interpret(height) }
                if (this.options.lineWrapping) { clearLineMeasurementCache(this) }
                var lineNo = this.display.viewFrom
                this.doc.iter(lineNo, this.display.viewTo, function (line) {
                    if (line.widgets) { for (var i = 0; i < line.widgets.length; i++)
                    { if (line.widgets[i].noHScroll) { regLineChange(this$1, lineNo, "widget"); break } } }
                    ++lineNo
                })
                this.curOp.forceUpdate = true
                signal(this, "refresh", this)
            }),

            operation: function(f){return runInOp(this, f)},
            startOperation: function(){return startOperation(this)},
            endOperation: function(){return endOperation(this)},

            refresh: methodOp(function() {
                var oldHeight = this.display.cachedTextHeight
                regChange(this)
                this.curOp.forceUpdate = true
                clearCaches(this)
                scrollToCoords(this, this.doc.scrollLeft, this.doc.scrollTop)
                updateGutterSpace(this)
                if (oldHeight == null || Math.abs(oldHeight - textHeight(this.display)) > .5)
                { estimateLineHeights(this) }
                signal(this, "refresh", this)
            }),

            swapDoc: methodOp(function(doc) {
                var old = this.doc
                old.cm = null
                attachDoc(this, doc)
                clearCaches(this)
                this.display.input.reset()
                scrollToCoords(this, doc.scrollLeft, doc.scrollTop)
                this.curOp.forceScroll = true
                signalLater(this, "swapDoc", this, old)
                return old
            }),

            getInputField: function(){return this.display.input.getField()},
            getWrapperElement: function(){return this.display.wrapper},
            getScrollerElement: function(){return this.display.scroller},
            getGutterElement: function(){return this.display.gutters}
        }
        eventMixin(CodeMirror)

        CodeMirror.registerHelper = function(type, name, value) {
            if (!helpers.hasOwnProperty(type)) { helpers[type] = CodeMirror[type] = {_global: []} }
            helpers[type][name] = value
        }
        CodeMirror.registerGlobalHelper = function(type, name, predicate, value) {
            CodeMirror.registerHelper(type, name, value)
            helpers[type]._global.push({pred: predicate, val: value})
        }
    }

// Used for horizontal relative motion. Dir is -1 or 1 (left or
// right), unit can be "char", "column" (like char, but doesn't
// cross line boundaries), "word" (across next word), or "group" (to
// the start of next group of word or non-word-non-whitespace
// chars). The visually param controls whether, in right-to-left
// text, direction 1 means to move towards the next index in the
// string, or towards the character to the right of the current
// position. The resulting position will have a hitSide=true
// property if it reached the end of the document.
    function findPosH(doc, pos, dir, unit, visually) {
        var oldPos = pos
        var origDir = dir
        var lineObj = getLine(doc, pos.line)
        function findNextLine() {
            var l = pos.line + dir
            if (l < doc.first || l >= doc.first + doc.size) { return false }
            pos = new Pos(l, pos.ch, pos.sticky)
            return lineObj = getLine(doc, l)
        }
        function moveOnce(boundToLine) {
            var next
            if (visually) {
                next = moveVisually(doc.cm, lineObj, pos, dir)
            } else {
                next = moveLogically(lineObj, pos, dir)
            }
            if (next == null) {
                if (!boundToLine && findNextLine())
                { pos = endOfLine(visually, doc.cm, lineObj, pos.line, dir) }
                else
                { return false }
            } else {
                pos = next
            }
            return true
        }

        if (unit == "char") {
            moveOnce()
        } else if (unit == "column") {
            moveOnce(true)
        } else if (unit == "word" || unit == "group") {
            var sawType = null, group = unit == "group"
            var helper = doc.cm && doc.cm.getHelper(pos, "wordChars")
            for (var first = true;; first = false) {
                if (dir < 0 && !moveOnce(!first)) { break }
                var cur = lineObj.text.charAt(pos.ch) || "\n"
                var type = isWordChar(cur, helper) ? "w"
                    : group && cur == "\n" ? "n"
                        : !group || /\s/.test(cur) ? null
                            : "p"
                if (group && !first && !type) { type = "s" }
                if (sawType && sawType != type) {
                    if (dir < 0) {dir = 1; moveOnce(); pos.sticky = "after"}
                    break
                }

                if (type) { sawType = type }
                if (dir > 0 && !moveOnce(!first)) { break }
            }
        }
        var result = skipAtomic(doc, pos, oldPos, origDir, true)
        if (equalCursorPos(oldPos, result)) { result.hitSide = true }
        return result
    }

// For relative vertical movement. Dir may be -1 or 1. Unit can be
// "page" or "line". The resulting position will have a hitSide=true
// property if it reached the end of the document.
    function findPosV(cm, pos, dir, unit) {
        var doc = cm.doc, x = pos.left, y
        if (unit == "page") {
            var pageSize = Math.min(cm.display.wrapper.clientHeight, window.innerHeight || document.documentElement.clientHeight)
            var moveAmount = Math.max(pageSize - .5 * textHeight(cm.display), 3)
            y = (dir > 0 ? pos.bottom : pos.top) + dir * moveAmount

        } else if (unit == "line") {
            y = dir > 0 ? pos.bottom + 3 : pos.top - 3
        }
        var target
        for (;;) {
            target = coordsChar(cm, x, y)
            if (!target.outside) { break }
            if (dir < 0 ? y <= 0 : y >= doc.height) { target.hitSide = true; break }
            y += dir * 5
        }
        return target
    }

// CONTENTEDITABLE INPUT STYLE

    var ContentEditableInput = function(cm) {
        this.cm = cm
        this.lastAnchorNode = this.lastAnchorOffset = this.lastFocusNode = this.lastFocusOffset = null
        this.polling = new Delayed()
        this.composing = null
        this.gracePeriod = false
        this.readDOMTimeout = null
    };

    ContentEditableInput.prototype.init = function (display) {
        var this$1 = this;

        var input = this, cm = input.cm
        var div = input.div = display.lineDiv
        disableBrowserMagic(div, cm.options.spellcheck)

        on(div, "paste", function (e) {
            if (signalDOMEvent(cm, e) || handlePaste(e, cm)) { return }
            // IE doesn't fire input events, so we schedule a read for the pasted content in this way
            if (ie_version <= 11) { setTimeout(operation(cm, function () { return this$1.updateFromDOM(); }), 20) }
        })

        on(div, "compositionstart", function (e) {
            this$1.composing = {data: e.data, done: false}
        })
        on(div, "compositionupdate", function (e) {
            if (!this$1.composing) { this$1.composing = {data: e.data, done: false} }
        })
        on(div, "compositionend", function (e) {
            if (this$1.composing) {
                if (e.data != this$1.composing.data) { this$1.readFromDOMSoon() }
                this$1.composing.done = true
            }
        })

        on(div, "touchstart", function () { return input.forceCompositionEnd(); })

        on(div, "input", function () {
            if (!this$1.composing) { this$1.readFromDOMSoon() }
        })

        function onCopyCut(e) {
            if (signalDOMEvent(cm, e)) { return }
            if (cm.somethingSelected()) {
                setLastCopied({lineWise: false, text: cm.getSelections()})
                if (e.type == "cut") { cm.replaceSelection("", null, "cut") }
            } else if (!cm.options.lineWiseCopyCut) {
                return
            } else {
                var ranges = copyableRanges(cm)
                setLastCopied({lineWise: true, text: ranges.text})
                if (e.type == "cut") {
                    cm.operation(function () {
                        cm.setSelections(ranges.ranges, 0, sel_dontScroll)
                        cm.replaceSelection("", null, "cut")
                    })
                }
            }
            if (e.clipboardData) {
                e.clipboardData.clearData()
                var content = lastCopied.text.join("\n")
                // iOS exposes the clipboard API, but seems to discard content inserted into it
                e.clipboardData.setData("Text", content)
                if (e.clipboardData.getData("Text") == content) {
                    e.preventDefault()
                    return
                }
            }
            // Old-fashioned briefly-focus-a-textarea hack
            var kludge = hiddenTextarea(), te = kludge.firstChild
            cm.display.lineSpace.insertBefore(kludge, cm.display.lineSpace.firstChild)
            te.value = lastCopied.text.join("\n")
            var hadFocus = document.activeElement
            selectInput(te)
            setTimeout(function () {
                cm.display.lineSpace.removeChild(kludge)
                hadFocus.focus()
                if (hadFocus == div) { input.showPrimarySelection() }
            }, 50)
        }
        on(div, "copy", onCopyCut)
        on(div, "cut", onCopyCut)
    };

    ContentEditableInput.prototype.prepareSelection = function () {
        var result = prepareSelection(this.cm, false)
        result.focus = this.cm.state.focused
        return result
    };

    ContentEditableInput.prototype.showSelection = function (info, takeFocus) {
        if (!info || !this.cm.display.view.length) { return }
        if (info.focus || takeFocus) { this.showPrimarySelection() }
        this.showMultipleSelections(info)
    };

    ContentEditableInput.prototype.showPrimarySelection = function () {
        var sel = window.getSelection(), cm = this.cm, prim = cm.doc.sel.primary()
        var from = prim.from(), to = prim.to()

        if (cm.display.viewTo == cm.display.viewFrom || from.line >= cm.display.viewTo || to.line < cm.display.viewFrom) {
            sel.removeAllRanges()
            return
        }

        var curAnchor = domToPos(cm, sel.anchorNode, sel.anchorOffset)
        var curFocus = domToPos(cm, sel.focusNode, sel.focusOffset)
        if (curAnchor && !curAnchor.bad && curFocus && !curFocus.bad &&
            cmp(minPos(curAnchor, curFocus), from) == 0 &&
            cmp(maxPos(curAnchor, curFocus), to) == 0)
        { return }

        var view = cm.display.view
        var start = (from.line >= cm.display.viewFrom && posToDOM(cm, from)) ||
            {node: view[0].measure.map[2], offset: 0}
        var end = to.line < cm.display.viewTo && posToDOM(cm, to)
        if (!end) {
            var measure = view[view.length - 1].measure
            var map = measure.maps ? measure.maps[measure.maps.length - 1] : measure.map
            end = {node: map[map.length - 1], offset: map[map.length - 2] - map[map.length - 3]}
        }

        if (!start || !end) {
            sel.removeAllRanges()
            return
        }

        var old = sel.rangeCount && sel.getRangeAt(0), rng
        try { rng = range(start.node, start.offset, end.offset, end.node) }
        catch(e) {} // Our model of the DOM might be outdated, in which case the range we try to set can be impossible
        if (rng) {
            if (!gecko && cm.state.focused) {
                sel.collapse(start.node, start.offset)
                if (!rng.collapsed) {
                    sel.removeAllRanges()
                    sel.addRange(rng)
                }
            } else {
                sel.removeAllRanges()
                sel.addRange(rng)
            }
            if (old && sel.anchorNode == null) { sel.addRange(old) }
            else if (gecko) { this.startGracePeriod() }
        }
        this.rememberSelection()
    };

    ContentEditableInput.prototype.startGracePeriod = function () {
        var this$1 = this;

        clearTimeout(this.gracePeriod)
        this.gracePeriod = setTimeout(function () {
            this$1.gracePeriod = false
            if (this$1.selectionChanged())
            { this$1.cm.operation(function () { return this$1.cm.curOp.selectionChanged = true; }) }
        }, 20)
    };

    ContentEditableInput.prototype.showMultipleSelections = function (info) {
        removeChildrenAndAdd(this.cm.display.cursorDiv, info.cursors)
        removeChildrenAndAdd(this.cm.display.selectionDiv, info.selection)
    };

    ContentEditableInput.prototype.rememberSelection = function () {
        var sel = window.getSelection()
        this.lastAnchorNode = sel.anchorNode; this.lastAnchorOffset = sel.anchorOffset
        this.lastFocusNode = sel.focusNode; this.lastFocusOffset = sel.focusOffset
    };

    ContentEditableInput.prototype.selectionInEditor = function () {
        var sel = window.getSelection()
        if (!sel.rangeCount) { return false }
        var node = sel.getRangeAt(0).commonAncestorContainer
        return contains(this.div, node)
    };

    ContentEditableInput.prototype.focus = function () {
        if (this.cm.options.readOnly != "nocursor") {
            if (!this.selectionInEditor())
            { this.showSelection(this.prepareSelection(), true) }
            this.div.focus()
        }
    };
    ContentEditableInput.prototype.blur = function () { this.div.blur() };
    ContentEditableInput.prototype.getField = function () { return this.div };

    ContentEditableInput.prototype.supportsTouch = function () { return true };

    ContentEditableInput.prototype.receivedFocus = function () {
        var input = this
        if (this.selectionInEditor())
        { this.pollSelection() }
        else
        { runInOp(this.cm, function () { return input.cm.curOp.selectionChanged = true; }) }

        function poll() {
            if (input.cm.state.focused) {
                input.pollSelection()
                input.polling.set(input.cm.options.pollInterval, poll)
            }
        }
        this.polling.set(this.cm.options.pollInterval, poll)
    };

    ContentEditableInput.prototype.selectionChanged = function () {
        var sel = window.getSelection()
        return sel.anchorNode != this.lastAnchorNode || sel.anchorOffset != this.lastAnchorOffset ||
            sel.focusNode != this.lastFocusNode || sel.focusOffset != this.lastFocusOffset
    };

    ContentEditableInput.prototype.pollSelection = function () {
        if (this.readDOMTimeout != null || this.gracePeriod || !this.selectionChanged()) { return }
        var sel = window.getSelection(), cm = this.cm
        // On Android Chrome (version 56, at least), backspacing into an
        // uneditable block element will put the cursor in that element,
        // and then, because it's not editable, hide the virtual keyboard.
        // Because Android doesn't allow us to actually detect backspace
        // presses in a sane way, this code checks for when that happens
        // and simulates a backspace press in this case.
        if (android && chrome && this.cm.options.gutters.length && isInGutter(sel.anchorNode)) {
            this.cm.triggerOnKeyDown({type: "keydown", keyCode: 8, preventDefault: Math.abs})
            this.blur()
            this.focus()
            return
        }
        if (this.composing) { return }
        this.rememberSelection()
        var anchor = domToPos(cm, sel.anchorNode, sel.anchorOffset)
        var head = domToPos(cm, sel.focusNode, sel.focusOffset)
        if (anchor && head) { runInOp(cm, function () {
            setSelection(cm.doc, simpleSelection(anchor, head), sel_dontScroll)
            if (anchor.bad || head.bad) { cm.curOp.selectionChanged = true }
        }) }
    };

    ContentEditableInput.prototype.pollContent = function () {
        if (this.readDOMTimeout != null) {
            clearTimeout(this.readDOMTimeout)
            this.readDOMTimeout = null
        }

        var cm = this.cm, display = cm.display, sel = cm.doc.sel.primary()
        var from = sel.from(), to = sel.to()
        if (from.ch == 0 && from.line > cm.firstLine())
        { from = Pos(from.line - 1, getLine(cm.doc, from.line - 1).length) }
        if (to.ch == getLine(cm.doc, to.line).text.length && to.line < cm.lastLine())
        { to = Pos(to.line + 1, 0) }
        if (from.line < display.viewFrom || to.line > display.viewTo - 1) { return false }

        var fromIndex, fromLine, fromNode
        if (from.line == display.viewFrom || (fromIndex = findViewIndex(cm, from.line)) == 0) {
            fromLine = lineNo(display.view[0].line)
            fromNode = display.view[0].node
        } else {
            fromLine = lineNo(display.view[fromIndex].line)
            fromNode = display.view[fromIndex - 1].node.nextSibling
        }
        var toIndex = findViewIndex(cm, to.line)
        var toLine, toNode
        if (toIndex == display.view.length - 1) {
            toLine = display.viewTo - 1
            toNode = display.lineDiv.lastChild
        } else {
            toLine = lineNo(display.view[toIndex + 1].line) - 1
            toNode = display.view[toIndex + 1].node.previousSibling
        }

        if (!fromNode) { return false }
        var newText = cm.doc.splitLines(domTextBetween(cm, fromNode, toNode, fromLine, toLine))
        var oldText = getBetween(cm.doc, Pos(fromLine, 0), Pos(toLine, getLine(cm.doc, toLine).text.length))
        while (newText.length > 1 && oldText.length > 1) {
            if (lst(newText) == lst(oldText)) { newText.pop(); oldText.pop(); toLine-- }
            else if (newText[0] == oldText[0]) { newText.shift(); oldText.shift(); fromLine++ }
            else { break }
        }

        var cutFront = 0, cutEnd = 0
        var newTop = newText[0], oldTop = oldText[0], maxCutFront = Math.min(newTop.length, oldTop.length)
        while (cutFront < maxCutFront && newTop.charCodeAt(cutFront) == oldTop.charCodeAt(cutFront))
        { ++cutFront }
        var newBot = lst(newText), oldBot = lst(oldText)
        var maxCutEnd = Math.min(newBot.length - (newText.length == 1 ? cutFront : 0),
            oldBot.length - (oldText.length == 1 ? cutFront : 0))
        while (cutEnd < maxCutEnd &&
        newBot.charCodeAt(newBot.length - cutEnd - 1) == oldBot.charCodeAt(oldBot.length - cutEnd - 1))
        { ++cutEnd }
        // Try to move start of change to start of selection if ambiguous
        if (newText.length == 1 && oldText.length == 1 && fromLine == from.line) {
            while (cutFront && cutFront > from.ch &&
            newBot.charCodeAt(newBot.length - cutEnd - 1) == oldBot.charCodeAt(oldBot.length - cutEnd - 1)) {
                cutFront--
                cutEnd++
            }
        }

        newText[newText.length - 1] = newBot.slice(0, newBot.length - cutEnd).replace(/^\u200b+/, "")
        newText[0] = newText[0].slice(cutFront).replace(/\u200b+$/, "")

        var chFrom = Pos(fromLine, cutFront)
        var chTo = Pos(toLine, oldText.length ? lst(oldText).length - cutEnd : 0)
        if (newText.length > 1 || newText[0] || cmp(chFrom, chTo)) {
            replaceRange(cm.doc, newText, chFrom, chTo, "+input")
            return true
        }
    };

    ContentEditableInput.prototype.ensurePolled = function () {
        this.forceCompositionEnd()
    };
    ContentEditableInput.prototype.reset = function () {
        this.forceCompositionEnd()
    };
    ContentEditableInput.prototype.forceCompositionEnd = function () {
        if (!this.composing) { return }
        clearTimeout(this.readDOMTimeout)
        this.composing = null
        this.updateFromDOM()
        this.div.blur()
        this.div.focus()
    };
    ContentEditableInput.prototype.readFromDOMSoon = function () {
        var this$1 = this;

        if (this.readDOMTimeout != null) { return }
        this.readDOMTimeout = setTimeout(function () {
            this$1.readDOMTimeout = null
            if (this$1.composing) {
                if (this$1.composing.done) { this$1.composing = null }
                else { return }
            }
            this$1.updateFromDOM()
        }, 80)
    };

    ContentEditableInput.prototype.updateFromDOM = function () {
        var this$1 = this;

        if (this.cm.isReadOnly() || !this.pollContent())
        { runInOp(this.cm, function () { return regChange(this$1.cm); }) }
    };

    ContentEditableInput.prototype.setUneditable = function (node) {
        node.contentEditable = "false"
    };

    ContentEditableInput.prototype.onKeyPress = function (e) {
        if (e.charCode == 0) { return }
        e.preventDefault()
        if (!this.cm.isReadOnly())
        { operation(this.cm, applyTextInput)(this.cm, String.fromCharCode(e.charCode == null ? e.keyCode : e.charCode), 0) }
    };

    ContentEditableInput.prototype.readOnlyChanged = function (val) {
        this.div.contentEditable = String(val != "nocursor")
    };

    ContentEditableInput.prototype.onContextMenu = function () {};
    ContentEditableInput.prototype.resetPosition = function () {};

    ContentEditableInput.prototype.needsContentAttribute = true

    function posToDOM(cm, pos) {
        var view = findViewForLine(cm, pos.line)
        if (!view || view.hidden) { return null }
        var line = getLine(cm.doc, pos.line)
        var info = mapFromLineView(view, line, pos.line)

        var order = getOrder(line, cm.doc.direction), side = "left"
        if (order) {
            var partPos = getBidiPartAt(order, pos.ch)
            side = partPos % 2 ? "right" : "left"
        }
        var result = nodeAndOffsetInLineMap(info.map, pos.ch, side)
        result.offset = result.collapse == "right" ? result.end : result.start
        return result
    }

    function isInGutter(node) {
        for (var scan = node; scan; scan = scan.parentNode)
        { if (/CodeMirror-gutter-wrapper/.test(scan.className)) { return true } }
        return false
    }

    function badPos(pos, bad) { if (bad) { pos.bad = true; } return pos }

    function domTextBetween(cm, from, to, fromLine, toLine) {
        var text = "", closing = false, lineSep = cm.doc.lineSeparator()
        function recognizeMarker(id) { return function (marker) { return marker.id == id; } }
        function close() {
            if (closing) {
                text += lineSep
                closing = false
            }
        }
        function addText(str) {
            if (str) {
                close()
                text += str
            }
        }
        function walk(node) {
            if (node.nodeType == 1) {
                var cmText = node.getAttribute("cm-text")
                if (cmText != null) {
                    addText(cmText || node.textContent.replace(/\u200b/g, ""))
                    return
                }
                var markerID = node.getAttribute("cm-marker"), range
                if (markerID) {
                    var found = cm.findMarks(Pos(fromLine, 0), Pos(toLine + 1, 0), recognizeMarker(+markerID))
                    if (found.length && (range = found[0].find(0)))
                    { addText(getBetween(cm.doc, range.from, range.to).join(lineSep)) }
                    return
                }
                if (node.getAttribute("contenteditable") == "false") { return }
                var isBlock = /^(pre|div|p)$/i.test(node.nodeName)
                if (isBlock) { close() }
                for (var i = 0; i < node.childNodes.length; i++)
                { walk(node.childNodes[i]) }
                if (isBlock) { closing = true }
            } else if (node.nodeType == 3) {
                addText(node.nodeValue)
            }
        }
        for (;;) {
            walk(from)
            if (from == to) { break }
            from = from.nextSibling
        }
        return text
    }

    function domToPos(cm, node, offset) {
        var lineNode
        if (node == cm.display.lineDiv) {
            lineNode = cm.display.lineDiv.childNodes[offset]
            if (!lineNode) { return badPos(cm.clipPos(Pos(cm.display.viewTo - 1)), true) }
            node = null; offset = 0
        } else {
            for (lineNode = node;; lineNode = lineNode.parentNode) {
                if (!lineNode || lineNode == cm.display.lineDiv) { return null }
                if (lineNode.parentNode && lineNode.parentNode == cm.display.lineDiv) { break }
            }
        }
        for (var i = 0; i < cm.display.view.length; i++) {
            var lineView = cm.display.view[i]
            if (lineView.node == lineNode)
            { return locateNodeInLineView(lineView, node, offset) }
        }
    }

    function locateNodeInLineView(lineView, node, offset) {
        var wrapper = lineView.text.firstChild, bad = false
        if (!node || !contains(wrapper, node)) { return badPos(Pos(lineNo(lineView.line), 0), true) }
        if (node == wrapper) {
            bad = true
            node = wrapper.childNodes[offset]
            offset = 0
            if (!node) {
                var line = lineView.rest ? lst(lineView.rest) : lineView.line
                return badPos(Pos(lineNo(line), line.text.length), bad)
            }
        }

        var textNode = node.nodeType == 3 ? node : null, topNode = node
        if (!textNode && node.childNodes.length == 1 && node.firstChild.nodeType == 3) {
            textNode = node.firstChild
            if (offset) { offset = textNode.nodeValue.length }
        }
        while (topNode.parentNode != wrapper) { topNode = topNode.parentNode }
        var measure = lineView.measure, maps = measure.maps

        function find(textNode, topNode, offset) {
            for (var i = -1; i < (maps ? maps.length : 0); i++) {
                var map = i < 0 ? measure.map : maps[i]
                for (var j = 0; j < map.length; j += 3) {
                    var curNode = map[j + 2]
                    if (curNode == textNode || curNode == topNode) {
                        var line = lineNo(i < 0 ? lineView.line : lineView.rest[i])
                        var ch = map[j] + offset
                        if (offset < 0 || curNode != textNode) { ch = map[j + (offset ? 1 : 0)] }
                        return Pos(line, ch)
                    }
                }
            }
        }
        var found = find(textNode, topNode, offset)
        if (found) { return badPos(found, bad) }

        // FIXME this is all really shaky. might handle the few cases it needs to handle, but likely to cause problems
        for (var after = topNode.nextSibling, dist = textNode ? textNode.nodeValue.length - offset : 0; after; after = after.nextSibling) {
            found = find(after, after.firstChild, 0)
            if (found)
            { return badPos(Pos(found.line, found.ch - dist), bad) }
            else
            { dist += after.textContent.length }
        }
        for (var before = topNode.previousSibling, dist$1 = offset; before; before = before.previousSibling) {
            found = find(before, before.firstChild, -1)
            if (found)
            { return badPos(Pos(found.line, found.ch + dist$1), bad) }
            else
            { dist$1 += before.textContent.length }
        }
    }

// TEXTAREA INPUT STYLE

    var TextareaInput = function(cm) {
        this.cm = cm
        // See input.poll and input.reset
        this.prevInput = ""

        // Flag that indicates whether we expect input to appear real soon
        // now (after some event like 'keypress' or 'input') and are
        // polling intensively.
        this.pollingFast = false
        // Self-resetting timeout for the poller
        this.polling = new Delayed()
        // Used to work around IE issue with selection being forgotten when focus moves away from textarea
        this.hasSelection = false
        this.composing = null
    };

    TextareaInput.prototype.init = function (display) {
        var this$1 = this;

        var input = this, cm = this.cm

        // Wraps and hides input textarea
        var div = this.wrapper = hiddenTextarea()
        // The semihidden textarea that is focused when the editor is
        // focused, and receives input.
        var te = this.textarea = div.firstChild
        display.wrapper.insertBefore(div, display.wrapper.firstChild)

        // Needed to hide big blue blinking cursor on Mobile Safari (doesn't seem to work in iOS 8 anymore)
        if (ios) { te.style.width = "0px" }

        on(te, "input", function () {
            if (ie && ie_version >= 9 && this$1.hasSelection) { this$1.hasSelection = null }
            input.poll()
        })

        on(te, "paste", function (e) {
            if (signalDOMEvent(cm, e) || handlePaste(e, cm)) { return }

            cm.state.pasteIncoming = true
            input.fastPoll()
        })

        function prepareCopyCut(e) {
            if (signalDOMEvent(cm, e)) { return }
            if (cm.somethingSelected()) {
                setLastCopied({lineWise: false, text: cm.getSelections()})
            } else if (!cm.options.lineWiseCopyCut) {
                return
            } else {
                var ranges = copyableRanges(cm)
                setLastCopied({lineWise: true, text: ranges.text})
                if (e.type == "cut") {
                    cm.setSelections(ranges.ranges, null, sel_dontScroll)
                } else {
                    input.prevInput = ""
                    te.value = ranges.text.join("\n")
                    selectInput(te)
                }
            }
            if (e.type == "cut") { cm.state.cutIncoming = true }
        }
        on(te, "cut", prepareCopyCut)
        on(te, "copy", prepareCopyCut)

        on(display.scroller, "paste", function (e) {
            if (eventInWidget(display, e) || signalDOMEvent(cm, e)) { return }
            cm.state.pasteIncoming = true
            input.focus()
        })

        // Prevent normal selection in the editor (we handle our own)
        on(display.lineSpace, "selectstart", function (e) {
            if (!eventInWidget(display, e)) { e_preventDefault(e) }
        })

        on(te, "compositionstart", function () {
            var start = cm.getCursor("from")
            if (input.composing) { input.composing.range.clear() }
            input.composing = {
                start: start,
                range: cm.markText(start, cm.getCursor("to"), {className: "CodeMirror-composing"})
            }
        })
        on(te, "compositionend", function () {
            if (input.composing) {
                input.poll()
                input.composing.range.clear()
                input.composing = null
            }
        })
    };

    TextareaInput.prototype.prepareSelection = function () {
        // Redraw the selection and/or cursor
        var cm = this.cm, display = cm.display, doc = cm.doc
        var result = prepareSelection(cm)

        // Move the hidden textarea near the cursor to prevent scrolling artifacts
        if (cm.options.moveInputWithCursor) {
            var headPos = cursorCoords(cm, doc.sel.primary().head, "div")
            var wrapOff = display.wrapper.getBoundingClientRect(), lineOff = display.lineDiv.getBoundingClientRect()
            result.teTop = Math.max(0, Math.min(display.wrapper.clientHeight - 10,
                headPos.top + lineOff.top - wrapOff.top))
            result.teLeft = Math.max(0, Math.min(display.wrapper.clientWidth - 10,
                headPos.left + lineOff.left - wrapOff.left))
        }

        return result
    };

    TextareaInput.prototype.showSelection = function (drawn) {
        var cm = this.cm, display = cm.display
        removeChildrenAndAdd(display.cursorDiv, drawn.cursors)
        removeChildrenAndAdd(display.selectionDiv, drawn.selection)
        if (drawn.teTop != null) {
            this.wrapper.style.top = drawn.teTop + "px"
            this.wrapper.style.left = drawn.teLeft + "px"
        }
    };

// Reset the input to correspond to the selection (or to be empty,
// when not typing and nothing is selected)
    TextareaInput.prototype.reset = function (typing) {
        if (this.contextMenuPending || this.composing) { return }
        var cm = this.cm
        if (cm.somethingSelected()) {
            this.prevInput = ""
            var content = cm.getSelection()
            this.textarea.value = content
            if (cm.state.focused) { selectInput(this.textarea) }
            if (ie && ie_version >= 9) { this.hasSelection = content }
        } else if (!typing) {
            this.prevInput = this.textarea.value = ""
            if (ie && ie_version >= 9) { this.hasSelection = null }
        }
    };

    TextareaInput.prototype.getField = function () { return this.textarea };

    TextareaInput.prototype.supportsTouch = function () { return false };

    TextareaInput.prototype.focus = function () {
        if (this.cm.options.readOnly != "nocursor" && (!mobile || activeElt() != this.textarea)) {
            try { this.textarea.focus() }
            catch (e) {} // IE8 will throw if the textarea is display: none or not in DOM
        }
    };

    TextareaInput.prototype.blur = function () { this.textarea.blur() };

    TextareaInput.prototype.resetPosition = function () {
        this.wrapper.style.top = this.wrapper.style.left = 0
    };

    TextareaInput.prototype.receivedFocus = function () { this.slowPoll() };

// Poll for input changes, using the normal rate of polling. This
// runs as long as the editor is focused.
    TextareaInput.prototype.slowPoll = function () {
        var this$1 = this;

        if (this.pollingFast) { return }
        this.polling.set(this.cm.options.pollInterval, function () {
            this$1.poll()
            if (this$1.cm.state.focused) { this$1.slowPoll() }
        })
    };

// When an event has just come in that is likely to add or change
// something in the input textarea, we poll faster, to ensure that
// the change appears on the screen quickly.
    TextareaInput.prototype.fastPoll = function () {
        var missed = false, input = this
        input.pollingFast = true
        function p() {
            var changed = input.poll()
            if (!changed && !missed) {missed = true; input.polling.set(60, p)}
            else {input.pollingFast = false; input.slowPoll()}
        }
        input.polling.set(20, p)
    };

// Read input from the textarea, and update the document to match.
// When something is selected, it is present in the textarea, and
// selected (unless it is huge, in which case a placeholder is
// used). When nothing is selected, the cursor sits after previously
// seen text (can be empty), which is stored in prevInput (we must
// not reset the textarea when typing, because that breaks IME).
    TextareaInput.prototype.poll = function () {
        var this$1 = this;

        var cm = this.cm, input = this.textarea, prevInput = this.prevInput
        // Since this is called a *lot*, try to bail out as cheaply as
        // possible when it is clear that nothing happened. hasSelection
        // will be the case when there is a lot of text in the textarea,
        // in which case reading its value would be expensive.
        if (this.contextMenuPending || !cm.state.focused ||
            (hasSelection(input) && !prevInput && !this.composing) ||
            cm.isReadOnly() || cm.options.disableInput || cm.state.keySeq)
        { return false }

        var text = input.value
        // If nothing changed, bail.
        if (text == prevInput && !cm.somethingSelected()) { return false }
        // Work around nonsensical selection resetting in IE9/10, and
        // inexplicable appearance of private area unicode characters on
        // some key combos in Mac (#2689).
        if (ie && ie_version >= 9 && this.hasSelection === text ||
            mac && /[\uf700-\uf7ff]/.test(text)) {
            cm.display.input.reset()
            return false
        }

        if (cm.doc.sel == cm.display.selForContextMenu) {
            var first = text.charCodeAt(0)
            if (first == 0x200b && !prevInput) { prevInput = "\u200b" }
            if (first == 0x21da) { this.reset(); return this.cm.execCommand("undo") }
        }
        // Find the part of the input that is actually new
        var same = 0, l = Math.min(prevInput.length, text.length)
        while (same < l && prevInput.charCodeAt(same) == text.charCodeAt(same)) { ++same }

        runInOp(cm, function () {
            applyTextInput(cm, text.slice(same), prevInput.length - same,
                null, this$1.composing ? "*compose" : null)

            // Don't leave long text in the textarea, since it makes further polling slow
            if (text.length > 1000 || text.indexOf("\n") > -1) { input.value = this$1.prevInput = "" }
            else { this$1.prevInput = text }

            if (this$1.composing) {
                this$1.composing.range.clear()
                this$1.composing.range = cm.markText(this$1.composing.start, cm.getCursor("to"),
                    {className: "CodeMirror-composing"})
            }
        })
        return true
    };

    TextareaInput.prototype.ensurePolled = function () {
        if (this.pollingFast && this.poll()) { this.pollingFast = false }
    };

    TextareaInput.prototype.onKeyPress = function () {
        if (ie && ie_version >= 9) { this.hasSelection = null }
        this.fastPoll()
    };

    TextareaInput.prototype.onContextMenu = function (e) {
        var input = this, cm = input.cm, display = cm.display, te = input.textarea
        var pos = posFromMouse(cm, e), scrollPos = display.scroller.scrollTop
        if (!pos || presto) { return } // Opera is difficult.

        // Reset the current text selection only if the click is done outside of the selection
        // and 'resetSelectionOnContextMenu' option is true.
        var reset = cm.options.resetSelectionOnContextMenu
        if (reset && cm.doc.sel.contains(pos) == -1)
        { operation(cm, setSelection)(cm.doc, simpleSelection(pos), sel_dontScroll) }

        var oldCSS = te.style.cssText, oldWrapperCSS = input.wrapper.style.cssText
        input.wrapper.style.cssText = "position: absolute"
        var wrapperBox = input.wrapper.getBoundingClientRect()
        te.style.cssText = "position: absolute; width: 30px; height: 30px;\n      top: " + (e.clientY - wrapperBox.top - 5) + "px; left: " + (e.clientX - wrapperBox.left - 5) + "px;\n      z-index: 1000; background: " + (ie ? "rgba(255, 255, 255, .05)" : "transparent") + ";\n      outline: none; border-width: 0; outline: none; overflow: hidden; opacity: .05; filter: alpha(opacity=5);"
        var oldScrollY
        if (webkit) { oldScrollY = window.scrollY } // Work around Chrome issue (#2712)
        display.input.focus()
        if (webkit) { window.scrollTo(null, oldScrollY) }
        display.input.reset()
        // Adds "Select all" to context menu in FF
        if (!cm.somethingSelected()) { te.value = input.prevInput = " " }
        input.contextMenuPending = true
        display.selForContextMenu = cm.doc.sel
        clearTimeout(display.detectingSelectAll)

        // Select-all will be greyed out if there's nothing to select, so
        // this adds a zero-width space so that we can later check whether
        // it got selected.
        function prepareSelectAllHack() {
            if (te.selectionStart != null) {
                var selected = cm.somethingSelected()
                var extval = "\u200b" + (selected ? te.value : "")
                te.value = "\u21da" // Used to catch context-menu undo
                te.value = extval
                input.prevInput = selected ? "" : "\u200b"
                te.selectionStart = 1; te.selectionEnd = extval.length
                // Re-set this, in case some other handler touched the
                // selection in the meantime.
                display.selForContextMenu = cm.doc.sel
            }
        }
        function rehide() {
            input.contextMenuPending = false
            input.wrapper.style.cssText = oldWrapperCSS
            te.style.cssText = oldCSS
            if (ie && ie_version < 9) { display.scrollbars.setScrollTop(display.scroller.scrollTop = scrollPos) }

            // Try to detect the user choosing select-all
            if (te.selectionStart != null) {
                if (!ie || (ie && ie_version < 9)) { prepareSelectAllHack() }
                var i = 0, poll = function () {
                    if (display.selForContextMenu == cm.doc.sel && te.selectionStart == 0 &&
                        te.selectionEnd > 0 && input.prevInput == "\u200b") {
                        operation(cm, selectAll)(cm)
                    } else if (i++ < 10) {
                        display.detectingSelectAll = setTimeout(poll, 500)
                    } else {
                        display.selForContextMenu = null
                        display.input.reset()
                    }
                }
                display.detectingSelectAll = setTimeout(poll, 200)
            }
        }

        if (ie && ie_version >= 9) { prepareSelectAllHack() }
        if (captureRightClick) {
            e_stop(e)
            var mouseup = function () {
                off(window, "mouseup", mouseup)
                setTimeout(rehide, 20)
            }
            on(window, "mouseup", mouseup)
        } else {
            setTimeout(rehide, 50)
        }
    };

    TextareaInput.prototype.readOnlyChanged = function (val) {
        if (!val) { this.reset() }
        this.textarea.disabled = val == "nocursor"
    };

    TextareaInput.prototype.setUneditable = function () {};

    TextareaInput.prototype.needsContentAttribute = false

    function fromTextArea(textarea, options) {
        options = options ? copyObj(options) : {}
        options.value = textarea.value
        if (!options.tabindex && textarea.tabIndex)
        { options.tabindex = textarea.tabIndex }
        if (!options.placeholder && textarea.placeholder)
        { options.placeholder = textarea.placeholder }
        // Set autofocus to true if this textarea is focused, or if it has
        // autofocus and no other element is focused.
        if (options.autofocus == null) {
            var hasFocus = activeElt()
            options.autofocus = hasFocus == textarea ||
                textarea.getAttribute("autofocus") != null && hasFocus == document.body
        }

        function save() {textarea.value = cm.getValue()}

        var realSubmit
        if (textarea.form) {
            on(textarea.form, "submit", save)
            // Deplorable hack to make the submit method do the right thing.
            if (!options.leaveSubmitMethodAlone) {
                var form = textarea.form
                realSubmit = form.submit
                try {
                    var wrappedSubmit = form.submit = function () {
                        save()
                        form.submit = realSubmit
                        form.submit()
                        form.submit = wrappedSubmit
                    }
                } catch(e) {}
            }
        }

        options.finishInit = function (cm) {
            cm.save = save
            cm.getTextArea = function () { return textarea; }
            cm.toTextArea = function () {
                cm.toTextArea = isNaN // Prevent this from being ran twice
                save()
                textarea.parentNode.removeChild(cm.getWrapperElement())
                textarea.style.display = ""
                if (textarea.form) {
                    off(textarea.form, "submit", save)
                    if (typeof textarea.form.submit == "function")
                    { textarea.form.submit = realSubmit }
                }
            }
        }

        textarea.style.display = "none"
        var cm = CodeMirror(function (node) { return textarea.parentNode.insertBefore(node, textarea.nextSibling); },
            options)
        return cm
    }

    function addLegacyProps(CodeMirror) {
        CodeMirror.off = off
        CodeMirror.on = on
        CodeMirror.wheelEventPixels = wheelEventPixels
        CodeMirror.Doc = Doc
        CodeMirror.splitLines = splitLinesAuto
        CodeMirror.countColumn = countColumn
        CodeMirror.findColumn = findColumn
        CodeMirror.isWordChar = isWordCharBasic
        CodeMirror.Pass = Pass
        CodeMirror.signal = signal
        CodeMirror.Line = Line
        CodeMirror.changeEnd = changeEnd
        CodeMirror.scrollbarModel = scrollbarModel
        CodeMirror.Pos = Pos
        CodeMirror.cmpPos = cmp
        CodeMirror.modes = modes
        CodeMirror.mimeModes = mimeModes
        CodeMirror.resolveMode = resolveMode
        CodeMirror.getMode = getMode
        CodeMirror.modeExtensions = modeExtensions
        CodeMirror.extendMode = extendMode
        CodeMirror.copyState = copyState
        CodeMirror.startState = startState
        CodeMirror.innerMode = innerMode
        CodeMirror.commands = commands
        CodeMirror.keyMap = keyMap
        CodeMirror.keyName = keyName
        CodeMirror.isModifierKey = isModifierKey
        CodeMirror.lookupKey = lookupKey
        CodeMirror.normalizeKeyMap = normalizeKeyMap
        CodeMirror.StringStream = StringStream
        CodeMirror.SharedTextMarker = SharedTextMarker
        CodeMirror.TextMarker = TextMarker
        CodeMirror.LineWidget = LineWidget
        CodeMirror.e_preventDefault = e_preventDefault
        CodeMirror.e_stopPropagation = e_stopPropagation
        CodeMirror.e_stop = e_stop
        CodeMirror.addClass = addClass
        CodeMirror.contains = contains
        CodeMirror.rmClass = rmClass
        CodeMirror.keyNames = keyNames
    }

// EDITOR CONSTRUCTOR

    defineOptions(CodeMirror)

    addEditorMethods(CodeMirror)

// Set up methods on CodeMirror's prototype to redirect to the editor's document.
    var dontDelegate = "iter insert remove copy getEditor constructor".split(" ")
    for (var prop in Doc.prototype) { if (Doc.prototype.hasOwnProperty(prop) && indexOf(dontDelegate, prop) < 0)
    { CodeMirror.prototype[prop] = (function(method) {
        return function() {return method.apply(this.doc, arguments)}
    })(Doc.prototype[prop]) } }

    eventMixin(Doc)

// INPUT HANDLING

    CodeMirror.inputStyles = {"textarea": TextareaInput, "contenteditable": ContentEditableInput}

// MODE DEFINITION AND QUERYING

// Extra arguments are stored as the mode's dependencies, which is
// used by (legacy) mechanisms like loadmode.js to automatically
// load a mode. (Preferred mechanism is the require/define calls.)
    CodeMirror.defineMode = function(name/*, mode, …*/) {
        if (!CodeMirror.defaults.mode && name != "null") { CodeMirror.defaults.mode = name }
        defineMode.apply(this, arguments)
    }

    CodeMirror.defineMIME = defineMIME

// Minimal default mode.
    CodeMirror.defineMode("null", function () { return ({token: function (stream) { return stream.skipToEnd(); }}); })
    CodeMirror.defineMIME("text/plain", "null")

// EXTENSIONS

    CodeMirror.defineExtension = function (name, func) {
        CodeMirror.prototype[name] = func
    }
    CodeMirror.defineDocExtension = function (name, func) {
        Doc.prototype[name] = func
    }

    CodeMirror.fromTextArea = fromTextArea

    addLegacyProps(CodeMirror)

    CodeMirror.version = "5.32.0"

    return CodeMirror;

})));
// CodeMirror, copyright (c) by Marijn Haverbeke and others
// Distributed under an MIT license: http://codemirror.net/LICENSE

(function(mod) {
        mod(CodeMirror);
})(function(CodeMirror) {
    "use strict";

    var htmlConfig = {
        autoSelfClosers: {'area': true, 'base': true, 'br': true, 'col': true, 'command': true,
            'embed': true, 'frame': true, 'hr': true, 'img': true, 'input': true,
            'keygen': true, 'link': true, 'meta': true, 'param': true, 'source': true,
            'track': true, 'wbr': true, 'menuitem': true},
        implicitlyClosed: {'dd': true, 'li': true, 'optgroup': true, 'option': true, 'p': true,
            'rp': true, 'rt': true, 'tbody': true, 'td': true, 'tfoot': true,
            'th': true, 'tr': true},
        contextGrabbers: {
            'dd': {'dd': true, 'dt': true},
            'dt': {'dd': true, 'dt': true},
            'li': {'li': true},
            'option': {'option': true, 'optgroup': true},
            'optgroup': {'optgroup': true},
            'p': {'address': true, 'article': true, 'aside': true, 'blockquote': true, 'dir': true,
                'div': true, 'dl': true, 'fieldset': true, 'footer': true, 'form': true,
                'h1': true, 'h2': true, 'h3': true, 'h4': true, 'h5': true, 'h6': true,
                'header': true, 'hgroup': true, 'hr': true, 'menu': true, 'nav': true, 'ol': true,
                'p': true, 'pre': true, 'section': true, 'table': true, 'ul': true},
            'rp': {'rp': true, 'rt': true},
            'rt': {'rp': true, 'rt': true},
            'tbody': {'tbody': true, 'tfoot': true},
            'td': {'td': true, 'th': true},
            'tfoot': {'tbody': true},
            'th': {'td': true, 'th': true},
            'thead': {'tbody': true, 'tfoot': true},
            'tr': {'tr': true}
        },
        doNotIndent: {"pre": true},
        allowUnquoted: true,
        allowMissing: true,
        caseFold: true
    }

    var xmlConfig = {
        autoSelfClosers: {},
        implicitlyClosed: {},
        contextGrabbers: {},
        doNotIndent: {},
        allowUnquoted: false,
        allowMissing: false,
        caseFold: false
    }

    CodeMirror.defineMode("xml", function(editorConf, config_) {
        var indentUnit = editorConf.indentUnit
        var config = {}
        var defaults = config_.htmlMode ? htmlConfig : xmlConfig
        for (var prop in defaults) config[prop] = defaults[prop]
        for (var prop in config_) config[prop] = config_[prop]

        // Return variables for tokenizers
        var type, setStyle;
        var tagHtml, isInTag = false;

        function inText(stream, state) {
            function chain(parser) {
                state.tokenize = parser;
                return parser(stream, state);
            }

            var ch = stream.next();
            if (ch == "<") {
                if (stream.eat("!")) {
                    if (stream.eat("[")) {
                        if (stream.match("CDATA[")) return chain(inBlock("atom", "]]>"));
                        else return null;
                    } else if (stream.match("--")) {
                        return chain(inBlock("comment", "-->"));
                    } else if (stream.match("DOCTYPE", true, true)) {
                        stream.eatWhile(/[\w\._\-]/);
                        return chain(doctype(1));
                    } else {
                        return null;
                    }
                } else if (stream.eat("?")) {
                    stream.eatWhile(/[\w\._\-]/);
                    state.tokenize = inBlock("meta", "?>");
                    return "meta";
                } else {
                    type = stream.eat("/") ? "closeTag" : "openTag";
                    state.tokenize = inTag;
                    tagHtml = "";
                    isInTag = true;
                    return "tag bracket";
                }
            } else if (ch == "&") {
                var ok;
                if (stream.eat("#")) {
                    if (stream.eat("x")) {
                        ok = stream.eatWhile(/[a-fA-F\d]/) && stream.eat(";");
                    } else {
                        ok = stream.eatWhile(/[\d]/) && stream.eat(";");
                    }
                } else {
                    ok = stream.eatWhile(/[\w\.\-:]/) && stream.eat(";");
                }
                return ok ? "atom" : "error";
            } else {
                stream.eatWhile(/[^&<]/);
                return null;
            }
        }
        inText.isInText = true;

        function inTag(stream, state) {
            var ch = stream.next();
            if (ch == ">" || (ch == "/" && stream.eat(">"))) {
                isInTag = false;
                state.tokenize = inText;
                type = ch == ">" ? "endTag" : "selfcloseTag";
                return "tag bracket";
            } else if (ch == "=") {
                type = "equals";
                return null;
            } else if (ch == "<") {
                state.tokenize = inText;
                state.state = baseState;
                state.tagName = state.tagStart = null;
                var next = state.tokenize(stream, state);
                return next ? next + " tag error" : "tag error";
            } else if (/[\'\"]/.test(ch)) {
                state.tokenize = inAttribute(ch);
                state.stringStartCol = stream.column();
                return state.tokenize(stream, state);
            } else {
                stream.match(/^[^\s\u00a0=<>\"\']*[^\s\u00a0=<>\"\'\/]/);
                return "word";
            }
        }

        function inAttribute(quote) {
            var closure = function(stream, state) {
                while (!stream.eol()) {
                    if (stream.next() == quote) {
                        state.tokenize = inTag;
                        break;
                    }
                }
                return "string";
            };
            closure.isInAttribute = true;
            return closure;
        }

        function inBlock(style, terminator) {
            return function(stream, state) {
                while (!stream.eol()) {
                    if (stream.match(terminator)) {
                        state.tokenize = inText;
                        break;
                    }
                    stream.next();
                }
                return style;
            };
        }
        function doctype(depth) {
            return function(stream, state) {
                var ch;
                while ((ch = stream.next()) != null) {
                    if (ch == "<") {
                        state.tokenize = doctype(depth + 1);
                        return state.tokenize(stream, state);
                    } else if (ch == ">") {
                        if (depth == 1) {
                            state.tokenize = inText;
                            break;
                        } else {
                            state.tokenize = doctype(depth - 1);
                            return state.tokenize(stream, state);
                        }
                    }
                }
                return "meta";
            };
        }

        function Context(state, tagName, startOfLine) {
            this.prev = state.context;
            this.tagName = tagName;
            this.indent = state.indented;
            this.startOfLine = startOfLine;
            this.tagHtml = tagHtml;
            if (config.doNotIndent.hasOwnProperty(tagName) || (state.context && state.context.noIndent))
                this.noIndent = true;
        }
        function popContext(state) {
            if (state.context) state.context = state.context.prev;
        }
        function maybePopContext(state, nextTagName) {
            var parentTagName;
            while (true) {
                if (!state.context) {
                    return;
                }
                parentTagName = state.context.tagName;
                if (!config.contextGrabbers.hasOwnProperty(parentTagName) ||
                    !config.contextGrabbers[parentTagName].hasOwnProperty(nextTagName)) {
                    return;
                }
                popContext(state);
            }
        }

        function baseState(type, stream, state) {
            if (type == "openTag") {
                state.tagStart = stream.column();
                return tagNameState;
            } else if (type == "closeTag") {
                return closeTagNameState;
            } else {
                return baseState;
            }
        }
        function tagNameState(type, stream, state) {
            if (type == "word") {
                state.tagName = stream.current();
                setStyle = "tag";
                return attrState;
            } else {
                setStyle = "error";
                return tagNameState;
            }
        }
        function closeTagNameState(type, stream, state) {
            if (type == "word") {
                var tagName = stream.current();
                if (state.context && state.context.tagName != tagName &&
                    config.implicitlyClosed.hasOwnProperty(state.context.tagName))
                    popContext(state);
                if ((state.context && state.context.tagName == tagName) || config.matchClosing === false) {
                    setStyle = "tag";
                    return closeState;
                } else {
                    setStyle = "tag error";
                    return closeStateErr;
                }
            } else {
                setStyle = "error";
                return closeStateErr;
            }
        }

        function closeState(type, _stream, state) {
            if (type != "endTag") {
                setStyle = "error";
                return closeState;
            }
            popContext(state);
            return baseState;
        }
        function closeStateErr(type, stream, state) {
            setStyle = "error";
            return closeState(type, stream, state);
        }

        function attrState(type, _stream, state) {
            if (type == "word") {
                setStyle = "attribute";
                return attrEqState;
            } else if (type == "endTag" || type == "selfcloseTag") {
                var tagName = state.tagName, tagStart = state.tagStart;
                state.tagName = state.tagStart = null;
                if (type == "selfcloseTag" ||
                    config.autoSelfClosers.hasOwnProperty(tagName)) {
                    maybePopContext(state, tagName);
                } else {
                    maybePopContext(state, tagName);
                    state.context = new Context(state, tagName, tagStart == state.indented);
                }
                return baseState;
            }
            setStyle = "error";
            return attrState;
        }
        function attrEqState(type, stream, state) {
            if (type == "equals") return attrValueState;
            if (!config.allowMissing) setStyle = "error";
            return attrState(type, stream, state);
        }
        function attrValueState(type, stream, state) {
            if (type == "string") return attrContinuedState;
            if (type == "word" && config.allowUnquoted) {setStyle = "string"; return attrState;}
            setStyle = "error";
            return attrState(type, stream, state);
        }
        function attrContinuedState(type, stream, state) {
            if (type == "string") return attrContinuedState;
            return attrState(type, stream, state);
        }

        return {
            startState: function(baseIndent) {
                var state = {tokenize: inText,
                    state: baseState,
                    indented: baseIndent || 0,
                    tagName: null, tagStart: null,
                    context: null}
                if (baseIndent != null) state.baseIndent = baseIndent
                return state
            },

            token: function(stream, state) {
                var prevPos = stream.pos;
                if (!state.tagName && stream.sol())
                    state.indented = stream.indentation();

                if (stream.eatSpace()) {
                    if (isInTag) {
                        tagHtml += stream.string.substring(prevPos, stream.pos);
                        return null;
                    }
                    return null;
                }
                type = null;
                var style = state.tokenize(stream, state);
                if ((style || type) && style != "comment") {
                    setStyle = null;
                    state.state = state.state(type || style, stream, state);
                    if (setStyle)
                        style = setStyle == "error" ? style + " error" : setStyle;
                }
                if (isInTag) {
                    tagHtml += stream.string.substring(prevPos, stream.pos);
                }
                return style;
            },

            indent: function(state, textAfter, fullLine) {
                var context = state.context;
                // Indent multi-line strings (e.g. css).
                if (state.tokenize.isInAttribute) {
                    if (state.tagStart == state.indented)
                        return state.stringStartCol + 1;
                    else
                        return state.indented + indentUnit;
                }
                if (context && context.noIndent) return CodeMirror.Pass;
                if (state.tokenize != inTag && state.tokenize != inText)
                    return fullLine ? fullLine.match(/^(\s*)/)[0].length : 0;
                // Indent the starts of attribute names.
                if (state.tagName) {
                    if (config.multilineTagIndentPastTag !== false)
                        return state.tagStart + state.tagName.length + 2;
                    else
                        return state.tagStart + indentUnit * (config.multilineTagIndentFactor || 1);
                }
                if (config.alignCDATA && /<!\[CDATA\[/.test(textAfter)) return 0;
                var tagAfter = textAfter && /^<(\/)?([\w_:\.-]*)/.exec(textAfter);
                if (tagAfter && tagAfter[1]) { // Closing tag spotted
                    while (context) {
                        if (context.tagName == tagAfter[2]) {
                            context = context.prev;
                            break;
                        } else if (config.implicitlyClosed.hasOwnProperty(context.tagName)) {
                            context = context.prev;
                        } else {
                            break;
                        }
                    }
                } else if (tagAfter) { // Opening tag spotted
                    while (context) {
                        var grabbers = config.contextGrabbers[context.tagName];
                        if (grabbers && grabbers.hasOwnProperty(tagAfter[2]))
                            context = context.prev;
                        else
                            break;
                    }
                }
                while (context && context.prev && !context.startOfLine)
                    context = context.prev;
                if (context) return context.indent + indentUnit;
                else return state.baseIndent || 0;
            },

            electricInput: /<\/[\s\w:]+>$/,
            blockCommentStart: "<!--",
            blockCommentEnd: "-->",

            configuration: config.htmlMode ? "html" : "xml",
            helperType: config.htmlMode ? "html" : "xml",

            skipAttribute: function(state) {
                if (state.state == attrValueState)
                    state.state = attrState
            }
        };
    });

    CodeMirror.defineMIME("text/xml", "xml");
    CodeMirror.defineMIME("application/xml", "xml");
    if (!CodeMirror.mimeModes.hasOwnProperty("text/html"))
        CodeMirror.defineMIME("text/html", {name: "xml", htmlMode: true});

});

// CodeMirror, copyright (c) by Marijn Haverbeke and others
// Distributed under an MIT license: http://codemirror.net/LICENSE

(function(mod) {
        mod(CodeMirror);
})(function(CodeMirror) {
    "use strict";

    CodeMirror.defineMode("javascript", function(config, parserConfig) {
        var indentUnit = config.indentUnit;
        var statementIndent = parserConfig.statementIndent;
        var jsonldMode = parserConfig.jsonld;
        var jsonMode = parserConfig.json || jsonldMode;
        var isTS = parserConfig.typescript;
        var wordRE = parserConfig.wordCharacters || /[\w$\xa1-\uffff]/;

        // Tokenizer

        var keywords = function(){
            function kw(type) {return {type: type, style: "keyword"};}
            var A = kw("keyword a"), B = kw("keyword b"), C = kw("keyword c"), D = kw("keyword d");
            var operator = kw("operator"), atom = {type: "atom", style: "atom"};

            var jsKeywords = {
                "if": kw("if"), "while": A, "with": A, "else": B, "do": B, "try": B, "finally": B,
                "return": D, "break": D, "continue": D, "new": kw("new"), "delete": C, "void": C, "throw": C,
                "debugger": kw("debugger"), "var": kw("var"), "const": kw("var"), "let": kw("var"),
                "function": kw("function"), "catch": kw("catch"),
                "for": kw("for"), "switch": kw("switch"), "case": kw("case"), "default": kw("default"),
                "in": operator, "typeof": operator, "instanceof": operator,
                "true": atom, "false": atom, "null": atom, "undefined": atom, "NaN": atom, "Infinity": atom,
                "this": kw("this"), "class": kw("class"), "super": kw("atom"),
                "yield": C, "export": kw("export"), "import": kw("import"), "extends": C,
                "await": C
            };

            // Extend the 'normal' keywords with the TypeScript language extensions
            if (isTS) {
                var type = {type: "variable", style: "type"};
                var tsKeywords = {
                    // object-like things
                    "interface": kw("class"),
                    "implements": C,
                    "namespace": C,

                    // scope modifiers
                    "public": kw("modifier"),
                    "private": kw("modifier"),
                    "protected": kw("modifier"),
                    "abstract": kw("modifier"),
                    "readonly": kw("modifier"),

                    // types
                    "string": type, "number": type, "boolean": type, "any": type
                };

                for (var attr in tsKeywords) {
                    jsKeywords[attr] = tsKeywords[attr];
                }
            }

            return jsKeywords;
        }();

        var isOperatorChar = /[+\-*&%=<>!?|~^@]/;
        var isJsonldKeyword = /^@(context|id|value|language|type|container|list|set|reverse|index|base|vocab|graph)"/;

        function readRegexp(stream) {
            var escaped = false, next, inSet = false;
            while ((next = stream.next()) != null) {
                if (!escaped) {
                    if (next == "/" && !inSet) return;
                    if (next == "[") inSet = true;
                    else if (inSet && next == "]") inSet = false;
                }
                escaped = !escaped && next == "\\";
            }
        }

        // Used as scratch variables to communicate multiple values without
        // consing up tons of objects.
        var type, content;
        function ret(tp, style, cont) {
            type = tp; content = cont;
            return style;
        }
        function tokenBase(stream, state) {
            var ch = stream.next();
            if (ch == '"' || ch == "'") {
                state.tokenize = tokenString(ch);
                return state.tokenize(stream, state);
            } else if (ch == "." && stream.match(/^\d+(?:[eE][+\-]?\d+)?/)) {
                return ret("number", "number");
            } else if (ch == "." && stream.match("..")) {
                return ret("spread", "meta");
            } else if (/[\[\]{}\(\),;\:\.]/.test(ch)) {
                return ret(ch);
            } else if (ch == "=" && stream.eat(">")) {
                return ret("=>", "operator");
            } else if (ch == "0" && stream.eat(/x/i)) {
                stream.eatWhile(/[\da-f]/i);
                return ret("number", "number");
            } else if (ch == "0" && stream.eat(/o/i)) {
                stream.eatWhile(/[0-7]/i);
                return ret("number", "number");
            } else if (ch == "0" && stream.eat(/b/i)) {
                stream.eatWhile(/[01]/i);
                return ret("number", "number");
            } else if (/\d/.test(ch)) {
                stream.match(/^\d*(?:\.\d*)?(?:[eE][+\-]?\d+)?/);
                return ret("number", "number");
            } else if (ch == "/") {
                if (stream.eat("*")) {
                    state.tokenize = tokenComment;
                    return tokenComment(stream, state);
                } else if (stream.eat("/")) {
                    stream.skipToEnd();
                    return ret("comment", "comment");
                } else if (expressionAllowed(stream, state, 1)) {
                    readRegexp(stream);
                    stream.match(/^\b(([gimyu])(?![gimyu]*\2))+\b/);
                    return ret("regexp", "string-2");
                } else {
                    stream.eat("=");
                    return ret("operator", "operator", stream.current());
                }
            } else if (ch == "`") {
                state.tokenize = tokenQuasi;
                return tokenQuasi(stream, state);
            } else if (ch == "#") {
                stream.skipToEnd();
                return ret("error", "error");
            } else if (isOperatorChar.test(ch)) {
                if (ch != ">" || !state.lexical || state.lexical.type != ">") {
                    if (stream.eat("=")) {
                        if (ch == "!" || ch == "=") stream.eat("=")
                    } else if (/[<>*+\-]/.test(ch)) {
                        stream.eat(ch)
                        if (ch == ">") stream.eat(ch)
                    }
                }
                return ret("operator", "operator", stream.current());
            } else if (wordRE.test(ch)) {
                stream.eatWhile(wordRE);
                var word = stream.current()
                if (state.lastType != ".") {
                    if (keywords.propertyIsEnumerable(word)) {
                        var kw = keywords[word]
                        return ret(kw.type, kw.style, word)
                    }
                    if (word == "async" && stream.match(/^(\s|\/\*.*?\*\/)*[\(\w]/, false))
                        return ret("async", "keyword", word)
                }
                return ret("variable", "variable", word)
            }
        }

        function tokenString(quote) {
            return function(stream, state) {
                var escaped = false, next;
                if (jsonldMode && stream.peek() == "@" && stream.match(isJsonldKeyword)){
                    state.tokenize = tokenBase;
                    return ret("jsonld-keyword", "meta");
                }
                while ((next = stream.next()) != null) {
                    if (next == quote && !escaped) break;
                    escaped = !escaped && next == "\\";
                }
                if (!escaped) state.tokenize = tokenBase;
                return ret("string", "string");
            };
        }

        function tokenComment(stream, state) {
            var maybeEnd = false, ch;
            while (ch = stream.next()) {
                if (ch == "/" && maybeEnd) {
                    state.tokenize = tokenBase;
                    break;
                }
                maybeEnd = (ch == "*");
            }
            return ret("comment", "comment");
        }

        function tokenQuasi(stream, state) {
            var escaped = false, next;
            while ((next = stream.next()) != null) {
                if (!escaped && (next == "`" || next == "$" && stream.eat("{"))) {
                    state.tokenize = tokenBase;
                    break;
                }
                escaped = !escaped && next == "\\";
            }
            return ret("quasi", "string-2", stream.current());
        }

        var brackets = "([{}])";
        // This is a crude lookahead trick to try and notice that we're
        // parsing the argument patterns for a fat-arrow function before we
        // actually hit the arrow token. It only works if the arrow is on
        // the same line as the arguments and there's no strange noise
        // (comments) in between. Fallback is to only notice when we hit the
        // arrow, and not declare the arguments as locals for the arrow
        // body.
        function findFatArrow(stream, state) {
            if (state.fatArrowAt) state.fatArrowAt = null;
            var arrow = stream.string.indexOf("=>", stream.start);
            if (arrow < 0) return;

            if (isTS) { // Try to skip TypeScript return type declarations after the arguments
                var m = /:\s*(?:\w+(?:<[^>]*>|\[\])?|\{[^}]*\})\s*$/.exec(stream.string.slice(stream.start, arrow))
                if (m) arrow = m.index
            }

            var depth = 0, sawSomething = false;
            for (var pos = arrow - 1; pos >= 0; --pos) {
                var ch = stream.string.charAt(pos);
                var bracket = brackets.indexOf(ch);
                if (bracket >= 0 && bracket < 3) {
                    if (!depth) { ++pos; break; }
                    if (--depth == 0) { if (ch == "(") sawSomething = true; break; }
                } else if (bracket >= 3 && bracket < 6) {
                    ++depth;
                } else if (wordRE.test(ch)) {
                    sawSomething = true;
                } else if (/["'\/]/.test(ch)) {
                    return;
                } else if (sawSomething && !depth) {
                    ++pos;
                    break;
                }
            }
            if (sawSomething && !depth) state.fatArrowAt = pos;
        }

        // Parser

        var atomicTypes = {"atom": true, "number": true, "variable": true, "string": true, "regexp": true, "this": true, "jsonld-keyword": true};

        function JSLexical(indented, column, type, align, prev, info) {
            this.indented = indented;
            this.column = column;
            this.type = type;
            this.prev = prev;
            this.info = info;
            if (align != null) this.align = align;
        }

        function inScope(state, varname) {
            for (var v = state.localVars; v; v = v.next)
                if (v.name == varname) return true;
            for (var cx = state.context; cx; cx = cx.prev) {
                for (var v = cx.vars; v; v = v.next)
                    if (v.name == varname) return true;
            }
        }

        function parseJS(state, style, type, content, stream) {
            var cc = state.cc;
            // Communicate our context to the combinators.
            // (Less wasteful than consing up a hundred closures on every call.)
            cx.state = state; cx.stream = stream; cx.marked = null, cx.cc = cc; cx.style = style;

            if (!state.lexical.hasOwnProperty("align"))
                state.lexical.align = true;

            while(true) {
                var combinator = cc.length ? cc.pop() : jsonMode ? expression : statement;
                if (combinator(type, content)) {
                    while(cc.length && cc[cc.length - 1].lex)
                        cc.pop()();
                    if (cx.marked) return cx.marked;
                    if (type == "variable" && inScope(state, content)) return "variable-2";
                    return style;
                }
            }
        }

        // Combinator utils

        var cx = {state: null, column: null, marked: null, cc: null};
        function pass() {
            for (var i = arguments.length - 1; i >= 0; i--) cx.cc.push(arguments[i]);
        }
        function cont() {
            pass.apply(null, arguments);
            return true;
        }
        function register(varname) {
            function inList(list) {
                for (var v = list; v; v = v.next)
                    if (v.name == varname) return true;
                return false;
            }
            var state = cx.state;
            cx.marked = "def";
            if (state.context) {
                if (inList(state.localVars)) return;
                state.localVars = {name: varname, next: state.localVars};
            } else {
                if (inList(state.globalVars)) return;
                if (parserConfig.globalVars)
                    state.globalVars = {name: varname, next: state.globalVars};
            }
        }

        // Combinators

        var defaultVars = {name: "this", next: {name: "arguments"}};
        function pushcontext() {
            cx.state.context = {prev: cx.state.context, vars: cx.state.localVars};
            cx.state.localVars = defaultVars;
        }
        function popcontext() {
            cx.state.localVars = cx.state.context.vars;
            cx.state.context = cx.state.context.prev;
        }
        function pushlex(type, info) {
            var result = function() {
                var state = cx.state, indent = state.indented;
                if (state.lexical.type == "stat") indent = state.lexical.indented;
                else for (var outer = state.lexical; outer && outer.type == ")" && outer.align; outer = outer.prev)
                    indent = outer.indented;
                state.lexical = new JSLexical(indent, cx.stream.column(), type, null, state.lexical, info);
            };
            result.lex = true;
            return result;
        }
        function poplex() {
            var state = cx.state;
            if (state.lexical.prev) {
                if (state.lexical.type == ")")
                    state.indented = state.lexical.indented;
                state.lexical = state.lexical.prev;
            }
        }
        poplex.lex = true;

        function expect(wanted) {
            function exp(type) {
                if (type == wanted) return cont();
                else if (wanted == ";") return pass();
                else return cont(exp);
            };
            return exp;
        }

        function statement(type, value) {
            if (type == "var") return cont(pushlex("vardef", value.length), vardef, expect(";"), poplex);
            if (type == "keyword a") return cont(pushlex("form"), parenExpr, statement, poplex);
            if (type == "keyword b") return cont(pushlex("form"), statement, poplex);
            if (type == "keyword d") return cx.stream.match(/^\s*$/, false) ? cont() : cont(pushlex("stat"), maybeexpression, expect(";"), poplex);
            if (type == "debugger") return cont(expect(";"));
            if (type == "{") return cont(pushlex("}"), block, poplex);
            if (type == ";") return cont();
            if (type == "if") {
                if (cx.state.lexical.info == "else" && cx.state.cc[cx.state.cc.length - 1] == poplex)
                    cx.state.cc.pop()();
                return cont(pushlex("form"), parenExpr, statement, poplex, maybeelse);
            }
            if (type == "function") return cont(functiondef);
            if (type == "for") return cont(pushlex("form"), forspec, statement, poplex);
            if (type == "variable") {
                if (isTS && value == "type") {
                    cx.marked = "keyword"
                    return cont(typeexpr, expect("operator"), typeexpr, expect(";"));
                } else if (isTS && value == "declare") {
                    cx.marked = "keyword"
                    return cont(statement)
                } else if (isTS && (value == "module" || value == "enum") && cx.stream.match(/^\s*\w/, false)) {
                    cx.marked = "keyword"
                    return cont(pushlex("form"), pattern, expect("{"), pushlex("}"), block, poplex, poplex)
                } else {
                    return cont(pushlex("stat"), maybelabel);
                }
            }
            if (type == "switch") return cont(pushlex("form"), parenExpr, expect("{"), pushlex("}", "switch"),
                block, poplex, poplex);
            if (type == "case") return cont(expression, expect(":"));
            if (type == "default") return cont(expect(":"));
            if (type == "catch") return cont(pushlex("form"), pushcontext, expect("("), funarg, expect(")"),
                statement, poplex, popcontext);
            if (type == "class") return cont(pushlex("form"), className, poplex);
            if (type == "export") return cont(pushlex("stat"), afterExport, poplex);
            if (type == "import") return cont(pushlex("stat"), afterImport, poplex);
            if (type == "async") return cont(statement)
            if (value == "@") return cont(expression, statement)
            return pass(pushlex("stat"), expression, expect(";"), poplex);
        }
        function expression(type) {
            return expressionInner(type, false);
        }
        function expressionNoComma(type) {
            return expressionInner(type, true);
        }
        function parenExpr(type) {
            if (type != "(") return pass()
            return cont(pushlex(")"), expression, expect(")"), poplex)
        }
        function expressionInner(type, noComma) {
            if (cx.state.fatArrowAt == cx.stream.start) {
                var body = noComma ? arrowBodyNoComma : arrowBody;
                if (type == "(") return cont(pushcontext, pushlex(")"), commasep(funarg, ")"), poplex, expect("=>"), body, popcontext);
                else if (type == "variable") return pass(pushcontext, pattern, expect("=>"), body, popcontext);
            }

            var maybeop = noComma ? maybeoperatorNoComma : maybeoperatorComma;
            if (atomicTypes.hasOwnProperty(type)) return cont(maybeop);
            if (type == "function") return cont(functiondef, maybeop);
            if (type == "class") return cont(pushlex("form"), classExpression, poplex);
            if (type == "keyword c" || type == "async") return cont(noComma ? expressionNoComma : expression);
            if (type == "(") return cont(pushlex(")"), maybeexpression, expect(")"), poplex, maybeop);
            if (type == "operator" || type == "spread") return cont(noComma ? expressionNoComma : expression);
            if (type == "[") return cont(pushlex("]"), arrayLiteral, poplex, maybeop);
            if (type == "{") return contCommasep(objprop, "}", null, maybeop);
            if (type == "quasi") return pass(quasi, maybeop);
            if (type == "new") return cont(maybeTarget(noComma));
            return cont();
        }
        function maybeexpression(type) {
            if (type.match(/[;\}\)\],]/)) return pass();
            return pass(expression);
        }

        function maybeoperatorComma(type, value) {
            if (type == ",") return cont(expression);
            return maybeoperatorNoComma(type, value, false);
        }
        function maybeoperatorNoComma(type, value, noComma) {
            var me = noComma == false ? maybeoperatorComma : maybeoperatorNoComma;
            var expr = noComma == false ? expression : expressionNoComma;
            if (type == "=>") return cont(pushcontext, noComma ? arrowBodyNoComma : arrowBody, popcontext);
            if (type == "operator") {
                if (/\+\+|--/.test(value) || isTS && value == "!") return cont(me);
                if (isTS && value == "<" && cx.stream.match(/^([^>]|<.*?>)*>\s*\(/, false))
                    return cont(pushlex(">"), commasep(typeexpr, ">"), poplex, me);
                if (value == "?") return cont(expression, expect(":"), expr);
                return cont(expr);
            }
            if (type == "quasi") { return pass(quasi, me); }
            if (type == ";") return;
            if (type == "(") return contCommasep(expressionNoComma, ")", "call", me);
            if (type == ".") return cont(property, me);
            if (type == "[") return cont(pushlex("]"), maybeexpression, expect("]"), poplex, me);
            if (isTS && value == "as") { cx.marked = "keyword"; return cont(typeexpr, me) }
            if (type == "regexp") {
                cx.state.lastType = cx.marked = "operator"
                cx.stream.backUp(cx.stream.pos - cx.stream.start - 1)
                return cont(expr)
            }
        }
        function quasi(type, value) {
            if (type != "quasi") return pass();
            if (value.slice(value.length - 2) != "${") return cont(quasi);
            return cont(expression, continueQuasi);
        }
        function continueQuasi(type) {
            if (type == "}") {
                cx.marked = "string-2";
                cx.state.tokenize = tokenQuasi;
                return cont(quasi);
            }
        }
        function arrowBody(type) {
            findFatArrow(cx.stream, cx.state);
            return pass(type == "{" ? statement : expression);
        }
        function arrowBodyNoComma(type) {
            findFatArrow(cx.stream, cx.state);
            return pass(type == "{" ? statement : expressionNoComma);
        }
        function maybeTarget(noComma) {
            return function(type) {
                if (type == ".") return cont(noComma ? targetNoComma : target);
                else if (type == "variable" && isTS) return cont(maybeTypeArgs, noComma ? maybeoperatorNoComma : maybeoperatorComma)
                else return pass(noComma ? expressionNoComma : expression);
            };
        }
        function target(_, value) {
            if (value == "target") { cx.marked = "keyword"; return cont(maybeoperatorComma); }
        }
        function targetNoComma(_, value) {
            if (value == "target") { cx.marked = "keyword"; return cont(maybeoperatorNoComma); }
        }
        function maybelabel(type) {
            if (type == ":") return cont(poplex, statement);
            return pass(maybeoperatorComma, expect(";"), poplex);
        }
        function property(type) {
            if (type == "variable") {cx.marked = "property"; return cont();}
        }
        function objprop(type, value) {
            if (type == "async") {
                cx.marked = "property";
                return cont(objprop);
            } else if (type == "variable" || cx.style == "keyword") {
                cx.marked = "property";
                if (value == "get" || value == "set") return cont(getterSetter);
                var m // Work around fat-arrow-detection complication for detecting typescript typed arrow params
                if (isTS && cx.state.fatArrowAt == cx.stream.start && (m = cx.stream.match(/^\s*:\s*/, false)))
                    cx.state.fatArrowAt = cx.stream.pos + m[0].length
                return cont(afterprop);
            } else if (type == "number" || type == "string") {
                cx.marked = jsonldMode ? "property" : (cx.style + " property");
                return cont(afterprop);
            } else if (type == "jsonld-keyword") {
                return cont(afterprop);
            } else if (type == "modifier") {
                return cont(objprop)
            } else if (type == "[") {
                return cont(expression, expect("]"), afterprop);
            } else if (type == "spread") {
                return cont(expressionNoComma, afterprop);
            } else if (value == "*") {
                cx.marked = "keyword";
                return cont(objprop);
            } else if (type == ":") {
                return pass(afterprop)
            }
        }
        function getterSetter(type) {
            if (type != "variable") return pass(afterprop);
            cx.marked = "property";
            return cont(functiondef);
        }
        function afterprop(type) {
            if (type == ":") return cont(expressionNoComma);
            if (type == "(") return pass(functiondef);
        }
        function commasep(what, end, sep) {
            function proceed(type, value) {
                if (sep ? sep.indexOf(type) > -1 : type == ",") {
                    var lex = cx.state.lexical;
                    if (lex.info == "call") lex.pos = (lex.pos || 0) + 1;
                    return cont(function(type, value) {
                        if (type == end || value == end) return pass()
                        return pass(what)
                    }, proceed);
                }
                if (type == end || value == end) return cont();
                return cont(expect(end));
            }
            return function(type, value) {
                if (type == end || value == end) return cont();
                return pass(what, proceed);
            };
        }
        function contCommasep(what, end, info) {
            for (var i = 3; i < arguments.length; i++)
                cx.cc.push(arguments[i]);
            return cont(pushlex(end, info), commasep(what, end), poplex);
        }
        function block(type) {
            if (type == "}") return cont();
            return pass(statement, block);
        }
        function maybetype(type, value) {
            if (isTS) {
                if (type == ":") return cont(typeexpr);
                if (value == "?") return cont(maybetype);
            }
        }
        function mayberettype(type) {
            if (isTS && type == ":") {
                if (cx.stream.match(/^\s*\w+\s+is\b/, false)) return cont(expression, isKW, typeexpr)
                else return cont(typeexpr)
            }
        }
        function isKW(_, value) {
            if (value == "is") {
                cx.marked = "keyword"
                return cont()
            }
        }
        function typeexpr(type, value) {
            if (type == "variable" || value == "void") {
                if (value == "keyof") {
                    cx.marked = "keyword"
                    return cont(typeexpr)
                } else {
                    cx.marked = "type"
                    return cont(afterType)
                }
            }
            if (type == "string" || type == "number" || type == "atom") return cont(afterType);
            if (type == "[") return cont(pushlex("]"), commasep(typeexpr, "]", ","), poplex, afterType)
            if (type == "{") return cont(pushlex("}"), commasep(typeprop, "}", ",;"), poplex, afterType)
            if (type == "(") return cont(commasep(typearg, ")"), maybeReturnType)
        }
        function maybeReturnType(type) {
            if (type == "=>") return cont(typeexpr)
        }
        function typeprop(type, value) {
            if (type == "variable" || cx.style == "keyword") {
                cx.marked = "property"
                return cont(typeprop)
            } else if (value == "?") {
                return cont(typeprop)
            } else if (type == ":") {
                return cont(typeexpr)
            } else if (type == "[") {
                return cont(expression, maybetype, expect("]"), typeprop)
            }
        }
        function typearg(type) {
            if (type == "variable") return cont(typearg)
            else if (type == ":") return cont(typeexpr)
        }
        function afterType(type, value) {
            if (value == "<") return cont(pushlex(">"), commasep(typeexpr, ">"), poplex, afterType)
            if (value == "|" || type == ".") return cont(typeexpr)
            if (type == "[") return cont(expect("]"), afterType)
            if (value == "extends") return cont(typeexpr)
        }
        function maybeTypeArgs(_, value) {
            if (value == "<") return cont(pushlex(">"), commasep(typeexpr, ">"), poplex, afterType)
        }
        function typeparam() {
            return pass(typeexpr, maybeTypeDefault)
        }
        function maybeTypeDefault(_, value) {
            if (value == "=") return cont(typeexpr)
        }
        function vardef() {
            return pass(pattern, maybetype, maybeAssign, vardefCont);
        }
        function pattern(type, value) {
            if (type == "modifier") return cont(pattern)
            if (type == "variable") { register(value); return cont(); }
            if (type == "spread") return cont(pattern);
            if (type == "[") return contCommasep(pattern, "]");
            if (type == "{") return contCommasep(proppattern, "}");
        }
        function proppattern(type, value) {
            if (type == "variable" && !cx.stream.match(/^\s*:/, false)) {
                register(value);
                return cont(maybeAssign);
            }
            if (type == "variable") cx.marked = "property";
            if (type == "spread") return cont(pattern);
            if (type == "}") return pass();
            return cont(expect(":"), pattern, maybeAssign);
        }
        function maybeAssign(_type, value) {
            if (value == "=") return cont(expressionNoComma);
        }
        function vardefCont(type) {
            if (type == ",") return cont(vardef);
        }
        function maybeelse(type, value) {
            if (type == "keyword b" && value == "else") return cont(pushlex("form", "else"), statement, poplex);
        }
        function forspec(type) {
            if (type == "(") return cont(pushlex(")"), forspec1, expect(")"), poplex);
        }
        function forspec1(type) {
            if (type == "var") return cont(vardef, expect(";"), forspec2);
            if (type == ";") return cont(forspec2);
            if (type == "variable") return cont(formaybeinof);
            return pass(expression, expect(";"), forspec2);
        }
        function formaybeinof(_type, value) {
            if (value == "in" || value == "of") { cx.marked = "keyword"; return cont(expression); }
            return cont(maybeoperatorComma, forspec2);
        }
        function forspec2(type, value) {
            if (type == ";") return cont(forspec3);
            if (value == "in" || value == "of") { cx.marked = "keyword"; return cont(expression); }
            return pass(expression, expect(";"), forspec3);
        }
        function forspec3(type) {
            if (type != ")") cont(expression);
        }
        function functiondef(type, value) {
            if (value == "*") {cx.marked = "keyword"; return cont(functiondef);}
            if (type == "variable") {register(value); return cont(functiondef);}
            if (type == "(") return cont(pushcontext, pushlex(")"), commasep(funarg, ")"), poplex, mayberettype, statement, popcontext);
            if (isTS && value == "<") return cont(pushlex(">"), commasep(typeparam, ">"), poplex, functiondef)
        }
        function funarg(type, value) {
            if (value == "@") cont(expression, funarg)
            if (type == "spread" || type == "modifier") return cont(funarg);
            return pass(pattern, maybetype, maybeAssign);
        }
        function classExpression(type, value) {
            // Class expressions may have an optional name.
            if (type == "variable") return className(type, value);
            return classNameAfter(type, value);
        }
        function className(type, value) {
            if (type == "variable") {register(value); return cont(classNameAfter);}
        }
        function classNameAfter(type, value) {
            if (value == "<") return cont(pushlex(">"), commasep(typeparam, ">"), poplex, classNameAfter)
            if (value == "extends" || value == "implements" || (isTS && type == ","))
                return cont(isTS ? typeexpr : expression, classNameAfter);
            if (type == "{") return cont(pushlex("}"), classBody, poplex);
        }
        function classBody(type, value) {
            if (type == "modifier" || type == "async" ||
                (type == "variable" &&
                    (value == "static" || value == "get" || value == "set") &&
                    cx.stream.match(/^\s+[\w$\xa1-\uffff]/, false))) {
                cx.marked = "keyword";
                return cont(classBody);
            }
            if (type == "variable" || cx.style == "keyword") {
                cx.marked = "property";
                return cont(isTS ? classfield : functiondef, classBody);
            }
            if (type == "[")
                return cont(expression, expect("]"), isTS ? classfield : functiondef, classBody)
            if (value == "*") {
                cx.marked = "keyword";
                return cont(classBody);
            }
            if (type == ";") return cont(classBody);
            if (type == "}") return cont();
            if (value == "@") return cont(expression, classBody)
        }
        function classfield(type, value) {
            if (value == "?") return cont(classfield)
            if (type == ":") return cont(typeexpr, maybeAssign)
            if (value == "=") return cont(expressionNoComma)
            return pass(functiondef)
        }
        function afterExport(type, value) {
            if (value == "*") { cx.marked = "keyword"; return cont(maybeFrom, expect(";")); }
            if (value == "default") { cx.marked = "keyword"; return cont(expression, expect(";")); }
            if (type == "{") return cont(commasep(exportField, "}"), maybeFrom, expect(";"));
            return pass(statement);
        }
        function exportField(type, value) {
            if (value == "as") { cx.marked = "keyword"; return cont(expect("variable")); }
            if (type == "variable") return pass(expressionNoComma, exportField);
        }
        function afterImport(type) {
            if (type == "string") return cont();
            return pass(importSpec, maybeMoreImports, maybeFrom);
        }
        function importSpec(type, value) {
            if (type == "{") return contCommasep(importSpec, "}");
            if (type == "variable") register(value);
            if (value == "*") cx.marked = "keyword";
            return cont(maybeAs);
        }
        function maybeMoreImports(type) {
            if (type == ",") return cont(importSpec, maybeMoreImports)
        }
        function maybeAs(_type, value) {
            if (value == "as") { cx.marked = "keyword"; return cont(importSpec); }
        }
        function maybeFrom(_type, value) {
            if (value == "from") { cx.marked = "keyword"; return cont(expression); }
        }
        function arrayLiteral(type) {
            if (type == "]") return cont();
            return pass(commasep(expressionNoComma, "]"));
        }

        function isContinuedStatement(state, textAfter) {
            return state.lastType == "operator" || state.lastType == "," ||
                isOperatorChar.test(textAfter.charAt(0)) ||
                /[,.]/.test(textAfter.charAt(0));
        }

        function expressionAllowed(stream, state, backUp) {
            return state.tokenize == tokenBase &&
                /^(?:operator|sof|keyword [bcd]|case|new|export|default|spread|[\[{}\(,;:]|=>)$/.test(state.lastType) ||
                (state.lastType == "quasi" && /\{\s*$/.test(stream.string.slice(0, stream.pos - (backUp || 0))))
        }

        // Interface

        return {
            startState: function(basecolumn) {
                var state = {
                    tokenize: tokenBase,
                    lastType: "sof",
                    cc: [],
                    lexical: new JSLexical((basecolumn || 0) - indentUnit, 0, "block", false),
                    localVars: parserConfig.localVars,
                    context: parserConfig.localVars && {vars: parserConfig.localVars},
                    indented: basecolumn || 0
                };
                if (parserConfig.globalVars && typeof parserConfig.globalVars == "object")
                    state.globalVars = parserConfig.globalVars;
                return state;
            },

            token: function(stream, state) {
                if (stream.sol()) {
                    if (!state.lexical.hasOwnProperty("align"))
                        state.lexical.align = false;
                    state.indented = stream.indentation();
                    findFatArrow(stream, state);
                }
                if (state.tokenize != tokenComment && stream.eatSpace()) return null;
                var style = state.tokenize(stream, state);
                if (type == "comment") return style;
                state.lastType = type == "operator" && (content == "++" || content == "--") ? "incdec" : type;
                return parseJS(state, style, type, content, stream);
            },

            indent: function(state, textAfter) {
                if (state.tokenize == tokenComment) return CodeMirror.Pass;
                if (state.tokenize != tokenBase) return 0;
                var firstChar = textAfter && textAfter.charAt(0), lexical = state.lexical, top
                // Kludge to prevent 'maybelse' from blocking lexical scope pops
                if (!/^\s*else\b/.test(textAfter)) for (var i = state.cc.length - 1; i >= 0; --i) {
                    var c = state.cc[i];
                    if (c == poplex) lexical = lexical.prev;
                    else if (c != maybeelse) break;
                }
                while ((lexical.type == "stat" || lexical.type == "form") &&
                (firstChar == "}" || ((top = state.cc[state.cc.length - 1]) &&
                    (top == maybeoperatorComma || top == maybeoperatorNoComma) &&
                    !/^[,\.=+\-*:?[\(]/.test(textAfter))))
                    lexical = lexical.prev;
                if (statementIndent && lexical.type == ")" && lexical.prev.type == "stat")
                    lexical = lexical.prev;
                var type = lexical.type, closing = firstChar == type;

                if (type == "vardef") return lexical.indented + (state.lastType == "operator" || state.lastType == "," ? lexical.info + 1 : 0);
                else if (type == "form" && firstChar == "{") return lexical.indented;
                else if (type == "form") return lexical.indented + indentUnit;
                else if (type == "stat")
                    return lexical.indented + (isContinuedStatement(state, textAfter) ? statementIndent || indentUnit : 0);
                else if (lexical.info == "switch" && !closing && parserConfig.doubleIndentSwitch != false)
                    return lexical.indented + (/^(?:case|default)\b/.test(textAfter) ? indentUnit : 2 * indentUnit);
                else if (lexical.align) return lexical.column + (closing ? 0 : 1);
                else return lexical.indented + (closing ? 0 : indentUnit);
            },

            electricInput: /^\s*(?:case .*?:|default:|\{|\})$/,
            blockCommentStart: jsonMode ? null : "/*",
            blockCommentEnd: jsonMode ? null : "*/",
            blockCommentContinue: jsonMode ? null : " * ",
            lineComment: jsonMode ? null : "//",
            fold: "brace",
            closeBrackets: "()[]{}''\"\"``",

            helperType: jsonMode ? "json" : "javascript",
            jsonldMode: jsonldMode,
            jsonMode: jsonMode,

            expressionAllowed: expressionAllowed,

            skipExpression: function(state) {
                var top = state.cc[state.cc.length - 1]
                if (top == expression || top == expressionNoComma) state.cc.pop()
            }
        };
    });

    CodeMirror.registerHelper("wordChars", "javascript", /[\w$]/);

    CodeMirror.defineMIME("text/javascript", "javascript");
    CodeMirror.defineMIME("text/ecmascript", "javascript");
    CodeMirror.defineMIME("application/javascript", "javascript");
    CodeMirror.defineMIME("application/x-javascript", "javascript");
    CodeMirror.defineMIME("application/ecmascript", "javascript");
    CodeMirror.defineMIME("application/json", {name: "javascript", json: true});
    CodeMirror.defineMIME("application/x-json", {name: "javascript", json: true});
    CodeMirror.defineMIME("application/ld+json", {name: "javascript", jsonld: true});
    CodeMirror.defineMIME("text/typescript", { name: "javascript", typescript: true });
    CodeMirror.defineMIME("application/typescript", { name: "javascript", typescript: true });

});

// CodeMirror, copyright (c) by Marijn Haverbeke and others
// Distributed under an MIT license: http://codemirror.net/LICENSE

(function(mod) {
        mod(CodeMirror);
})(function(CodeMirror) {
    "use strict";

    CodeMirror.defineMode("css", function(config, parserConfig) {
        var inline = parserConfig.inline
        if (!parserConfig.propertyKeywords) parserConfig = CodeMirror.resolveMode("text/css");

        var indentUnit = config.indentUnit,
            tokenHooks = parserConfig.tokenHooks,
            documentTypes = parserConfig.documentTypes || {},
            mediaTypes = parserConfig.mediaTypes || {},
            mediaFeatures = parserConfig.mediaFeatures || {},
            mediaValueKeywords = parserConfig.mediaValueKeywords || {},
            propertyKeywords = parserConfig.propertyKeywords || {},
            nonStandardPropertyKeywords = parserConfig.nonStandardPropertyKeywords || {},
            fontProperties = parserConfig.fontProperties || {},
            counterDescriptors = parserConfig.counterDescriptors || {},
            colorKeywords = parserConfig.colorKeywords || {},
            valueKeywords = parserConfig.valueKeywords || {},
            allowNested = parserConfig.allowNested,
            lineComment = parserConfig.lineComment,
            supportsAtComponent = parserConfig.supportsAtComponent === true;

        var type, override;
        function ret(style, tp) { type = tp; return style; }

        // Tokenizers

        function tokenBase(stream, state) {
            var ch = stream.next();
            if (tokenHooks[ch]) {
                var result = tokenHooks[ch](stream, state);
                if (result !== false) return result;
            }
            if (ch == "@") {
                stream.eatWhile(/[\w\\\-]/);
                return ret("def", stream.current());
            } else if (ch == "=" || (ch == "~" || ch == "|") && stream.eat("=")) {
                return ret(null, "compare");
            } else if (ch == "\"" || ch == "'") {
                state.tokenize = tokenString(ch);
                return state.tokenize(stream, state);
            } else if (ch == "#") {
                stream.eatWhile(/[\w\\\-]/);
                return ret("atom", "hash");
            } else if (ch == "!") {
                stream.match(/^\s*\w*/);
                return ret("keyword", "important");
            } else if (/\d/.test(ch) || ch == "." && stream.eat(/\d/)) {
                stream.eatWhile(/[\w.%]/);
                return ret("number", "unit");
            } else if (ch === "-") {
                if (/[\d.]/.test(stream.peek())) {
                    stream.eatWhile(/[\w.%]/);
                    return ret("number", "unit");
                } else if (stream.match(/^-[\w\\\-]+/)) {
                    stream.eatWhile(/[\w\\\-]/);
                    if (stream.match(/^\s*:/, false))
                        return ret("variable-2", "variable-definition");
                    return ret("variable-2", "variable");
                } else if (stream.match(/^\w+-/)) {
                    return ret("meta", "meta");
                }
            } else if (/[,+>*\/]/.test(ch)) {
                return ret(null, "select-op");
            } else if (ch == "." && stream.match(/^-?[_a-z][_a-z0-9-]*/i)) {
                return ret("qualifier", "qualifier");
            } else if (/[:;{}\[\]\(\)]/.test(ch)) {
                return ret(null, ch);
            } else if ((ch == "u" && stream.match(/rl(-prefix)?\(/)) ||
                (ch == "d" && stream.match("omain(")) ||
                (ch == "r" && stream.match("egexp("))) {
                stream.backUp(1);
                state.tokenize = tokenParenthesized;
                return ret("property", "word");
            } else if (/[\w\\\-]/.test(ch)) {
                stream.eatWhile(/[\w\\\-]/);
                return ret("property", "word");
            } else {
                return ret(null, null);
            }
        }

        function tokenString(quote) {
            return function(stream, state) {
                var escaped = false, ch;
                while ((ch = stream.next()) != null) {
                    if (ch == quote && !escaped) {
                        if (quote == ")") stream.backUp(1);
                        break;
                    }
                    escaped = !escaped && ch == "\\";
                }
                if (ch == quote || !escaped && quote != ")") state.tokenize = null;
                return ret("string", "string");
            };
        }

        function tokenParenthesized(stream, state) {
            stream.next(); // Must be '('
            if (!stream.match(/\s*[\"\')]/, false))
                state.tokenize = tokenString(")");
            else
                state.tokenize = null;
            return ret(null, "(");
        }

        // Context management

        function Context(type, indent, prev) {
            this.type = type;
            this.indent = indent;
            this.prev = prev;
        }

        function pushContext(state, stream, type, indent) {
            state.context = new Context(type, stream.indentation() + (indent === false ? 0 : indentUnit), state.context);
            return type;
        }

        function popContext(state) {
            if (state.context.prev)
                state.context = state.context.prev;
            return state.context.type;
        }

        function pass(type, stream, state) {
            return states[state.context.type](type, stream, state);
        }
        function popAndPass(type, stream, state, n) {
            for (var i = n || 1; i > 0; i--)
                state.context = state.context.prev;
            return pass(type, stream, state);
        }

        // Parser

        function wordAsValue(stream) {
            var word = stream.current().toLowerCase();
            if (valueKeywords.hasOwnProperty(word))
                override = "atom";
            else if (colorKeywords.hasOwnProperty(word))
                override = "keyword";
            else
                override = "variable";
        }

        var states = {};

        states.top = function(type, stream, state) {
            if (type == "{") {
                return pushContext(state, stream, "block");
            } else if (type == "}" && state.context.prev) {
                return popContext(state);
            } else if (supportsAtComponent && /@component/.test(type)) {
                return pushContext(state, stream, "atComponentBlock");
            } else if (/^@(-moz-)?document$/.test(type)) {
                return pushContext(state, stream, "documentTypes");
            } else if (/^@(media|supports|(-moz-)?document|import)$/.test(type)) {
                return pushContext(state, stream, "atBlock");
            } else if (/^@(font-face|counter-style)/.test(type)) {
                state.stateArg = type;
                return "restricted_atBlock_before";
            } else if (/^@(-(moz|ms|o|webkit)-)?keyframes$/.test(type)) {
                return "keyframes";
            } else if (type && type.charAt(0) == "@") {
                return pushContext(state, stream, "at");
            } else if (type == "hash") {
                override = "builtin";
            } else if (type == "word") {
                override = "tag";
            } else if (type == "variable-definition") {
                return "maybeprop";
            } else if (type == "interpolation") {
                return pushContext(state, stream, "interpolation");
            } else if (type == ":") {
                return "pseudo";
            } else if (allowNested && type == "(") {
                return pushContext(state, stream, "parens");
            }
            return state.context.type;
        };

        states.block = function(type, stream, state) {
            if (type == "word") {
                var word = stream.current().toLowerCase();
                if (propertyKeywords.hasOwnProperty(word)) {
                    override = "property";
                    return "maybeprop";
                } else if (nonStandardPropertyKeywords.hasOwnProperty(word)) {
                    override = "string-2";
                    return "maybeprop";
                } else if (allowNested) {
                    override = stream.match(/^\s*:(?:\s|$)/, false) ? "property" : "tag";
                    return "block";
                } else {
                    override += " error";
                    return "maybeprop";
                }
            } else if (type == "meta") {
                return "block";
            } else if (!allowNested && (type == "hash" || type == "qualifier")) {
                override = "error";
                return "block";
            } else {
                return states.top(type, stream, state);
            }
        };

        states.maybeprop = function(type, stream, state) {
            if (type == ":") return pushContext(state, stream, "prop");
            return pass(type, stream, state);
        };

        states.prop = function(type, stream, state) {
            if (type == ";") return popContext(state);
            if (type == "{" && allowNested) return pushContext(state, stream, "propBlock");
            if (type == "}" || type == "{") return popAndPass(type, stream, state);
            if (type == "(") return pushContext(state, stream, "parens");

            if (type == "hash" && !/^#([0-9a-fA-f]{3,4}|[0-9a-fA-f]{6}|[0-9a-fA-f]{8})$/.test(stream.current())) {
                override += " error";
            } else if (type == "word") {
                wordAsValue(stream);
            } else if (type == "interpolation") {
                return pushContext(state, stream, "interpolation");
            }
            return "prop";
        };

        states.propBlock = function(type, _stream, state) {
            if (type == "}") return popContext(state);
            if (type == "word") { override = "property"; return "maybeprop"; }
            return state.context.type;
        };

        states.parens = function(type, stream, state) {
            if (type == "{" || type == "}") return popAndPass(type, stream, state);
            if (type == ")") return popContext(state);
            if (type == "(") return pushContext(state, stream, "parens");
            if (type == "interpolation") return pushContext(state, stream, "interpolation");
            if (type == "word") wordAsValue(stream);
            return "parens";
        };

        states.pseudo = function(type, stream, state) {
            if (type == "meta") return "pseudo";

            if (type == "word") {
                override = "variable-3";
                return state.context.type;
            }
            return pass(type, stream, state);
        };

        states.documentTypes = function(type, stream, state) {
            if (type == "word" && documentTypes.hasOwnProperty(stream.current())) {
                override = "tag";
                return state.context.type;
            } else {
                return states.atBlock(type, stream, state);
            }
        };

        states.atBlock = function(type, stream, state) {
            if (type == "(") return pushContext(state, stream, "atBlock_parens");
            if (type == "}" || type == ";") return popAndPass(type, stream, state);
            if (type == "{") return popContext(state) && pushContext(state, stream, allowNested ? "block" : "top");

            if (type == "interpolation") return pushContext(state, stream, "interpolation");

            if (type == "word") {
                var word = stream.current().toLowerCase();
                if (word == "only" || word == "not" || word == "and" || word == "or")
                    override = "keyword";
                else if (mediaTypes.hasOwnProperty(word))
                    override = "attribute";
                else if (mediaFeatures.hasOwnProperty(word))
                    override = "property";
                else if (mediaValueKeywords.hasOwnProperty(word))
                    override = "keyword";
                else if (propertyKeywords.hasOwnProperty(word))
                    override = "property";
                else if (nonStandardPropertyKeywords.hasOwnProperty(word))
                    override = "string-2";
                else if (valueKeywords.hasOwnProperty(word))
                    override = "atom";
                else if (colorKeywords.hasOwnProperty(word))
                    override = "keyword";
                else
                    override = "error";
            }
            return state.context.type;
        };

        states.atComponentBlock = function(type, stream, state) {
            if (type == "}")
                return popAndPass(type, stream, state);
            if (type == "{")
                return popContext(state) && pushContext(state, stream, allowNested ? "block" : "top", false);
            if (type == "word")
                override = "error";
            return state.context.type;
        };

        states.atBlock_parens = function(type, stream, state) {
            if (type == ")") return popContext(state);
            if (type == "{" || type == "}") return popAndPass(type, stream, state, 2);
            return states.atBlock(type, stream, state);
        };

        states.restricted_atBlock_before = function(type, stream, state) {
            if (type == "{")
                return pushContext(state, stream, "restricted_atBlock");
            if (type == "word" && state.stateArg == "@counter-style") {
                override = "variable";
                return "restricted_atBlock_before";
            }
            return pass(type, stream, state);
        };

        states.restricted_atBlock = function(type, stream, state) {
            if (type == "}") {
                state.stateArg = null;
                return popContext(state);
            }
            if (type == "word") {
                if ((state.stateArg == "@font-face" && !fontProperties.hasOwnProperty(stream.current().toLowerCase())) ||
                    (state.stateArg == "@counter-style" && !counterDescriptors.hasOwnProperty(stream.current().toLowerCase())))
                    override = "error";
                else
                    override = "property";
                return "maybeprop";
            }
            return "restricted_atBlock";
        };

        states.keyframes = function(type, stream, state) {
            if (type == "word") { override = "variable"; return "keyframes"; }
            if (type == "{") return pushContext(state, stream, "top");
            return pass(type, stream, state);
        };

        states.at = function(type, stream, state) {
            if (type == ";") return popContext(state);
            if (type == "{" || type == "}") return popAndPass(type, stream, state);
            if (type == "word") override = "tag";
            else if (type == "hash") override = "builtin";
            return "at";
        };

        states.interpolation = function(type, stream, state) {
            if (type == "}") return popContext(state);
            if (type == "{" || type == ";") return popAndPass(type, stream, state);
            if (type == "word") override = "variable";
            else if (type != "variable" && type != "(" && type != ")") override = "error";
            return "interpolation";
        };

        return {
            startState: function(base) {
                return {tokenize: null,
                    state: inline ? "block" : "top",
                    stateArg: null,
                    context: new Context(inline ? "block" : "top", base || 0, null)};
            },

            token: function(stream, state) {
                if (!state.tokenize && stream.eatSpace()) return null;
                var style = (state.tokenize || tokenBase)(stream, state);
                if (style && typeof style == "object") {
                    type = style[1];
                    style = style[0];
                }
                override = style;
                if (type != "comment")
                    state.state = states[state.state](type, stream, state);
                return override;
            },

            indent: function(state, textAfter) {
                var cx = state.context, ch = textAfter && textAfter.charAt(0);
                var indent = cx.indent;
                if (cx.type == "prop" && (ch == "}" || ch == ")")) cx = cx.prev;
                if (cx.prev) {
                    if (ch == "}" && (cx.type == "block" || cx.type == "top" ||
                            cx.type == "interpolation" || cx.type == "restricted_atBlock")) {
                        // Resume indentation from parent context.
                        cx = cx.prev;
                        indent = cx.indent;
                    } else if (ch == ")" && (cx.type == "parens" || cx.type == "atBlock_parens") ||
                        ch == "{" && (cx.type == "at" || cx.type == "atBlock")) {
                        // Dedent relative to current context.
                        indent = Math.max(0, cx.indent - indentUnit);
                    }
                }
                return indent;
            },

            electricChars: "}",
            blockCommentStart: "/*",
            blockCommentEnd: "*/",
            blockCommentContinue: " * ",
            lineComment: lineComment,
            fold: "brace"
        };
    });

    function keySet(array) {
        var keys = {};
        for (var i = 0; i < array.length; ++i) {
            keys[array[i].toLowerCase()] = true;
        }
        return keys;
    }

    var documentTypes_ = [
        "domain", "regexp", "url", "url-prefix"
    ], documentTypes = keySet(documentTypes_);

    var mediaTypes_ = [
        "all", "aural", "braille", "handheld", "print", "projection", "screen",
        "tty", "tv", "embossed"
    ], mediaTypes = keySet(mediaTypes_);

    var mediaFeatures_ = [
        "width", "min-width", "max-width", "height", "min-height", "max-height",
        "device-width", "min-device-width", "max-device-width", "device-height",
        "min-device-height", "max-device-height", "aspect-ratio",
        "min-aspect-ratio", "max-aspect-ratio", "device-aspect-ratio",
        "min-device-aspect-ratio", "max-device-aspect-ratio", "color", "min-color",
        "max-color", "color-index", "min-color-index", "max-color-index",
        "monochrome", "min-monochrome", "max-monochrome", "resolution",
        "min-resolution", "max-resolution", "scan", "grid", "orientation",
        "device-pixel-ratio", "min-device-pixel-ratio", "max-device-pixel-ratio",
        "pointer", "any-pointer", "hover", "any-hover"
    ], mediaFeatures = keySet(mediaFeatures_);

    var mediaValueKeywords_ = [
        "landscape", "portrait", "none", "coarse", "fine", "on-demand", "hover",
        "interlace", "progressive"
    ], mediaValueKeywords = keySet(mediaValueKeywords_);

    var propertyKeywords_ = [
        "align-content", "align-items", "align-self", "alignment-adjust",
        "alignment-baseline", "anchor-point", "animation", "animation-delay",
        "animation-direction", "animation-duration", "animation-fill-mode",
        "animation-iteration-count", "animation-name", "animation-play-state",
        "animation-timing-function", "appearance", "azimuth", "backface-visibility",
        "background", "background-attachment", "background-blend-mode", "background-clip",
        "background-color", "background-image", "background-origin", "background-position",
        "background-repeat", "background-size", "baseline-shift", "binding",
        "bleed", "bookmark-label", "bookmark-level", "bookmark-state",
        "bookmark-target", "border", "border-bottom", "border-bottom-color",
        "border-bottom-left-radius", "border-bottom-right-radius",
        "border-bottom-style", "border-bottom-width", "border-collapse",
        "border-color", "border-image", "border-image-outset",
        "border-image-repeat", "border-image-slice", "border-image-source",
        "border-image-width", "border-left", "border-left-color",
        "border-left-style", "border-left-width", "border-radius", "border-right",
        "border-right-color", "border-right-style", "border-right-width",
        "border-spacing", "border-style", "border-top", "border-top-color",
        "border-top-left-radius", "border-top-right-radius", "border-top-style",
        "border-top-width", "border-width", "bottom", "box-decoration-break",
        "box-shadow", "box-sizing", "break-after", "break-before", "break-inside",
        "caption-side", "caret-color", "clear", "clip", "color", "color-profile", "column-count",
        "column-fill", "column-gap", "column-rule", "column-rule-color",
        "column-rule-style", "column-rule-width", "column-span", "column-width",
        "columns", "content", "counter-increment", "counter-reset", "crop", "cue",
        "cue-after", "cue-before", "cursor", "direction", "display",
        "dominant-baseline", "drop-initial-after-adjust",
        "drop-initial-after-align", "drop-initial-before-adjust",
        "drop-initial-before-align", "drop-initial-size", "drop-initial-value",
        "elevation", "empty-cells", "fit", "fit-position", "flex", "flex-basis",
        "flex-direction", "flex-flow", "flex-grow", "flex-shrink", "flex-wrap",
        "float", "float-offset", "flow-from", "flow-into", "font", "font-feature-settings",
        "font-family", "font-kerning", "font-language-override", "font-size", "font-size-adjust",
        "font-stretch", "font-style", "font-synthesis", "font-variant",
        "font-variant-alternates", "font-variant-caps", "font-variant-east-asian",
        "font-variant-ligatures", "font-variant-numeric", "font-variant-position",
        "font-weight", "grid", "grid-area", "grid-auto-columns", "grid-auto-flow",
        "grid-auto-rows", "grid-column", "grid-column-end", "grid-column-gap",
        "grid-column-start", "grid-gap", "grid-row", "grid-row-end", "grid-row-gap",
        "grid-row-start", "grid-template", "grid-template-areas", "grid-template-columns",
        "grid-template-rows", "hanging-punctuation", "height", "hyphens",
        "icon", "image-orientation", "image-rendering", "image-resolution",
        "inline-box-align", "justify-content", "justify-items", "justify-self", "left", "letter-spacing",
        "line-break", "line-height", "line-stacking", "line-stacking-ruby",
        "line-stacking-shift", "line-stacking-strategy", "list-style",
        "list-style-image", "list-style-position", "list-style-type", "margin",
        "margin-bottom", "margin-left", "margin-right", "margin-top",
        "marks", "marquee-direction", "marquee-loop",
        "marquee-play-count", "marquee-speed", "marquee-style", "max-height",
        "max-width", "min-height", "min-width", "move-to", "nav-down", "nav-index",
        "nav-left", "nav-right", "nav-up", "object-fit", "object-position",
        "opacity", "order", "orphans", "outline",
        "outline-color", "outline-offset", "outline-style", "outline-width",
        "overflow", "overflow-style", "overflow-wrap", "overflow-x", "overflow-y",
        "padding", "padding-bottom", "padding-left", "padding-right", "padding-top",
        "page", "page-break-after", "page-break-before", "page-break-inside",
        "page-policy", "pause", "pause-after", "pause-before", "perspective",
        "perspective-origin", "pitch", "pitch-range", "place-content", "place-items", "place-self", "play-during", "position",
        "presentation-level", "punctuation-trim", "quotes", "region-break-after",
        "region-break-before", "region-break-inside", "region-fragment",
        "rendering-intent", "resize", "rest", "rest-after", "rest-before", "richness",
        "right", "rotation", "rotation-point", "ruby-align", "ruby-overhang",
        "ruby-position", "ruby-span", "shape-image-threshold", "shape-inside", "shape-margin",
        "shape-outside", "size", "speak", "speak-as", "speak-header",
        "speak-numeral", "speak-punctuation", "speech-rate", "stress", "string-set",
        "tab-size", "table-layout", "target", "target-name", "target-new",
        "target-position", "text-align", "text-align-last", "text-decoration",
        "text-decoration-color", "text-decoration-line", "text-decoration-skip",
        "text-decoration-style", "text-emphasis", "text-emphasis-color",
        "text-emphasis-position", "text-emphasis-style", "text-height",
        "text-indent", "text-justify", "text-outline", "text-overflow", "text-shadow",
        "text-size-adjust", "text-space-collapse", "text-transform", "text-underline-position",
        "text-wrap", "top", "transform", "transform-origin", "transform-style",
        "transition", "transition-delay", "transition-duration",
        "transition-property", "transition-timing-function", "unicode-bidi",
        "user-select", "vertical-align", "visibility", "voice-balance", "voice-duration",
        "voice-family", "voice-pitch", "voice-range", "voice-rate", "voice-stress",
        "voice-volume", "volume", "white-space", "widows", "width", "will-change", "word-break",
        "word-spacing", "word-wrap", "z-index",
        // SVG-specific
        "clip-path", "clip-rule", "mask", "enable-background", "filter", "flood-color",
        "flood-opacity", "lighting-color", "stop-color", "stop-opacity", "pointer-events",
        "color-interpolation", "color-interpolation-filters",
        "color-rendering", "fill", "fill-opacity", "fill-rule", "image-rendering",
        "marker", "marker-end", "marker-mid", "marker-start", "shape-rendering", "stroke",
        "stroke-dasharray", "stroke-dashoffset", "stroke-linecap", "stroke-linejoin",
        "stroke-miterlimit", "stroke-opacity", "stroke-width", "text-rendering",
        "baseline-shift", "dominant-baseline", "glyph-orientation-horizontal",
        "glyph-orientation-vertical", "text-anchor", "writing-mode"
    ], propertyKeywords = keySet(propertyKeywords_);

    var nonStandardPropertyKeywords_ = [
        "scrollbar-arrow-color", "scrollbar-base-color", "scrollbar-dark-shadow-color",
        "scrollbar-face-color", "scrollbar-highlight-color", "scrollbar-shadow-color",
        "scrollbar-3d-light-color", "scrollbar-track-color", "shape-inside",
        "searchfield-cancel-button", "searchfield-decoration", "searchfield-results-button",
        "searchfield-results-decoration", "zoom"
    ], nonStandardPropertyKeywords = keySet(nonStandardPropertyKeywords_);

    var fontProperties_ = [
        "font-family", "src", "unicode-range", "font-variant", "font-feature-settings",
        "font-stretch", "font-weight", "font-style"
    ], fontProperties = keySet(fontProperties_);

    var counterDescriptors_ = [
        "additive-symbols", "fallback", "negative", "pad", "prefix", "range",
        "speak-as", "suffix", "symbols", "system"
    ], counterDescriptors = keySet(counterDescriptors_);

    var colorKeywords_ = [
        "aliceblue", "antiquewhite", "aqua", "aquamarine", "azure", "beige",
        "bisque", "black", "blanchedalmond", "blue", "blueviolet", "brown",
        "burlywood", "cadetblue", "chartreuse", "chocolate", "coral", "cornflowerblue",
        "cornsilk", "crimson", "cyan", "darkblue", "darkcyan", "darkgoldenrod",
        "darkgray", "darkgreen", "darkkhaki", "darkmagenta", "darkolivegreen",
        "darkorange", "darkorchid", "darkred", "darksalmon", "darkseagreen",
        "darkslateblue", "darkslategray", "darkturquoise", "darkviolet",
        "deeppink", "deepskyblue", "dimgray", "dodgerblue", "firebrick",
        "floralwhite", "forestgreen", "fuchsia", "gainsboro", "ghostwhite",
        "gold", "goldenrod", "gray", "grey", "green", "greenyellow", "honeydew",
        "hotpink", "indianred", "indigo", "ivory", "khaki", "lavender",
        "lavenderblush", "lawngreen", "lemonchiffon", "lightblue", "lightcoral",
        "lightcyan", "lightgoldenrodyellow", "lightgray", "lightgreen", "lightpink",
        "lightsalmon", "lightseagreen", "lightskyblue", "lightslategray",
        "lightsteelblue", "lightyellow", "lime", "limegreen", "linen", "magenta",
        "maroon", "mediumaquamarine", "mediumblue", "mediumorchid", "mediumpurple",
        "mediumseagreen", "mediumslateblue", "mediumspringgreen", "mediumturquoise",
        "mediumvioletred", "midnightblue", "mintcream", "mistyrose", "moccasin",
        "navajowhite", "navy", "oldlace", "olive", "olivedrab", "orange", "orangered",
        "orchid", "palegoldenrod", "palegreen", "paleturquoise", "palevioletred",
        "papayawhip", "peachpuff", "peru", "pink", "plum", "powderblue",
        "purple", "rebeccapurple", "red", "rosybrown", "royalblue", "saddlebrown",
        "salmon", "sandybrown", "seagreen", "seashell", "sienna", "silver", "skyblue",
        "slateblue", "slategray", "snow", "springgreen", "steelblue", "tan",
        "teal", "thistle", "tomato", "turquoise", "violet", "wheat", "white",
        "whitesmoke", "yellow", "yellowgreen"
    ], colorKeywords = keySet(colorKeywords_);

    var valueKeywords_ = [
        "above", "absolute", "activeborder", "additive", "activecaption", "afar",
        "after-white-space", "ahead", "alias", "all", "all-scroll", "alphabetic", "alternate",
        "always", "amharic", "amharic-abegede", "antialiased", "appworkspace",
        "arabic-indic", "armenian", "asterisks", "attr", "auto", "auto-flow", "avoid", "avoid-column", "avoid-page",
        "avoid-region", "background", "backwards", "baseline", "below", "bidi-override", "binary",
        "bengali", "blink", "block", "block-axis", "bold", "bolder", "border", "border-box",
        "both", "bottom", "break", "break-all", "break-word", "bullets", "button", "button-bevel",
        "buttonface", "buttonhighlight", "buttonshadow", "buttontext", "calc", "cambodian",
        "capitalize", "caps-lock-indicator", "caption", "captiontext", "caret",
        "cell", "center", "checkbox", "circle", "cjk-decimal", "cjk-earthly-branch",
        "cjk-heavenly-stem", "cjk-ideographic", "clear", "clip", "close-quote",
        "col-resize", "collapse", "color", "color-burn", "color-dodge", "column", "column-reverse",
        "compact", "condensed", "contain", "content", "contents",
        "content-box", "context-menu", "continuous", "copy", "counter", "counters", "cover", "crop",
        "cross", "crosshair", "currentcolor", "cursive", "cyclic", "darken", "dashed", "decimal",
        "decimal-leading-zero", "default", "default-button", "dense", "destination-atop",
        "destination-in", "destination-out", "destination-over", "devanagari", "difference",
        "disc", "discard", "disclosure-closed", "disclosure-open", "document",
        "dot-dash", "dot-dot-dash",
        "dotted", "double", "down", "e-resize", "ease", "ease-in", "ease-in-out", "ease-out",
        "element", "ellipse", "ellipsis", "embed", "end", "ethiopic", "ethiopic-abegede",
        "ethiopic-abegede-am-et", "ethiopic-abegede-gez", "ethiopic-abegede-ti-er",
        "ethiopic-abegede-ti-et", "ethiopic-halehame-aa-er",
        "ethiopic-halehame-aa-et", "ethiopic-halehame-am-et",
        "ethiopic-halehame-gez", "ethiopic-halehame-om-et",
        "ethiopic-halehame-sid-et", "ethiopic-halehame-so-et",
        "ethiopic-halehame-ti-er", "ethiopic-halehame-ti-et", "ethiopic-halehame-tig",
        "ethiopic-numeric", "ew-resize", "exclusion", "expanded", "extends", "extra-condensed",
        "extra-expanded", "fantasy", "fast", "fill", "fixed", "flat", "flex", "flex-end", "flex-start", "footnotes",
        "forwards", "from", "geometricPrecision", "georgian", "graytext", "grid", "groove",
        "gujarati", "gurmukhi", "hand", "hangul", "hangul-consonant", "hard-light", "hebrew",
        "help", "hidden", "hide", "higher", "highlight", "highlighttext",
        "hiragana", "hiragana-iroha", "horizontal", "hsl", "hsla", "hue", "icon", "ignore",
        "inactiveborder", "inactivecaption", "inactivecaptiontext", "infinite",
        "infobackground", "infotext", "inherit", "initial", "inline", "inline-axis",
        "inline-block", "inline-flex", "inline-grid", "inline-table", "inset", "inside", "intrinsic", "invert",
        "italic", "japanese-formal", "japanese-informal", "justify", "kannada",
        "katakana", "katakana-iroha", "keep-all", "khmer",
        "korean-hangul-formal", "korean-hanja-formal", "korean-hanja-informal",
        "landscape", "lao", "large", "larger", "left", "level", "lighter", "lighten",
        "line-through", "linear", "linear-gradient", "lines", "list-item", "listbox", "listitem",
        "local", "logical", "loud", "lower", "lower-alpha", "lower-armenian",
        "lower-greek", "lower-hexadecimal", "lower-latin", "lower-norwegian",
        "lower-roman", "lowercase", "ltr", "luminosity", "malayalam", "match", "matrix", "matrix3d",
        "media-controls-background", "media-current-time-display",
        "media-fullscreen-button", "media-mute-button", "media-play-button",
        "media-return-to-realtime-button", "media-rewind-button",
        "media-seek-back-button", "media-seek-forward-button", "media-slider",
        "media-sliderthumb", "media-time-remaining-display", "media-volume-slider",
        "media-volume-slider-container", "media-volume-sliderthumb", "medium",
        "menu", "menulist", "menulist-button", "menulist-text",
        "menulist-textfield", "menutext", "message-box", "middle", "min-intrinsic",
        "mix", "mongolian", "monospace", "move", "multiple", "multiply", "myanmar", "n-resize",
        "narrower", "ne-resize", "nesw-resize", "no-close-quote", "no-drop",
        "no-open-quote", "no-repeat", "none", "normal", "not-allowed", "nowrap",
        "ns-resize", "numbers", "numeric", "nw-resize", "nwse-resize", "oblique", "octal", "opacity", "open-quote",
        "optimizeLegibility", "optimizeSpeed", "oriya", "oromo", "outset",
        "outside", "outside-shape", "overlay", "overline", "padding", "padding-box",
        "painted", "page", "paused", "persian", "perspective", "plus-darker", "plus-lighter",
        "pointer", "polygon", "portrait", "pre", "pre-line", "pre-wrap", "preserve-3d",
        "progress", "push-button", "radial-gradient", "radio", "read-only",
        "read-write", "read-write-plaintext-only", "rectangle", "region",
        "relative", "repeat", "repeating-linear-gradient",
        "repeating-radial-gradient", "repeat-x", "repeat-y", "reset", "reverse",
        "rgb", "rgba", "ridge", "right", "rotate", "rotate3d", "rotateX", "rotateY",
        "rotateZ", "round", "row", "row-resize", "row-reverse", "rtl", "run-in", "running",
        "s-resize", "sans-serif", "saturation", "scale", "scale3d", "scaleX", "scaleY", "scaleZ", "screen",
        "scroll", "scrollbar", "scroll-position", "se-resize", "searchfield",
        "searchfield-cancel-button", "searchfield-decoration",
        "searchfield-results-button", "searchfield-results-decoration", "self-start", "self-end",
        "semi-condensed", "semi-expanded", "separate", "serif", "show", "sidama",
        "simp-chinese-formal", "simp-chinese-informal", "single",
        "skew", "skewX", "skewY", "skip-white-space", "slide", "slider-horizontal",
        "slider-vertical", "sliderthumb-horizontal", "sliderthumb-vertical", "slow",
        "small", "small-caps", "small-caption", "smaller", "soft-light", "solid", "somali",
        "source-atop", "source-in", "source-out", "source-over", "space", "space-around", "space-between", "space-evenly", "spell-out", "square",
        "square-button", "start", "static", "status-bar", "stretch", "stroke", "sub",
        "subpixel-antialiased", "super", "sw-resize", "symbolic", "symbols", "system-ui", "table",
        "table-caption", "table-cell", "table-column", "table-column-group",
        "table-footer-group", "table-header-group", "table-row", "table-row-group",
        "tamil",
        "telugu", "text", "text-bottom", "text-top", "textarea", "textfield", "thai",
        "thick", "thin", "threeddarkshadow", "threedface", "threedhighlight",
        "threedlightshadow", "threedshadow", "tibetan", "tigre", "tigrinya-er",
        "tigrinya-er-abegede", "tigrinya-et", "tigrinya-et-abegede", "to", "top",
        "trad-chinese-formal", "trad-chinese-informal", "transform",
        "translate", "translate3d", "translateX", "translateY", "translateZ",
        "transparent", "ultra-condensed", "ultra-expanded", "underline", "unset", "up",
        "upper-alpha", "upper-armenian", "upper-greek", "upper-hexadecimal",
        "upper-latin", "upper-norwegian", "upper-roman", "uppercase", "urdu", "url",
        "var", "vertical", "vertical-text", "visible", "visibleFill", "visiblePainted",
        "visibleStroke", "visual", "w-resize", "wait", "wave", "wider",
        "window", "windowframe", "windowtext", "words", "wrap", "wrap-reverse", "x-large", "x-small", "xor",
        "xx-large", "xx-small"
    ], valueKeywords = keySet(valueKeywords_);

    var allWords = documentTypes_.concat(mediaTypes_).concat(mediaFeatures_).concat(mediaValueKeywords_)
        .concat(propertyKeywords_).concat(nonStandardPropertyKeywords_).concat(colorKeywords_)
        .concat(valueKeywords_);
    CodeMirror.registerHelper("hintWords", "css", allWords);

    function tokenCComment(stream, state) {
        var maybeEnd = false, ch;
        while ((ch = stream.next()) != null) {
            if (maybeEnd && ch == "/") {
                state.tokenize = null;
                break;
            }
            maybeEnd = (ch == "*");
        }
        return ["comment", "comment"];
    }

    CodeMirror.defineMIME("text/css", {
        documentTypes: documentTypes,
        mediaTypes: mediaTypes,
        mediaFeatures: mediaFeatures,
        mediaValueKeywords: mediaValueKeywords,
        propertyKeywords: propertyKeywords,
        nonStandardPropertyKeywords: nonStandardPropertyKeywords,
        fontProperties: fontProperties,
        counterDescriptors: counterDescriptors,
        colorKeywords: colorKeywords,
        valueKeywords: valueKeywords,
        tokenHooks: {
            "/": function(stream, state) {
                if (!stream.eat("*")) return false;
                state.tokenize = tokenCComment;
                return tokenCComment(stream, state);
            }
        },
        name: "css"
    });

    CodeMirror.defineMIME("text/x-scss", {
        mediaTypes: mediaTypes,
        mediaFeatures: mediaFeatures,
        mediaValueKeywords: mediaValueKeywords,
        propertyKeywords: propertyKeywords,
        nonStandardPropertyKeywords: nonStandardPropertyKeywords,
        colorKeywords: colorKeywords,
        valueKeywords: valueKeywords,
        fontProperties: fontProperties,
        allowNested: true,
        lineComment: "//",
        tokenHooks: {
            "/": function(stream, state) {
                if (stream.eat("/")) {
                    stream.skipToEnd();
                    return ["comment", "comment"];
                } else if (stream.eat("*")) {
                    state.tokenize = tokenCComment;
                    return tokenCComment(stream, state);
                } else {
                    return ["operator", "operator"];
                }
            },
            ":": function(stream) {
                if (stream.match(/\s*\{/, false))
                    return [null, null]
                return false;
            },
            "$": function(stream) {
                stream.match(/^[\w-]+/);
                if (stream.match(/^\s*:/, false))
                    return ["variable-2", "variable-definition"];
                return ["variable-2", "variable"];
            },
            "#": function(stream) {
                if (!stream.eat("{")) return false;
                return [null, "interpolation"];
            }
        },
        name: "css",
        helperType: "scss"
    });

    CodeMirror.defineMIME("text/x-less", {
        mediaTypes: mediaTypes,
        mediaFeatures: mediaFeatures,
        mediaValueKeywords: mediaValueKeywords,
        propertyKeywords: propertyKeywords,
        nonStandardPropertyKeywords: nonStandardPropertyKeywords,
        colorKeywords: colorKeywords,
        valueKeywords: valueKeywords,
        fontProperties: fontProperties,
        allowNested: true,
        lineComment: "//",
        tokenHooks: {
            "/": function(stream, state) {
                if (stream.eat("/")) {
                    stream.skipToEnd();
                    return ["comment", "comment"];
                } else if (stream.eat("*")) {
                    state.tokenize = tokenCComment;
                    return tokenCComment(stream, state);
                } else {
                    return ["operator", "operator"];
                }
            },
            "@": function(stream) {
                if (stream.eat("{")) return [null, "interpolation"];
                if (stream.match(/^(charset|document|font-face|import|(-(moz|ms|o|webkit)-)?keyframes|media|namespace|page|supports)\b/, false)) return false;
                stream.eatWhile(/[\w\\\-]/);
                if (stream.match(/^\s*:/, false))
                    return ["variable-2", "variable-definition"];
                return ["variable-2", "variable"];
            },
            "&": function() {
                return ["atom", "atom"];
            }
        },
        name: "css",
        helperType: "less"
    });

    CodeMirror.defineMIME("text/x-gss", {
        documentTypes: documentTypes,
        mediaTypes: mediaTypes,
        mediaFeatures: mediaFeatures,
        propertyKeywords: propertyKeywords,
        nonStandardPropertyKeywords: nonStandardPropertyKeywords,
        fontProperties: fontProperties,
        counterDescriptors: counterDescriptors,
        colorKeywords: colorKeywords,
        valueKeywords: valueKeywords,
        supportsAtComponent: true,
        tokenHooks: {
            "/": function(stream, state) {
                if (!stream.eat("*")) return false;
                state.tokenize = tokenCComment;
                return tokenCComment(stream, state);
            }
        },
        name: "css",
        helperType: "gss"
    });

});

// CodeMirror, copyright (c) by Marijn Haverbeke and others
// Distributed under an MIT license: http://codemirror.net/LICENSE

(function(mod) {
        mod(CodeMirror);
})(function(CodeMirror) {
    "use strict";

    var defaultTags = {
        script: [
            ["lang", /(javascript|babel)/i, "javascript"],
            ["type", /^(?:text|application)\/(?:x-)?(?:java|ecma)script$|^module$|^$/i, "javascript"],
            ["type", /./, "text/plain"],
            [null, null, "javascript"]
        ],
        style:  [
            ["lang", /^css$/i, "css"],
            ["type", /^(text\/)?(x-)?(stylesheet|css)$/i, "css"],
            ["type", /./, "text/plain"],
            [null, null, "css"]
        ]
    };

    function maybeBackup(stream, pat, style) {
        var cur = stream.current(), close = cur.search(pat);
        if (close > -1) {
            stream.backUp(cur.length - close);
        } else if (cur.match(/<\/?$/)) {
            stream.backUp(cur.length);
            if (!stream.match(pat, false)) stream.match(cur);
        }
        return style;
    }

    var attrRegexpCache = {};
    function getAttrRegexp(attr) {
        var regexp = attrRegexpCache[attr];
        if (regexp) return regexp;
        return attrRegexpCache[attr] = new RegExp("\\s+" + attr + "\\s*=\\s*('|\")?([^'\"]+)('|\")?\\s*");
    }

    function getAttrValue(text, attr) {
        var match = text.match(getAttrRegexp(attr))
        return match ? /^\s*(.*?)\s*$/.exec(match[2])[1] : ""
    }

    function getTagRegexp(tagName, anchored) {
        return new RegExp((anchored ? "^" : "") + "<\/\s*" + tagName + "\s*>", "i");
    }

    function addTags(from, to) {
        for (var tag in from) {
            var dest = to[tag] || (to[tag] = []);
            var source = from[tag];
            for (var i = source.length - 1; i >= 0; i--)
                dest.unshift(source[i])
        }
    }

    function findMatchingMode(tagInfo, tagText) {
        for (var i = 0; i < tagInfo.length; i++) {
            var spec = tagInfo[i];
            if (!spec[0] || spec[1].test(getAttrValue(tagText, spec[0]))) return spec[2];
        }
    }

    CodeMirror.defineMode("htmlmixed", function (config, parserConfig) {
        var htmlMode = CodeMirror.getMode(config, {
            name: "xml",
            htmlMode: true,
            multilineTagIndentFactor: parserConfig.multilineTagIndentFactor,
            multilineTagIndentPastTag: parserConfig.multilineTagIndentPastTag
        });

        var tags = {};
        var configTags = parserConfig && parserConfig.tags, configScript = parserConfig && parserConfig.scriptTypes;
        addTags(defaultTags, tags);
        if (configTags) addTags(configTags, tags);
        if (configScript) for (var i = configScript.length - 1; i >= 0; i--)
            tags.script.unshift(["type", configScript[i].matches, configScript[i].mode])

        function html(stream, state) {
            var style = htmlMode.token(stream, state.htmlState), tag = /\btag\b/.test(style), tagName
            if (tag && !/[<>\s\/]/.test(stream.current()) &&
                (tagName = state.htmlState.tagName && state.htmlState.tagName.toLowerCase()) &&
                tags.hasOwnProperty(tagName)) {
                state.inTag = tagName + " "
            } else if (state.inTag && tag && />$/.test(stream.current())) {
                var inTag = /^([\S]+) (.*)/.exec(state.inTag)
                state.inTag = null
                var modeSpec = stream.current() == ">" && findMatchingMode(tags[inTag[1]], inTag[2])
                var mode = CodeMirror.getMode(config, modeSpec)
                var endTagA = getTagRegexp(inTag[1], true), endTag = getTagRegexp(inTag[1], false);
                state.token = function (stream, state) {
                    if (stream.match(endTagA, false)) {
                        state.token = html;
                        state.localState = state.localMode = null;
                        return null;
                    }
                    return maybeBackup(stream, endTag, state.localMode.token(stream, state.localState));
                };
                state.localMode = mode;
                state.localState = CodeMirror.startState(mode, htmlMode.indent(state.htmlState, ""));
            } else if (state.inTag) {
                state.inTag += stream.current()
                if (stream.eol()) state.inTag += " "
            }
            return style;
        };

        return {
            startState: function () {
                var state = CodeMirror.startState(htmlMode);
                return {token: html, inTag: null, localMode: null, localState: null, htmlState: state};
            },

            copyState: function (state) {
                var local;
                if (state.localState) {
                    local = CodeMirror.copyState(state.localMode, state.localState);
                }
                return {token: state.token, inTag: state.inTag,
                    localMode: state.localMode, localState: local,
                    htmlState: CodeMirror.copyState(htmlMode, state.htmlState)};
            },

            token: function (stream, state) {
                return state.token(stream, state);
            },

            indent: function (state, textAfter, line) {
                if (!state.localMode || /^\s*<\//.test(textAfter))
                    return htmlMode.indent(state.htmlState, textAfter);
                else if (state.localMode.indent)
                    return state.localMode.indent(state.localState, textAfter, line);
                else
                    return CodeMirror.Pass;
            },

            innerMode: function (state) {
                return {state: state.localState || state.htmlState, mode: state.localMode || htmlMode};
            }
        };
    }, "xml", "javascript", "css");

    CodeMirror.defineMIME("text/html", "htmlmixed");
});

(function(a) {
  var r = a.fn.domManip,
    d = "_tmplitem",
    q = /^[^<]*(<[\w\W]+>)[^>]*$|\{\{\! /,
    b = {},
    f = {},
    e, p = {
      key: 0,
      data: {}
    },
    h = 0,
    c = 0,
    l = [];

  function g(e, d, g, i) {
    var c = {
      data: i || (d ? d.data : {}),
      _wrap: d ? d._wrap : null,
      tmpl: null,
      parent: d || null,
      nodes: [],
      calls: u,
      nest: w,
      wrap: x,
      html: v,
      update: t
    };
    e && a.extend(c, e, {
      nodes: [],
      parent: d
    });
    if (g) {
      c.tmpl = g;
      c._ctnt = c._ctnt || c.tmpl(a, c);
      c.key = ++h;
      (l.length ? f : b)[h] = c
    }
    return c
  }
  a.each({
    appendTo: "append",
    prependTo: "prepend",
    insertBefore: "before",
    insertAfter: "after",
    replaceAll: "replaceWith"
  }, function(f, d) {
    a.fn[f] = function(n) {
      var g = [],
        i = a(n),
        k, h, m, l, j = this.length === 1 && this[0].parentNode;
      e = b || {};
      if (j && j.nodeType === 11 && j.childNodes.length === 1 && i.length === 1) {
        i[d](this[0]);
        g = this
      } else {
        for (h = 0, m = i.length; h < m; h++) {
          c = h;
          k = (h > 0 ? this.clone(true) : this).get();
          a.fn[d].apply(a(i[h]), k);
          g = g.concat(k)
        }
        c = 0;
        g = this.pushStack(g, f, i.selector)
      }
      l = e;
      e = null;
      a.tmpl.complete(l);
      return g
    }
  });
  a.fn.extend({
    tmpl: function(d, c, b) {
      return a.tmpl(this[0], d, c, b)
    },
    tmplItem: function() {
      return a.tmplItem(this[0])
    },
    template: function(b) {
      return a.template(b, this[0])
    },
    domManip: function(d, l, j) {
      if (d[0] && d[0].nodeType) {
        var f = a.makeArray(arguments),
          g = d.length,
          i = 0,
          h;
        while (i < g && !(h = a.data(d[i++], "tmplItem")));
        if (g > 1) f[0] = [a.makeArray(d)];
        if (h && c) f[2] = function(b) {
          a.tmpl.afterManip(this, b, j)
        };
        r.apply(this, f)
      } else r.apply(this, arguments);
      c = 0;
      !e && a.tmpl.complete(b);
      return this
    }
  });
  a.extend({
    tmpl: function(d, h, e, c) {
      var j, k = !c;
      if (k) {
        c = p;
        d = a.template[d] || a.template(null, d);
        f = {}
      } else if (!d) {
        d = c.tmpl;
        b[c.key] = c;
        c.nodes = [];
        c.wrapped && n(c, c.wrapped);
        return a(i(c, null, c.tmpl(a, c)))
      }
      if (!d) return [];
      if (typeof h === "function") h = h.call(c || {});
      e && e.wrapped && n(e, e.wrapped);
      j = a.isArray(h) ? a.map(h, function(a) {
        return a ? g(e, c, d, a) : null
      }) : [g(e, c, d, h)];
      return k ? a(i(c, null, j)) : j
    },
    tmplItem: function(b) {
      var c;
      if (b instanceof a) b = b[0];
      while (b && b.nodeType === 1 && !(c = a.data(b, "tmplItem")) && (b = b.parentNode));
      return c || p
    },
    template: function(c, b) {
      if (b) {
        if (typeof b === "string") b = o(b);
        else if (b instanceof a) b = b[0] || {};
        if (b.nodeType) b = a.data(b, "tmpl") || a.data(b, "tmpl", o(b.innerHTML));
        return typeof c === "string" ? (a.template[c] = b) : b
      }
      return c ? typeof c !== "string" ? a.template(null, c) : a.template[c] || a.template(null, q.test(c) ? c : a(c)) : null
    },
    encode: function(a) {
      return ("" + a).split("<").join("&lt;").split(">").join("&gt;").split('"').join("&#34;").split("'").join("&#39;")
    }
  });
  a.extend(a.tmpl, {
    tag: {
      tmpl: {
        _default: {
          $2: "null"
        },
        open: "if($notnull_1){_=_.concat($item.nest($1,$2));}"
      },
      wrap: {
        _default: {
          $2: "null"
        },
        open: "$item.calls(_,$1,$2);_=[];",
        close: "call=$item.calls();_=call._.concat($item.wrap(call,_));"
      },
      each: {
        _default: {
          $2: "$index, $value"
        },
        open: "if($notnull_1){$.each($1a,function($2){with(this){",
        close: "}});}"
      },
      "if": {
        open: "if(($notnull_1) && $1a){",
        close: "}"
      },
      "else": {
        _default: {
          $1: "true"
        },
        open: "}else if(($notnull_1) && $1a){"
      },
      html: {
        open: "if($notnull_1){_.push($1a);}"
      },
      "=": {
        _default: {
          $1: "$data"
        },
        open: "if($notnull_1){_.push($.encode($1a));}"
      },
      "!": {
        open: ""
      }
    },
    complete: function() {
      b = {}
    },
    afterManip: function(f, b, d) {
      var e = b.nodeType === 11 ? a.makeArray(b.childNodes) : b.nodeType === 1 ? [b] : [];
      d.call(f, b);
      m(e);
      c++
    }
  });

  function i(e, g, f) {
    var b, c = f ? a.map(f, function(a) {
      return typeof a === "string" ? e.key ? a.replace(/(<\w+)(?=[\s>])(?![^>]*_tmplitem)([^>]*)/g, "$1 " + d + '="' + e.key + '" $2') : a : i(a, e, a._ctnt)
    }) : e;
    if (g) return c;
    c = c.join("");
    c.replace(/^\s*([^<\s][^<]*)?(<[\w\W]+>)([^>]*[^>\s])?\s*$/, function(f, c, e, d) {
      b = a(e).get();
      m(b);
      if (c) b = j(c).concat(b);
      if (d) b = b.concat(j(d))
    });
    return b ? b : j(c)
  }
  function j(c) {
    var b = document.createElement("div");
    b.innerHTML = c;
    return a.makeArray(b.childNodes)
  }
  function o(b) {
    return new Function("jQuery", "$item", "var $=jQuery,call,_=[],$data=$item.data;with($data){_.push('" + a.trim(b).replace(/([\\'])/g, "\\$1").replace(/[\r\t\n]/g, " ").replace(/\$\{([^\}]*)\}/g, "{{= $1}}").replace(/\{\{(\/?)(\w+|.)(?:\(((?:[^\}]|\}(?!\}))*?)?\))?(?:\s+(.*?)?)?(\(((?:[^\}]|\}(?!\}))*?)\))?\s*\}\}/g, function(m, l, j, d, b, c, e) {
      var i = a.tmpl.tag[j],
        h, f, g;
      if (!i) throw "Template command not found: " + j;
      h = i._default || [];
      if (c && !/\w$/.test(b)) {
        b += c;
        c = ""
      }
      if (b) {
        b = k(b);
        e = e ? "," + k(e) + ")" : c ? ")" : "";
        f = c ? b.indexOf(".") > -1 ? b + c : "(" + b + ").call($item" + e : b;
        g = c ? f : "(typeof(" + b + ")==='function'?(" + b + ").call($item):(" + b + "))"
      } else g = f = h.$1 || "null";
      d = k(d);
      return "');" + i[l ? "close" : "open"].split("$notnull_1").join(b ? "typeof(" + b + ")!=='undefined' && (" + b + ")!=null" : "true").split("$1a").join(g).split("$1").join(f).split("$2").join(d ? d.replace(/\s*([^\(]+)\s*(\((.*?)\))?/g, function(d, c, b, a) {
        a = a ? "," + a + ")" : b ? ")" : "";
        return a ? "(" + c + ").call($item" + a : d
      }) : h.$2 || "") + "_.push('"
    }) + "');}return _;")
  }
  function n(c, b) {
    c._wrap = i(c, true, a.isArray(b) ? b : [q.test(b) ? b : a(b).html()]).join("")
  }
  function k(a) {
    return a ? a.replace(/\\'/g, "'").replace(/\\\\/g, "\\") : null
  }
  function s(b) {
    var a = document.createElement("div");
    a.appendChild(b.cloneNode(true));
    return a.innerHTML
  }
  function m(o) {
    var n = "_" + c,
      k, j, l = {},
      e, p, i;
    for (e = 0, p = o.length; e < p; e++) {
      if ((k = o[e]).nodeType !== 1) continue;
      j = k.getElementsByTagName("*");
      for (i = j.length - 1; i >= 0; i--) m(j[i]);
      m(k)
    }
    function m(j) {
      var p, i = j,
        k, e, m;
      if (m = j.getAttribute(d)) {
        while (i.parentNode && (i = i.parentNode).nodeType === 1 && !(p = i.getAttribute(d)));
        if (p !== m) {
          i = i.parentNode ? i.nodeType === 11 ? 0 : i.getAttribute(d) || 0 : 0;
          if (!(e = b[m])) {
            e = f[m];
            e = g(e, b[i] || f[i], null, true);
            e.key = ++h;
            b[h] = e
          }
          c && o(m)
        }
        j.removeAttribute(d)
      } else if (c && (e = a.data(j, "tmplItem"))) {
        o(e.key);
        b[e.key] = e;
        i = a.data(j.parentNode, "tmplItem");
        i = i ? i.key : 0
      }
      if (e) {
        k = e;
        while (k && k.key != i) {
          k.nodes.push(j);
          k = k.parent
        }
        delete e._ctnt;
        delete e._wrap;
        a.data(j, "tmplItem", e)
      }
      function o(a) {
        a = a + n;
        e = l[a] = l[a] || g(e, b[e.parent.key + n] || e.parent, null, true)
      }
    }
  }
  function u(a, d, c, b) {
    if (!a) return l.pop();
    l.push({
      _: a,
      tmpl: d,
      item: this,
      data: c,
      options: b
    })
  }
  function w(d, c, b) {
    return a.tmpl(a.template(d), c, b, this)
  }
  function x(b, d) {
    var c = b.options || {};
    c.wrapped = d;
    return a.tmpl(a.template(b.tmpl), b.data, c, b.item)
  }
  function v(d, c) {
    var b = this._wrap;
    return a.map(a(a.isArray(b) ? b.join("") : b).filter(d || "*"), function(a) {
      return c ? a.innerText || a.textContent : a.outerHTML || s(a)
    })
  }
  function t() {
    var b = this.nodes;
    a.tmpl(null, null, null, this).insertBefore(b[0]);
    a(b).remove()
  }
})(jQuery)
/*
 * Konami Code For jQuery Plugin
 *
 * Using the Konami code, easily configure and Easter Egg for your page or any element on the page.
 *
 * Copyright 2011 - 2013 8BIT, http://8BIT.io
 * Released under the MIT License
 */;(function(e){"use strict";e.fn.konami=function(t){var n,r,i,s,o,u,a,n=e.extend({},e.fn.konami.defaults,t);return this.each(function(){r=[38,38,40,40,37,39,37,39,66,65];i=[];e(window).keyup(function(e){s=e.keyCode?e.keyCode:e.which;i.push(s);if(10===i.length){o=!0;for(u=0,a=r.length;u<a;u++)r[u]!==i[u]&&(o=!1);o&&n.cheat();i=[]}})})};e.fn.konami.defaults={cheat:null}})(jQuery);


/**
 * 레이아웃 리스트
 */
var SDELayout = function(){
    return [];
};


/**
 * CSS 리스트
 */
var SDELayoutCSS = [
    ["/css/default.css"]
];


/**
 * Import 리스트
 */
var SDELayoutImport = [
    ["/import/menu.html"]
];



/**
 * 앱 리스트 
 */
var SDEApps= function() {
    var apps = [];
    
    for (var k in SDE.CODE_ASSIST) {
        apps.push([k, SDE.CODE_ASSIST[k].name]); 
    }
    
    return apps;
}();

/**
 * 변수명 리스트
 */
var SDEVariables = function() {
    
    var variables = {};
    
    for (var k in SDE.CODE_ASSIST) {                
        
        for (var f in SDE.CODE_ASSIST[k]['actions']) {
            variables[k+'_'+f] = {
                name : SDE.CODE_ASSIST[k]['actions'][f]['name'],
                vars : handleVar(SDE.CODE_ASSIST[k]['actions'][f]['var'])    
            };          
        }        
    }
    
    return variables;
    
    function handleVar(vars)
    {
        _vars  = [];        
        for (var k in vars) {
            _vars.push([k, vars[k]]);
        }        
        return _vars;
    }
    
}();

/**
 * 시퀀스 리스트 
 */
var SDEModuleSequance = function() {
    
    var sequance = {};
    var aSeq     = [];
    
    for (var k in SDE.CODE_ASSIST) {   
         
        if (SDE.CODE_ASSIST[k].seq == null) {
            continue;
        }
        
        aSeq = getSequance(SDE.CODE_ASSIST[k].seq);
        
        for (var f in SDE.CODE_ASSIST[k]['actions']) {            
            if (jQuery.inArray(f, SDE.CODE_ASSIST[k].no_seq) == -1) {
                sequance[k+'_'+f] = aSeq;
            }
        }        
    }
    
    function getSequance(aSeq) {
        var _aSeq = [];
        
        for(var i=0; i<aSeq.length; i++) {
            _aSeq.push([aSeq[i].value, aSeq[i].name]);
        }
        return _aSeq;
    }
    
    return sequance;    
}();
var AutoComplete = (function() {
    var self = {
        setOption: setOption,
        keyEvent: keyEvent,
        startComplete: startComplete,
        insert: insert,
        convertData: convertData,
        replace_start: 0,
        replace_end: 0,
        replace_tail: "",
        move_cursor: 0,
        is_run: true
    };
    var options = {
        unsort: []
    };

    function setOption(option, value) {
        options[option] = value;
    }
    var shiftCode = {
        50: "@",
        52: "$",
        188: "<",
        220: "|"
    };

    function keyEvent(editor, e) {
        if (e.type != "keydown")
            return;
        if (editor.getOption("readOnly"))
            return;
        var code = e.keyCode;

        // 32 : SPACE
        if (code === 32 && ((e.ctrlKey || e.metaKey) || e.shiftKey) && !e.altKey) {
            CodeMirror.e_stop(e);
            setTimeout(function() {
                startComplete(editor, true);
            });
            return;
        }
        if (e.altKey || e.ctrlKey || e.metaKey) return;
        if (e.shiftKey) {
            if (shiftCode.hasOwnProperty(code)) {
                CodeMirror.e_stop(e);
                insert(shiftCode[code]);
                setTimeout(function() {
                    startComplete(editor);
                });
            }
        }

        function insert(string) {
            var cur = editor.getCursor(false);
            editor.replaceRange(string, cur, cur);
        }
    };
    var cur;

    function insert(editor, string, move) {
        var self = AutoComplete;
        editor.replaceRange(string + self.replace_tail, {
            line: cur.line,
            ch: cur.ch + self.replace_start
        }, {
            line: cur.line,
            ch: cur.ch + self.replace_end
        });
        move = move || self.move_cursor;
        if (move) {
            editor.setCursor({
                line: cur.line,
                ch: cur.ch + self.replace_start + string.length + self.replace_tail.length + move
            });
        }
        self.is_run = true;
    }

    function startComplete(editor, direct) {
        if (editor.somethingSelected()) return;
        var self = AutoComplete;
        self.replace_start = 0;
        self.replace_end = 0;
        self.replace_tail = "";
        self.move_cursor = 0;
        cur = editor.getCursor(false);

        var completions = editor.getOption("AutoCompletions").get(editor, cur);
        if (!completions || completions.list.length === 0) {
            AutoCompleteLayer.close();
            return;
        }
        var completionList = completions.list;
        if (completionList.length == 1 && direct == true) {
            insert(editor, completionList[0][0] + (completionList[0][2] || ""));
            return true;
        }
        if (jQuery.inArray(completions.name, options.unsort) === -1) {
            completionList = completionList.sort(function(a1, a2) {
                return ((a1[0] == a2[0]) ? 0 : ((a1[0] > a2[0]) ? 1 : -1));
            });
        }
        var max_length_value = 0;
        for (var i = 0; i < completionList.length; ++i) {
            var l = String(completionList[i][0]).length;
            if (max_length_value < l) {
                max_length_value = l;
            }
        }
        var data = [];
        for (var i = 0; i < completions.list.length; ++i) {
            var value = String(completions.list[i][0]);
            var text = value + new Array(max_length_value - value.length + 3).join(" ") + (completions.list[i][1] || "");
            data.push({
                label: text.replace(/ /g, "&nbsp;"),
                value: value + (completions.list[i][2] || ""),
                move: completions.list[i][3] || 0
            });
        }
        editor._removeKeyEvent(AutoComplete.keyEvent);
        editor._setKeyEvent(AutoCompleteLayer.keyEvent);
        AutoCompleteLayer.setData(editor, data);
    }

    function convertData(dataName, findText, data) {
        self.replace_start = -findText.length;
        var ret = {
            name: dataName,
            list: []
        };
        if (findText) {
            var matcher = new RegExp("^" + findText.replace(/[-[\]{}()*+?.,\\^$|#\s]/g, "\\$&"), "i");
            for (var i = 0, limit = data.length; i < limit; ++i) {
                if (matcher.test(data[i][0])) {
                    ret.list.push(data[i]);
                }
            }
        }
        else {
            ret.list = data;
        }
        return ret;
    }
    return self;
})();
var AutoCompleteLayer = (function() {
    var editor, editorScroller;
    var _data = [];
    var _menu = $('<ul class="ui-autocomplete"></ul>').appendTo(document.body).menu({
        selected: function(event, ui) {
            if (AutoComplete.is_run) {
                setTimeout(function() {
                    AutoComplete.startComplete(editor, true)
                }, 50);
            }
            if (ui.item) AutoComplete.insert(editor, ui.item.data("value"), Number(ui.item.data("move")));
            _close();
            editor.focus();
        }
    }).zIndex(3).css({
        position: "absolute",
        top: 0,
        left: 0
    }).hide().data("menu");
    $(window).bind("blur", _close);

    function _suggest() {
        var items = _data;
        if (!items || !items.length) {
            _close();
            return;
        }
        var ul = _menu.element.empty();
        var aHtml = [];
        for (var i = 0, limit = items.length; i < limit; i++) {
            aHtml.push('<li data-value="' + items[i]["value"] + '" data-move="' + items[i]["move"] + '"><a><span>' + items[i]["label"] + '</span></a></li>');
        }
        ul.get(0).innerHTML = aHtml.join("");
        _menu.refresh();
        _menu.next(new $.Event("mouseover"));
        var cur = editor.getCursor(false);
        var pos = editor.charCoords(cur);
        var parentOffset = $(editorScroller).offset();
        ul.css({
            top: (pos.top + 16) + editorScroller.scrollTop - parentOffset.top,
            left: pos.left - parentOffset.left,
            width: 1000
        });
        var max_width = 0;
        ul.show();
        ul.find(">li>a>span").each(function() {
            var width = this.offsetWidth;
            if (max_width < width) {
                max_width = width;
            }
        });
        ul.width(max_width + 40);
    }

    function _close() {
        if (!_menu.element.is(":visible")) {
            return;
        }
        _menu.element.hide().appendTo(document.body);
        _menu.deactivate();
        editor._removeKeyEvent(AutoCompleteLayer.keyEvent);
        editor._setKeyEvent(AutoComplete.keyEvent);
    }

    function _move(direction, event) {
        if (!_menu.element.is(":visible")) {
            return;
        }
        if (_menu.first() && /^previous/.test(direction) || _menu.last() && /^next/.test(direction)) {
            _menu.deactivate();
            return;
        }
        _menu[direction](event);
    }
    return {
        keyEvent: function(instance, e) {
            var code = e.keyCode;
            // 13 : ENTER
            if (code === 13) {
                if (e.type === 'keydown') {
                    CodeMirror.e_stop(e);
                    _menu.select(e);
                }
                return true;
            }
            if (e.type == "keyup") {
                return;
            }
            // 27 : ESC
            if (code === 27) {
                _close();
                return true;
            }
            // 32 : SPACE
            if (code === 32 && ((e.ctrlKey || e.metaKey) || e.shiftKey) && !e.altKey) {
                CodeMirror.e_stop(e);
                return true;
            }

            // 16 : SHIFT, 17 : CTRL, 18 : ALT
            if (code === 16 || code === 17 || code === 18) {
                return true;
            }
            var cursorKeys = {
                33: "previousPage",
                34: "nextPage",
                38: "previous",
                40: "next"
            };
            if (cursorKeys.hasOwnProperty(code)) {
                if (e.type == "keydown") {
                    _move(cursorKeys[code], e);
                    CodeMirror.e_stop(e);
                    return true;
                }
            }
            setTimeout(function() {
                AutoComplete.startComplete(instance)
            }, 50);
        },
        setData: function(instance, data) {
            if (instance) {
                editor = instance;
                editorScroller = instance.getScrollerElement();
            }
            $(editorScroller.firstChild).unbind("mousedown.menu").bind("mousedown.menu", _close);
            _menu.element.appendTo(editorScroller);
            _data = data;
            _suggest();
        },
        close: function() {
            _close();
        }
    };
})();
var SDEAutoCompletions = (function() {
    var self = {
        get: get
    };

    function get(editor, cur) {
        var token = editor.getTokenAt(cur);
        var string = editor.getLine(cur.line);
        var type = token.type;
        var mode = editor.getOption("mode");

        switch (mode) {
            case "text/html":
                var Completions = htmlSDE(type, string, cur, token);
                if (Completions !== false) {
                    return Completions;
                }
                return html(type, string, cur, token);
            default:
                return null;
        }

        function htmlSDE(type, string, cur, token) {
            var prevString = string.substr(0, cur.ch);
            var matches = prevString.match(/{\$([a-zA-Z0-9_]+)\|([a-zA-Z0-9_]*)$/);
            if (matches) return getSDEModifier(string, cur, token, matches);
            var matches = prevString.match(/{\$([a-zA-Z0-9_]*)$/);
            if (matches) return getSDEVariables(string, cur, token, matches);

            if (type == "comment") {
                var fn_list = [];
                fn_list.push([getSDEModuleOptions, /\s*\$([a-zA-Z0-9_]*)$/]);
                fn_list.push([getSDELayout, /<!--@layout\(([^)]*)$/]);
                fn_list.push([getSDELayoutCSS, /<!--@css\(([^)]*)$/]);
                fn_list.push([getSDELayoutJS, /<!--@js\(([^)]*)$/]);
                fn_list.push([getSDELayoutImport, /<!--@import\(([^)]*)$/]);
                fn_list.push([getSDELayoutGrammar, /<!--@(\S*)$/]);
                for (var i = 0, limit = fn_list.length; i < limit; i++) {
                    var matches = prevString.match(fn_list[i][1]);
                    if (matches) return fn_list[i][0](string, cur, token, matches);
                }
            }
            else if (type == "string") {
                var matches = prevString.match(/\s*module\s*=\s*["']?([a-zA-Z0-9]*)(_[a-zA-Z0-9]*)?(_[0-9]*)?$/);
                if (matches) {
                    if (matches[3]) {
                        return getSDEModuleSequence(string, cur, token, matches);
                    }
                    else if (matches[2]) {
                        return getSDEModules(string, cur, token, matches);
                    }
                    else {
                        return getSDEApps(string, cur, token, matches);
                    }
                }
            }
            return false;
        }

        function html(type, string, cur, token) {
            var prevString = string.substr(0, cur.ch);

            if (type == "tag bracket" || type == "tag") {
                return getHTMLStartTag(prevString, cur, token);
            }
            else if (type == "attribute" || type == null) {
                return getHTMLTagAttributes(prevString, cur, token);
            }
            else if (type == "string") {
                return getHTMLTagAttributeValue(string, cur, token);
            }
            return null;
        }

        function css(type, string, cur, token) {
            if (type == "variable") {
                return getCSSAttributes(string, cur, token);
            }
            else if (type == "number") {
                return getCSSAttributeValue(string, cur, token);
            }
            return null;
        }
    }
    var HTMLpublicTags = "script,noscript" +
        ",section,nav,article,aside,h1,h2,h3,h4,h5,h6,header,footer,address" +
        ",p,hr,br,pre,dialog,blockquote,ol,ul,dl" +
        ",a,q,cite,em,strong,small,mark,dfn,abbr,time,progress,meter,code,var,samp,kbd,sub,sups,span,i,b,bdo,ruby,rt,rp" +
        ",ins,del" +
        ",figure,img,iframe,embed,object,video,audio,source,canvas,map" +
        ",table" +
        ",form,fieldset,label,input,button,select,datalist,textarea,output" +
        ",details,command,bb,menu" +
        ",legend,div,style";
    var HTMLchildTags = {
        "html": "head,body",
        "head": "title,base,link,meta,style,script,noscript",
        "table": "caption,colgroup,col,tbody,thead,tfoot,tr",
        "thead": "tr",
        "tbody": "tr",
        "tfoot": "tr",
        "tr": "td,th",
        "ol": "li",
        "ul": "li",
        "dl": "dt,dd",
        "map": "area",
        "object": "param",
        "colgroup": "col",
        "select": "optgroup,option",
        "optgroup": "option"
    };

    function getHTMLStartTag(prevString, cur, token) {
        var matches = prevString.match(/<([a-zA-Z0-9]*)$/);
        if (!matches) return null;
        var input_string = matches[1];
        var parentTagName = token.state.htmlState.context ? token.state.htmlState.context.tagName : "";
        var foundList = (HTMLchildTags.hasOwnProperty(parentTagName) ? HTMLchildTags[parentTagName] : HTMLpublicTags).split(",");
        for (var i = 0, limit = foundList.length; i < limit; i++) {
            foundList[i] = [foundList[i]];
        }
        return AutoComplete.convertData("HTMLStartTag", input_string, foundList);
    }
    var HTMLGlobalAttributes = "accesskey,class,contextmenu,id,style,tabindex,title" +
        ",module";
    var HTMLTagAttributes = {
        "base": "href,target",
        "link": "href,rel,media,hreflang,type,sizes",
        "meta": "name,http-equiv,content,charset",
        "style": "media,type,scoped",
        "script": "src,async,defer,type,charset",
        "body": "onbeforeunload,onerror,onhashchange,onload,onmessage,onoffline,ononline,onpopstate,onresize,onstorage,onunload",
        "ol": "reversed,start",
        "a": "href,target,ping,rel,media,hreflang,type",
        "q": "cite",
        "time": "datetime",
        "progress": "value,max",
        "meter": "value,min,low,high,max,optimum",
        "ins": "cite,datetime",
        "del": "cite,datetime",
        "img": "alt,src,usemap,ismap,width,height",
        "iframe": "src,name,sandbox,seamless,width,height",
        "embed": "src,type,width,height",
        "object": "data,type,name,usemap,form,width,height",
        "param": "name,value",
        "video": "src,poster,autobuffer,autoplay,loop,controls,width,height",
        "audio": "src,autobuffer,autoplay,loop,controls",
        "source": "src,type,media",
        "canvas": "width,height",
        "map": "name",
        "area": "alt,coords,shape,href,target,ping,rel,media,hreflang,type",
        "colgroup": "span",
        "col": "span",
        "td": "colspan,rowspan,headers",
        "th": "colspan,rowspan,headers,scope",
        "form": "accept-charset,action,autocomplete,enctype,method,name,novalidate,target",
        "fieldset": "disabled,form,name",
        "label": "form,for",
        "input": "accept,action,alt,autocomplete,autofocus,checked,disabled,enctype,form,height,list,max,maxlength,method,min,multiple,name,novalidate,pattern,placeholder,readonly,required,size,src,step,target,type,value,width",
        "button": "action,autofocus,disabled,enctype,form,method,name,novalidate,target,type,value",
        "select": "autofocus,disabled,form,multiple,name,size",
        "optgroup": "disabled,label",
        "option": "disabled,label,selected,value",
        "textarea": "autofocus,cols,disabled,form,maxlength,name,readonly,required,rows,wrap",
        "output": "for,form,name",
        "details": "open",
        "command": "type,label,icon,disabled,checked,radiogroup,default",
        "bb": "type",
        "menu": "type,label"
    };

    function getHTMLTagAttributes(prevString, cur, token) {
        var matches = prevString.match(/[\s'"]([\w\\\-_]*)$/);
        if (!matches) return null;
        AutoComplete.replace_tail = '=""';
        AutoComplete.move_cursor = -1;
        var input_string = matches[1];
        var tagName = token.state.htmlState.tagName;
        var foundList = (HTMLGlobalAttributes + (HTMLTagAttributes.hasOwnProperty(tagName) ? "," + HTMLTagAttributes[tagName] : "")).split(",");
        for (var i = 0, limit = foundList.length; i < limit; i++) {
            foundList[i] = [foundList[i]];
        }
        return AutoComplete.convertData("HTMLTagAttributes", input_string, foundList);
    }
    var HTMLTagAttributeValue = {
        "target": "_blank,_self,_parent,_top",
        "input.type": "text,checkbox,radio,image,button,submit",
        "script.type": "text/javascript"
    };

    function getHTMLTagAttributeValue(string, cur, token) {
        var tagName = token.state.htmlState.tagName;
        var matches = string.substr(0, token.start + 1).match(/([^\s"']+)\s*=\s*\S?$/);
        var attribute = matches[1];
        var startPos = token.start;
        switch (token.string.substr(0, 1)) {
            case '"':
            case "'":
                startPos++;
                break;
        }
        var input_string = string.substring(startPos, cur.ch);
        if (attribute == "style") {
            if (input_string.split(";").pop().match(/\s*[\w\\\-_]+\s*:\s*/)) {
                return getCSSAttributeValue(string, cur, token);
            }
            else {
                return getCSSAttributes(string, cur, token);
            }
        }
        else {
            var foundList;
            if (HTMLTagAttributeValue.hasOwnProperty(tagName + "." + attribute)) {
                foundList = HTMLTagAttributeValue[tagName + "." + attribute].split(",");
            }
            else if (HTMLTagAttributeValue.hasOwnProperty(attribute)) {
                foundList = HTMLTagAttributeValue[attribute].split(",");
            }
            else {
                foundList = [];
            }
            for (var i = 0, limit = foundList.length; i < limit; i++) {
                foundList[i] = [foundList[i]];
            }
            return AutoComplete.convertData("HTMLTagAttributeValue", input_string, foundList);
        }
    }
    var CSSAttributeValue = {
        "background": "",
        "background-attachment": "scroll,fixed",
        "background-color": "",
        "background-image": "",
        "background-position": "left,right,center,top,bottom",
        "background-repeat": "repeat,repeat-x,repeat-y,no-repeat",
        "border": "",
        "border-bottom": "",
        "border-bottom-color": "",
        "border-bottom-style": "",
        "border-bottom-width": "",
        "border-color": "",
        "border-left": "",
        "border-left-color": "",
        "border-left-style": "",
        "border-left-width": "",
        "border-right": "",
        "border-right-color": "",
        "border-right-style": "",
        "border-right-width": "",
        "border-style": "none,hidden,dotted,dashed,solid,double,groove,ridge,inset,outset",
        "border-top": "",
        "border-top-color": "",
        "border-top-style": "",
        "border-top-width": "",
        "border-width": "",
        "height": "",
        "max-height": "",
        "max-width": "",
        "min-height": "",
        "min-width": "",
        "width": "",
        "font": "",
        "font-family": "",
        "font-size": "",
        "font-style": "normal,italic,oblique",
        "font-variant": "",
        "font-weight": "",
        "list-style": "",
        "list-style-image": "",
        "list-style-position": "inside,outside",
        "list-style-type": "armenian,circle,cjk-ideographic,decimal,decimal-leading-zero,disc,georgian,hebrew,hiragana,hiragana-iroha,inherit,katakana,katakana-iroha,lower-alpha,lower-greek,lower-latin,lower-roman,none,square,upper-alpha,upper-latin,upper-roman",
        "margin": "",
        "margin-bottom": "",
        "margin-left": "",
        "margin-right": "",
        "margin-top": "",
        "padding": "",
        "padding-bottom": "",
        "padding-left": "",
        "padding-right": "",
        "padding-top": "",
        "clear": "left,right,both,none",
        "clip": "auto",
        "cursor": "crosshair,default,e-resize,help,move,n-resize,ne-resize,nw-resize,pointer,progress,s-resize,se-resize,sw-resize,text,w-resize,wait",
        "display": "none,block,inline,inline-block,inline-table,list-item,run-in,table,table-caption,table-cell,table-column,table-column-group,table-footer-group,table-header-group,table-row,table-row-group",
        "float": "left,right,none",
        "overflow": "visible,hidden,scroll,auto",
        "position": "static,absolute,fixed,relative",
        "visibility": "visible,hidden,collapse",
        "z-index": "",
        "left": "",
        "bottom": "",
        "right": "",
        "top": "",
        "color": "",
        "direction": "ltr,rtl",
        "letter-spacing": "",
        "line-height": "",
        "text-align": "left,right,center,justify",
        "text-decoration": "none,underline,overline,line-through,blink",
        "text-indent": "",
        "text-transform": "none,capitalize,uppercase,lowercase",
        "unicode-bidi": "",
        "vertical-align": "",
        "white-space": "",
        "word-spacing": "",
        "word-break": "normal,break-all,hyphenate",
        "word-wrap": "normal,break-word"
    };
    var arr = ["border", "border-bottom-style", "border-left-style", "border-right-style", "border-top-style", "border-bottom", "border-left", "border-right", "border-top"];
    for (var i = 0, limit = arr.length; i < limit; i++) {
        CSSAttributeValue[arr[i]] += CSSAttributeValue["border-style"];
    }
    arr = undefined;
    CSSAttributeValue["list-style"] = CSSAttributeValue["list-style-type"];
    var CSSAttributes = [];
    for (var k in CSSAttributeValue) {
        CSSAttributes.push([k]);
    }

    function getCSSAttributes(string, cur, token) {
        var matches = string.substr(0, cur.ch).match(/([\w\\\-_]*)$/);
        var input_string = matches[1];
        AutoComplete.replace_tail = ': ';
        return AutoComplete.convertData("CSSAttributes", input_string, CSSAttributes);
    }

    function getCSSAttributeValue(string, cur, token) {
        var matches = string.substr(0, cur.ch).match(/([\w\\\-_]+)\s*:(\s*([^\s{:;]*))*$/);
        if (!matches) return null;
        var attribute = matches[1];
        var input_string = matches[0].match(/[^:;\s]*$/)[0];
        AutoComplete.replace_tail = '; ';
        AutoComplete.is_run = false;
        var foundList = [];
        if (CSSAttributeValue.hasOwnProperty(attribute)) {
            foundList = CSSAttributeValue[attribute].split(",");
        }
        for (var i = 0, limit = foundList.length; i < limit; i++) {
            foundList[i] = [foundList[i]];
        }
        return AutoComplete.convertData("CSSAttributeValue", input_string, foundList);
    }
    for (var i = 0, limit = SDEApps.length; i < limit; i++) {
        SDEApps[i][0] = SDEApps[i][0].toLocaleLowerCase();
    }

    function getSDEApps(string, cur, token, matches) {
        var input_string = matches[1];
        AutoComplete.replace_tail = '_';
        return AutoComplete.convertData("SDEApps", input_string, SDEApps);
    }
    var SDEModules = [];
    var SDEModuleVariables = [];
    for (var k in SDEVariables) {
        var key = k.toLocaleLowerCase();
        SDEModules.push([key, SDEVariables[k]["name"], (SDEModuleSequance[k] ? '_' : '')]);
        SDEModuleVariables[key] = SDEVariables[k];
    }

    function getSDEModules(string, cur, token, matches) {
        var input_string = matches[1] + matches[2];
        var ret = AutoComplete.convertData("SDEModules", input_string, SDEModules);
        if (string.substr(cur.ch, 1) != '"') {
            for (var i = 0, limit = ret.list.length; i < limit; i++) {
                if (!ret.list[i][2]) ret.list[i][2] = '"';
            }
        }
        return ret;
    }

    function getSDEModuleOptions(string, cur, token, matches) {
        var input_string = matches[1];
        AutoComplete.replace_tail = " = ";
        var module_name = _getModuleName(string, token);
        if (!module_name || !SDEModuleVariables[module_name] || !SDEModuleVariables[module_name]["options"]) {
            return null;
        }
        return AutoComplete.convertData("SDEModuleOptions", input_string, SDEModuleVariables[module_name]["options"]);
    }
    var SDEModuleSequanceData = [];
    for (var k in SDEModuleSequance) {
        SDEModuleSequanceData[k.toLocaleLowerCase()] = SDEModuleSequance[k];
    }

    function getSDEModuleSequence(string, cur, token, matches) {
        var module_name = String(matches[1] + matches[2]).toLocaleLowerCase();
        if (SDEModuleSequanceData[module_name]) {
            AutoComplete.is_run = false;
            AutoComplete.replace_start = -matches[3].length + 1;
            return AutoComplete.convertData("SDEModuleSequence", "", SDEModuleSequanceData[module_name]);
        }
        else {
            return null;
        }
    }
    var modifiers = [
        ["cover", __('WRAP', 'EDITOR.AUTOCOMPLETE'), ":(,)", -2],
        ["cut", __('CROP.STRING', 'EDITOR.AUTOCOMPLETE'), ":,"],
        ["date", __('DATE.FORMAT', 'EDITOR.AUTOCOMPLETE'), ":Y-m-d H:i:s"],
        ["display", __('WHETHER.VARIABLE.EXPOSED', 'EDITOR.AUTOCOMPLETE'), ":{$}", -1],
        ["imgconv", __('IMAGE.TAG', 'EDITOR.AUTOCOMPLETE'), ":"],
        ["nl2br", __('LINE.BREAKS.CHANGE.TO.TAG', 'EDITOR.AUTOCOMPLETE')],
        ["numberformat", __('COMMAS.IN.NUMBERS', 'EDITOR.AUTOCOMPLETE')],
        ["replace", __('VARIABLE.SUBSTITUTION', 'EDITOR.AUTOCOMPLETE'), ":,", -1],
        ["strconv", __('REPLACE.EMPTY.VALUE', 'EDITOR.AUTOCOMPLETE'), ":"],
        ["striptag", __('REMOVE.TAG', 'EDITOR.AUTOCOMPLETE')],
        ["thumbnail", __('THUMBNAIL', 'EDITOR.AUTOCOMPLETE'), ":"],
        ["timetodate", __('DATE.FORMAT', 'EDITOR.AUTOCOMPLETE'), ":Y-m-d H:i:s"],
        ["lower", __('CHANGE.TO.LOWERCASE', 'EDITOR.AUTOCOMPLETE')],
        ["upper", __('CHANGE.TO.UPPERCASE', 'EDITOR.AUTOCOMPLETE')]
    ];

    function getSDEModifier(string, cur, token, matches) {
        var input_string = matches[2] || "";
        return AutoComplete.convertData("SDEModifier", input_string, modifiers);
    }

    function getSDEVariables(string, cur, token, matches) {
        var input_string = matches[1];
        if (string.substr(cur.ch, 1) != "}") {
            AutoComplete.replace_tail = "}";
        }
        var module_name = _getModuleName(string, token);
        if (!module_name || !SDEModuleVariables[module_name] || !SDEModuleVariables[module_name]["vars"]) {
            return null;
        }
        return AutoComplete.convertData("SDEVariables", input_string, SDEModuleVariables[module_name]["vars"]);
    }

    function _getModuleName(string, token) {
        var matches = string.split('').reverse().join('').match(/["']([a-zA-Z0-9_]+)["']\s*=\s*eludom\s*/);
        if (matches) return matches[1].split('').reverse().join('').replace(/_[0-9]+$/, '').toLocaleLowerCase();
        if (token.state && token.state.htmlState && token.state.htmlState.context) {
            var context = token.state.htmlState.context;
            while (context) {
                matches = context.tagHtml.match(/\s*module\s*=\s*["']\s*([a-zA-Z0-9_]*)/);
                if (matches && matches[1]) {
                    return matches[1].replace(/_[0-9]+$/, '').toLocaleLowerCase();
                }
                context = context.prev;
            }
        }
        return null;
    }

    function getSDELayout(string, cur, token, matches) {
        if (typeof SDELayout == "undefined")
            return null;
        var data = [];
        if (typeof SDELayout == "function") {
            data = SDELayout();
        }
        else {
            data = SDELayout;
        }
        var input_string = matches[1];
        return AutoComplete.convertData("SDELayout", input_string, data);
    }

    function getSDELayoutCSS(string, cur, token, matches) {
        if (typeof SDELayoutCSS == "undefined")
            return null;
        var data = [];
        if (typeof SDELayoutCSS == "function") {
            data = SDELayoutCSS();
        }
        else {
            data = SDELayoutCSS;
        }
        var input_string = matches[1];
        return AutoComplete.convertData("SDELayoutCSS", input_string, data);
    }

    function getSDELayoutJS(string, cur, token, matches) {
        if (typeof SDELayoutJS == "undefined")
            return null;
        var data = [];
        if (typeof SDELayoutJS == "function") {
            data = SDELayoutJS();
        }
        else {
            data = SDELayoutJS;
        }
        var input_string = matches[1];
        return AutoComplete.convertData("SDELayoutJS", input_string, data);
    }

    function getSDELayoutImport(string, cur, token, matches) {
        if (typeof SDELayoutImport == "undefined")
            return null;
        var data = [];
        if (typeof SDELayoutImport == "function") {
            data = SDELayoutImport();
        }
        else {
            data = SDELayoutImport;
        }
        var input_string = matches[1];
        return AutoComplete.convertData("SDELayoutImport", input_string, data);
    }
    var layoutGrammars = [
        ["layout", __('LAYOUT', 'EDITOR.AUTOCOMPLETE'), "(/layout/)-->", -4],
        ["contents", __('CONTENTS', 'EDITOR.AUTOCOMPLETE'), "-->"],
        ["import", __('INCLUDE.FILES', 'EDITOR.AUTOCOMPLETE'), "()-->", -4],
        ["css", "CSS", "()-->", -4],
        ["js", __('JAVASCRIPT', 'EDITOR.AUTOCOMPLETE'), "()-->", -4]
    ];

    function getSDELayoutGrammar(string, cur, token, matches) {
        var input_string = matches[1];
        return AutoComplete.convertData("SDELayoutGrammar", input_string, layoutGrammars);
    }
    return self;
})();
SDE.Util.File = {

    aAllowExtension : ["html","htm","js","css","xml","json"],

    aAllowImageExtension : ['jpg', 'jpeg', 'png', 'gif'],

    /**
     * File Mime Type
     */
    aMimeType : {
            css     : 'text/css',
            js      : 'text/javascript',
            xml     : 'application/xml',
            html    : 'text/html'
    },

    /**
     * 에디터에서 오픈이 허용된 파일인지, 아닌지를 확인한다.
     */
    isAllowFile: function(sUrl)
    {
        return ($.inArray(this.getExtension(sUrl), this.aAllowExtension) != -1);
    },


    /**
     * 유효할 파일명인지 확인
     */
    isValidName : function(sName)
    {
        /*rev.b5.20131015.4@sinseki #SDE-22 파일 추가시 extend.file.name.html 형태의 파일 내 DOT 추가시 미생성 오류*/
        var oRegExp = new RegExp(/^[0-9A-Za-z][0-9A-Za-z\.\-_]+\.[a-z]+$/);

        return (oRegExp.test(sName) === true && this.isAllowFile(sName) === true);
    },

    /**
     * 업로드가 유효한 이미지 이름인지 확인
     */
    isValidImageName : function(sName)
    {
        return ($.inArray(this.getExtension(sName), this.aAllowImageExtension) != -1);
    },


    getFileDir : function(sUrl)
    {
        return sUrl.substring(0, sUrl.lastIndexOf('/'));
    },

    getFileName : function(sUrl)
    {
        return sUrl.split('/').pop();
    },

    /**
     * 파일의 Mime Type 가져오기
     */
    getMimeType: function(sUrl)
    {
        return this.aMimeType[this.getExtension(sUrl)] || 'text/html';
    },

    /**
     * 파일 확장자명 가져오기
     */
    getExtension: function(sUrl)
    {
        if (typeof sUrl != 'string') return '';

        return sUrl.split('.').pop().toLowerCase();
    },

    /**
     * 파일 아이콘의 Suffix를 반환
     */
    getSuffix : function(sUrl) {
        var sExt = this.getExtension(sUrl);

        if (sUrl == '/index.html') return 'main';

        if (sExt == 'htm') sExt = 'html';

        if ($.inArray(sExt, ["html", "js","css","xml"]) != -1) return sExt;

        return 'etc';
    }
};
SDE.Util.Module = {
    deleteSelection : function() {
        SDE.editor.deleteSelection();

        SDE.File.Manager.saveTemp(SDE.File.Manager.getCurrentUrl());
    },

    hasVariables : function(variables, str) {
        var r, i;

        str = str || SDE.editor.getSelection();

        for (i in variables) {
            if (str.indexOf(variables[i]) === -1) return false;
        }

        return true;
    },

    getInfo : function(key) {
        var response = $.parseJSON($.ajax({
            url : getMultiShopUrl('/exec/admin/editor/moduleInfo'),
            data : {
                module : key,
                platform : SDE.mo()? "mobile":  "pc"
            },

            async : false
        }).responseText);

        this.currentModuleName = (response && response.module_info && response.module_info.action_name) ? response.module_info.module_name + ' - ' + response.module_info.action_name : null;

        return response;
    },

    getCurrentName : function() {
        return this.currentModuleName;
    },

    getSelectedElement : function() {
        var previewWindow = SDE.View.Manager.getPreviewWindow();

        return previewWindow.SDE.Ghost.getCurrentModule();
    },

    getCount : function(key, text) {

        var re = new RegExp('<([a-z]+[^>]*\\s+)module\\s*=\\s*("'+ key + '|\''+ key + '|'+ key + ')', 'gi'),
            text = text || SDE.editor.getValue(),
            result = text.match(re);

        return result ? result.length : 0;
    },

    find : function(type, key, index) {
        var response = $.parseJSON($.ajax({
            url : getMultiShopUrl('/exec/admin/editor/filesearchmodule'),
            data : {
                skin_no : SDE.SKIN_NO,
                file : SDE.File.Manager.getCurrentUrl(),
                key : key,
                type : type,
                index : index || 0
            },

            async : false
        }).responseText);

        if (response.bComplete == false) return;

        return {
            'file' : response.file,
            'index' : response.index
        };
    },

    findSelectedSrc : function() {
        var value = SDE.editor.getSelection();
            match = value.match(/src="(.*?)"/i);

        return match ? match[1] : null;
    },

    /*rev$@sinseki #SDE-15 이미지에 a href 로 감싸진 경우, 속성에 href 편집 입력 추가*/
    findSelectedHref : function() {
        var value = SDE.editor.getSelection();
            match = value.match(/href="(.*?)"/i);

        return match ? match[1] : null;
    },

    findSelectedInfo : function() {
        return this.findInfo(SDE.editor.getSelection());
    },

    findInfo : function(value) {
        var value = value || '',
            re = /(\S+)=["']?((?:.(?!["']?\s+(?:\S+)=|[>"']))+.)["']?/g,
            key, match, params = {};

        while (match = re.exec(value)) {
            key = match[1];

            if (typeof params[key] != 'undefined') continue;

            params[key] = match[2];
        }

        if (params['module']) {
            return { type : 'module', key : params['module'] };
        }

        if (params['src']) {
            return { type : 'image', key : params['src'] };
        }
    },

    has : function(type, key) {
        var module;

        if (type == 'module') {
            return SDE.editor.hasModule(key);
        }

        return SDE.editor.hasImageModule(key);
    },

    select : function(type, key, index) {
        var range = (type == 'module') ? SDE.editor.getModuleRange(key, index) : SDE.editor.getImageModuleRange(key, index);

        if (!range) return;

        SDE.editor.setSelection(range.from, range.to);

        $('.CodeMirror-scroll:visible').scrollTop((range.from.line - 1) * 16);
    }
};
SDE.Util.Preference = {
    store : {},

    /**
     * Get preference data
     */
    get : function(name) {
        var data = this.store[name];

        if (!this.store[name]) {
            result = $.parseJSON($.ajax({
                async : false,
                data : {
                    moduleName : name
                },
                dataType : 'json',
                url : getMultiShopUrl('/exec/admin/editor/preferenceRead')
            }).responseText);

            if (!result.bSuccess) {
                alert(__('PROBLEM.IMPORTING.DATA', 'EDITOR.UTIL.PREFERENCE'));
                return;
            }

            data = this.store[name] = result.data;
        }

        return $.extend(true, {}, data);
    },

    /**
     * Set preference Data
     */
    set : function(name, _data) {
         var data = {
             'moduleName' : name,
             'config' : _data
         };

         if (!this._set(data)) return false;

         this._remove(name);

         return true;
    },

    /**
     * Set preference Data Multiple
     *
     * 한번에 여러 Preference를 저장할 때 사용
     *
     * _data example
     *
     * _data = {
     *      'board_title_2' : { // ini file name
     *          'board_detail' : { // ini section name
     *              'menu_image' : '/web/image.gif' // key & value
     *              ...
     *          }
     *      }
     *
     *      ....
     * }
     */
    setMulti : function(_data) {
       var response, key, data;

       if (typeof(_data) != 'object' || Object.size(_data) == 0) return false;

       data = {
           'preferences' : _data
       };

       if (!this._set(data)) return false;

       for (key in _data) {
           this._remove(key);
       }

       return true;
    },

    _set : function(data) {
        var response = $.parseJSON($.ajax({
            async : false,
            data : data,
            dataType : 'json',
            type : 'POST',
            url : getMultiShopUrl('/exec/admin/editor/PreferenceWrite')
        }).responseText);

        return response.bSuccess;
    },

    /**
     * Store 데이터 삭제
     * @param name
     */
    _remove : function(name) {
        delete this.store[name];
    }
};

